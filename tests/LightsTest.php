<?php

namespace Mdwheele\OpenApi\Tests;

use Orchestra\Testbench\TestCase;

class LightsTest extends TestCase
{
    /** @test */
    public function true_is_true()
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function an_example_of_how_to_register_a_route_and_make_a_call()
    {
        // Register an amazing route of amazing-ness.
        $this->app['router']->get('hello', function () {
            return response('Hello, World!');
        });

        // Make a call to our unicorn route.
        $response = $this->get('hello');

        // Make some assertions on the response.
        $response
            ->assertStatus(200)
            ->assertSeeText('Hello, World!');
    }
}
