<?php

namespace App\Http\Controllers;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LlamadaPrivadaController extends Controller
{
    public function token(Request $request, int $id): JsonResponse
    {
        $usuario = $request->user();

        $solicitud = Solicitud::query()->findOrFail($id);

        $esPasajero = (int) ($usuario->pasajero_id ?? 0) > 0
            && (int) $usuario->pasajero_id === (int) $solicitud->id_pasajero;

        $esConductor = (int) ($usuario->mototaxista_id ?? 0) > 0
            && (int) $usuario->mototaxista_id === (int) $solicitud->mototaxista_id;

        abort_unless($esPasajero || $esConductor, 403, 'No autorizado para este viaje.');

        abort_unless(
            in_array($solicitud->estado, ['Aceptado', 'Llegó', 'En Curso'], true),
            409,
            'El viaje no permite llamadas.'
        );

        $llamada = \Illuminate\Support\Facades\DB::table('llamada_viajes')
            ->where('solicitud_id', $solicitud->id)
            ->where('estado', 'contestada')
            ->where(function ($query) use ($usuario) {
                $query->where('iniciador_user_id', $usuario->id)
                    ->orWhere('receptor_user_id', $usuario->id);
            })
            ->whereNotNull('contestada_en')
            ->whereNull('finalizada_en')
            ->orderByDesc('id')
            ->first();

        abort_unless($llamada, 409, 'Llamada no contestada.');
        $url = (string) config('livekit.url');
        $key = (string) config('livekit.key');
        $secret = (string) config('livekit.secret');

        if ($url === '' || $key === '' || $secret === '') {
            return response()->json([
                'message' => 'LiveKit aun no esta configurado.',
            ], 503);
        }

        $sala = 'motrix-llamada-' . $llamada->id;
        $identidad = 'motrix-usuario-' . $usuario->id;

        $opciones = (new AccessTokenOptions())
            ->setIdentity($identidad)
            ->setTtl(120);

        $permisos = (new VideoGrant())
            ->setRoomJoin()
            ->setRoomName($sala)
            ->setCanPublish(true)
            ->setCanSubscribe(true);

        $token = (new AccessToken($key, $secret))
            ->init($opciones)
            ->setGrant($permisos)
            ->toJwt();

        return response()->json([
            'url' => $url,
            'token' => $token,
            'expires_in' => 120,
            'viaje_id' => $solicitud->id,
        ]);
    }
}