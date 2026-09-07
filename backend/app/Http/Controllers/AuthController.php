<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        /*
         * Compatibilidad:
         * - El frontend integrado envía "login".
         * - Las versiones anteriores pueden seguir enviando "email".
         * - El módulo sindical puede enviar "nickname".
         */
        $datos = $request->validate([
            'login' => [
                'nullable',
                'string',
                'max:150',
                'required_without_all:email,nickname',
            ],
            'email' => [
                'nullable',
                'string',
                'max:150',
                'required_without_all:login,nickname',
            ],
            'nickname' => [
                'nullable',
                'string',
                'max:50',
                'required_without_all:login,email',
            ],
            'password' => ['required', 'string', 'max:255'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $identificador = trim((string) (
            $datos['login']
            ?? $datos['email']
            ?? $datos['nickname']
            ?? ''
        ));

        $identificadorNormalizado = mb_strtolower($identificador);
        $telefonoNormalizado = $this->normalizarTelefono(
            $identificador
        );

        if (strlen($telefonoNormalizado) < 7) {
            $telefonoNormalizado = '';
        }

        $user = User::query()
            ->where(function ($query) use (
                $identificadorNormalizado,
                $telefonoNormalizado
            ) {
                $query
                    ->whereRaw(
                        'LOWER(email) = ?',
                        [$identificadorNormalizado]
                    )
                    ->orWhereRaw(
                        'LOWER(nickname) = ?',
                        [$identificadorNormalizado]
                    );

                if ($telefonoNormalizado !== '') {
                    $query->orWhere(
                        'nickname',
                        $telefonoNormalizado
                    );
                }
            })
            ->first();

        if (
            ! $user
            || ! Hash::check(
                $datos['password'],
                $user->password
            )
        ) {
            throw ValidationException::withMessages([
                'login' => [
                    'El celular, correo, usuario o la contraseña son incorrectos.',
                ],
            ]);
        }

        $deviceName =
            $datos['device_name']
            ?? 'MOTRIX Web';

        /*
         * Conserva un token por navegador/dispositivo.
         */
        $user->tokens()
            ->where('name', $deviceName)
            ->delete();

        $token = $user
            ->createToken($deviceName)
            ->plainTextToken;

        return response()->json([
            'message' => 'Inicio de sesión correcto.',
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $this->datosUsuario($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->datosUsuario(
                $request->user()
            ),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()
            ?->currentAccessToken()
            ?->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $request->user()
            ?->tokens()
            ->delete();

        return response()->json([
            'message' => 'Todas las sesiones fueron cerradas.',
        ]);
    }

    private function normalizarTelefono(mixed $telefono): string
    {
        $numero = preg_replace(
            '/\D+/u',
            '',
            trim((string) $telefono)
        ) ?? '';

        if (str_starts_with($numero, '00591') && strlen($numero) === 13) {
            return substr($numero, 5);
        }

        if (str_starts_with($numero, '591') && strlen($numero) === 11) {
            return substr($numero, 3);
        }

        return $numero;
    }

    private function datosUsuario(User $user): array
    {
        $user->loadMissing([
            'persona',
            'mototaxista.persona',
            'pasajero.persona',
            'federacion',
            'sindicato.federacionEntidad',
        ]);

        $personaNombre =
            $user->persona?->nombre
            ?? $user->mototaxista?->persona?->nombre
            ?? $user->pasajero?->persona?->nombre
            ?? $user->name;

        $personaApellidos =
            $user->persona?->apellidos
            ?? $user->mototaxista?->persona?->apellidos
            ?? $user->pasajero?->persona?->apellidos
            ?? null;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'nickname' => $user->nickname,
            'persona_nombre' => trim(
                $personaNombre
                . (
                    $personaApellidos
                        ? ' ' . $personaApellidos
                        : ''
                )
            ),
            'email' => str_ends_with(
                mb_strtolower((string) $user->email),
                '@motrix.invalid'
            ) ? null : $user->email,
            'telefono' => $user->persona?->telefono
                ?? $user->mototaxista?->telefono
                ?? $user->mototaxista?->persona?->telefono
                ?? $user->pasajero?->persona?->telefono
                ?? (preg_match('/^[0-9]{7,15}$/', (string) $user->nickname)
                    ? $user->nickname
                    : null),
            'role' => $user->role,
            'mototaxista_id' => $user->mototaxista_id,
            'pasajero_id' => $user->pasajero_id,
            'persona_id' => $user->persona_id,
            'federacion_id' => $user->federacion_id,
            'federacion_nombre' => $user->federacion?->nombre,
            'sindicato_id' => $user->sindicato_id,
            'sindicato_nombre' => $user->sindicato?->nombre,
        ];
    }
}
