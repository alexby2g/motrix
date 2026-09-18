<?php

namespace App\Http\Controllers;

use App\Models\Mototaxista;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ConductorCuentaController extends Controller
{
    public function destroy(Request $request): JsonResponse
    {
        $usuario = $request->user();

        if (! $usuario || strtolower(trim((string) $usuario->role)) !== 'conductor') {
            abort(403, 'Esta acción corresponde únicamente a una cuenta de conductor.');
        }

        $datos = $request->validate([
            'password' => ['required', 'string', 'max:255'],
            'confirmacion' => ['required', 'string', 'in:ELIMINAR'],
        ], [
            'password.required' => 'Ingresa tu contraseña actual.',
            'confirmacion.in' => 'Escribe ELIMINAR para confirmar la eliminación de la cuenta.',
        ]);

        if (! Hash::check((string) $datos['password'], $usuario->password)) {
            return response()->json([
                'message' => 'La contraseña actual es incorrecta.',
                'errors' => ['password' => ['La contraseña actual es incorrecta.']],
            ], 422);
        }

        $mototaxistaId = (int) ($usuario->mototaxista_id ?? 0);
        $mototaxista = Mototaxista::query()->find($mototaxistaId);

        if (! $mototaxista) {
            return response()->json([
                'message' => 'La cuenta no tiene un perfil de mototaxista válido.',
            ], 409);
        }

        $viajeActivo = DB::table('solicitudes')
            ->where('mototaxista_id', $mototaxistaId)
            ->whereIn('estado', [
                'Pendiente',
                'Buscando conductor',
                'Aceptado',
                'Llegó',
                'En Curso',
            ])
            ->exists();

        if ($viajeActivo) {
            return response()->json([
                'message' => 'No puedes eliminar tu cuenta mientras tengas una solicitud o viaje activo.',
            ], 409);
        }

        DB::transaction(function () use ($usuario, $mototaxista) {
            $mototaxista->update([
                'disponible' => false,
                'latitud' => null,
                'longitud' => null,
            ]);

            $usuario->tokens()->delete();
            $usuario->delete();
        });

        return response()->json([
            'message' => 'Tu cuenta de conductor fue eliminada correctamente.',
            'registro_mototaxista_conservado' => true,
            'historial_conservado' => true,
        ]);
    }
}
