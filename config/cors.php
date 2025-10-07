<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Paths that should be accessible cross-origin
    |--------------------------------------------------------------------------
    */
    'paths' => ['api/*', 'login', 'logout', 'sanctum/csrf-cookie'],

    /*
    |--------------------------------------------------------------------------
    | Allowed methods
    |--------------------------------------------------------------------------
    */
    'allowed_methods' => ['*'],

    /*
    |--------------------------------------------------------------------------
    | Allowed origins
    |--------------------------------------------------------------------------
    | Use '*' for all during local testing, but set your domain in production
    */
    // Configure allowed origins via env for production security.
    // Example: CORS_ALLOWED_ORIGINS="https://brand.example.com,https://staging.example.com"
    'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '*'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
