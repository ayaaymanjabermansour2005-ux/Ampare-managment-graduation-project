<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'broadcasting/auth'],

    'allowed_methods' => ['*'],
    'allowed_origins' => array_filter(array_map(
        'trim',
        explode(
            ',',
            env(
                'CORS_ALLOWED_ORIGINS',
                'http://localhost:8000,http://127.0.0.1:8000,http://localhost:5173,http://127.0.0.1:5173'
            )
        )
    )),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-XSRF-TOKEN',
        'Accept',
    ],

    'exposed_headers' => [],

    'max_age' => 600,

    'supports_credentials' => true,

];
