<?php

namespace App\Http\Controllers;

use App\Models\Pasajero;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'credential' => ['required', 'string', 'max:5000'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $google = $this->verificarGoogleIdToken(
            $datos['credential']
        );

        $user = User::query()
            ->where('google_sub', $google['sub'])
            ->first();

        if (! $user) {
            $user = User::query()
                ->whereRaw(
                    'LOWER(email) = ?',
                    [mb_strtolower($google['email'])]
                )
                ->first();
        }

        if (! $user) {
            return response()->json([
                'message' => 'Completa tus datos para crear tu cuenta de pasajero.',
                'needs_profile' => true,
                'registration_token' => $this->crearTokenRegistroGoogle($google),
                'profile' => [
                    'name' => $google['name'],
                    'given_name' => $google['given_name'],
                    'family_name' => $google['family_name'],
                    'email' => $google['email'],
                    'picture' => $google['picture'],
                ],
            ], 202);
        }

        if (! $user->tieneRol('pasajero')) {
            return response()->json([
                'message' => 'Este correo pertenece a una cuenta administrativa o de conductor. Ingresa con tus credenciales de MOTRIX.',
            ], 403);
        }

        if (
            $user->google_sub
            && $user->google_sub !== $google['sub']
        ) {
            return response()->json([
                'message' => 'La cuenta ya se encuentra vinculada a otra identidad de Google.',
            ], 409);
        }

        $this->asegurarVinculoPasajero($user);

        $user->forceFill([
            'google_sub' => $google['sub'],
            'google_avatar_url' => $google['picture'],
            'google_linked_at' => $user->google_linked_at ?? now(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $this->crearSesion(
            $user->fresh(),
            $datos['device_name'] ?? 'MOTRIX Google Web'
        );
    }

    public function completeProfile(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'registration_token' => ['required', 'string', 'max:10000'],
            'ci' => [
                'required',
                'string',
                'max:20',
                'unique:personas,ci',
            ],
            'telefono' => [
                'required',
                'string',
                'max:20',
            ],
            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],
            'device_name' => ['nullable', 'string', 'max:100'],
        ], [
            'ci.unique' => 'Ya existe una persona registrada con este número de CI.',
        ]);

        $google = $this->resolverTokenRegistroGoogle(
            $datos['registration_token']
        );

        $existente = User::query()
            ->where('google_sub', $google['sub'])
            ->orWhereRaw(
                'LOWER(email) = ?',
                [mb_strtolower($google['email'])]
            )
            ->first();

        if ($existente) {
            if (! $existente->tieneRol('pasajero')) {
                return response()->json([
                    'message' => 'Este correo pertenece a una cuenta que no es de pasajero.',
                ], 403);
            }

            $this->asegurarVinculoPasajero($existente);

            $existente->forceFill([
                'google_sub' => $google['sub'],
                'google_avatar_url' => $google['picture'],
                'google_linked_at' => $existente->google_linked_at ?? now(),
                'email_verified_at' => $existente->email_verified_at ?? now(),
            ])->save();

            return $this->crearSesion(
                $existente->fresh(),
                $datos['device_name'] ?? 'MOTRIX Google Web'
            );
        }

        $resultado = DB::transaction(function () use ($datos, $google) {
            [$nombre, $apellidos] = $this->separarNombreGoogle($google);

            $persona = Persona::create([
                'nombre' => $nombre,
                'apellidos' => $apellidos,
                'telefono' => trim((string) $datos['telefono']),
                'ci' => trim((string) $datos['ci']),
                'direccion' => isset($datos['direccion'])
                    && trim((string) $datos['direccion']) !== ''
                        ? trim((string) $datos['direccion'])
                        : null,
                'sindicato_registro_id' => null,
            ]);

            $pasajero = Pasajero::create([
                'email' => mb_strtolower($google['email']),
                'password' => null,
                'id_persona' => $persona->id,
            ]);

            $user = User::create([
                'name' => trim($nombre . ' ' . ($apellidos ?? '')),
                'nickname' => null,
                'email' => mb_strtolower($google['email']),
                'password' => Str::random(64),
                'role' => 'pasajero',
                'mototaxista_id' => null,
                'pasajero_id' => $pasajero->id,
                'persona_id' => $persona->id,
                'federacion_id' => null,
                'sindicato_id' => null,
                'google_sub' => $google['sub'],
                'google_avatar_url' => $google['picture'],
                'google_linked_at' => now(),
                'email_verified_at' => now(),
            ]);

            return $user;
        });

        return $this->crearSesion(
            $resultado,
            $datos['device_name'] ?? 'MOTRIX Google Web',
            201,
            'Tu cuenta de pasajero con Google fue creada correctamente.'
        );
    }

    private function verificarGoogleIdToken(string $credential): array
    {
        $clientId = trim((string) config('google.client_id'));

        if ($clientId === '') {
            abort(
                500,
                'El acceso con Google todavía no está configurado en el servidor.'
            );
        }

        $client = new \Google_Client([
            'client_id' => $clientId,
        ]);

        $payload = $client->verifyIdToken($credential);

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'credential' => [
                    'No se pudo verificar la identidad de Google.',
                ],
            ]);
        }

        $sub = trim((string) ($payload['sub'] ?? ''));
        $email = mb_strtolower(
            trim((string) ($payload['email'] ?? ''))
        );
        $emailVerified = filter_var(
            $payload['email_verified'] ?? false,
            FILTER_VALIDATE_BOOL
        );

        if ($sub === '' || $email === '' || ! $emailVerified) {
            throw ValidationException::withMessages([
                'credential' => [
                    'Google no devolvió una cuenta con correo verificado.',
                ],
            ]);
        }

        return [
            'sub' => $sub,
            'email' => $email,
            'name' => trim((string) ($payload['name'] ?? '')),
            'given_name' => trim((string) ($payload['given_name'] ?? '')),
            'family_name' => trim((string) ($payload['family_name'] ?? '')),
            'picture' => trim((string) ($payload['picture'] ?? '')) ?: null,
        ];
    }

    private function crearTokenRegistroGoogle(array $google): string
    {
        $payload = [
            'sub' => $google['sub'],
            'email' => $google['email'],
            'name' => $google['name'],
            'given_name' => $google['given_name'],
            'family_name' => $google['family_name'],
            'picture' => $google['picture'],
            'expires_at' => now()->addMinutes(15)->timestamp,
        ];

        return Crypt::encryptString(
            json_encode(
                $payload,
                JSON_UNESCAPED_UNICODE
                | JSON_UNESCAPED_SLASHES
            )
        );
    }

    private function resolverTokenRegistroGoogle(string $token): array
    {
        try {
            $json = Crypt::decryptString($token);
            $payload = json_decode($json, true);
        } catch (\Throwable $error) {
            throw ValidationException::withMessages([
                'registration_token' => [
                    'La verificación temporal de Google ya no es válida. Vuelve a continuar con Google.',
                ],
            ]);
        }

        if (! is_array($payload)) {
            throw ValidationException::withMessages([
                'registration_token' => [
                    'La verificación temporal de Google no es válida.',
                ],
            ]);
        }

        $expira = (int) ($payload['expires_at'] ?? 0);

        if ($expira <= now()->timestamp) {
            throw ValidationException::withMessages([
                'registration_token' => [
                    'La verificación de Google expiró. Vuelve a continuar con Google.',
                ],
            ]);
        }

        $sub = trim((string) ($payload['sub'] ?? ''));
        $email = mb_strtolower(
            trim((string) ($payload['email'] ?? ''))
        );

        if ($sub === '' || $email === '') {
            throw ValidationException::withMessages([
                'registration_token' => [
                    'La verificación temporal de Google está incompleta.',
                ],
            ]);
        }

        return [
            'sub' => $sub,
            'email' => $email,
            'name' => trim((string) ($payload['name'] ?? '')),
            'given_name' => trim((string) ($payload['given_name'] ?? '')),
            'family_name' => trim((string) ($payload['family_name'] ?? '')),
            'picture' => trim((string) ($payload['picture'] ?? '')) ?: null,
        ];
    }

    private function separarNombreGoogle(array $google): array
    {
        $nombre = $google['given_name'];
        $apellidos = $google['family_name'];

        if ($nombre === '') {
            $partes = preg_split(
                '/\\s+/',
                trim($google['name']),
                2
            );

            $nombre = trim((string) ($partes[0] ?? 'Pasajero'));
            $apellidos = trim((string) ($partes[1] ?? ''));
        }

        if ($nombre === '') {
            $nombre = 'Pasajero';
        }

        return [
            $nombre,
            $apellidos !== '' ? $apellidos : null,
        ];
    }

    private function asegurarVinculoPasajero(User $user): void
    {
        if ($user->pasajero_id) {
            return;
        }

        $pasajero = null;

        if ($user->persona_id) {
            $pasajero = Pasajero::query()
                ->where('id_persona', $user->persona_id)
                ->first();
        }

        if (! $pasajero) {
            $pasajero = Pasajero::query()
                ->whereRaw(
                    'LOWER(email) = ?',
                    [mb_strtolower((string) $user->email)]
                )
                ->first();
        }

        if (! $pasajero) {
            abort(
                409,
                'La cuenta de pasajero existe, pero su perfil no está vinculado correctamente.'
            );
        }

        $user->forceFill([
            'pasajero_id' => $pasajero->id,
            'persona_id' => $user->persona_id ?? $pasajero->id_persona,
        ])->save();
    }

    private function crearSesion(
        User $user,
        string $deviceName,
        int $status = 200,
        string $message = 'Inicio de sesión con Google correcto.'
    ): JsonResponse {
        $user->tokens()
            ->where('name', $deviceName)
            ->delete();

        $token = $user
            ->createToken($deviceName)
            ->plainTextToken;

        return response()->json([
            'message' => $message,
            'token_type' => 'Bearer',
            'token' => $token,
            'user' => $this->datosUsuario($user),
        ], $status);
    }

    private function datosUsuario(User $user): array
    {
        $user->loadMissing([
            'persona',
            'pasajero.persona',
        ]);

        $persona = $user->persona
            ?? $user->pasajero?->persona;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'nickname' => $user->nickname,
            'persona_nombre' => trim(
                ($persona?->nombre ?? $user->name)
                . ($persona?->apellidos ? ' ' . $persona->apellidos : '')
            ),
            'email' => $user->email,
            'role' => $user->role,
            'mototaxista_id' => $user->mototaxista_id,
            'pasajero_id' => $user->pasajero_id,
            'persona_id' => $user->persona_id,
            'federacion_id' => $user->federacion_id,
            'federacion_nombre' => null,
            'sindicato_id' => $user->sindicato_id,
            'sindicato_nombre' => null,
            'google_avatar_url' => $user->google_avatar_url,
        ];
    }
}
