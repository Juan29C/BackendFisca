<?php

namespace App\Http;

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Routing\Middleware\SubstituteBindings;

class Kernel extends HttpKernel
{
    /**
     * Middleware globales de la aplicación.
     *
     * @var array
     */
    protected $middleware = [
        // Manejo de CORS
        \Illuminate\Http\Middleware\HandleCors::class,
        // Validación del tamaño de las peticiones POST
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
    ];

    /**
     * Grupos de middleware para diferentes tipos de rutas.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'api' => [
            // Manejo de CORS para API
            HandleCors::class,
            // Forzar respuestas JSON
            ForceJsonResponse::class,
            // Limitar número de peticiones
            'throttle:api',
            // Inyección de dependencias en rutas
            SubstituteBindings::class,
        ],
    ];

    /**
     * Aliases de middleware para rutas individuales.
     *
     * @var array
     */
    protected $middlewareAliases = [
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
    ];
}
