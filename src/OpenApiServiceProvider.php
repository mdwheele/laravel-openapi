<?php

namespace Mdwheele\OpenApi;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\SpecObjectInterface;
use Illuminate\Support\ServiceProvider;

class OpenApiProvider extends ServiceProvider
{

    /**
     * @var OpenApi|SpecObjectInterface
     */
    private $openapi;

    public function register()
    {
        $this->openapi = Reader::readFromYamlFile('/app/api/openapi.yaml');
        $this->app->instance(OpenApi::class, $this->openapi);
    }

    public function boot()
    {
        $this
            ->registerApiRoutes();
    }

    private function registerApiRoutes()
    {
        Route::prefix($this->getApiPrefix())->group(function () {
            foreach ($this->openapi->paths as $path => $pathItem) {
                foreach ($pathItem->getOperations() as $method => $operation) {
                    Route::{$method}($path, [
                        'uses' => $this->getMappedAction($operation),
                        'middleware' => ValidateOpenApi::class,
                        'openapi.operation' => $operation
                    ]);
                }
            }
        });

        return $this;
    }

    private function getApiPrefix()
    {
        return 'api';
    }

    private function getMappedAction(Operation $operation)
    {
        if (!$operation->operationId) {
            throw new OpenApiException('All operations must have an `operationId`.');
        }

        [$class, $method] = explode('@', $operation->operationId);

        try {
            $controller = new \ReflectionClass($class);
        } catch (ReflectionException $e) {
            throw OpenApiException::wrapPrevious($e->getMessage(), $e);
        }

        if ($controller->hasMethod($method) === false) {
            throw new OpenApiException("Controller ${class} does not have a method named ${method}.");
        }

        return $operation->operationId;
    }
}
