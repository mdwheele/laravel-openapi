<?php

return [

    /*
     * The path to your OpenApi specification root document.
     */
    'path' => env('OPENAPI_PATH'),

    /*
     * Whether or not to validate response schemas. You may want to
     * enable this in development and disable in production. Do as you
     * wish!
     */
    'validate_responses' => env('OPENAPI_VALIDATE_RESPONSES', true)

];
