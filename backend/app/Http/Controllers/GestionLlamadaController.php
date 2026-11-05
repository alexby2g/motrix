<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Services\FcmPushService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GestionLlamadaController extends Controller
{
    private function participantes(Request $request, Solicitud $viaje): array
    {
        $usuario = $request->user();

        $esPasajero = $usuario->role === 'pasajero'
            && (int) $usuario->pasajero_id > 0
            && (int) $usuario->pasajero_id === (int) $viaje->id_pasajero;

        $esConductor = $usuario->role === 'conductor'
            && (int) $usuario->mototaxista_id > 0
            && (int) $usuario->mototaxista_id === (int) $viaje->mototaxista_id;

        abort_unless($esPasajero || $esConductor, 403);

        return [$esPasajero, $esConductor];
    }

    public function iniciar(Request $request, int $id, FcmPushService $push)
    {
        $viaje = Solicitud::with([
            'pasajero.usuarioPasajero',
            'mototaxista.usuarioConductor',
        ])->findOrFail($id);

        [$esPasajero, $esConductor] = $this->participantes($request, $viaje);

        abort_unless(
            in_array($viaje->estado, ['Aceptado', 'Llegó', 'En Curso'], true),
            409,
            'El viaje no permite llamadas.'
        );

        $receptor = $esConductor
            ? $viaje->pasajero?->usuarioPasajero
            : $viaje->mototaxista?->usuarioConductor;

        abort_unless($receptor, 422, 'Destinatario no disponible.');

        $llamada = DB::transaction(function () use ($viaje, $request, $receptor) {
            Solicitud::whereKey($viaje->id)->lockForUpdate()->firstOrFail();

            DB::table('llamada_viajes')
                ->where('solicitud_id', $viaje->id)
                ->where('estado', 'pendiente')
                ->where('expira_en', '<=', now())
                ->update([
                    'estado' => 'expirada',
                    'finalizada_en' => now(),
                    'updated_at' => now(),
                ]);

            $existente = DB::table('llamada_viajes')
                ->where('solicitud_id', $viaje->id)
                ->whereIn('estado', ['pendiente', 'contestada'])
                ->first();

            abort_if($existente, 409, 'Ya existe una llamada activa.');

            return DB::table('llamada_viajes')->insertGetId([
                'solicitud_id' => $viaje->id,
                'iniciador_user_id' => $request->user()->id,
                'receptor_user_id' => $receptor->id,
                'estado' => 'pendiente',
                'expira_en' => now()->addSeconds(45),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $enviadas = $push->sendToUser(
            $receptor,
            'Llamada privada MOTRIX',
            $esConductor
                ? 'Tu mototaxista te esta llamando.'
                : 'Tu pasajero te esta llamando.',
            [
                'tipo' => 'llamada_entrante',
                'viaje_id' => (string) $viaje->id,
                'llamada_id' => (string) $llamada,
                'route' => $esConductor
                    ? '/pasajero/solicitar'
                    : '/conductor',
            ]
        );

        return response()->json([
            'llamada_id' => $llamada,
            'estado' => 'pendiente',
            'aviso_firebase_enviado' => $enviadas > 0,
        ], 201);
    }

    public function estado(Request $request, int $id)
    {
        $viaje = Solicitud::findOrFail($id);
        $this->participantes($request, $viaje);

        $llamada = DB::table('llamada_viajes')
            ->where('solicitud_id', $id)
            ->where(function ($q) use ($request) {
                $q->where('iniciador_user_id', $request->user()->id)
                  ->orWhere('receptor_user_id', $request->user()->id);
            })
            ->orderByDesc('id')
            ->first();

        if (!$llamada) {
            return response()->json(['estado' => 'sin_llamada']);
        }

        if ($llamada->estado === 'pendiente'
            && $llamada->expira_en <= now()) {
            DB::table('llamada_viajes')
                ->where('id', $llamada->id)
                ->where('estado', 'pendiente')
                ->update([
                    'estado' => 'expirada',
                    'finalizada_en' => now(),
                    'updated_at' => now(),
                ]);
            $llamada->estado = 'expirada';
        }

        return response()->json([
            'llamada_id' => $llamada->id,
            'estado' => $llamada->estado,
            'soy_receptor' => (int) $llamada->receptor_user_id
                === (int) $request->user()->id,
        ]);
    }

    public function responder(Request $request, int $id)
    {
        $datos = $request->validate([
            'accion' => ['required', 'in:aceptar,rechazar'],
        ]);

        $resultado = DB::transaction(function () use ($request, $id, $datos) {
            $viaje = Solicitud::findOrFail($id);
            $this->participantes($request, $viaje);

            abort_unless(
                in_array($viaje->estado, ['Aceptado', 'Llegó', 'En Curso'], true),
                409,
                'El viaje ya no permite llamadas.'
            );

            $llamada = DB::table('llamada_viajes')
                ->where('solicitud_id', $id)
                ->where('receptor_user_id', $request->user()->id)
                ->where('estado', 'pendiente')
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            abort_unless($llamada, 409, 'No tienes una llamada pendiente.');

            if (now()->greaterThanOrEqualTo($llamada->expira_en)) {
                DB::table('llamada_viajes')
                    ->where('id', $llamada->id)
                    ->update([
                        'estado' => 'expirada',
                        'finalizada_en' => now(),
                        'updated_at' => now(),
                    ]);

                return ['estado' => 'expirada'];
            }

            $aceptar = $datos['accion'] === 'aceptar';
            $estado = $aceptar ? 'contestada' : 'rechazada';

            DB::table('llamada_viajes')
                ->where('id', $llamada->id)
                ->update([
                    'estado' => $estado,
                    'contestada_en' => $aceptar ? now() : null,
                    'finalizada_en' => $aceptar ? null : now(),
                    'updated_at' => now(),
                ]);

            return [
                'llamada_id' => $llamada->id,
                'estado' => $estado,
            ];
        });

        return response()->json($resultado);
    }

    public function finalizar(Request $request, int $id)
    {
        $viaje = Solicitud::findOrFail($id);
        $this->participantes($request, $viaje);

        $llamada = DB::table('llamada_viajes')
            ->where('solicitud_id', $id)
            ->where(function ($q) use ($request) {
                $q->where('iniciador_user_id', $request->user()->id)
                  ->orWhere('receptor_user_id', $request->user()->id);
            })
            ->whereIn('estado', ['pendiente', 'contestada', 'finalizada'])
            ->orderByDesc('id')
            ->first();

        if (!$llamada) {
            return response()->json([
                'estado' => 'sin_llamada',
            ]);
        }

        $actualizadas = DB::table('llamada_viajes')
            ->where('id', $llamada->id)
            ->whereIn('estado', ['pendiente', 'contestada'])
            ->update([
                'estado' => 'finalizada',
                'finalizada_en' => now(),
                'updated_at' => now(),
            ]);

        $url = (string) config('livekit.url');
        $key = (string) config('livekit.key');
        $secret = (string) config('livekit.secret');

        if ($url === '' || $key === '' || $secret === '') {
            return response()->json([
                'estado' => 'finalizada',
                'audio_remoto_cerrado' => false,
                'message' => 'LiveKit no esta configurado para cerrar la sala.',
            ], 503);
        }

        try {
            $host = preg_replace('/^wss:/i', 'https:', $url);
            $host = preg_replace('/^ws:/i', 'http:', $host);

            $livekit = new \Agence104\LiveKit\RoomServiceClient(
                $host,
                $key,
                $secret
            );

            $livekit->deleteRoom(
                'motrix-llamada-' . $llamada->id
            );

            return response()->json([
                'estado' => 'finalizada',
                'actualizadas' => $actualizadas,
                'audio_remoto_cerrado' => true,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                'No se pudo confirmar el cierre remoto de LiveKit',
                [
                    'llamada_id' => $llamada->id,
                    'error' => $e->getMessage(),
                ]
            );

            return response()->json([
                'estado' => 'finalizada',
                'audio_remoto_cerrado' => false,
                'message' => 'No se pudo confirmar el cierre remoto.',
            ], 503);
        }
    }
}