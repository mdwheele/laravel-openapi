<?php

namespace Mdwheele\OpenApi\Tests\Controllers;

class PetsController extends Controller
{
    public function index()
    {
        return [
            [
                'id' => 1,
                'name' => 'Dog'
            ],
            [
                'id' => 2,
                'name' => 'Cat'
            ]
        ];
    }

    public function store()
    {
        return request()->json();
    }

    public function show($petId)
    {
        return [
            'id' => 1,
            'name' => 'Dog'
        ];
    }
}
