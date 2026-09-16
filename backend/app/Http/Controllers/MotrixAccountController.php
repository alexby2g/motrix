<?php

namespace App\Http\Controllers;

use App\Models\Pasajero;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class MotrixAccountController extends Controller
{
    public function __construct(
        private readonly PasajeroController $pasajeroController,
        private readonly MototaxistaController $mototaxistaController
    ) {
    }

    public function registroPasajeroCelular(Request $request): JsonResponse
    {
        $telefono = $this->normalizarTelefono($request->input('telefono'));
        $request->merge(['telefono' => $telefono]);

        $request->validate([
            'telefono' => [
                'required',
                'string',
                'regex:/^[0-9]{7,15}$/',
                Rule::unique('users', 'nickname'),
            ],
            'email' => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('users', 'email'),
                Rule::unique('pasajeros', 'email'),
            ],
        ], [
            'telefono.regex' => 'Ingresa un número de celular válido.',
            'telefono.unique' => 'Este número de celular ya tiene una cuenta MOTRIX.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
        ]);

        $emailContacto = mb_strtolower(
            trim((string) $request->input('email', ''))
        );

        $request->merge([
            'email' => $emailContacto !== ''
                ? $emailContacto
                : $this->emailInterno('pasajero', $telefono),
        ]);

        $response = $this->pasajeroController->registroPublico($request);

        if ($response->getStatusCode() !== 201) {
            return $response;
        }

        $payload = $response->getData(true);

        if (isset($payload['user']) && is_array($payload['user'])) {
            $payload['user']['email'] = $emailContacto !== ''
                ? $emailContacto
                : null;
            $payload['user']['telefono'] = $telefono;
        }

        $payload['message'] = 'Tu cuenta de pasajero fue creada. Inicia sesión con tu número de celular.';
        $payload['telefono_acceso'] = $telefono;

        return response()->json($payload, 201);
    }

    public function crearCuentaConductorCelular(
        Request $request,
        int $id
    ): JsonResponse {
        $telefono = $this->normalizarTelefono($request->input('telefono'));
        $request->merge(['telefono' => $telefono]);

        $request->validate([
            'telefono' => [
                'required',
                'string',
                'regex:/^[0-9]{7,15}$/',
                Rule::unique('users', 'nickname'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
            ],
        ], [
            'telefono.regex' => 'Ingresa un número de celular válido.',
            'telefono.unique' => 'Este número de celular ya tiene una cuenta MOTRIX.',
        ]);

        $request->merge([
            'nickname' => $telefono,
            'email' => $this->emailInterno('conductor', $telefono),
        ]);

        $response = $this->mototaxistaController
            ->crearCuentaConductor($request, $id);

        if ($response->getStatusCode() === 201) {
            $payload = $response->getData(true);
            $mototaxista = $payload['data'] ?? null;

            $registro = \App\Models\Mototaxista::query()
                ->with('persona')
                ->find($id);

            if ($registro) {
                $registro->telefono = $telefono;
                $registro->save();
                if ($registro->persona) {
                    $registro->persona->telefono = $telefono;
                    $registro->persona->save();
                }
            }

            $payload['mensaje'] = 'Cuenta de conductor creada. El número de celular es el usuario de acceso.';
            $payload['telefono_acceso'] = $telefono;
            $payload['data'] = $registro?->fresh([
                'persona.imagenes',
                'sindicato.federacionRelacion',
                'motocicletas.imagenes',
                'usuarioConductor',
            ]) ?? $mototaxista;

            return response()->json($payload, 201);
        }

        return $response;
    }

    public function crearCuentaPasajeroCelular(
        Request $request,
        int $id
    ): JsonResponse {
        $telefono = $this->normalizarTelefono($request->input('telefono'));
        $request->merge(['telefono' => $telefono]);

        $request->validate([
            'telefono' => [
                'required',
                'string',
                'regex:/^[0-9]{7,15}$/',
                Rule::unique('users', 'nickname'),
            ],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
            ],
        ], [
            'telefono.regex' => 'Ingresa un número de celular válido.',
            'telefono.unique' => 'Este número de celular ya tiene una cuenta MOTRIX.',
        ]);

        $pasajero = Pasajero::query()
            ->with('persona')
            ->findOrFail($id);

        $emailActual = mb_strtolower(trim((string) $pasajero->email));
        $email = $emailActual !== '' && ! str_ends_with($emailActual, '@motrix.invalid')
            ? $emailActual
            : $this->emailInterno('pasajero', $telefono);

        $request->merge([
            'email' => $email,
            'nickname' => $telefono,
        ]);

        $response = $this->pasajeroController
            ->crearCuentaPasajero($request, $id);

        if ($response->getStatusCode() === 201) {
            if ($pasajero->persona) {
                $pasajero->persona->telefono = $telefono;
                $pasajero->persona->save();
            }

            $payload = $response->getData(true);
            $payload['mensaje'] = 'Cuenta de pasajero creada. El número de celular es el usuario de acceso.';
            $payload['telefono_acceso'] = $telefono;
            $payload['data'] = $pasajero->fresh([
                'persona',
                'usuarioPasajero',
            ]);

            return response()->json($payload, 201);
        }

        return $response;
    }

    public function cambiarPassword(Request $request): JsonResponse
    {
        $user = $request->user();
        $rol = strtolower(trim((string) ($user?->role ?? '')));

        if (! $user || ! in_array($rol, ['conductor', 'pasajero'], true)) {
            abort(403, 'Esta acción corresponde a pasajeros y mototaxistas.');
        }

        $datos = $request->validate([
            'password_actual' => ['required', 'string', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:8',
                'max:100',
                'confirmed',
                'different:password_actual',
            ],
        ], [
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'password.different' => 'La nueva contraseña debe ser diferente a la actual.',
        ]);

        if (! Hash::check($datos['password_actual'], $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual no es correcta.',
                'errors' => [
                    'password_actual' => ['La contraseña actual no es correcta.'],
                ],
            ], 422);
        }

        $user->password = Hash::make($datos['password']);
        $user->save();

        $tokenActual = $user->currentAccessToken();
        $tokenActualId = is_object($tokenActual)
            && method_exists($tokenActual, 'getKey')
                ? $tokenActual->getKey()
                : null;

        if ($tokenActualId) {
            $user->tokens()->where('id', '<>', $tokenActualId)->delete();
        } else {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Contraseña actualizada correctamente.',
        ]);
    }

    public function eliminarCuentaPasajeroSegura(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || strtolower(trim((string) $user->role)) !== 'pasajero') {
            abort(403, 'Esta acción corresponde únicamente a una cuenta de pasajero.');
        }

        $datos = $request->validate([
            'confirmacion' => ['required', 'string', 'in:ELIMINAR'],
            'password_actual' => ['required', 'string', 'max:255'],
        ], [
            'confirmacion.in' => 'Escribe ELIMINAR para confirmar la eliminación de la cuenta.',
        ]);

        if (! Hash::check($datos['password_actual'], $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual no es correcta.',
                'errors' => [
                    'password_actual' => ['La contraseña actual no es correcta.'],
                ],
            ], 422);
        }

        $request->merge([
            'email_confirmacion' => $user->email,
        ]);

        return $this->pasajeroController->eliminarMiCuenta($request);
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

    private function emailInterno(string $rol, string $telefono): string
    {
        return sprintf('%s.%s@motrix.invalid', $rol, $telefono);
    }
}
