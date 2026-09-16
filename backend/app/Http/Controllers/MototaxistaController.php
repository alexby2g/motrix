<?php

namespace App\Http\Controllers;

use App\Events\SolicitudActualizada;
use App\Http\Requests\MototaxistaRequest;
use App\Models\Mototaxista;
use App\Models\Solicitud;
use App\Models\User;
use App\Services\AsignacionConductorService;
use App\Services\FcmPushService;
use App\Services\MotrixPhoneRegistry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MototaxistaController extends Controller
{
    public function __construct(
        private readonly AsignacionConductorService $asignacionService,
        private readonly FcmPushService $pushService,
        private readonly MotrixPhoneRegistry $phoneRegistry
    ) {
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIONES ADMINISTRATIVAS
    |--------------------------------------------------------------------------
    */

    public function index(
        Request $request
    ) {
        $datos = $request->validate([
            'paginated' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
            'estado' => ['nullable', 'string', 'max:50'],
            'sindicato' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $consulta = Mototaxista::query();

        if (
            $this->rolUsuario($request)
            === 'secretario'
        ) {
            $consulta->where(
                'id_sindicato',
                $this->sindicatoUsuario($request)
            );
        }

        $baseVisible = clone $consulta;

        $texto = trim(
            (string) ($datos['q'] ?? '')
        );

        if ($texto !== '') {
            $terminos = array_values(
                array_filter(
                    preg_split('/\\s+/u', $texto) ?: []
                )
            );

            foreach (
                array_slice($terminos, 0, 5)
                as $termino
            ) {
                $consulta->where(
                    function ($query) use (
                        $termino
                    ) {
                        $patron =
                            '%' . $termino . '%';

                        $query
                            ->where(
                                'nro_chaleco',
                                'like',
                                $patron
                            )
                            ->orWhere(
                                'telefono',
                                'like',
                                $patron
                            )
                            ->orWhereHas(
                                'persona',
                                function (
                                    $persona
                                ) use (
                                    $patron
                                ) {
                                    $persona
                                        ->where(
                                            'nombre',
                                            'like',
                                            $patron
                                        )
                                        ->orWhere(
                                            'apellidos',
                                            'like',
                                            $patron
                                        )
                                        ->orWhere(
                                            'ci',
                                            'like',
                                            $patron
                                        );
                                }
                            )
                            ->orWhereHas(
                                'sindicato',
                                function (
                                    $sindicato
                                ) use (
                                    $patron
                                ) {
                                    $sindicato
                                        ->where(
                                            'nombre',
                                            'like',
                                            $patron
                                        );
                                }
                            );
                    }
                );
            }
        }

        $estado = trim(
            (string) ($datos['estado'] ?? '')
        );

        if (
            $estado !== ''
            && $estado !== 'Todos'
        ) {
            $consulta->where('estado', $estado);
        }

        $sindicato = trim(
            (string) ($datos['sindicato'] ?? '')
        );

        if (
            $sindicato !== ''
            && $sindicato !== 'Todos'
        ) {
            $consulta->whereHas(
                'sindicato',
                function ($query) use (
                    $sindicato
                ) {
                    $query->where(
                        'nombre',
                        $sindicato
                    );
                }
            );
        }

        $consulta
            ->with([
                'persona.imagenes',
                'sindicato.federacionRelacion',
                'motocicletas.imagenes',
                'usuarioConductor',
            ])
            ->orderByDesc('id');

        if (! $request->boolean('paginated')) {
            $items = $consulta->get();
            $this->adjuntarMetadatosOperativos($items);
            return $items;
        }

        $estadisticas = [
            'total' => (clone $baseVisible)->count(),
            'activos' => (clone $baseVisible)
                ->where('estado', 'Activo')
                ->count(),
            'con_qr' => (clone $baseVisible)
                ->whereNotNull('codigo_qr')
                ->where('codigo_qr', '<>', '')
                ->count(),
            'con_cuenta' => (clone $baseVisible)
                ->whereHas('usuarioConductor')
                ->count(),
        ];

        $porPagina = (int) (
            $datos['per_page'] ?? 12
        );

        $paginador = $consulta->paginate(
            $porPagina
        );

        $this->adjuntarMetadatosOperativos(
            collect($paginador->items())
        );

        return response()->json([
            'data' => $paginador->items(),
            'meta' => [
                'current_page' =>
                    $paginador->currentPage(),
                'last_page' =>
                    $paginador->lastPage(),
                'per_page' =>
                    $paginador->perPage(),
                'total' =>
                    $paginador->total(),
                'from' =>
                    $paginador->firstItem(),
                'to' =>
                    $paginador->lastItem(),
                'stats' =>
                    $estadisticas,
            ],
        ]);
    }

    /**
     * Opciones ligeras para asignar una motocicleta.
     * El secretario queda automáticamente limitado a su sindicato.
     */
    public function opcionesMotocicleta(
        Request $request
    ) {
        return $this->opcionesLigeras(
            $request,
            [
                'admin_general',
                'admin_registro',
                'secretario',
            ],
            'No tienes autorización para seleccionar mototaxistas para motocicletas.',
            true,
            50
        );
    }

    /**
     * Opciones ligeras para pagos sindicales.
     * Evita descargar el padrón completo al abrir el formulario.
     */
    public function opcionesPagoSindical(
        Request $request
    ) {
        return $this->opcionesLigeras(
            $request,
            [
                'admin_general',
                'admin_registro',
                'secretario',
            ],
            'No tienes autorización para seleccionar mototaxistas para pagos sindicales.'
        );
    }

    /**
     * Opciones ligeras de conductor para el formulario administrativo
     * de servicios. Evita cargar todo el padrón de mototaxistas.
     */
    public function opcionesServicio(
        Request $request
    ) {
        if (! in_array(
            $this->rolUsuario($request),
            [
                'admin_general',
                'admin_servicios',
            ],
            true
        )) {
            abort(
                403,
                'No tienes autorización para seleccionar conductores de servicios.'
            );
        }

        $datos = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
            'include_id' => [
                'nullable',
                'integer',
                'exists:mototaxistas,id',
            ],
        ]);

        $texto = trim(
            (string) ($datos['q'] ?? '')
        );

        $includeId = isset($datos['include_id'])
            ? (int) $datos['include_id']
            : null;

        if (
            mb_strlen($texto) < 2
            && ! $includeId
        ) {
            return response()->json([], 200);
        }

        $consulta = Mototaxista::query()
            ->with([
                'persona:id,nombre,apellidos,ci',
                'sindicato:id,nombre',
            ])
            ->where(
                function ($query) use (
                    $includeId
                ) {
                    $query->where(
                        'estado',
                        'Activo'
                    );

                    if ($includeId) {
                        $query->orWhere(
                            'id',
                            $includeId
                        );
                    }
                }
            );

        if ($texto !== '') {
            $terminos = array_values(
                array_filter(
                    preg_split('/\\s+/u', $texto) ?: []
                )
            );

            foreach (
                array_slice($terminos, 0, 5)
                as $termino
            ) {
                $consulta->where(
                    function ($query) use (
                        $termino
                    ) {
                        $patron =
                            '%' . $termino . '%';

                        $query
                            ->where(
                                'nro_chaleco',
                                'like',
                                $patron
                            )
                            ->orWhere(
                                'telefono',
                                'like',
                                $patron
                            )
                            ->orWhereHas(
                                'persona',
                                function (
                                    $persona
                                ) use (
                                    $patron
                                ) {
                                    $persona
                                        ->where(
                                            'nombre',
                                            'like',
                                            $patron
                                        )
                                        ->orWhere(
                                            'apellidos',
                                            'like',
                                            $patron
                                        )
                                        ->orWhere(
                                            'ci',
                                            'like',
                                            $patron
                                        );
                                }
                            );
                    }
                );
            }
        }

        return response()->json(
            $consulta
                ->select([
                    'id',
                    'nro_chaleco',
                    'estado',
                    'id_persona',
                    'id_sindicato',
                ])
                ->orderByDesc('id')
                ->limit(10)
                ->get(),
            200
        );
    }

    private function opcionesLigeras(
        Request $request,
        array $rolesPermitidos,
        string $mensajeNoAutorizado,
        bool $permitirSinBusqueda = false,
        int $limite = 8
    ) {
        $rol = $this->rolUsuario($request);

        if (! in_array(
            $rol,
            $rolesPermitidos,
            true
        )) {
            abort(403, $mensajeNoAutorizado);
        }

        $datos = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
            'include_id' => [
                'nullable',
                'integer',
                'exists:mototaxistas,id',
            ],
        ]);

        $texto = trim(
            (string) ($datos['q'] ?? '')
        );

        $includeId = isset($datos['include_id'])
            ? (int) $datos['include_id']
            : null;

        if (
            ! $permitirSinBusqueda
            && mb_strlen($texto) < 2
            && ! $includeId
        ) {
            return response()->json([], 200);
        }

        $consulta = Mototaxista::query()
            ->with([
                'persona:id,nombre,apellidos,ci,telefono',
                'sindicato:id,nombre',
            ]);

        if ($rol === 'secretario') {
            $consulta->where(
                'id_sindicato',
                $this->sindicatoUsuario($request)
            );
        }

        $consulta->where(
            function ($query) use (
                $includeId
            ) {
                $query->where(
                    'estado',
                    'Activo'
                );

                if ($includeId) {
                    $query->orWhere(
                        'id',
                        $includeId
                    );
                }
            }
        );

        if ($texto !== '') {
            $terminos = array_values(
                array_filter(
                    preg_split('/\\s+/u', $texto) ?: []
                )
            );

            foreach (
                array_slice($terminos, 0, 5)
                as $termino
            ) {
                $consulta->where(
                    function ($query) use (
                        $termino
                    ) {
                        $patron = '%' . $termino . '%';

                        $query
                            ->where(
                                'nro_chaleco',
                                'like',
                                $patron
                            )
                            ->orWhere(
                                'telefono',
                                'like',
                                $patron
                            )
                            ->orWhereHas(
                                'persona',
                                function ($persona) use (
                                    $patron
                                ) {
                                    $persona
                                        ->where(
                                            'nombre',
                                            'like',
                                            $patron
                                        )
                                        ->orWhere(
                                            'apellidos',
                                            'like',
                                            $patron
                                        )
                                        ->orWhere(
                                            'ci',
                                            'like',
                                            $patron
                                        );
                                }
                            );
                    }
                );
            }
        }

        return response()->json(
            $consulta
                ->select([
                    'id',
                    'nro_chaleco',
                    'telefono',
                    'estado',
                    'id_persona',
                    'id_sindicato',
                ])
                ->orderBy('nro_chaleco')
                ->orderBy('id')
                ->limit($limite)
                ->get(),
            200
        );
    }

    public function store(
        MototaxistaRequest $request
    ) {
        $datos = $request->validated();

        if (
            $this->rolUsuario($request)
            === 'secretario'
        ) {
            $datos['id_sindicato'] =
                $this->sindicatoUsuario($request);
        }

        /*
         * Los nuevos registros empiezan fuera de línea.
         * El estado de afiliación no debe confundirse con la
         * disponibilidad para recibir viajes.
         */
        $datos['disponible'] = 0;
        $datos['documentacion_en_regla'] = false;
        $datos['aportes_al_dia'] = false;
        $datos['estado_sindical'] = 'No habilitado';
        $datos['motivo_estado_sindical'] = 'Pendiente de revisión sindical';
        $datos['estado_sindical_actualizado_en'] = Carbon::now('UTC')
            ->format('Y-m-d H:i:s');

        $mototaxista = Mototaxista::create(
            $datos
        );

        if ($mototaxista->id_sindicato) {
            $mototaxista->persona()
                ->update([
                    'sindicato_registro_id' =>
                        $mototaxista->id_sindicato,
                ]);
        }

        return response()->json([
            'mensaje' =>
                'Mototaxista registrado correctamente.',
            'data' => $this->cargarDetalle(
                $mototaxista
            ),
        ], 201);
    }

    /**
     * El administrador puede consultar cualquier mototaxista.
     * Un conductor solamente puede consultar su propio perfil.
     */
    public function show(
        Request $request,
        $id = null
    ) {
        $mototaxista =
            $this->resolverMototaxista(
                $request,
                $id
            );

        return response()->json(
            $this->cargarDetalle(
                $mototaxista
            ),
            200
        );
    }

    public function update(
        MototaxistaRequest $request,
        $id
    ) {
        $mototaxista =
            $this->resolverMototaxistaAdministrativo(
                $request,
                (int) $id
            );

        $datos = $request->validated();

        if (
            $this->rolUsuario($request)
            === 'secretario'
        ) {
            $datos['id_sindicato'] =
                $this->sindicatoUsuario($request);
        }

        /*
         * Si el administrador inactiva al conductor, MOTRIX
         * también lo saca de línea inmediatamente.
         */
        if (
            ($datos['estado'] ?? null)
            === 'Inactivo'
        ) {
            $datos['disponible'] = 0;
        }

        $mototaxista->update($datos);

        if ($mototaxista->id_sindicato) {
            $mototaxista->persona()
                ->update([
                    'sindicato_registro_id' =>
                        $mototaxista->id_sindicato,
                ]);

            $mototaxista->load('sindicato');

            User::query()
                ->where(
                    'mototaxista_id',
                    $mototaxista->id
                )
                ->where(
                    'role',
                    'conductor'
                )
                ->update([
                    'sindicato_id' =>
                        $mototaxista->id_sindicato,
                    'federacion_id' =>
                        $mototaxista
                            ->sindicato
                            ?->id_federacion,
                ]);
        }

        return response()->json([
            'mensaje' =>
                'Mototaxista actualizado correctamente.',
            'data' => $this->cargarDetalle(
                $mototaxista
            ),
        ], 200);
    }

    public function cambiarEstado(
        Request $request,
        $id
    ) {
        $mototaxista =
            $this->resolverMototaxistaAdministrativo(
                $request,
                (int) $id
            );

        $nuevoEstado =
            $mototaxista->estado === 'Activo'
                ? 'Inactivo'
                : 'Activo';

        if ($nuevoEstado === 'Activo') {
            $otraAfiliacion =
                Mototaxista::query()
                    ->where(
                        'id_persona',
                        $mototaxista->id_persona
                    )
                    ->where(
                        'estado',
                        'Activo'
                    )
                    ->where(
                        'id',
                        '<>',
                        $mototaxista->id
                    )
                    ->exists();

            if ($otraAfiliacion) {
                return response()->json([
                    'mensaje' =>
                        'Esta persona ya tiene otra afiliación Activa. '
                        . 'Debes inactivar la anterior.',
                ], 409);
            }
        }

        $mototaxista->estado =
            $nuevoEstado;

        if ($nuevoEstado === 'Inactivo') {
            $mototaxista->disponible = 0;
        }

        $mototaxista->save();

        return response()->json([
            'mensaje' =>
                "Mototaxista marcado como {$nuevoEstado}.",
            'data' => $this->cargarDetalle(
                $mototaxista
            ),
        ], 200);
    }

    public function generarQr(
        Request $request,
        $id
    ) {
        $mototaxista =
            $this->resolverMototaxistaAdministrativo(
                $request,
                (int) $id
            );

        if (
            trim(
                (string) $mototaxista->codigo_qr
            ) === ''
        ) {
            $mototaxista->codigo_qr =
                (string) Str::uuid();

            $mototaxista->save();
        }

        return response()->json([
            'mensaje' =>
                'Código de verificación disponible.',
            'codigo_qr' =>
                $mototaxista->codigo_qr,
            'ruta_publica' =>
                '/verificar/'
                . $mototaxista->codigo_qr,
            'data' => $this->cargarDetalle(
                $mototaxista
            ),
        ], 200);
    }

    public function crearCuentaConductor(
        Request $request,
        $id
    ) {
        $mototaxista =
            $this->resolverMototaxistaAdministrativo(
                $request,
                (int) $id
            )->load([
                'persona',
                'sindicato',
            ]);

        if ($mototaxista->estado !== 'Activo') {
            return response()->json([
                'mensaje' =>
                    'El mototaxista debe estar Activo para crear su cuenta de conductor.',
            ], 409);
        }

        if (
            trim(
                (string) $mototaxista->codigo_qr
            ) === ''
        ) {
            return response()->json([
                'mensaje' =>
                    'Primero genera el código QR del mototaxista.',
            ], 409);
        }

        $cuentaExistente = User::query()
            ->where(
                'mototaxista_id',
                $mototaxista->id
            )
            ->first();

        if ($cuentaExistente) {
            return response()->json([
                'mensaje' =>
                    'Este mototaxista ya tiene una cuenta vinculada.',
                'user' => $cuentaExistente,
            ], 409);
        }

        $persona = $mototaxista->persona;
        $telefonoCuenta = $this->phoneRegistry->firstValidPhone(
            $request->input('nickname'),
            $mototaxista->telefono,
            $persona?->telefono
        );

        if ($telefonoCuenta !== '') {
            $request->merge([
                'nickname' => $telefonoCuenta,
            ]);
        }

        $datos = $request->validate([
            'email' => [
                'required',
                'email',
                'max:150',
                'unique:users,email',
            ],
            'nickname' => [
                'nullable',
                'string',
                'max:50',
                'unique:users,nickname',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
            ],
        ]);

        if ($telefonoCuenta !== '') {
            $this->phoneRegistry->assertAvailableForAccount(
                $telefonoCuenta,
                $mototaxista->id_persona
                    ? (int) $mototaxista->id_persona
                    : null,
                (int) $mototaxista->id,
                $request->has('telefono')
                    ? 'telefono'
                    : 'nickname'
            );
        }

        $nombreCompleto = trim(
            (
                $persona?->nombre
                ?? 'Mototaxista'
            )
            . ' '
            . (
                $persona?->apellidos
                ?? ''
            )
        );

        $user = User::create([
            'name' =>
                $nombreCompleto
                ?: 'Mototaxista MOTRIX',
            'nickname' =>
                $datos['nickname'] ?? null,
            'email' =>
                $datos['email'],
            'password' =>
                Hash::make(
                    $datos['password']
                ),
            'role' => 'conductor',
            'mototaxista_id' =>
                $mototaxista->id,
            'pasajero_id' => null,
            'persona_id' =>
                $mototaxista->id_persona,
            'federacion_id' =>
                $mototaxista
                    ->sindicato
                    ?->id_federacion,
            'sindicato_id' =>
                $mototaxista->id_sindicato,
        ]);

        return response()->json([
            'mensaje' =>
                'Cuenta de conductor creada correctamente.',
            'user' => $user,
            'data' => $this->cargarDetalle(
                $mototaxista
            ),
        ], 201);
    }

    public function destroy(
        Request $request,
        $id
    ) {
        $mototaxista =
            $this->resolverMototaxistaAdministrativo(
                $request,
                (int) $id
            );

        $bloqueos = [];

        if (
            DB::table('motocicletas')
                ->where(
                    'id_mototaxista',
                    $id
                )
                ->exists()
        ) {
            $bloqueos[] = 'motocicletas';
        }

        if (
            DB::table('solicitudes')
                ->where(
                    'mototaxista_id',
                    $id
                )
                ->exists()
        ) {
            $bloqueos[] = 'solicitudes de viaje';
        }

        if (
            DB::table('servicios')
                ->where(
                    'id_mototaxista',
                    $id
                )
                ->exists()
        ) {
            $bloqueos[] = 'servicios';
        }

        if (
            DB::table('users')
                ->where(
                    'mototaxista_id',
                    $id
                )
                ->exists()
        ) {
            $bloqueos[] = 'cuenta de conductor';
        }

        if (
            DB::table('pagos_sindicales')
                ->where(
                    'id_mototaxista',
                    $id
                )
                ->exists()
        ) {
            $bloqueos[] = 'pagos sindicales';
        }

        if ($bloqueos) {
            return response()->json([
                'mensaje' =>
                    'No se puede eliminar porque el mototaxista tiene registros asociados: '
                    . implode(', ', $bloqueos)
                    . '. Puedes marcarlo Inactivo para conservar su historial.',
            ], 409);
        }

        $mototaxista->delete();

        return response()->json([
            'mensaje' =>
                'Mototaxista eliminado correctamente.',
        ], 200);
    }


    /*
    |--------------------------------------------------------------------------
    | VERIFICACIÓN PÚBLICA MEDIANTE QR
    |--------------------------------------------------------------------------
    |
    | Esta respuesta NO expone ubicación GPS, teléfono, correo, dirección
    | particular ni el CI completo.
    |
    */

    public function verificarPublico(
        string $codigo
    ) {
        $codigo = trim($codigo);

        if (
            $codigo === ''
            || strlen($codigo) > 120
        ) {
            return response()->json([
                'verificado' => false,
                'mensaje' =>
                    'El código de verificación no es válido.',
            ], 404);
        }

        $registro = DB::table(
            'mototaxistas as m'
        )
            ->leftJoin(
                'personas as p',
                'p.id',
                '=',
                'm.id_persona'
            )
            ->leftJoin(
                'sindicatos as s',
                's.id',
                '=',
                'm.id_sindicato'
            )
            ->leftJoin(
                'federaciones as f',
                'f.id',
                '=',
                's.id_federacion'
            )
            ->where(
                'm.codigo_qr',
                $codigo
            )
            ->select([
                'm.id as mototaxista_id',
                'm.nro_chaleco',
                'm.codigo_qr',
                'm.estado',
                'm.documentacion_en_regla',
                'm.aportes_al_dia',
                'm.estado_sindical',
                'm.motivo_estado_sindical',
                'm.id_persona',
                'm.id_sindicato',

                'p.nombre',
                'p.apellidos',
                'p.ci',

                's.nombre as sindicato',
                's.id_federacion',

                'f.nombre as federacion',
            ])
            ->first();

        if (! $registro) {
            return response()->json([
                'verificado' => false,
                'mensaje' =>
                    'No existe un mototaxista registrado con este código QR.',
            ], 404);
        }

        $fotoPersona = DB::table(
            'imagenes_personas'
        )
            ->where(
                'id_persona',
                $registro->id_persona
            )
            ->orderByDesc('id')
            ->value('ruta');

        $motocicletas = DB::table(
            'motocicletas'
        )
            ->where(
                'id_mototaxista',
                $registro->mototaxista_id
            )
            ->orderByDesc('id')
            ->get()
            ->map(
                function ($motocicleta) {
                    $leer = static function (
                        object $fila,
                        string $campo
                    ) {
                        return property_exists(
                            $fila,
                            $campo
                        )
                            ? $fila->{$campo}
                            : null;
                    };

                    $soatRaw = $leer(
                        $motocicleta,
                        'tiene_soat'
                    );

                    $soat = null;

                    if ($soatRaw !== null) {
                        $valor = mb_strtolower(
                            trim(
                                (string) $soatRaw
                            )
                        );

                        if (in_array(
                            $valor,
                            [
                                '1',
                                'si',
                                'sí',
                                'true',
                                'vigente',
                            ],
                            true
                        )) {
                            $soat = true;
                        } elseif (in_array(
                            $valor,
                            [
                                '0',
                                'no',
                                'false',
                                'vencido',
                            ],
                            true
                        )) {
                            $soat = false;
                        }
                    }

                    return [
                        'id' =>
                            $leer(
                                $motocicleta,
                                'id'
                            ),
                        'placa' =>
                            $leer(
                                $motocicleta,
                                'placa'
                            ),
                        'tiene_placa' => (bool) (
                            $leer(
                                $motocicleta,
                                'tiene_placa'
                            ) ?? (
                                $leer(
                                    $motocicleta,
                                    'placa'
                                )
                                    ? true
                                    : false
                            )
                        ),
                        'modelo' =>
                            $leer(
                                $motocicleta,
                                'modelo'
                            ),
                        'color' =>
                            $leer(
                                $motocicleta,
                                'color'
                            ),
                        'tiene_soat' => $soat,
                    ];
                }
            )
            ->values();

        $tieneCuentaConductor =
            DB::table('users')
                ->where(
                    'mototaxista_id',
                    $registro->mototaxista_id
                )
                ->where(
                    'role',
                    'conductor'
                )
                ->exists();

        $ciVisible =
            $this->enmascararDocumento(
                (string) (
                    $registro->ci
                    ?? ''
                )
            );

        $estadoActivo =
            $registro->estado === 'Activo';

        $documentacionEnRegla = (bool) $registro->documentacion_en_regla;
        $aportesAlDia = (bool) $registro->aportes_al_dia;
        $estadoSindical = (string) ($registro->estado_sindical ?? 'No habilitado');

        $habilitado = $estadoActivo
            && $tieneCuentaConductor
            && $documentacionEnRegla
            && $aportesAlDia
            && $estadoSindical === 'Habilitado';

        $motivos = [];
        if (! $estadoActivo) {
            $motivos[] = 'registro administrativo inactivo';
        }
        if (! $tieneCuentaConductor) {
            $motivos[] = 'sin cuenta MOTRIX de conductor';
        }
        if ($estadoSindical === 'Expulsado') {
            $motivos[] = $registro->motivo_estado_sindical ?: 'afiliación expulsada';
        } else {
            if (! $documentacionEnRegla) {
                $motivos[] = 'documentación incompleta';
            }
            if (! $aportesAlDia) {
                $motivos[] = 'aportes pendientes';
            }
            if ($estadoSindical !== 'Habilitado' && $documentacionEnRegla && $aportesAlDia) {
                $motivos[] = $registro->motivo_estado_sindical ?: 'afiliación sindical no habilitada';
            }
        }

        $statsCalificacion = DB::table('solicitudes')
            ->where('mototaxista_id', $registro->mototaxista_id)
            ->where('estado', 'Finalizado')
            ->whereNotNull('calificacion')
            ->selectRaw('COUNT(calificacion) as total, AVG(calificacion) as promedio')
            ->first();

        return response()->json([
            'verificado' => true,
            'habilitado' => $habilitado,
            'motivo_inhabilitacion' => $habilitado
                ? null
                : implode(', ', array_values(array_unique($motivos))),

            'mototaxista' => [
                'id' =>
                    (int) $registro
                        ->mototaxista_id,

                'nombre' => trim(
                    (
                        $registro->nombre
                        ?? ''
                    )
                    . ' '
                    . (
                        $registro->apellidos
                        ?? ''
                    )
                ),

                'ci' => $ciVisible,

                'nro_chaleco' =>
                    $registro->nro_chaleco,

                'estado' =>
                    $registro->estado,

                'documentacion_en_regla' =>
                    $documentacionEnRegla,

                'aportes_al_dia' =>
                    $aportesAlDia,

                'estado_sindical' =>
                    $estadoSindical,

                'motivo_estado_sindical' =>
                    $registro->motivo_estado_sindical,

                'promedio_calificacion' =>
                    round((float) ($statsCalificacion?->promedio ?? 0), 2),

                'total_calificaciones' =>
                    (int) ($statsCalificacion?->total ?? 0),

                'sindicato' =>
                    $registro->sindicato,

                'federacion' =>
                    $registro->federacion,

                'foto_ruta' =>
                    $fotoPersona,

                'cuenta_conductor' =>
                    $tieneCuentaConductor,
            ],

            'motocicletas' =>
                $motocicletas,

            'seguridad' => [
                'ubicacion_publica' => false,
                'telefono_publico' => false,
                'correo_publico' => false,
                'direccion_publica' => false,
                'ci_completo_publico' => false,
            ],
        ], 200);
    }

    private function enmascararDocumento(
        string $documento
    ): string {
        $documento = trim($documento);

        if ($documento === '') {
            return 'No registrado';
        }

        $longitud = mb_strlen(
            $documento
        );

        if ($longitud <= 2) {
            return str_repeat(
                '*',
                $longitud
            );
        }

        $visibles = mb_substr(
            $documento,
            -2
        );

        return str_repeat(
            '*',
            max(
                1,
                $longitud - 2
            )
        ) . $visibles;
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIONES SEGURAS DEL CONDUCTOR
    |--------------------------------------------------------------------------
    */

    public function perfilConductor(
        Request $request
    ) {
        $mototaxista =
            $this->resolverMototaxista(
                $request
            );

        return response()->json(
            $this->cargarDetalle(
                $mototaxista
            ),
            200
        );
    }

    public function actualizarDisponibilidad(
        Request $request,
        $id = null
    ) {
        $datos = $request->validate([
            'disponible' => [
                'required',
                'boolean',
            ],
        ]);

        $mototaxista =
            $this->resolverMototaxista(
                $request,
                $id
            );

        /*
         * Para recibir viajes se exige el registro administrativo Activo,
         * la habilitación sindical vigente y una cuenta de conductor.
         * La disponibilidad ya no puede reactivar por sí sola a un afiliado
         * con documentación o aportes pendientes.
         */
        if ((bool) $datos['disponible']) {
            $mototaxista->loadMissing('usuarioConductor');

            if (
                ! $mototaxista->habilitadoSindicalmente()
                || $mototaxista->usuarioConductor === null
            ) {
                $motivo = $mototaxista->usuarioConductor === null
                    ? 'No existe una cuenta MOTRIX de conductor vinculada.'
                    : ($mototaxista->motivoInhabilitacionSindical()
                        ?: 'La afiliación sindical no está habilitada.');

                return response()->json([
                    'mensaje' => 'No puedes ponerte en línea: ' . $motivo,
                    'motivo_inhabilitacion' => $motivo,
                ], 409);
            }
        }

        $tieneViajeActivo =
            Solicitud::query()
                ->where(
                    'mototaxista_id',
                    $mototaxista->id
                )
                ->whereIn(
                    'estado',
                    [
                        'Aceptado',
                        'Llegó',
                        'En Curso',
                    ]
                )
                ->exists();

        if (
            (bool) $datos['disponible']
            && $tieneViajeActivo
        ) {
            return response()->json([
                'mensaje' =>
                    'El conductor tiene un viaje activo.',
            ], 409);
        }

        $mototaxista->disponible =
            (bool) $datos['disponible'];

        $mototaxista->ultima_conexion =
            Carbon::now('UTC')
                ->format('Y-m-d H:i:s');

        $mototaxista->save();

        $asignada = null;

        if ($mototaxista->disponible) {
            $asignada =
                $this->asignacionService
                    ->asignarSolicitudMasCercanaAlConductor(
                        $mototaxista
                    );

            $this->notificarAsignacionAutomatica($asignada);
        }

        return response()->json([
            'mensaje' =>
                $mototaxista->disponible
                    ? 'Conductor en línea.'
                    : 'Conductor fuera de línea.',
            'mototaxista' =>
                $this->cargarDetalle(
                    $mototaxista
                ),
            'solicitud_asignada' =>
                $asignada,
        ], 200);
    }

    public function actualizarUbicacion(
        Request $request,
        $id = null
    ) {
        $datos = $request->validate([
            'latitud' => [
                'required',
                'numeric',
                'between:-90,90',
            ],
            'longitud' => [
                'required',
                'numeric',
                'between:-180,180',
            ],
        ]);

        $mototaxista =
            $this->resolverMototaxista(
                $request,
                $id
            );

        $mototaxista->latitud =
            (float) $datos['latitud'];

        $mototaxista->longitud =
            (float) $datos['longitud'];

        $mototaxista->ultima_conexion =
            Carbon::now('UTC')
                ->format('Y-m-d H:i:s');

        $mototaxista->save();

        $asignada = null;

        if ($mototaxista->disponible) {
            $asignada =
                $this->asignacionService
                    ->asignarSolicitudMasCercanaAlConductor(
                        $mototaxista
                    );

            $this->notificarAsignacionAutomatica($asignada);
        }

        return response()->json([
            'mensaje' =>
                'Ubicación actualizada correctamente.',
            'ubicacion' => [
                'latitud' =>
                    (float) $mototaxista->latitud,
                'longitud' =>
                    (float) $mototaxista->longitud,
                'ultima_conexion' =>
                    $mototaxista->ultima_conexion,
            ],
            'solicitud_asignada' =>
                $asignada,
        ], 200);
    }

    public function solicitudesDisponibles(
        Request $request,
        $id = null
    ) {
        $mototaxista =
            $this->resolverMototaxista(
                $request,
                $id
            );

        $mototaxista->loadMissing('usuarioConductor');

        if (
            ! $mototaxista->habilitadoSindicalmente()
            || $mototaxista->usuarioConductor === null
            || !(bool) $mototaxista->disponible
        ) {
            if ((bool) $mototaxista->disponible) {
                $mototaxista->disponible = false;
                $mototaxista->save();
            }
            return response()->json(
                [],
                200
            );
        }

        $tieneViajeActivo =
            Solicitud::query()
                ->where(
                    'mototaxista_id',
                    $mototaxista->id
                )
                ->whereIn(
                    'estado',
                    [
                        'Aceptado',
                        'Llegó',
                        'En Curso',
                    ]
                )
                ->exists();

        if ($tieneViajeActivo) {
            return response()->json(
                [],
                200
            );
        }

        $ahoraUtc =
            Carbon::now('UTC')
                ->format('Y-m-d H:i:s');

        /*
         * Este mismo endpoint controla el turno individual de 30 segundos.
         * Así la reasignación no depende de que el pasajero mantenga abierta
         * su pantalla o haga polling en ese momento.
         */
        $solicitudAsignada =
            Solicitud::query()
                ->where(
                    'mototaxista_id',
                    $mototaxista->id
                )
                ->whereIn(
                    'estado',
                    [
                        'Pendiente',
                        'Buscando conductor',
                    ]
                )
                ->where(
                    function (
                        $query
                    ) use ($ahoraUtc) {
                        $query
                            ->whereNull(
                                'expira_en'
                            )
                            ->orWhere(
                                'expira_en',
                                '>',
                                $ahoraUtc
                            );
                    }
                )
                ->orderByDesc('id')
                ->first();

        if ($solicitudAsignada) {
            $resultadoAsignacion = $this->asignacionService
                ->revisarAsignacionPendiente(
                    (int) $solicitudAsignada->id
                );

            if (
                ($resultadoAsignacion['cambio'] ?? false)
                && ($resultadoAsignacion['conductor'] ?? null) !== null
            ) {
                $reasignada = Solicitud::query()
                    ->find($solicitudAsignada->id);

                $this->notificarAsignacionAutomatica(
                    $reasignada,
                    'conductor_reasignado'
                );
            }
        }

        $existeAsignada =
            Solicitud::query()
                ->where(
                    'mototaxista_id',
                    $mototaxista->id
                )
                ->whereIn(
                    'estado',
                    [
                        'Pendiente',
                        'Buscando conductor',
                    ]
                )
                ->where(
                    function (
                        $query
                    ) use ($ahoraUtc) {
                        $query
                            ->whereNull(
                                'expira_en'
                            )
                            ->orWhere(
                                'expira_en',
                                '>',
                                $ahoraUtc
                            );
                    }
                )
                ->exists();

        if (! $existeAsignada) {
            $asignada = $this->asignacionService
                ->asignarSolicitudMasCercanaAlConductor(
                    $mototaxista
                );

            $this->notificarAsignacionAutomatica($asignada);
        }

        $solicitudes =
            Solicitud::query()
                ->with([
                    'pasajero.persona',
                ])
                ->where(
                    'mototaxista_id',
                    $mototaxista->id
                )
                ->whereIn(
                    'estado',
                    [
                        'Pendiente',
                        'Buscando conductor',
                    ]
                )
                ->where(
                    function (
                        $query
                    ) use ($ahoraUtc) {
                        $query
                            ->whereNull(
                                'expira_en'
                            )
                            ->orWhere(
                                'expira_en',
                                '>',
                                $ahoraUtc
                            );
                    }
                )
                ->orderByDesc('id')
                ->get();

        $solicitudes->each(
            function (
                Solicitud $solicitud
            ) use ($mototaxista) {
                $distancia = null;

                if (
                    $mototaxista->latitud
                        !== null
                    && $mototaxista->longitud
                        !== null
                    && $solicitud->latitud_origen
                        !== null
                    && $solicitud->longitud_origen
                        !== null
                ) {
                    $distancia =
                        $this->calcularDistanciaKm(
                            (float) $mototaxista->latitud,
                            (float) $mototaxista->longitud,
                            (float) $solicitud->latitud_origen,
                            (float) $solicitud->longitud_origen
                        );
                }

                $solicitud->setAttribute(
                    'distancia_recogida_km',
                    $distancia
                );

                $tiempoRespuesta = $this->asignacionService
                    ->obtenerTiempoRespuestaAsignacion(
                        (int) $solicitud->id,
                        (int) $mototaxista->id
                    );

                $solicitud->setAttribute(
                    'respuesta_asignada_en',
                    $tiempoRespuesta['asignado_en'] ?? null
                );
                $solicitud->setAttribute(
                    'respuesta_expira_en',
                    $tiempoRespuesta['expira_en'] ?? null
                );
                $solicitud->setAttribute(
                    'segundos_respuesta_total',
                    $tiempoRespuesta['segundos_totales'] ?? 30
                );
                $solicitud->setAttribute(
                    'segundos_respuesta_restantes',
                    $tiempoRespuesta['segundos_restantes'] ?? 0
                );
            }
        );

        return response()->json(
            $solicitudes,
            200
        );
    }

    public function viajeActivo(
        Request $request,
        $id = null
    ) {
        $mototaxista =
            $this->resolverMototaxista(
                $request,
                $id
            );

        $viaje =
            Solicitud::query()
                ->with([
                    'pasajero.persona',
                    'mototaxista.persona',
                ])
                ->where(
                    'mototaxista_id',
                    $mototaxista->id
                )
                ->whereIn(
                    'estado',
                    [
                        'Aceptado',
                        'Llegó',
                        'En Curso',
                    ]
                )
                ->orderByDesc('id')
                ->first();

        return response()->json(
            $viaje,
            200
        );
    }


    private function notificarAsignacionAutomatica(
        ?Solicitud $solicitud,
        string $tipoEvento = 'conductor_asignado'
    ): void {
        if (! $solicitud) {
            return;
        }

        $solicitud->load([
            'pasajero.persona',
            'mototaxista.persona.imagenes',
            'mototaxista.sindicato',
        ]);

        if ($solicitud->mototaxista) {
            $this->adjuntarMetadatosOperativos(
                collect([$solicitud->mototaxista])
            );
        }

        broadcast(
            new SolicitudActualizada(
                $solicitud,
                $tipoEvento
            )
        )->toOthers();

        $userId = User::query()
            ->where('mototaxista_id', $solicitud->mototaxista_id)
            ->where('role', 'conductor')
            ->value('id');

        $this->pushService->sendToUser(
            $userId ? (int) $userId : null,
            'Nueva solicitud de viaje',
            'Tienes 30 segundos para aceptar la solicitud #' . $solicitud->id . '.',
            [
                'tipo' => $tipoEvento,
                'solicitud_id' => $solicitud->id,
                'route' => '/conductor',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | FUNCIONES INTERNAS
    |--------------------------------------------------------------------------
    */

    private function cargarDetalle(
        Mototaxista $mototaxista
    ): Mototaxista {
        $mototaxista->load([
            'persona.imagenes',
            'sindicato.federacionRelacion',
            'motocicletas.imagenes',
            'usuarioConductor',
        ]);

        $this->adjuntarMetadatosOperativos(collect([$mototaxista]));

        return $mototaxista;
    }

    private function adjuntarMetadatosOperativos($mototaxistas): void
    {
        $ids = collect($mototaxistas)
            ->pluck('id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $reputacion = Solicitud::query()
            ->selectRaw(
                'mototaxista_id, COUNT(calificacion) as total_calificaciones, AVG(calificacion) as promedio_calificacion'
            )
            ->whereIn('mototaxista_id', $ids)
            ->where('estado', 'Finalizado')
            ->whereNotNull('calificacion')
            ->groupBy('mototaxista_id')
            ->get()
            ->keyBy('mototaxista_id');

        foreach ($mototaxistas as $mototaxista) {
            $stats = $reputacion->get($mototaxista->id);

            $mototaxista->setAttribute(
                'promedio_calificacion',
                round((float) ($stats?->promedio_calificacion ?? 0), 2)
            );
            $mototaxista->setAttribute(
                'total_calificaciones',
                (int) ($stats?->total_calificaciones ?? 0)
            );
            $motivoInhabilitacion = $mototaxista->motivoInhabilitacionSindical();

            if ($mototaxista->usuarioConductor === null) {
                $motivoCuenta = 'sin cuenta MOTRIX de conductor';
                $motivoInhabilitacion = $motivoInhabilitacion
                    ? $motivoInhabilitacion . '; ' . $motivoCuenta
                    : $motivoCuenta;
            }

            $mototaxista->setAttribute(
                'motivo_inhabilitacion',
                $motivoInhabilitacion
            );
            $mototaxista->setAttribute(
                'habilitado_para_operar',
                $mototaxista->habilitadoSindicalmente()
                    && $mototaxista->usuarioConductor !== null
            );
        }
    }

    private function resolverMototaxista(
        Request $request,
        $id = null
    ): Mototaxista {
        $usuario = $request->user();

        if (! $usuario) {
            abort(
                401,
                'Debes iniciar sesión.'
            );
        }

        $rol = strtolower(
            trim(
                (string) $usuario->role
            )
        );

        if ($rol === 'conductor') {
            $mototaxistaId =
                (int) (
                    $usuario->mototaxista_id
                    ?? 0
                );

            if ($mototaxistaId <= 0) {
                abort(
                    403,
                    'La cuenta no está vinculada a un mototaxista.'
                );
            }

            if (
                $id !== null
                && (int) $id
                    !== $mototaxistaId
            ) {
                abort(
                    403,
                    'No puedes consultar ni modificar a otro conductor.'
                );
            }

            return Mototaxista::findOrFail(
                $mototaxistaId
            );
        }

        if (
            in_array($rol, [
                'admin_general',
                'admin_registro',
            ], true)
            && $id !== null
        ) {
            return Mototaxista::findOrFail(
                (int) $id
            );
        }

        if (
            $rol === 'secretario'
            && $id !== null
        ) {
            return Mototaxista::query()
                ->where(
                    'id',
                    (int) $id
                )
                ->where(
                    'id_sindicato',
                    $this->sindicatoUsuario(
                        $request
                    )
                )
                ->firstOrFail();
        }

        abort(
            403,
            'No tienes autorización para realizar esta acción.'
        );
    }

    private function resolverMototaxistaAdministrativo(
        Request $request,
        int $id
    ): Mototaxista {
        $rol = $this->rolUsuario(
            $request
        );

        if (in_array($rol, [
            'admin_general',
            'admin_registro',
        ], true)) {
            return Mototaxista::findOrFail(
                $id
            );
        }

        if ($rol === 'secretario') {
            return Mototaxista::query()
                ->where('id', $id)
                ->where(
                    'id_sindicato',
                    $this->sindicatoUsuario(
                        $request
                    )
                )
                ->firstOrFail();
        }

        abort(
            403,
            'No tienes autorización para administrar mototaxistas.'
        );
    }

    private function rolUsuario(
        Request $request
    ): string {
        return strtolower(
            trim(
                (string) (
                    $request->user()
                        ?->role
                    ?? ''
                )
            )
        );
    }

    private function sindicatoUsuario(
        Request $request
    ): int {
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

        return $sindicatoId;
    }

    private function calcularDistanciaKm(
        float $latitudOrigen,
        float $longitudOrigen,
        float $latitudDestino,
        float $longitudDestino
    ): float {
        $radioTierra = 6371;

        $dLat = deg2rad(
            $latitudDestino
            - $latitudOrigen
        );

        $dLng = deg2rad(
            $longitudDestino
            - $longitudOrigen
        );

        $a = sin($dLat / 2) ** 2
            + cos(
                deg2rad(
                    $latitudOrigen
                )
            )
            * cos(
                deg2rad(
                    $latitudDestino
                )
            )
            * sin($dLng / 2) ** 2;

        return round(
            $radioTierra
                * 2
                * atan2(
                    sqrt($a),
                    sqrt(1 - $a)
                ),
            2
        );
    }
}
