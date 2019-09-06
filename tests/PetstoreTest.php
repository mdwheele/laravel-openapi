<?php

namespace Mdwheele\OpenApi\Tests;

use Mdwheele\OpenApi\OpenApiServiceProvider;
use Orchestra\Testbench\TestCase;

class PetstoreTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        $app['config']->set('openapi.spec', __DIR__ . '/openapis/petstore/openapi.yaml');

        return [
            OpenApiServiceProvider::class
        ];
    }

    /** @test */
    public function it_has_a_list_of_pets()
    {
        $response = $this->get('api/pets');

        $response->assertJson([
            [
                'id' => 1,
                'name' => 'Dog'
            ],
            [
                'id' => 2,
                'name' => 'Cat'
            ]
        ]);
    }
}
