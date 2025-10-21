<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\RoleEnum;
use Tymon\JWTAuth\Facades\JWTAuth;

class FiscalizacionMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            $user = null;
        }

        if (!$user || $user->role !== RoleEnum::FISCALIZACION->value) {
            return response()->json(['message' => 'No autorizado (Fiscalización)'], 403);
        }

        return $next($request);
    }
}
