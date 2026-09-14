<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\ViajeCompartidoToken;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ViajeCompartidoController extends Controller
{
    public function create(Request $request, int $id): JsonResponse
    {
        $pasajeroId = (int) ($request->user()?->pasajero_id ?? 0);

        if ($pasajeroId <= 0) {
            return response()->json([
                'message' => 'La cuenta no está vinculada a un pasajero.',
            ], 403);
        }

        $solicitud = Solicitud::query()
            ->where('id', $id)
            ->where('id_pasajero', $pasajeroId)
            ->first();

        if (! $solicitud) {
            return response()->json([
                'message' => 'El viaje no existe o no te pertenece.',
            ], 404);
        }

        if (! in_array($solicitud->estado, ['Aceptado', 'Llegó', 'En Curso'], true)) {
            return response()->json([
                'message' => 'El seguimiento se puede compartir cuando un conductor ya aceptó el viaje.',
            ], 409);
        }

        $plainToken = Str::random(64);
        $expiresAt = Carbon::now('UTC')->addHours(2);

        DB::transaction(function () use ($request, $solicitud, $plainToken, $expiresAt) {
            ViajeCompartidoToken::query()
                ->where('solicitud_id', $solicitud->id)
                ->whereNull('revoked_at')
                ->where('expires_at', '>', Carbon::now('UTC'))
                ->update(['revoked_at' => Carbon::now('UTC')]);

            ViajeCompartidoToken::query()->create([
                'solicitud_id' => $solicitud->id,
                'token_hash' => hash('sha256', $plainToken),
                'created_by_user_id' => $request->user()?->id,
                'expires_at' => $expiresAt,
            ]);
        });

        $frontend = trim((string) config('app.frontend_url'));
        $frontend = preg_replace('/#.*$/', '', $frontend) ?: $frontend;
        $frontend = rtrim($frontend, '/');

        return response()->json([
            'message' => 'Enlace temporal creado correctamente.',
            // El frontend Quasar usa vueRouterMode=hash. La ruta publica debe
            // ir despues de # para que Vue Router no la confunda con la raiz
            // protegida y redirija al login al abrirla sin sesion.
            'url' => $frontend . '/#/seguimiento/' . $plainToken,
            'expires_at' => $expiresAt->toIso8601String(),
        ]);
    }

    public function show(string $token): JsonResponse
    {
        if (strlen($token) < 40 || strlen($token) > 255) {
            return $this->notFound();
        }

        $record = ViajeCompartidoToken::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $record) {
            return $this->notFound();
        }

        if (
            $record->revoked_at
            || ! $record->expires_at
            || $record->expires_at->lessThanOrEqualTo(Carbon::now('UTC'))
        ) {
            return response()->json([
                'message' => 'Este enlace de seguimiento ya venció.',
            ], 410);
        }

        $solicitud = Solicitud::query()
            ->with([
                'mototaxista.persona.imagenes',
                'mototaxista.sindicato',
                'mototaxista.motocicletas',
            ])
            ->find($record->solicitud_id);

        if (! $solicitud) {
            return $this->notFound();
        }

        $mototaxista = $solicitud->mototaxista;
        $persona = $mototaxista?->persona;
        $foto = $persona?->imagenes
            ?->sortByDesc('id')
            ->first()?->ruta;

        $rating = null;
        $ratingCount = 0;

        if ($mototaxista) {
            $ratingQuery = Solicitud::query()
                ->where('mototaxista_id', $mototaxista->id)
                ->where('estado', 'Finalizado')
                ->whereNotNull('calificacion');

            $rating = (float) ((clone $ratingQuery)->avg('calificacion') ?? 0);
            $ratingCount = (clone $ratingQuery)->count();
        }

        $active = in_array($solicitud->estado, ['Aceptado', 'Llegó', 'En Curso'], true);
        $motorcycle = $mototaxista?->motocicletas?->sortByDesc('id')->first();

        return response()->json([
            'viaje' => [
                'estado' => $solicitud->estado,
                'origen' => $solicitud->origen,
                'destino' => $solicitud->destino,
                'latitud_origen' => $solicitud->latitud_origen,
                'longitud_origen' => $solicitud->longitud_origen,
                'latitud_destino' => $solicitud->latitud_destino,
                'longitud_destino' => $solicitud->longitud_destino,
                'distancia_km' => $solicitud->distancia_km,
                'precio' => $solicitud->precio,
                'metodo_pago' => $solicitud->metodo_pago,
            ],
            'conductor' => $mototaxista ? [
                'nombre' => trim((string) (($persona?->nombre ?? '') . ' ' . ($persona?->apellidos ?? ''))),
                'nro_chaleco' => $mototaxista->nro_chaleco,
                'sindicato' => $mototaxista->sindicato?->nombre,
                'foto_ruta' => $foto,
                'promedio_calificacion' => round($rating ?? 0, 2),
                'total_calificaciones' => $ratingCount,
                'latitud' => $active ? $mototaxista->latitud : null,
                'longitud' => $active ? $mototaxista->longitud : null,
                'ultima_conexion' => $active ? $mototaxista->ultima_conexion : null,
                'motocicleta' => $motorcycle ? [
                    'placa' => $motorcycle->placa,
                    'modelo' => $motorcycle->modelo,
                    'color' => $motorcycle->color,
                ] : null,
            ] : null,
            'seguimiento_activo' => $active,
            'expires_at' => $record->expires_at->toIso8601String(),
        ])->withHeaders([
            'Cache-Control' => 'no-store, private, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'message' => 'El enlace de seguimiento no es válido.',
        ], 404);
    }
}
