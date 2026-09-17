<?php

namespace App\Http\Controllers;

use App\Models\Pasajero;
use App\Models\Persona;
use App\Models\User;
use App\Services\MotrixPhoneRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PasajeroController extends Controller
{
    public function __construct(
        private readonly MotrixPhoneRegistry $phoneRegistry
    ) {
    }

    /**
     * Registro público de pasajeros.
     *
     * El pasajero puede crear su propia cuenta desde la aplicación.
     * No existe registro público para conductores: la cuenta de conductor
     * continúa siendo habilitada únicamente después de registrar y verificar
     * al mototaxista dentro del módulo gremial de MOTRIX.
     */
    public function registroPublico(
        Request $request
    ): JsonResponse {
        $request->merge([
            'nombre' => trim((string) $request->input('nombre', '')),
            'apellidos' => trim((string) $request->input('apellidos', '')),
            'ci' => trim((string) $request->input('ci', '')) !== '' ? trim((string) $request->input('ci', '')) : null,
            'telefono' => $this->phoneRegistry->normalize(
                $request->input('telefono')
            ),
            'direccion' => trim((string) $request->input('direccion', '')),
            'email' => mb_strtolower(
                trim((string) $request->input('email', ''))
            ),
        ]);

        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'apellidos' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'ci' => [
                'nullable',
                'string',
                'max:20',
                'unique:personas,ci',
            ],
            'telefono' => [
                'required',
                'string',
                'regex:/^[0-9]{7,15}$/',
                Rule::unique('users', 'nickname'),
            ],
            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                'unique:pasajeros,email',
                'unique:users,email',
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'confirmed',
            ],
            'device_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ], [
            'ci.unique' =>
                'Ya existe una persona registrada con este número de CI.',
            'telefono.regex' =>
                'Ingresa un número de celular válido.',
            'telefono.unique' =>
                'Este número de celular ya tiene una cuenta MOTRIX.',
            'email.unique' =>
                'Este correo electrónico ya se encuentra registrado.',
            'password.confirmed' =>
                'La confirmación de la contraseña no coincide.',
        ]);

        $this->phoneRegistry->assertAvailableForNewAccount(
            $datos['telefono']
        );

        $resultado = DB::transaction(
            function () use (
                $datos
            ) {
                $persona = Persona::create([
                    'nombre' => $datos['nombre'],
                    'apellidos' => $datos['apellidos'],
                    'telefono' => $datos['telefono'],
                    'ci' => $datos['ci'],
                    'direccion' =>
                        $datos['direccion'] !== ''
                            ? $datos['direccion']
                            : null,
                    'sindicato_registro_id' => null,
                ]);

                $pasajero = Pasajero::create([
                    'email' => $datos['email'],
                    'password' => null,
                    'id_persona' => $persona->id,
                ]);

                $nombreCompleto = trim(
                    $persona->nombre
                    . ' '
                    . $persona->apellidos
                );

                $user = User::create([
                    'name' => $nombreCompleto,
                    'nickname' => $datos['telefono'],
                    'email' => $datos['email'],
                    'password' => Hash::make(
                        $datos['password']
                    ),
                    'role' => 'pasajero',
                    'mototaxista_id' => null,
                    'pasajero_id' => $pasajero->id,
                    'persona_id' => $persona->id,
                    'federacion_id' => null,
                    'sindicato_id' => null,
                ]);

                $deviceName =
                    $datos['device_name']
                    ?? 'MOTRIX Pasajero';

                $token = $user
                    ->createToken($deviceName)
                    ->plainTextToken;

                return [
                    'persona' => $persona,
                    'pasajero' => $pasajero,
                    'user' => $user,
                    'token' => $token,
                ];
            }
        );

        $user = $resultado['user'];
        $persona = $resultado['persona'];
        $pasajero = $resultado['pasajero'];

        return response()->json([
            'message' =>
                'Cuenta de pasajero creada correctamente.',
            'token_type' => 'Bearer',
            'token' => $resultado['token'],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nickname' => $user->nickname,
                'persona_nombre' => trim(
                    $persona->nombre
                    . ' '
                    . $persona->apellidos
                ),
                'email' => $user->email,
                'role' => $user->role,
                'mototaxista_id' => null,
                'pasajero_id' => $pasajero->id,
                'persona_id' => $persona->id,
                'federacion_id' => null,
                'federacion_nombre' => null,
                'sindicato_id' => null,
                'sindicato_nombre' => null,
            ],
        ], 201);
    }

    /**
     * Lista administrativa de pasajeros.
     *
     * Incluye la cuenta de acceso cuando ya existe para que el
     * panel pueda mostrar "Cuenta pasajero" o "Sin cuenta".
     */
    public function index(
        Request $request
    ): JsonResponse {
        $datos = $request->validate([
            'paginated' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $consulta = Pasajero::query();

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
                                'email',
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
                                        )
                                        ->orWhere(
                                            'telefono',
                                            'like',
                                            $patron
                                        );
                                }
                            );
                    }
                );
            }
        }

        $consulta
            ->with([
                'persona',
                'usuarioPasajero',
            ])
            ->orderByDesc('id');

        if (! $request->boolean('paginated')) {
            return response()->json(
                $consulta->get()
            );
        }

        $porPagina = (int) (
            $datos['per_page'] ?? 12
        );

        $paginador = $consulta->paginate(
            $porPagina
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
            ],
        ]);
    }

    /**
     * Registra el perfil de pasajero.
     *
     * Registrar un pasajero NO crea automáticamente una contraseña.
     * El Administrador General crea la cuenta de acceso desde
     * /pasajeros/{id}/cuenta-pasajero.
     */
    public function store(
        Request $request
    ): JsonResponse {
        $validated = $request->validate([
            'id_persona' => [
                'required',
                'integer',
                'exists:personas,id',
                'unique:pasajeros,id_persona',
            ],
            'email' => [
                'required',
                'email',
                'max:100',
                'unique:pasajeros,email',
            ],
        ]);

        $pasajero = Pasajero::create([
            'id_persona' =>
                (int) $validated['id_persona'],
            'email' =>
                mb_strtolower(
                    trim(
                        (string) $validated['email']
                    )
                ),
        ]);

        return response()->json(
            $pasajero->load([
                'persona',
                'usuarioPasajero',
            ]),
            201
        );
    }

    /**
     * Muestra un pasajero junto con su historial.
     */
    public function show(
        int $id
    ): JsonResponse {
        $pasajero = Pasajero::query()
            ->with([
                'persona',
                'usuarioPasajero',
                'solicitudes' => function (
                    $query
                ) {
                    $query->orderByDesc('id');
                },
            ])
            ->findOrFail($id);

        return response()->json($pasajero);
    }

    /**
     * Actualiza el correo del perfil de pasajero.
     *
     * Si el pasajero ya tiene cuenta de acceso, se mantiene el
     * correo sincronizado también en users para evitar que el panel
     * muestre un correo distinto al utilizado para iniciar sesión.
     */
    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $pasajero = Pasajero::query()
            ->with('usuarioPasajero')
            ->findOrFail($id);

        $validated = $request->validate([
            'id_persona' => [
                'sometimes',
                'integer',
                'exists:personas,id',
                Rule::unique(
                    'pasajeros',
                    'id_persona'
                )->ignore($pasajero->id),
            ],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:100',
                Rule::unique(
                    'pasajeros',
                    'email'
                )->ignore($pasajero->id),
            ],
        ]);

        if (
            array_key_exists(
                'email',
                $validated
            )
        ) {
            $nuevoEmail = mb_strtolower(
                trim(
                    (string) $validated['email']
                )
            );

            $cuenta =
                $pasajero->usuarioPasajero;

            if ($cuenta) {
                $request->validate([
                    'email' => [
                        Rule::unique(
                            'users',
                            'email'
                        )->ignore($cuenta->id),
                    ],
                ]);
            }

            $validated['email'] =
                $nuevoEmail;
        }

        DB::transaction(
            function () use (
                $pasajero,
                $validated
            ) {
                $pasajero->update(
                    $validated
                );

                if (
                    isset(
                        $validated['email']
                    )
                    && $pasajero
                        ->usuarioPasajero
                ) {
                    $pasajero
                        ->usuarioPasajero
                        ->update([
                            'email' =>
                                $validated[
                                    'email'
                                ],
                        ]);
                }
            }
        );

        return response()->json(
            $pasajero->fresh([
                'persona',
                'usuarioPasajero',
            ])
        );
    }

    /**
     * Crea la cuenta de inicio de sesión del pasajero.
     *
     * Esta acción se publica únicamente bajo middleware
     * role:admin_general en routes/api.php.
     */
    public function crearCuentaPasajero(
        Request $request,
        int $id
    ): JsonResponse {
        $pasajero = Pasajero::query()
            ->with([
                'persona',
                'usuarioPasajero',
            ])
            ->findOrFail($id);

        if ($pasajero->usuarioPasajero) {
            return response()->json([
                'mensaje' =>
                    'Este pasajero ya tiene una cuenta vinculada.',
                'user' =>
                    $pasajero->usuarioPasajero,
            ], 409);
        }

        /*
         * Protección adicional por si existen datos históricos
         * donde una cuenta pasajero fue vinculada por persona
         * pero quedó sin pasajero_id.
         */
        $cuentaHistorica = User::query()
            ->where(
                'role',
                'pasajero'
            )
            ->where(
                'persona_id',
                $pasajero->id_persona
            )
            ->whereNull(
                'pasajero_id'
            )
            ->first();

        if ($cuentaHistorica) {
            return response()->json([
                'mensaje' =>
                    'Esta persona ya posee una cuenta de pasajero sin vincular. Revisa el registro antes de crear otra cuenta.',
            ], 409);
        }

        $persona = $pasajero->persona;
        $telefonoCuenta = $this->phoneRegistry->firstValidPhone(
            $request->input('nickname'),
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
                (int) $pasajero->id_persona,
                null,
                $request->has('telefono')
                    ? 'telefono'
                    : 'nickname'
            );
        }

        $nombreCompleto = trim(
            (
                $persona?->nombre
                ?? 'Pasajero'
            )
            . ' '
            . (
                $persona?->apellidos
                ?? ''
            )
        );

        $email = mb_strtolower(
            trim(
                (string) $datos['email']
            )
        );

        $nickname = isset(
            $datos['nickname']
        )
            ? trim(
                (string) $datos['nickname']
            )
            : null;

        if ($nickname === '') {
            $nickname = null;
        }

        $user = DB::transaction(
            function () use (
                $pasajero,
                $nombreCompleto,
                $nickname,
                $email,
                $datos
            ) {
                $user = User::create([
                    'name' =>
                        $nombreCompleto
                        ?: 'Pasajero MOTRIX',
                    'nickname' =>
                        $nickname,
                    'email' =>
                        $email,
                    'password' =>
                        Hash::make(
                            $datos['password']
                        ),
                    'role' =>
                        'pasajero',
                    'mototaxista_id' =>
                        null,
                    'pasajero_id' =>
                        $pasajero->id,
                    'persona_id' =>
                        $pasajero->id_persona,
                    'federacion_id' =>
                        null,
                    'sindicato_id' =>
                        null,
                ]);

                /*
                 * El correo visible en pasajeros debe coincidir
                 * con el correo de acceso.
                 */
                if (
                    $pasajero->email
                    !== $email
                ) {
                    $pasajero->update([
                        'email' => $email,
                    ]);
                }

                return $user;
            }
        );

        return response()->json([
            'mensaje' =>
                'Cuenta de pasajero creada correctamente.',
            'user' =>
                $user,
            'data' =>
                $pasajero->fresh([
                    'persona',
                    'usuarioPasajero',
                ]),
        ], 201);
    }

    /**
     * Elimina la cuenta del pasajero autenticado.
     *
     * Los viajes históricos no se eliminan para no romper pagos,
     * reportes ni integridad referencial. Cuando la Persona no está
     * vinculada a otro rol, sus datos identificables se anonimizan.
     */
    public function eliminarMiCuenta(
        Request $request
    ): JsonResponse {
        $usuario = $request->user();

        if (
            ! $usuario
            || strtolower(trim((string) $usuario->role)) !== 'pasajero'
        ) {
            abort(
                403,
                'Esta acción corresponde únicamente a una cuenta de pasajero.'
            );
        }

        $datos = $request->validate([
            'confirmacion' => [
                'required',
                'string',
                'in:ELIMINAR',
            ],
            'email_confirmacion' => [
                'required',
                'email',
                'max:150',
            ],
        ], [
            'confirmacion.in' =>
                'Escribe ELIMINAR para confirmar la eliminación de la cuenta.',
        ]);

        if (
            mb_strtolower(trim((string) $datos['email_confirmacion']))
            !== mb_strtolower(trim((string) $usuario->email))
        ) {
            return response()->json([
                'message' =>
                    'El correo de confirmación no coincide con la cuenta actual.',
                'errors' => [
                    'email_confirmacion' => [
                        'El correo no coincide con tu cuenta.',
                    ],
                ],
            ], 422);
        }

        $pasajero = Pasajero::query()
            ->with([
                'persona.imagenes',
                'persona.mototaxista',
                'persona.usuarios',
            ])
            ->find((int) ($usuario->pasajero_id ?? 0));

        if (! $pasajero) {
            return response()->json([
                'message' =>
                    'La cuenta no tiene un perfil de pasajero válido.',
            ], 409);
        }

        $viajeActivo = DB::table('solicitudes')
            ->where('id_pasajero', $pasajero->id)
            ->whereNotIn('estado', [
                'Finalizado',
                'Cancelado',
            ])
            ->exists();

        if ($viajeActivo) {
            return response()->json([
                'message' =>
                    'No puedes eliminar tu cuenta mientras tengas una solicitud o viaje activo.',
            ], 409);
        }

        $persona = $pasajero->persona;

        $personaCompartida = false;
        $rutasImagenes = [];

        if ($persona) {
            $personaCompartida = (
                $persona->mototaxista !== null
                || $persona->usuarios
                    ->where('id', '!=', $usuario->id)
                    ->isNotEmpty()
            );

            if (! $personaCompartida) {
                $rutasImagenes = $persona->imagenes
                    ->pluck('ruta')
                    ->filter()
                    ->values()
                    ->all();
            }
        }

        DB::transaction(
            function () use (
                $usuario,
                $pasajero,
                $persona,
                $personaCompartida
            ) {
                /* Revoca todas las sesiones antes de eliminar el usuario. */
                $usuario->tokens()->delete();

                $pasajero->update([
                    'email' => null,
                    'password' => null,
                ]);

                if (
                    $persona
                    && ! $personaCompartida
                ) {
                    DB::table('imagenes_personas')
                        ->where('id_persona', $persona->id)
                        ->delete();

                    $persona->update([
                        'nombre' => 'Cuenta eliminada',
                        'apellidos' => null,
                        'telefono' => null,
                        'ci' => null,
                        'direccion' => null,
                        'sindicato_registro_id' => null,
                    ]);
                }

                $usuario->delete();
            }
        );

        foreach ($rutasImagenes as $ruta) {
            try {
                if (
                    Storage::disk('public')->exists($ruta)
                ) {
                    Storage::disk('public')->delete($ruta);
                }
            } catch (\Throwable $error) {
                /*
                 * La cuenta ya fue eliminada de forma transaccional.
                 * Un fallo aislado del filesystem no debe devolver un 500
                 * engañoso al usuario. Se registra para limpieza operativa.
                 */
                Log::warning(
                    'No se pudo eliminar una imagen durante la eliminación de cuenta de pasajero.',
                    [
                        'ruta' => $ruta,
                        'error' => $error->getMessage(),
                    ]
                );
            }
        }

        return response()->json([
            'message' =>
                'Tu cuenta de pasajero fue eliminada correctamente.',
            'historial_anonimizado' => true,
            'datos_personales_conservados_por_otro_vinculo' =>
                $personaCompartida,
        ], 200);
    }

    /**
     * Elimina un pasajero únicamente cuando no posee historial
     * ni una cuenta de acceso vinculada.
     */
    public function destroy(
        int $id
    ): JsonResponse {
        $pasajero = Pasajero::query()
            ->with('usuarioPasajero')
            ->findOrFail($id);

        if ($pasajero->usuarioPasajero) {
            return response()->json([
                'mensaje' =>
                    'No se puede eliminar el pasajero porque tiene una cuenta de acceso vinculada.',
            ], 409);
        }

        if (
            DB::table('solicitudes')
                ->where(
                    'id_pasajero',
                    $pasajero->id
                )
                ->exists()
        ) {
            return response()->json([
                'mensaje' =>
                    'No se puede eliminar el pasajero porque tiene historial de viajes.',
            ], 409);
        }

        $pasajero->delete();

        return response()->json([
            'mensaje' =>
                'Pasajero eliminado con éxito.',
        ]);
    }
}
