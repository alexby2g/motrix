<?php

namespace App\Http\Controllers;

use App\Events\IncidenciaViajeActualizada;
use App\Events\IncidenciaViajeReportada;
use App\Models\IncidenciaViaje;
use App\Models\Solicitud;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class IncidenciaViajeController extends Controller
{
    private const ESTADOS_VIAJE_PERMITIDOS = [
        'Aceptado',
        'Llegó',
        'En Curso',
    ];

    private const TIPOS_PERMITIDOS = [
        'Accidente',
        'Emergencia médica',
        'Situación de inseguridad',
        'Falla de la motocicleta',
        'Pasajero no localizado',
        'Conductor no localizado',
        'Otro',
    ];

    private const ESTADOS_INCIDENCIA = [
        'Reportado',
        'Recibido',
        'En atención',
        'Resuelto',
    ];

    /**
     * Incidencias visibles para el pasajero o conductor del viaje.
     */
    public function indexViaje(Request $request, $id)
    {
        $solicitud = Solicitud::query()
            ->with([
                'pasajero.persona',
                'mototaxista.persona',
            ])
            ->findOrFail($id);

        $this->autorizarParticipante(
            $request,
            $solicitud
        );

        $incidencias = IncidenciaViaje::query()
            ->where('solicitud_id', $solicitud->id)
            ->orderByDesc('id')
            ->get();

        return response()->json([
            'incidencias' => $incidencias,
            'incidencia_activa' => $incidencias
                ->first(fn ($incidencia) => in_array(
                    $incidencia->estado,
                    ['Reportado', 'Recibido', 'En atención'],
                    true
                )),
        ], 200);
    }

    /**
     * Pasajero o conductor reporta una incidencia durante el viaje.
     */
    public function store(Request $request, $id)
    {
        $datos = $request->validate([
            'tipo' => [
                'required',
                'string',
                'in:' . implode(',', self::TIPOS_PERMITIDOS),
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:500',
            ],
            'latitud' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitud' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
            'precision_metros' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100000',
            ],
        ]);

        $incidencia = DB::transaction(function () use (
            $request,
            $id,
            $datos
        ) {
            $solicitud = Solicitud::query()
                ->with([
                    'pasajero.persona',
                    'mototaxista.persona',
                ])
                ->lockForUpdate()
                ->findOrFail($id);

            $contexto = $this->autorizarParticipante(
                $request,
                $solicitud
            );

            if (!in_array(
                $solicitud->estado,
                self::ESTADOS_VIAJE_PERMITIDOS,
                true
            )) {
                abort(
                    409,
                    'El botón SOS solo está disponible en un viaje aceptado, cuando el conductor llegó o durante el viaje.'
                );
            }

            $duplicada = IncidenciaViaje::query()
                ->where('solicitud_id', $solicitud->id)
                ->where('tipo', $datos['tipo'])
                ->activas()
                ->exists();

            if ($duplicada) {
                abort(
                    409,
                    'Ya existe una incidencia activa del mismo tipo para este viaje.'
                );
            }

            return IncidenciaViaje::create([
                'codigo' => $this->generarCodigo(),
                'solicitud_id' => $solicitud->id,
                'reportado_por_usuario_id' => $request->user()?->id,
                'reportado_por_rol' => $contexto['rol'],
                'reportado_por_nombre' => $contexto['nombre'],
                'tipo' => $datos['tipo'],
                'prioridad' => $this->resolverPrioridad(
                    $datos['tipo']
                ),
                'descripcion' => $this->limpiarTexto(
                    $datos['descripcion'] ?? null
                ),
                'latitud' => $datos['latitud'] ?? null,
                'longitud' => $datos['longitud'] ?? null,
                'precision_metros' => $datos['precision_metros'] ?? null,
                'estado' => 'Reportado',
                'fecha_reportada' => Carbon::now(
                    'America/La_Paz'
                )->format('Y-m-d H:i:s'),
            ])->load([
                'solicitud.pasajero.persona',
                'solicitud.mototaxista.persona',
            ]);
        });

        broadcast(
            new IncidenciaViajeReportada($incidencia)
        );

        return response()->json([
            'mensaje' => 'La alerta fue enviada a la central MOTRIX.',
            'incidencia' => $incidencia,
            'advertencia' => (
                'Esta alerta es interna de MOTRIX y no reemplaza '
                . 'una llamada a la Policía, ambulancia u otro servicio de emergencia.'
            ),
        ], 201);
    }

    /**
     * Listado administrativo de incidencias.
     */
    public function indexAdmin(Request $request)
    {
        $datos = $request->validate([
            'estado' => [
                'nullable',
                'string',
                'in:' . implode(
                    ',',
                    self::ESTADOS_INCIDENCIA
                ),
            ],
            'tipo' => [
                'nullable',
                'string',
                'in:' . implode(
                    ',',
                    self::TIPOS_PERMITIDOS
                ),
            ],
            'solicitud_id' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ]);

        $base = $this->consultaIncidenciasAdministrativas(
            $request
        );

        $consulta = (clone $base)
            ->with([
                'solicitud.pasajero.persona',
                'solicitud.mototaxista.persona',
                'solicitud.mototaxista.sindicato',
            ]);

        if (! empty($datos['estado'])) {
            $consulta->where(
                'estado',
                $datos['estado']
            );
        }

        if (! empty($datos['tipo'])) {
            $consulta->where(
                'tipo',
                $datos['tipo']
            );
        }

        if (
            ! empty(
                $datos['solicitud_id']
            )
        ) {
            $consulta->where(
                'solicitud_id',
                (int)
                $datos['solicitud_id']
            );
        }

        $incidencias = $consulta
            ->orderByRaw(
                "CASE estado
                    WHEN 'Reportado' THEN 1
                    WHEN 'Recibido' THEN 2
                    WHEN 'En atención' THEN 3
                    ELSE 4
                END"
            )
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $contador = function (
            ?string $estado = null,
            bool $soloActivas = false
        ) use ($base): int {
            $query = clone $base;

            if ($estado !== null) {
                $query->where(
                    'estado',
                    $estado
                );
            }

            if ($soloActivas) {
                $query->activas();
            }

            return $query->count();
        };

        return response()->json([
            'incidencias' =>
                $incidencias,
            'resumen' => [
                'reportadas' =>
                    $contador('Reportado'),
                'recibidas' =>
                    $contador('Recibido'),
                'en_atencion' =>
                    $contador('En atención'),
                'resueltas' =>
                    $contador('Resuelto'),
                'activas' =>
                    $contador(
                        null,
                        true
                    ),
            ],
        ], 200);
    }

    public function showAdmin(
        Request $request,
        $id
    ) {
        $incidencia =
            $this->consultaIncidenciasAdministrativas(
                $request
            )
                ->with([
                    'solicitud.pasajero.persona',
                    'solicitud.mototaxista.persona',
                    'solicitud.mototaxista.sindicato',
                ])
                ->findOrFail($id);

        return response()->json(
            $incidencia,
            200
        );
    }

    public function actualizarEstadoAdmin(
        Request $request,
        $id
    ) {
        $datos = $request->validate([
            'estado' => [
                'required',
                'string',
                'in:Recibido,En atención,Resuelto',
            ],
            'nota_administrador' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $incidencia = DB::transaction(
            function () use (
                $request,
                $id,
                $datos
            ) {
                $incidencia =
                    $this
                        ->consultaIncidenciasAdministrativas(
                            $request
                        )
                        ->lockForUpdate()
                        ->findOrFail($id);

                $transiciones = [
                    'Reportado' => [
                        'Recibido',
                        'En atención',
                        'Resuelto',
                    ],
                    'Recibido' => [
                        'En atención',
                        'Resuelto',
                    ],
                    'En atención' => [
                        'Resuelto',
                    ],
                    'Resuelto' => [],
                ];

                $permitidos =
                    $transiciones[
                        $incidencia->estado
                    ] ?? [];

                if (
                    ! in_array(
                        $datos['estado'],
                        $permitidos,
                        true
                    )
                ) {
                    abort(
                        409,
                        'No se puede cambiar la incidencia de '
                        . $incidencia->estado
                        . ' a '
                        . $datos['estado']
                        . '.'
                    );
                }

                $ahora = Carbon::now(
                    'America/La_Paz'
                )->format(
                    'Y-m-d H:i:s'
                );

                $incidencia->estado =
                    $datos['estado'];

                $incidencia
                    ->nota_administrador =
                    $this->limpiarTexto(
                        $datos[
                            'nota_administrador'
                        ] ?? null
                    );

                $incidencia
                    ->atendido_por_usuario_id =
                    $request->user()?->id;

                $incidencia
                    ->atendido_por_nombre =
                    $this->nombreUsuario(
                        $request->user()
                    );

                if (
                    $datos['estado']
                    === 'Recibido'
                ) {
                    $incidencia->recibido_en =
                        $incidencia->recibido_en
                        ?? $ahora;
                }

                if (
                    $datos['estado']
                    === 'En atención'
                ) {
                    $incidencia->recibido_en =
                        $incidencia->recibido_en
                        ?? $ahora;

                    $incidencia->atencion_en =
                        $incidencia->atencion_en
                        ?? $ahora;
                }

                if (
                    $datos['estado']
                    === 'Resuelto'
                ) {
                    $incidencia->recibido_en =
                        $incidencia->recibido_en
                        ?? $ahora;

                    $incidencia->atencion_en =
                        $incidencia->atencion_en
                        ?? $ahora;

                    $incidencia->resuelto_en =
                        $ahora;
                }

                $incidencia->save();

                return $incidencia->load([
                    'solicitud.pasajero.persona',
                    'solicitud.mototaxista.persona',
                    'solicitud.mototaxista.sindicato',
                ]);
            }
        );

        broadcast(
            new IncidenciaViajeActualizada(
                $incidencia
            )
        );

        return response()->json([
            'mensaje' =>
                'Estado de la incidencia actualizado.',
            'incidencia' =>
                $incidencia,
        ], 200);
    }

    private function consultaIncidenciasAdministrativas(
        Request $request
    ) {
        $rol = strtolower(
            trim(
                (string) (
                    $request->user()
                        ?->role
                    ?? ''
                )
            )
        );

        $consulta =
            IncidenciaViaje::query();

        if ($rol === 'admin_general') {
            return $consulta;
        }

        if ($rol === 'secretario') {
            $sindicatoId = (int) (
                $request->user()
                    ?->sindicato_id
                ?? 0
            );

            if ($sindicatoId <= 0) {
                abort(
                    403,
                    'La cuenta de secretario no está vinculada a un sindicato.'
                );
            }

            return $consulta->whereHas(
                'solicitud.mototaxista',
                function ($query) use (
                    $sindicatoId
                ) {
                    $query->where(
                        'id_sindicato',
                        $sindicatoId
                    );
                }
            );
        }

        abort(
            403,
            'No tienes autorización para administrar incidencias.'
        );
    }

    private function autorizarParticipante(
        Request $request,
        Solicitud $solicitud
    ): array {
        $usuario = $request->user();

        if (!$usuario) {
            abort(401, 'Debes iniciar sesión.');
        }

        $rol = strtolower(
            trim((string) ($usuario->role ?? ''))
        );

        if ($rol === 'pasajero') {
            if (
                (int) ($usuario->pasajero_id ?? 0)
                !== (int) $solicitud->id_pasajero
            ) {
                abort(
                    403,
                    'No puedes reportar incidencias en un viaje que no te pertenece.'
                );
            }

            return [
                'rol' => 'pasajero',
                'nombre' => (
                    data_get(
                        $solicitud,
                        'pasajero.persona.nombre'
                    )
                    ?: $this->nombreUsuario($usuario)
                ),
            ];
        }

        if ($rol === 'conductor') {
            $mototaxistaId = (int) (
                $usuario->mototaxista_id ?? 0
            );

            if (
                $mototaxistaId <= 0
                || $mototaxistaId
                    !== (int) ($solicitud->mototaxista_id ?? 0)
            ) {
                abort(
                    403,
                    'No puedes reportar incidencias en un viaje asignado a otro conductor.'
                );
            }

            return [
                'rol' => 'conductor',
                'nombre' => (
                    data_get(
                        $solicitud,
                        'mototaxista.persona.nombre'
                    )
                    ?: $this->nombreUsuario($usuario)
                ),
            ];
        }

        abort(
            403,
            'Solo el pasajero o conductor del viaje puede reportar una incidencia.'
        );
    }

    private function resolverPrioridad(string $tipo): string
    {
        if (in_array($tipo, [
            'Accidente',
            'Emergencia médica',
            'Situación de inseguridad',
        ], true)) {
            return 'Crítica';
        }

        if (in_array($tipo, [
            'Pasajero no localizado',
            'Conductor no localizado',
        ], true)) {
            return 'Alta';
        }

        return 'Media';
    }

    private function generarCodigo(): string
    {
        do {
            $codigo = 'SOS-'
                . Carbon::now('America/La_Paz')
                    ->format('Ymd')
                . '-'
                . Str::upper(Str::random(6));
        } while (
            IncidenciaViaje::query()
                ->where('codigo', $codigo)
                ->exists()
        );

        return $codigo;
    }

    private function limpiarTexto($valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto !== '' ? $texto : null;
    }

    private function nombreUsuario($usuario): string
    {
        return trim((string) (
            data_get($usuario, 'persona.nombre')
            ?: data_get($usuario, 'name')
            ?: data_get($usuario, 'nombre')
            ?: data_get($usuario, 'email')
            ?: 'Usuario MOTRIX'
        ));
    }
}
