<?php

use App\Http\Middleware\AuthenticateJWT;
use App\Http\Middleware\CoactivoMiddleware;
use App\Http\Middleware\FiscalizacionMiddleware;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\IsUserAuth;
use App\Http\Middleware\JWTAuthenticate;
use App\Http\Middleware\JWTRefresh;
use App\Http\Middleware\MultiRoleMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth.jwt' => AuthenticateJWT::class,
            'coactivo' => CoactivoMiddleware::class,
            'fiscalizacion' => FiscalizacionMiddleware::class,
            'multi.role' => MultiRoleMiddleware::class,
            'user.auth' => IsUserAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (UnauthorizedHttpException $e, $request) {
            return response()->json([
                'status'  => 401,
                'message' => 'No autenticado',
            ], 401);
        });
    })->create();
