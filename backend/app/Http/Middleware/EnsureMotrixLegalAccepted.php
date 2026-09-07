<?php

namespace App\Http\Middleware;

use App\Models\MotrixLegalAcceptance;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class EnsureMotrixLegalAccepted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $role = strtolower(trim((string) $user->role));

        if (! in_array($role, ['pasajero', 'conductor'], true)) {
            return $next($request);
        }

        /*
         * La aceptación se exige antes de ejecutar acciones operativas.
         * Las lecturas se mantienen disponibles para que el perfil y la
         * pantalla legal puedan cargarse sin generar errores en cascada.
         */
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        /*
         * Se limita el bloqueo a las operaciones propias de pasajero
         * y conductor. Logout, cambio de contraseña, eliminación de
         * cuenta y aceptación legal quedan disponibles aunque el usuario
         * decida no aceptar.
         */
        $isOperationalRequest = $request->is(
            'api/pasajero/*',
            'api/conductor/*'
        );

        if (! $isOperationalRequest) {
            return $next($request);
        }

        if ($request->is(
            'api/pasajero/cuenta',
            'api/conductor/cuenta',
            'api/legal/*'
        )) {
            return $next($request);
        }

        if (! Schema::hasTable('motrix_legal_acceptances')) {
            return response()->json([
                'message' => 'La aceptación de Términos y Privacidad debe configurarse antes de continuar.',
                'code' => 'LEGAL_MIGRATION_REQUIRED',
            ], 503);
        }

        $accepted = MotrixLegalAcceptance::query()
            ->where('user_id', $user->id)
            ->where(
                'terms_version',
                (string) config('motrix_legal.terms.version')
            )
            ->where(
                'privacy_version',
                (string) config('motrix_legal.privacy.version')
            )
            ->exists();

        if (! $accepted) {
            return response()->json([
                'message' => 'Debes aceptar los Términos y Condiciones y la Política de Privacidad de MOTRIX antes de continuar.',
                'code' => 'LEGAL_ACCEPTANCE_REQUIRED',
                'terms_version' => config('motrix_legal.terms.version'),
                'privacy_version' => config('motrix_legal.privacy.version'),
            ], 428);
        }

        return $next($request);
    }
}
