<?php

use App\Http\Middleware\CoactivoMiddleware;
use App\Http\Middleware\FiscalizacionMiddleware;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\IsUserAuth;
use App\Http\Middleware\JWTAuthenticate;
use App\Http\Middleware\JWTRefresh;
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
        CoactivoMiddleware::class;
        FiscalizacionMiddleware::class;
        IsUserAuth::class;
    })
    ->withMiddleware(function (Middleware $middleware): void {
        CoactivoMiddleware::class;
        FiscalizacionMiddleware::class;
        IsUserAuth::class;
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (UnauthorizedHttpException $e, $request) {
            return response()->json([
                'status'  => 401,
                'message' => 'No autenticado',
            ], 401);
        });

        $exceptions->render(function (AuthenticationException $e, $request) {
            return response()->json([
                'status'  => 401,
                'message' => 'No autenticado',
            ], 401);
        });

        $exceptions->render(function (TokenExpiredException $e, $request) {
            return response()->json([
                'status'  => 401,
                'message' => 'El token ha expirado',
            ], 401);
        });

        $exceptions->render(function (TokenInvalidException $e, $request) {
            return response()->json([
                'status'  => 401,
                'message' => 'Token inválido',
            ], 401);
        });

        $exceptions->render(function (JWTException $e, $request) {
            return response()->json([
                'status'  => 401,
                'message' => 'Token no proporcionado',
            ], 401);
        });

        $exceptions->render(function (AuthorizationException|AccessDeniedHttpException $e, $request) {
            return response()->json([
                'status'  => 403,
                'message' => 'No autorizado',
            ], 403);
        });

        $exceptions->render(function (NotFoundHttpException $e, $request) {
            return response()->json([
                'status'  => 404,
                'message' => 'Recurso no encontrado',
            ], 404);
        });

        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            return response()->json([
                'status'  => 405,
                'message' => 'Método no permitido',
            ], 405);
        });

        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            return response()->json([
                'status'  => 429,
                'message' => 'Demasiadas solicitudes',
            ], 429);
        });

        $exceptions->render(function (\Throwable $e, $request) {
            return response()->json([
                'status'  => 500,
                'message' => 'Error interno del servidor',
            ], 500);
        });

    })->create();
