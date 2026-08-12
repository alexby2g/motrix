<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Permite el acceso cuando el usuario posee cualquiera
     * de los roles indicados.
     *
     * Mantiene compatibilidad temporal con los nombres anteriores:
     * admin  -> admin_general
     * cajero -> admin_servicios
     */
    public function handle(
        Request $request,
        Closure $next,
        string ...$roles
    ): Response|JsonResponse {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        $rolUsuario = $this->normalizarRol(
            (string) $user->role
        );

        $rolesPermitidos = array_map(
            fn (string $role) =>
                $this->normalizarRol($role),
            $roles
        );

        if (! in_array(
            $rolUsuario,
            $rolesPermitidos,
            true
        )) {
            return response()->json([
                'message' =>
                    'No tienes permiso para realizar esta acción.',
            ], 403);
        }

        return $next($request);
    }

    private function normalizarRol(
        string $rol
    ): string {
        $valor = strtolower(
            trim($rol)
        );

        return match ($valor) {
            'admin' => 'admin_general',
            'cajero' => 'admin_servicios',
            default => $valor,
        };
    }
}
