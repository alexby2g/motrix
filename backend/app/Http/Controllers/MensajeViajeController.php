<?php

namespace App\Http\Controllers;

use App\Events\MensajeViajeEnviado;
use App\Models\MensajeViaje;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MensajeViajeController extends Controller
{
    private const ESTADOS_CHAT_ABIERTO = [
        'Buscando conductor',
        'Aceptado',
        'Llegó',
        'En Curso',
    ];

    /**
     * Devuelve los últimos 200 mensajes del viaje.
     * El pasajero y el conductor solo acceden a su propio viaje.
     * El administrador puede revisar el chat en modo lectura.
     */
    public function index(
        Request $request,
        $id
    ): JsonResponse {
        $solicitud = $this->obtenerSolicitudAutorizada(
            $request,
            (int) $id
        );

        $rol = $this->resolverRol($request);

        $mensajes = MensajeViaje::query()
            ->where('solicitud_id', $solicitud->id)
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'solicitud_id' => (int) $solicitud->id,
            'chat_habilitado' => in_array(
                $solicitud->estado,
                self::ESTADOS_CHAT_ABIERTO,
                true
            ),
            'estado_solicitud' => $solicitud->estado,
            'rol_actual' => $rol,
            'solo_lectura' => $rol === 'admin',
            'no_leidos' => $this->contarNoLeidos(
                (int) $solicitud->id,
                $rol
            ),
            'mensajes' => $mensajes,
        ], 200);
    }

    /**
     * Envía un mensaje como pasajero o conductor.
     * El administrador no puede escribir.
     */
    public function store(
        Request $request,
        $id
    ): JsonResponse {
        $solicitud = $this->obtenerSolicitudAutorizada(
            $request,
            (int) $id
        );

        $rol = $this->resolverRol($request);

        if ($rol === 'admin') {
            return response()->json([
                'mensaje' => 'El administrador solo puede revisar el chat.',
            ], 403);
        }

        if (!in_array(
            $solicitud->estado,
            self::ESTADOS_CHAT_ABIERTO,
            true
        )) {
            return response()->json([
                'mensaje' => (
                    'El chat está cerrado porque el viaje '
                    . 'finalizó, fue cancelado o todavía no tiene conductor.'
                ),
            ], 409);
        }

        $datos = $request->validate([
            'mensaje' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $texto = trim(
            strip_tags((string) $datos['mensaje'])
        );

        if ($texto === '') {
            return response()->json([
                'mensaje' => 'Escribe un mensaje válido.',
            ], 422);
        }

        $usuario = $request->user();
        $ahora = Carbon::now('America/La_Paz')
            ->format('Y-m-d H:i:s');

        $mensaje = DB::transaction(
            function () use (
                $solicitud,
                $usuario,
                $rol,
                $texto,
                $ahora
            ) {
                return MensajeViaje::create([
                    'solicitud_id' => $solicitud->id,
                    'remitente_usuario_id' => $usuario?->id,
                    'remitente_tipo' => $rol,
                    'remitente_nombre' => $this->resolverNombreRemitente(
                        $solicitud,
                        $usuario,
                        $rol
                    ),
                    'mensaje' => $texto,
                    'leido_pasajero_en' => $rol === 'pasajero'
                        ? $ahora
                        : null,
                    'leido_conductor_en' => $rol === 'conductor'
                        ? $ahora
                        : null,
                    'creado_en' => $ahora,
                ]);
            }
        );

        broadcast(
            new MensajeViajeEnviado($mensaje)
        );

        return response()->json([
            'mensaje' => 'Mensaje enviado correctamente.',
            'chat_mensaje' => $mensaje,
        ], 201);
    }

    /**
     * Marca como leídos los mensajes recibidos por el usuario autenticado.
     */
    public function marcarLeidos(
        Request $request,
        $id
    ): JsonResponse {
        $solicitud = $this->obtenerSolicitudAutorizada(
            $request,
            (int) $id
        );

        $rol = $this->resolverRol($request);

        if (!in_array($rol, ['pasajero', 'conductor'], true)) {
            return response()->json([
                'mensaje' => 'Esta acción corresponde al pasajero o conductor.',
            ], 403);
        }

        $ahora = Carbon::now('America/La_Paz')
            ->format('Y-m-d H:i:s');

        $consulta = MensajeViaje::query()
            ->where('solicitud_id', $solicitud->id);

        if ($rol === 'pasajero') {
            $actualizados = $consulta
                ->where('remitente_tipo', 'conductor')
                ->whereNull('leido_pasajero_en')
                ->update([
                    'leido_pasajero_en' => $ahora,
                ]);
        } else {
            $actualizados = $consulta
                ->where('remitente_tipo', 'pasajero')
                ->whereNull('leido_conductor_en')
                ->update([
                    'leido_conductor_en' => $ahora,
                ]);
        }

        return response()->json([
            'mensaje' => 'Mensajes marcados como leídos.',
            'actualizados' => $actualizados,
        ], 200);
    }

    private function obtenerSolicitudAutorizada(
        Request $request,
        int $solicitudId
    ): Solicitud {
        $solicitud = Solicitud::query()
            ->with([
                'pasajero.persona',
                'mototaxista.persona',
            ])
            ->findOrFail($solicitudId);

        $usuario = $request->user();
        $rol = $this->resolverRol($request);

        if ($rol === 'admin') {
            return $solicitud;
        }

        if (
            $rol === 'pasajero'
            && (int) ($usuario?->pasajero_id ?? 0)
                === (int) $solicitud->id_pasajero
        ) {
            return $solicitud;
        }

        if (
            $rol === 'conductor'
            && (int) ($usuario?->mototaxista_id ?? 0) > 0
            && (int) ($usuario?->mototaxista_id ?? 0)
                === (int) ($solicitud->mototaxista_id ?? 0)
        ) {
            return $solicitud;
        }

        abort(
            403,
            'No tienes autorización para acceder al chat de este viaje.'
        );
    }

    private function resolverRol(Request $request): string
    {
        $rol = strtolower(
            trim(
                (string) ($request->user()?->role ?? '')
            )
        );

        if (!in_array(
            $rol,
            ['admin', 'pasajero', 'conductor'],
            true
        )) {
            abort(
                403,
                'Tu cuenta no tiene un rol válido para utilizar el chat.'
            );
        }

        return $rol;
    }

    private function resolverNombreRemitente(
        Solicitud $solicitud,
        $usuario,
        string $rol
    ): string {
        if ($rol === 'pasajero') {
            return (string) (
                $solicitud->pasajero?->persona?->nombre
                ?? $usuario?->name
                ?? $usuario?->email
                ?? 'Pasajero'
            );
        }

        if ($rol === 'conductor') {
            return (string) (
                $solicitud->mototaxista?->persona?->nombre
                ?? $usuario?->name
                ?? $usuario?->email
                ?? 'Mototaxista'
            );
        }

        return (string) (
            $usuario?->name
            ?? $usuario?->email
            ?? 'Administrador'
        );
    }

    private function contarNoLeidos(
        int $solicitudId,
        string $rol
    ): int {
        if ($rol === 'admin') {
            return 0;
        }

        $consulta = MensajeViaje::query()
            ->where('solicitud_id', $solicitudId);

        if ($rol === 'pasajero') {
            return $consulta
                ->where('remitente_tipo', 'conductor')
                ->whereNull('leido_pasajero_en')
                ->count();
        }

        return $consulta
            ->where('remitente_tipo', 'pasajero')
            ->whereNull('leido_conductor_en')
            ->count();
    }
}
