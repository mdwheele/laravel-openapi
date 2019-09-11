<?php

namespace Mdwheele\OpenApi\Tests;

use Carbon\Carbon;
use Mdwheele\OpenApi\Exceptions\OpenApiException;
use Mdwheele\OpenApi\Tests\Controllers\CoreFeaturesController;

class CoreFeaturesTest extends TestCase
{
    public function getSpecification()
    {
        return 'core-features';
    }

    /** @test */
    public function health_status_can_be_queried()
    {
        $response = $this->get('api/health');

        $response->assertJson([
            'status' => 'ok'
        ]);
    }

    /** @test */
    public function different_types_of_parameters_can_be_sent()
    {
        $response = $this->call('POST', 'api/parameters/user@example.com?filter=apples',
            [],                                                                     // Parameters
            ['X-Super-Cookie' => 'cookie monster'],                                 // Cookies
            [],                                                                     // Files
            $this->transformHeadersToServerVars(['X-Super-Hero' => 'wonder-woman']) // Server + Headers
        );

        $response->assertJson([
            'message' => "Hey user@example.com! Looks like you're filtering by apples and love wonder-woman."
        ]);
    }

    /** @test */
    public function health_status_updates_have_message_and_rfc3339_timestamp()
    {
        $this->stub(CoreFeaturesController::class, 'healthCheck', [
            'status' => 'ok',
            'updates' => [
                [
                    'message' => 'All good here!',
                    'timestamp' => Carbon::now()->toRfc3339String()
                ]
            ]
        ]);

        $response = $this->get('api/health');

        $response
            ->assertStatus(200)
            ->assertJson([
                'updates' => [
                    [
                        'message' => 'All good here!'
                    ]
                ]
            ]);
    }

    /**
     * @test
     * @group response-validation
     */
    public function invalid_status_causes_openapi_exception()
    {
        $this->expectException(OpenApiException::class);

        $this->stub(CoreFeaturesController::class, 'healthCheck', [
            'status' => 'invalid-status'
        ]);

        $this->get('api/health');
    }

    /**
     * @test
     * @group response-validation
     */
    public function invalid_response_schemas_give_awesome_error_messages()
    {
        // This time, we're looking for a damn-helpful error message.
        // Being told "response schema didn't match" is super unhelpful...
        // Let's see if we can do better.

        // First, we need a broken response. Let's break it in 3 ways:
        $this->stub(CoreFeaturesController::class, 'healthCheck', [
            // 1) This is not ok, warning or critical.
            'status' => 'invalid-status',
            'updates' => [
                [
                    'message' => 'Oh dear...',
                    // 2) Timestamp is missing from response.
                    // 3) Additional unspecified attributes are *not allowed* here.
                    'unsupported' => 'additional attribute'
                ]
            ]
        ]);

        try {
            $this->get('api/health');
        } catch (OpenApiException $exception) {
            $this->assertSame(
                'The response from CoreFeaturesController@healthCheck does not match your OpenAPI specification.',
                $exception->getMessage()
            );

            $errors = $exception->getErrors();

            $this->assertContains('The [status] property must be one of [ok, warning, critical].', $errors);
            $this->assertContains('The [updates[0].timestamp] property is missing. It must be included.', $errors);
            $this->assertContains('The property unsupported is not defined and the definition for [updates[0]] does not allow additional properties.', $errors);
        }
    }

}
