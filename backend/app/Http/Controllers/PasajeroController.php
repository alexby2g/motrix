<?php

namespace App\Http\Controllers;

use App\Models\Pasajero;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PasajeroController extends Controller
{
    /**
     * Lista administrativa de pasajeros.
     *
     * Incluye la cuenta de acceso cuando ya existe para que el
     * panel pueda mostrar "Cuenta pasajero" o "Sin cuenta".
     */
    public function index(): JsonResponse
    {
        $pasajeros = Pasajero::query()
            ->with([
                'persona',
                'usuarioPasajero',
            ])
            ->orderBy('id')
            ->get();

        return response()->json($pasajeros);
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
                'min:6',
                'max:100',
            ],
        ]);

        $persona = $pasajero->persona;

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
