<?php

namespace Mdwheele\OpenApi\Tests\Controllers;

use Carbon\Carbon;

class CoreFeaturesController extends Controller
{
    public function healthCheck()
    {
        return [
            'status' => 'ok',
            'updates' => [
                [
                    'message' => 'Everything is green!',
                    'timestamp' => Carbon::now()->toRfc3339String()
                ]
            ]
        ];
    }
}
