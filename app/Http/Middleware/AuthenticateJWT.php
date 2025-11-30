<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateJWT
{
    /**
     * Handle an incoming request.
     * Solo valida que el usuario esté autenticado con JWT, NO valida roles.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'No autenticado'
            ], 401);
        }

        return $next($request);
    }
}
