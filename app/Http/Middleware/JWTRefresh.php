<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;

class JWTRefresh
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = JWTAuth::getToken();
            if (!$token) {
                return response()->json(['message' => 'Token no proporcionado.'], 401);
            }

            $newToken = JWTAuth::refresh($token);

            $response = $next($request);
            if (method_exists($response, 'header')) {
                $response->headers->set('Authorization', 'Bearer ' . $newToken);
            }

            return $response;
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'El token ha expirado y no puede ser refrescado.'], 401);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error de autenticación.'], 401);
        }
    }
}
