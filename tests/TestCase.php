<?php

namespace Mdwheele\OpenApi\Tests;

use Mdwheele\OpenApi\OpenApiServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    protected function getPackageProviders($app)
    {
        $app['config']->set('openapi.spec', __DIR__ . "/openapis/{$this->getSpecification()}/openapi.yaml");

        return [
            OpenApiServiceProvider::class
        ];
    }

    public function getSpecification() {
        throw new \Exception('OpenApi TestCase must implement #getSpecification.');
    }

    public function stub(string $controller, string $method, $data = [], $status = 200)
    {
        $this->mock($controller, function ($mock) use ($method, $data, $status) {
            /** @var Mockery\Mock $mock */
            $mock->makePartial()->shouldReceive($method)->andReturn(response()->json($data, $status));
        });
    }
}
