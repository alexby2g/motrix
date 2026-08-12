<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Sindicato;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsuarioController extends Controller
{
    private const ROLES_ADMINISTRATIVOS = [
        'admin_general',
        'admin_servicios',
        'secretario',
    ];

    private const ROLES_GESTIONABLES = [
        'admin_general',
        'admin_servicios',
        'secretario',
        'conductor',
        'pasajero',
    ];

    public function index(): JsonResponse
    {
        $usuarios = User::query()
            ->with($this->relaciones())
            ->whereIn('role', self::ROLES_GESTIONABLES)
            ->orderByDesc('id')
            ->get();

        return response()->json($usuarios);
    }

    public function show(int $id): JsonResponse
    {
        $usuario = User::query()
            ->with($this->relaciones())
            ->whereIn('role', self::ROLES_GESTIONABLES)
            ->find($id);

        if (! $usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        return response()->json($usuario);
    }

    /**
     * La creación directa desde este módulo se reserva para las
     * cuentas administrativas. Las cuentas de conductor y pasajero
     * deben nacer desde sus perfiles para conservar los vínculos.
     */
    public function store(Request $request): JsonResponse
    {
        $datos = $this->validarAdministrativo($request);

        $persona = Persona::findOrFail($datos['persona_id']);
        $vinculos = $this->resolverVinculos($datos);
        $nickname = trim($datos['nickname']);
        $email = $this->resolverEmail($datos['email'] ?? null, $nickname);

        $usuario = User::create([
            'name' => $this->nombrePersona($persona),
            'nickname' => $nickname,
            'email' => $email,
            'password' => Hash::make($datos['password']),
            'role' => $datos['role'],
            'persona_id' => $persona->id,
            'federacion_id' => $vinculos['federacion_id'],
            'sindicato_id' => $vinculos['sindicato_id'],
            'mototaxista_id' => null,
            'pasajero_id' => null,
        ]);

        return response()->json([
            'message' => 'Usuario administrativo creado correctamente.',
            'data' => $usuario->load($this->relaciones()),
        ], 201);
    }

    /**
     * Administrativos: permite editar persona, rol, nickname, correo y
     * contraseña. Conductores/pasajeros: mantiene rol y vínculos, pero
     * permite administrar sus credenciales de acceso.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $usuario = User::query()
            ->whereIn('role', self::ROLES_GESTIONABLES)
            ->find($id);

        if (! $usuario) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        if (in_array($usuario->role, self::ROLES_ADMINISTRATIVOS, true)) {
            return $this->actualizarAdministrativo($request, $usuario);
        }

        return $this->actualizarCredencialesOperativas($request, $usuario);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $usuario = User::query()
            ->whereIn('role', self::ROLES_ADMINISTRATIVOS)
            ->find($id);

        if (! $usuario) {
            return response()->json([
                'message' => 'Solo las cuentas administrativas pueden eliminarse desde este módulo.',
            ], 409);
        }

        if ((int) $request->user()->id === (int) $usuario->id) {
            return response()->json([
                'message' => 'No puedes eliminar la cuenta con la que tienes la sesión iniciada.',
            ], 409);
        }

        $usuario->tokens()->delete();
        $usuario->delete();

        return response()->json([
            'message' => 'Usuario administrativo eliminado correctamente.',
        ]);
    }

    private function actualizarAdministrativo(
        Request $request,
        User $usuario
    ): JsonResponse {
        $datos = $this->validarAdministrativo($request, $usuario);
        $persona = Persona::findOrFail($datos['persona_id']);
        $vinculos = $this->resolverVinculos($datos);
        $nickname = trim($datos['nickname']);
        $email = $this->resolverEmail($datos['email'] ?? null, $nickname);

        $actualizacion = [
            'name' => $this->nombrePersona($persona),
            'nickname' => $nickname,
            'email' => $email,
            'role' => $datos['role'],
            'persona_id' => $persona->id,
            'federacion_id' => $vinculos['federacion_id'],
            'sindicato_id' => $vinculos['sindicato_id'],
        ];

        if (! empty($datos['password'])) {
            $actualizacion['password'] = Hash::make($datos['password']);
        }

        $usuario->update($actualizacion);

        return response()->json([
            'message' => 'Usuario administrativo actualizado correctamente.',
            'data' => $usuario->fresh($this->relaciones()),
        ]);
    }

    private function actualizarCredencialesOperativas(
        Request $request,
        User $usuario
    ): JsonResponse {
        $datos = $request->validate([
            'nickname' => [
                'required',
                'string',
                'min:4',
                'max:50',
                Rule::unique('users', 'nickname')->ignore($usuario->id),
            ],
            'email' => [
                'required',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($usuario->id),
            ],
            'password' => [
                'nullable',
                'string',
                'min:6',
                'max:255',
            ],
        ], [
            'nickname.required' => 'El nickname es obligatorio.',
            'nickname.min' => 'El nickname debe tener al menos 4 caracteres.',
            'nickname.unique' => 'Ese nickname ya está registrado.',
            'email.required' => 'El correo es obligatorio para esta cuenta.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'Ese correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
        ]);

        $actualizacion = [
            'nickname' => trim($datos['nickname']),
            'email' => mb_strtolower(trim($datos['email'])),
        ];

        if (! empty($datos['password'])) {
            $actualizacion['password'] = Hash::make($datos['password']);
        }

        $usuario->update($actualizacion);

        return response()->json([
            'message' => 'Credenciales actualizadas correctamente.',
            'data' => $usuario->fresh($this->relaciones()),
        ]);
    }

    private function validarAdministrativo(
        Request $request,
        ?User $usuario = null
    ): array {
        return $request->validate([
            'persona_id' => [
                'required',
                'integer',
                'exists:personas,id',
            ],
            'nickname' => [
                'required',
                'string',
                'min:4',
                'max:50',
                Rule::unique('users', 'nickname')->ignore($usuario?->id),
            ],
            'email' => [
                'nullable',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($usuario?->id),
            ],
            'password' => [
                $usuario ? 'nullable' : 'required',
                'string',
                'min:6',
                'max:255',
            ],
            'role' => [
                'required',
                Rule::in(self::ROLES_ADMINISTRATIVOS),
            ],
            'federacion_id' => [
                'nullable',
                'integer',
                'exists:federaciones,id',
            ],
            'sindicato_id' => [
                Rule::requiredIf(
                    fn () => $request->input('role') === 'secretario'
                ),
                'nullable',
                'integer',
                'exists:sindicatos,id',
            ],
        ], [
            'persona_id.required' => 'Selecciona una persona.',
            'persona_id.exists' => 'La persona seleccionada no existe.',
            'nickname.required' => 'El nickname es obligatorio.',
            'nickname.min' => 'El nickname debe tener al menos 4 caracteres.',
            'nickname.unique' => 'Ese nickname ya está registrado.',
            'email.email' => 'El correo no tiene un formato válido.',
            'email.unique' => 'Ese correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'role.in' => 'El rol administrativo seleccionado no es válido.',
            'sindicato_id.required' => 'El secretario debe estar vinculado a un sindicato.',
            'sindicato_id.exists' => 'El sindicato seleccionado no existe.',
        ]);
    }

    private function resolverVinculos(array $datos): array
    {
        if (($datos['role'] ?? null) !== 'secretario') {
            return [
                'federacion_id' => null,
                'sindicato_id' => null,
            ];
        }

        $sindicato = Sindicato::findOrFail((int) $datos['sindicato_id']);

        return [
            'federacion_id' => $sindicato->id_federacion,
            'sindicato_id' => $sindicato->id,
        ];
    }

    private function resolverEmail(?string $email, string $nickname): string
    {
        $valor = trim((string) $email);

        if ($valor !== '') {
            return mb_strtolower($valor);
        }

        return mb_strtolower($nickname) . '@motrix.local';
    }

    private function nombrePersona(Persona $persona): string
    {
        return trim(
            $persona->nombre . ' ' . ($persona->apellidos ?? '')
        );
    }

    private function relaciones(): array
    {
        return [
            'persona',
            'mototaxista.persona',
            'pasajero.persona',
            'federacion',
            'sindicato.federacionEntidad',
        ];
    }
}
