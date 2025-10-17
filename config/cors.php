<?php

return [
    // Rutas que permitirán CORS
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    // Métodos HTTP permitidos
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Orígenes permitidos (dominios)
    'allowed_origins' => [
        'http://localhost:3000',     
        'http://localhost:5173',     
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
    ],

    // Headers permitidos
    'allowed_headers' => [
        'Accept',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

    'allowed_origins_patterns' => [],
];
