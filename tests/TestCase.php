<?php

namespace Mdwheele\OpenApi\Tests;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
    }

    public function stub(string $controller, string $method, $data = [], $status = 200)
    {
        $this->mock($controller, function ($mock) use ($method, $data, $status) {
            /** @var Mockery\Mock $mock */
            $mock->makePartial()->shouldReceive($method)->andReturn(response()->json($data, $status));
        });
    }
}
