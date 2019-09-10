<?php

namespace Mdwheele\OpenApi\Tests;

use Mdwheele\OpenApi\Exceptions\OpenApiException;
use Mdwheele\OpenApi\Tests\Controllers\PetsController;

class PetstoreTest extends TestCase
{
    public function getSpecification()
    {
        return 'petstore';
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
    public function throws_exception_if_required_object_property_not_found()
    {
        $this->expectException(OpenApiException::class);

        $this->stub(PetsController::class, 'show', [
            'id' => 1,
            // 'name' => 'Cow',
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
        $this->expectException(OpenApiException::class);

        $this->stub(PetsController::class, 'index', [
            'broken' => 'This response does not comply with components/schemas/Error.'
        ], 400);

        $this
            ->get('api/pets')
            ->assertStatus(400);
    }
}
