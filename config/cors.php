<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    'paths' => ['*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
         env('FRONTEND_URL', 'http://localhost:3000'),
         'http://127.0.0.1:8000',
         'https://les-casaniers-frontend.vercel.app',
         'http://localhost:5173',
         'http://localhost:8080',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
