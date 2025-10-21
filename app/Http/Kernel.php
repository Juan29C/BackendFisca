<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;

// Middleware propios
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\FiscalizacionMiddleware;
use App\Http\Middleware\CoactivoMiddleware;
use App\Http\Middleware\JWTAuthenticate;
use Illuminate\Routing\Middleware\ThrottleRequests;

class Kernel extends HttpKernel
{

    protected $middleware = [
        HandleCors::class,          
        ValidatePostSize::class,    
    ];

    protected $middlewareAliases = [
        // Roles
        'fiscalizacion'  => FiscalizacionMiddleware::class,
        'coactivo'       => CoactivoMiddleware::class,
        'throttle'      => ThrottleRequests::class,
    ];
}
