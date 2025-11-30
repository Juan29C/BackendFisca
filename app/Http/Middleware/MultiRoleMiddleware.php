<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\RoleEnum;

class MultiRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles  Los roles permitidos (ej: 'fiscalizacion', 'coactivo')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'error' => 'No autenticado'
            ], 401);
        }

        // Convertir strings a valores del enum
        $allowedRoles = [];
        foreach ($roles as $role) {
            $roleEnum = match(strtoupper($role)) {
                'FISCALIZACION' => RoleEnum::FISCALIZACION,
                'COACTIVO' => RoleEnum::COACTIVO,
                'USUARIO' => RoleEnum::USUARIO,
                default => null
            };
            
            if ($roleEnum) {
                $allowedRoles[] = $roleEnum->value;
            }
        }

        if (!in_array($user->role, $allowedRoles)) {
            return response()->json([
                'error' => 'No autorizado. Se requiere uno de los siguientes roles: ' . implode(', ', $roles)
            ], 403);
        }

        return $next($request);
    }
}
