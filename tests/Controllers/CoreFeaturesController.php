<?php

namespace Mdwheele\OpenApi\Tests\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;

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

    public function parameters(Request $request, $email)
    {
        return [
            'message' => "Hey {$email}! Looks like you're filtering by {$request->query('filter')} and love {$request->header('X-Super-Hero')}.",
            'path' => [
                'email' => $email
            ],
            'query' => $request->query(),
            'header' => $request->headers->all(),
            'cookie' => $request->cookies->all()
        ];
    }
}
