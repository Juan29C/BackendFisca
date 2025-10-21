<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JWTAuthenticate
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $e) {
            return response()->json(['message' => 'El token ha expirado.'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['message' => 'Token inválido.'], 401);
        } catch (JWTException $e) {
            return response()->json(['message' => 'Token no proporcionado.'], 401);
        }

        // el usuario está autenticado
        return $next($request);
    }
}
