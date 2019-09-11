<?php

namespace Mdwheele\OpenApi\Http\Middleware;

use cebe\openapi\spec\Operation;
use cebe\openapi\spec\Parameter;
use cebe\openapi\spec\RequestBody;
use cebe\openapi\spec\Responses;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use JsonSchema\Validator;
use Mdwheele\OpenApi\Exceptions\OpenApiException;

class ValidateOpenApi
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     * @throws OpenApiException
     */
    public function handle($request, Closure $next)
    {
        /** @var Operation $operation */
        $operation = $request->route()->action['openapi.operation'];

        $this->validateParameters($request, $operation);

        if ($operation->requestBody !== null) {
            $this->validateBody($request, $operation);
        }

        $response = $next($request);

        if ($operation->responses !== null) {
            $this->validateResponse($response, $operation);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param Operation $operation
     * @throws OpenApiException
     */
    private function validateParameters($request, Operation $operation)
    {
        $route = $request->route();
        $parameters = $operation->parameters;

        foreach ($parameters as $parameter) {
            // Verify presence, if required.
            if ($parameter->required === true) {
                // Parameters can be found in query, header, path or cookie.
                if ($parameter->in === 'path' && !$route->hasParameter($parameter->name)) {
                    throw new OpenApiException("Missing required parameter {$parameter->name} in URL path.");
                } elseif ($parameter->in === 'query' && !$request->query->has($parameter->name)) {
                    throw new OpenApiException("Missing required query parameter [?{$parameter->name}=].");
                } elseif ($parameter->in === 'header' && !$request->headers->has($parameter->name)) {
                    throw new OpenApiException("Missing required header [{$parameter->name}].");
                } elseif ($parameter->in === 'cookie' && !$request->cookies->has($parameter->name)) {
                    throw new OpenApiException("Missing required cookie [{$parameter->name}].");
                }
            }

            // Validate schemas, if provided. Required or not.
            if ($parameter->schema) {
                $validator = new Validator();
                $jsonSchema = $parameter->schema->getSerializableData();

                if ($parameter->in === 'path' && $route->hasParameter($parameter->name)) {
                    $data = $route->parameters();
                    $validator->coerce($data[$parameter->name], $jsonSchema);
                } elseif ($parameter->in === 'query' && $request->query->has($parameter->name)) {
                    $data = $request->query->get($parameter->name);
                    $validator->coerce($data, $jsonSchema);
                } elseif ($parameter->in === 'header' && $request->headers->has($parameter->name)) {
                    $data = $request->headers->get($parameter->name);
                    $validator->coerce($data, $jsonSchema);
                } elseif ($parameter->in === 'cookie' && $request->cookies->has($parameter->name)) {
                    $data = $request->cookies->get($parameter->name);
                    $validator->coerce($data, $jsonSchema);
                }

                if (!$validator->isValid()) {
                    throw OpenApiException::withSchemaErrors(
                        "Parameter [{$parameter->name}] did not match provided JSON schema.",
                        $validator->getErrors()
                    );
                }
            }
        }
    }

    /**
     * @param Request $request
     * @param Operation $operation
     * @throws OpenApiException
     */
    private function validateBody($request, Operation $operation)
    {
        $contentType = $request->header('Content-Type');
        $body = $request->getContent();
        $requestBody = $operation->requestBody;

        if ($requestBody->required === true) {
            if (empty($body)) {
                throw new OpenApiException('Request body required.');
            }

            // This isn't good enough for production. This is an *exact* match
            // for the media type and does not really take into account media ranges
            // at all. We'll fix this later.
            if (!array_key_exists($contentType, $requestBody->content) ) {
                throw new OpenApiException('Request did not match any specified media type for request body.');
            }
        }

        if (empty($request->getContent())) {
            return;
        }

        $jsonSchema = $requestBody->content[$contentType]->schema;
        $validator = new Validator();

        if ($jsonSchema->type === 'object' || $jsonSchema->type === 'array') {
            if ($contentType === 'application/json') {
                $body = json_decode($body, true);
            } else {
                throw new OpenApiException("Unable to map [{$contentType}] to schema type [object].");
            }
        }

        $validator->coerce($body, $jsonSchema->getSerializableData());

        if ($validator->isValid() !== true) {
            throw new OpenApiException("Request body did not match provided JSON schema.");
        }
    }

    /**
     * @param Response $response
     * @param Operation $operation
     * @throws OpenApiException
     */
    private function validateResponse($response, Operation $operation)
    {
        $contentType = $response->headers->get('Content-Type');
        $body = $response->getContent();
        $responses = $operation->responses;

        $shortHandler = class_basename($operation->operationId);

        // Get matching response object based on status code.
        if ($responses[$response->getStatusCode()] !== null) {
            $responseObject = $responses[$response->getStatusCode()];
        } elseif ($responses['default'] !== null) {
            $responseObject = $responses['default'];
        } else {
            throw new OpenApiException("No response object matching returned status code [{$response->getStatusCode()}].");
        }

        // This isn't good enough for production. This is an *exact* match
        // for the media type and does not really take into account media ranges
        // at all. We'll fix this later.
        if (!array_key_exists($contentType, $responseObject->content)) {
            throw new OpenApiException('Response did not match any specified media type.');
        }

        $jsonSchema = $responseObject->content[$contentType]->schema;
        $validator = new Validator();

        if ($jsonSchema->type === 'object' || $jsonSchema->type === 'array') {
            if ($contentType === 'application/json') {
                $body = json_decode($body);
            } else {
                throw new OpenApiException("Unable to map [{$contentType}] to schema type [object].");
            }
        }

        $validator->coerce($body, $jsonSchema->getSerializableData());

        if ($validator->isValid() !== true) {
            throw OpenApiException::withSchemaErrors(
                "The response from {$shortHandler} does not match your OpenAPI specification.",
                $validator->getErrors()
            );
        }
    }
}
