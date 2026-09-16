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
use Illuminate\Support\Facades\Mail;
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

        $identifier = trim((string) $data['login']);
        $user = $this->findUser($identifier);

        $response = [
            'message' => 'Si la cuenta dispone de un medio de recuperación, recibirás un código de 6 dígitos válido por 10 minutos.',
        ];

        if (! $user || ! in_array($user->role, ['conductor', 'pasajero'], true)) {
            return response()->json($response);
        }

        $code = (string) random_int(100000, 999999);
        [$channel, $destination] = $this->deliverCode($user, $code);

        PasswordResetOtp::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'identifier_hash' => hash('sha256', mb_strtolower($identifier)),
                'code_hash' => Hash::make($code),
                'reset_token_hash' => null,
                'canal' => $channel,
                'destino_enmascarado' => $destination,
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

        $user = $this->findUser(trim((string) $data['login']));

        if (! $user || ! in_array($user->role, ['conductor', 'pasajero'], true)) {
            return $this->invalidCodeResponse();
        }

        $record = PasswordResetOtp::query()
            ->where('user_id', $user->id)
            ->first();

        if (
            ! $record
            || $record->consumed_at
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

        $user = $this->findUser(trim((string) $data['login']));

        if (! $user || ! in_array($user->role, ['conductor', 'pasajero'], true)) {
            return $this->invalidTokenResponse();
        }

        $record = PasswordResetOtp::query()
            ->where('user_id', $user->id)
            ->first();

        $tokenHash = hash('sha256', (string) $data['reset_token']);

        if (
            ! $record
            || $record->consumed_at
            || ! $record->verified_at
            || ! $record->reset_token_hash
            || ! hash_equals((string) $record->reset_token_hash, $tokenHash)
            || ! $record->expires_at
            || $record->expires_at->lessThanOrEqualTo(now())
        ) {
            return $this->invalidTokenResponse();
        }

        $user->password = (string) $data['password'];
        $user->save();
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

        return response()->json([
            'message' => 'Contraseña restablecida correctamente. Inicia sesión con tu nueva contraseña.',
        ]);
    }

    private function findUser(string $identifier): ?User
    {
        $normalized = mb_strtolower(trim($identifier));
        $phone = $this->phoneRegistry->isPhoneLike($identifier)
            ? $this->phoneRegistry->normalize($identifier)
            : '';

        $baseQuery = User::query()
            ->with([
                'persona',
                'mototaxista.persona',
                'pasajero.persona',
            ])
            ->whereIn('role', [
                'conductor',
                'pasajero',
            ]);

        if ($phone !== '') {
            $userIds = $this->phoneRegistry
                ->matchingEligibleUserIds($phone);

            if (count($userIds) !== 1) {
                if (count($userIds) > 1) {
                    Log::warning(
                        'Recuperación MOTRIX bloqueada por celular ambiguo.',
                        [
                            'identifier_hash' => hash(
                                'sha256',
                                $phone
                            ),
                            'candidate_user_ids' => $userIds,
                        ]
                    );
                }

                return null;
            }

            return $baseQuery
                ->whereKey($userIds[0])
                ->first();
        }

        $candidates = $baseQuery
            ->where(function ($query) use ($normalized) {
                $query
                    ->whereRaw(
                        'LOWER(email) = ?',
                        [$normalized]
                    )
                    ->orWhereRaw(
                        'LOWER(nickname) = ?',
                        [$normalized]
                    );
            })
            ->orderBy('id')
            ->limit(2)
            ->get();

        if ($candidates->count() !== 1) {
            if ($candidates->count() > 1) {
                Log::warning(
                    'Recuperación MOTRIX bloqueada por identificador ambiguo.',
                    [
                        'identifier_hash' => hash(
                            'sha256',
                            $normalized
                        ),
                        'candidate_user_ids' =>
                            $candidates
                                ->pluck('id')
                                ->map(
                                    static fn ($id) => (int) $id
                                )
                                ->all(),
                    ]
                );
            }

            return null;
        }

        return $candidates->first();
    }

    private function deliverCode(User $user, string $code): array
    {
        $email = trim((string) $user->email);

        $emailLower = mb_strtolower($email);

        if (
            $email !== ''
            && ! str_ends_with($emailLower, '@motrix.invalid')
            && ! str_ends_with($emailLower, '@motrix.local')
            && ! str_ends_with($emailLower, '@motrix.test')
        ) {
            try {
                Mail::raw(
                    "Tu código de recuperación MOTRIX es {$code}. Vence en " . self::OTP_MINUTES . ' minutos. No compartas este código.',
                    function ($message) use ($email) {
                        $message->to($email)
                            ->subject('Código de recuperación MOTRIX');
                    }
                );

                return ['email', $this->maskEmail($email), true];
            } catch (Throwable $exception) {
                Log::warning('No se pudo enviar OTP MOTRIX por correo.', [
                    'user_id' => $user->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $phone = $this->userPhone($user);
        $webhook = trim((string) config('services.motrix_sms.webhook_url'));

        if ($phone !== '' && $webhook !== '') {
            try {
                $request = Http::acceptJson()->timeout(8);
                $token = trim((string) config('services.motrix_sms.token'));
                if ($token !== '') {
                    $request = $request->withToken($token);
                }

                $response = $request->post($webhook, [
                    'to' => $phone,
                    'message' => "MOTRIX: tu código de recuperación es {$code}. Vence en " . self::OTP_MINUTES . ' minutos.',
                    'purpose' => 'password_reset',
                ]);

                if ($response->successful()) {
                    return ['sms', $this->maskPhone($phone), true];
                }

                Log::warning('Webhook SMS MOTRIX rechazó el OTP.', [
                    'user_id' => $user->id,
                    'status' => $response->status(),
                ]);
            } catch (Throwable $exception) {
                Log::warning('No se pudo enviar OTP MOTRIX por SMS.', [
                    'user_id' => $user->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return ['sin_configurar', null, false];
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

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');
        $visible = mb_substr($name, 0, min(2, mb_strlen($name)));
        return $visible . str_repeat('*', max(2, mb_strlen($name) - mb_strlen($visible))) . '@' . $domain;
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
