<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetOtp;
use App\Models\Pasajero;
use App\Models\User;
use App\Services\MotrixPhoneRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class PasswordRecoveryController extends Controller
{
    private const OTP_MINUTES = 10;
    private const MAX_ATTEMPTS = 5;

    public function __construct(
        private readonly MotrixPhoneRegistry $phoneRegistry
    ) {
    }

    public function requestCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:150'],
        ]);

        // Respuesta uniforme: nunca revelar si un número tiene cuenta.
        $response = [
            'message' => 'Si este celular está registrado y habilitado, recibirás un SMS con un código de 6 dígitos válido por 10 minutos.',
        ];

        // El acceso al SMS es un requisito real. No crear códigos que
        // nadie podrá recibir ni fingir un envío exitoso.
        if (! $this->smsConfigured()) {
            return response()->json([
                'message' => 'La recuperación por SMS todavía no está habilitada. Comunícate con soporte MOTRIX.',
            ], 503);
        }

        $identifier = trim((string) $data['login']);
        if (! $this->phoneRegistry->isPhoneLike($identifier)) {
            return response()->json($response);
        }

        $phone = $this->phoneRegistry->normalize($identifier);
        if (! preg_match('/^[67][0-9]{7}$/', $phone)) {
            return response()->json($response);
        }

        $user = $this->findUser($phone);
        if (! $user || ! in_array($user->role, ['conductor', 'pasajero'], true)) {
            return response()->json($response);
        }

        // La posesión del celular registrado es la prueba de identidad.
        // NO aceptar teléfonos arbitrarios enviados en el formulario.
        if ($this->userPhone($user) !== $phone) {
            return response()->json($response);
        }

        $previous = PasswordResetOtp::query()
            ->where('user_id', $user->id)
            ->first();

        // Reenvío limitado incluso si se usa otra IP.
        if ($previous?->updated_at && $previous->updated_at->greaterThan(now()->subSeconds(60))) {
            return response()->json($response);
        }

        $code = (string) random_int(100000, 999999);
        if (! $this->sendSmsCode($phone, $code, (int) $user->id)) {
            return response()->json($response);
        }

        PasswordResetOtp::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'identifier_hash' => hash('sha256', $phone),
                'code_hash' => Hash::make($code),
                'reset_token_hash' => null,
                'canal' => 'sms',
                'destino_enmascarado' => $this->maskPhone($phone),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::OTP_MINUTES),
                'verified_at' => null,
                'consumed_at' => null,
            ]
        );

        return response()->json($response);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'codigo' => ['required', 'digits:6'],
        ]);

        $phone = $this->phoneRegistry->normalize((string) $data['login']);
        $user = $this->findUser($phone);

        if (! $user || ! in_array($user->role, ['conductor', 'pasajero'], true)) {
            return $this->invalidCodeResponse();
        }

        $record = PasswordResetOtp::query()
            ->where('user_id', $user->id)
            ->first();

        if (
            ! $record
            || $record->consumed_at
            || $record->verified_at
            || $record->canal !== 'sms'
            || ! hash_equals((string) $record->identifier_hash, hash('sha256', $phone))
            || ! $record->expires_at
            || $record->expires_at->lessThanOrEqualTo(now())
            || $record->attempts >= self::MAX_ATTEMPTS
        ) {
            return $this->invalidCodeResponse();
        }

        $record->attempts = min(255, $record->attempts + 1);

        if (! Hash::check((string) $data['codigo'], (string) $record->code_hash)) {
            $record->save();
            return $this->invalidCodeResponse();
        }

        $plainToken = Str::random(64);
        $record->verified_at = now();
        $record->reset_token_hash = hash('sha256', $plainToken);
        $record->save();

        return response()->json([
            'message' => 'Código verificado. Ya puedes establecer una nueva contraseña.',
            'reset_token' => $plainToken,
            'expires_in_seconds' => max(
                0,
                now()->diffInSeconds($record->expires_at, false)
            ),
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'login' => ['required', 'string', 'max:150'],
            'reset_token' => ['required', 'string', 'min:40', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ]);

        $phone = $this->phoneRegistry->normalize((string) $data['login']);
        $user = $this->findUser($phone);

        if (! $user || ! in_array($user->role, ['conductor', 'pasajero'], true)) {
            return $this->invalidTokenResponse();
        }

        $tokenHash = hash('sha256', (string) $data['reset_token']);

        // Un solo uso, sin carreras de solicitudes concurrentes.
        // Si falla la actualización, la transacción revierte todo.
        $actualizado = DB::transaction(function () use ($user, $phone, $tokenHash, $data): bool {
            $record = PasswordResetOtp::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (
                ! $record
                || $record->consumed_at
                || ! $record->verified_at
                || $record->canal !== 'sms'
                || ! hash_equals((string) $record->identifier_hash, hash('sha256', $phone))
                || ! $record->reset_token_hash
                || ! hash_equals((string) $record->reset_token_hash, $tokenHash)
                || ! $record->expires_at
                || $record->expires_at->lessThanOrEqualTo(now())
            ) {
                return false;
            }

            $user->password = (string) $data['password'];
            $user->save();

            // Cerrar todas las sesiones y notificaciones de esta cuenta.
            $user->tokens()->delete();
            $user->pushDevices()->update(['active' => false]);

            if ($user->pasajero_id) {
                Pasajero::query()
                    ->where('id', $user->pasajero_id)
                    ->first()
                    ?->update(['password' => (string) $data['password']]);
            }

            $record->consumed_at = now();
            $record->reset_token_hash = null;
            $record->save();

            return true;
        });

        if (! $actualizado) {
            return $this->invalidTokenResponse();
        }

        return response()->json([
            'message' => 'Contraseña restablecida correctamente. Inicia sesión con tu nueva contraseña.',
        ]);
    }

    private function findUser(string $identifier): ?User
    {
        // Recuperación exclusivamente por celular. Una coincidencia de
        // correo o alias no reemplaza la posesión del número verificado.
        if (! $this->phoneRegistry->isPhoneLike($identifier)) {
            return null;
        }

        $phone = $this->phoneRegistry->normalize($identifier);
        if (! preg_match('/^[67][0-9]{7}$/', $phone)) {
            return null;
        }

        $userIds = $this->phoneRegistry->matchingEligibleUserIds($phone);
        if (count($userIds) !== 1) {
            if (count($userIds) > 1) {
                Log::warning('Recuperación SMS MOTRIX bloqueada: celular ambiguo.', [
                    'phone_hash' => hash('sha256', $phone),
                ]);
            }
            return null;
        }

        return User::query()
            ->with(['persona', 'mototaxista.persona', 'pasajero.persona'])
            ->whereIn('role', ['conductor', 'pasajero'])
            ->whereKey($userIds[0])
            ->first();
    }

    private function smsConfigured(): bool
    {
        $sid = trim((string) config('services.motrix_sms.twilio_account_sid'));
        $token = trim((string) config('services.motrix_sms.twilio_auth_token'));
        $sender = trim((string) config('services.motrix_sms.twilio_from'));
        $service = trim((string) config('services.motrix_sms.twilio_messaging_service_sid'));

        $twilio = $sid !== '' && $token !== '' && ($sender !== '' || $service !== '');
        $webhook = trim((string) config('services.motrix_sms.webhook_url')) !== '';

        return $twilio || $webhook;
    }

    private function sendSmsCode(string $phone, string $code, int $userId): bool
    {
        $to = '+591' . $phone;
        $message = "MOTRIX: tu codigo de recuperacion es {$code}. Valido 10 minutos. No lo compartas.";

        $sid = trim((string) config('services.motrix_sms.twilio_account_sid'));
        $authToken = trim((string) config('services.motrix_sms.twilio_auth_token'));
        $sender = trim((string) config('services.motrix_sms.twilio_from'));
        $service = trim((string) config('services.motrix_sms.twilio_messaging_service_sid'));

        if ($sid !== '' && $authToken !== '' && ($sender !== '' || $service !== '')) {
            try {
                $body = ['To' => $to, 'Body' => $message];
                if ($service !== '') {
                    $body['MessagingServiceSid'] = $service;
                } else {
                    $body['From'] = $sender;
                }

                $response = Http::withBasicAuth($sid, $authToken)
                    ->asForm()
                    ->acceptJson()
                    ->timeout(10)
                    ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", $body);

                if ($response->successful()) return true;

                Log::warning('Twilio rechazó un SMS de recuperación MOTRIX.', [
                    'user_id' => $userId,
                    'status' => $response->status(),
                ]);
            } catch (Throwable $error) {
                Log::warning('No se pudo solicitar SMS de recuperación a Twilio.', [
                    'user_id' => $userId,
                    'tipo' => get_class($error),
                ]);
            }

            return false;
        }

        $url = trim((string) config('services.motrix_sms.webhook_url'));
        if ($url === '') return false;

        try {
            $request = Http::acceptJson()->timeout(10);
            $token = trim((string) config('services.motrix_sms.token'));
            if ($token !== '') $request = $request->withToken($token);

            $response = $request->post($url, [
                'to' => $to,
                'message' => $message,
                'purpose' => 'password_reset',
            ]);

            if ($response->successful()) return true;

            Log::warning('Proveedor SMS rechazó una recuperación MOTRIX.', [
                'user_id' => $userId,
                'status' => $response->status(),
            ]);
        } catch (Throwable $error) {
            Log::warning('No se pudo solicitar SMS de recuperación MOTRIX.', [
                'user_id' => $userId,
                'tipo' => get_class($error),
            ]);
        }

        return false;
    }

    private function userPhone(User $user): string
    {
        return $this->normalizePhone(
            $user->persona?->telefono
            ?? $user->mototaxista?->telefono
            ?? $user->mototaxista?->persona?->telefono
            ?? $user->pasajero?->persona?->telefono
            ?? (preg_match('/^[0-9]{7,15}$/', (string) $user->nickname)
                ? $user->nickname
                : '')
        );
    }

    private function normalizePhone(mixed $phone): string
    {
        $number = preg_replace('/\D+/u', '', trim((string) $phone)) ?? '';

        if (str_starts_with($number, '00591') && strlen($number) === 13) {
            return substr($number, 5);
        }

        if (str_starts_with($number, '591') && strlen($number) === 11) {
            return substr($number, 3);
        }

        return $number;
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) <= 4) {
            return str_repeat('*', strlen($phone));
        }

        return str_repeat('*', strlen($phone) - 4) . substr($phone, -4);
    }

    private function invalidCodeResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'El código es inválido, venció o alcanzó el máximo de intentos.',
        ], 422);
    }

    private function invalidTokenResponse(): JsonResponse
    {
        return response()->json([
            'message' => 'La autorización para restablecer la contraseña es inválida o venció.',
        ], 422);
    }
}
