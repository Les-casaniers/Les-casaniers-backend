<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    */

    // 'paths' => ['*'],

    // 'allowed_methods' => ['*'],

    // 'allowed_origins' => [
    //     env('FRONTEND_URL', 'http://localhost:3000'),
    //     'http://127.0.0.1:8000',
    //     'http://172.26.160.1:8080',
    //     'https://les-casaniers-frontend.vercel.app/',
    //     // AJOUTEZ CES LIGNES :
    //     'http://localhost:5173',
    //     'http://127.0.0.1:5173',
    //     'http://localhost:8080',
    //     'http://127.0.0.1:8080',
    //     'http://192.168.1.134:8080',  // Votre IP exacte
    // ],

    // 'allowed_origins_patterns' => [],

    // 'allowed_headers' => ['*'],

    // 'exposed_headers' => [],

    // 'max_age' => 0,

    // 'supports_credentials' => true,

    'paths' => ['api/*', 'sanctum/csrf-cookie', '*'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => ['*'], // Temporaire pour le développement
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => false, // Mettre false pour éviter les problèmes

];