<?php

namespace Mdwheele\OpenApi\Tests;

use Exception;
use Illuminate\Http\Response;
use Mdwheele\OpenApi\OpenApiServiceProvider;
use Mdwheele\OpenApi\Tests\Controllers\PetsController;
use Mockery;

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

        $response
            ->assertStatus(200)
            ->assertJson([
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

    /** @test */
    public function if_pets_have_extra_appendages_openapi_cares()
    {
        $this->expectExceptionMessage('Response did not match provided JSON schema.');

        $this->stub(PetsController::class, 'show', [
            'id' => 1,
            'name' => 'Cow',
            'legs' => 10
        ]);

        $this->get('api/pets/1');
    }

    /** @test */
    public function it_returns_error_object_on_failure()
    {
        $this->stub(PetsController::class, 'index', [
            'code' => 500,
            'message' => 'Something valid.'
        ], 500);

        $response = $this->get('api/pets');

        $response->assertStatus(500);
    }

    /** @test */
    public function blow_chunks_if_error_schema_is_malformed()
    {
        $this->expectExceptionMessage('Response did not match provided JSON schema.');

        $this->stub(PetsController::class, 'index', [
            'broken' => 'This response does not comply with components/schemas/Error.'
        ], 400);

        $this
            ->get('api/pets')
            ->assertStatus(400);
    }
}
