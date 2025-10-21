<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\RoleEnum;
use Tymon\JWTAuth\Facades\JWTAuth;

class CoactivoMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (\Exception $e) {
            $user = null;
        }

        if (!$user || $user->role !== RoleEnum::COACTIVO->value) {
            return response()->json(['message' => 'No autorizado (Coactivo)'], 403);
        }

        return $next($request);
    }
}
