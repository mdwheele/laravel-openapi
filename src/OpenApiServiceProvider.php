<?php

namespace Mdwheele\OpenApi;

use cebe\openapi\Reader;
use cebe\openapi\spec\OpenApi;
use cebe\openapi\spec\Operation;
use cebe\openapi\SpecObjectInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Mdwheele\OpenApi\Exceptions\OpenApiException;
use Mdwheele\OpenApi\Http\Middleware\ValidateOpenApi;
use ReflectionException;

class OpenApiServiceProvider extends ServiceProvider
{

    /**
     * @var OpenApi|SpecObjectInterface
     */
    private $openapi;

    public function register()
    {
        $this->openapi = Reader::readFromYamlFile(config('openapi.path'));
        $this->app->instance(OpenApi::class, $this->openapi);

        $this->mergeConfigFrom(__DIR__.'/../config/openapi.php', 'openapi');
    }

    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/openapi.php' => config_path('openapi.php'),
        ]);

        $this->registerApiRoutes();
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
        if ($operation->operationId === null) {
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
