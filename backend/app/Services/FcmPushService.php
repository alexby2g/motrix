<?php

namespace App\Services;

use App\Models\PushDevice;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FcmPushService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    public function configured(): bool
    {
        return $this->credentials() !== null;
    }

    public function sendToUser(
        User|int|null $user,
        string $title,
        string $body,
        array $data = []
    ): int {
        $userId = $user instanceof User
            ? (int) $user->id
            : (int) ($user ?? 0);

        if ($userId <= 0 || ! $this->configured()) {
            return 0;
        }

        $devices = PushDevice::query()
            ->where('user_id', $userId)
            ->where('active', true)
            ->orderByDesc('last_seen_at')
            ->limit(8)
            ->get();

        if ($devices->isEmpty()) {
            return 0;
        }

        $accessToken = $this->accessToken();
        $credentials = $this->credentials();
        $projectId = (string) ($credentials['project_id'] ?? '');

        if (! $accessToken || $projectId === '') {
            return 0;
        }

        $sent = 0;

        foreach ($devices as $device) {
            try {
                $response = Http::withToken($accessToken)
                    ->acceptJson()
                    ->timeout(8)
                    ->post(
                        "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                        [
                            'message' => [
                                'token' => $device->token,
                                'notification' => [
                                    'title' => $title,
                                    'body' => $body,
                                ],
                                'data' => $this->normalizarData($data),
                                'android' => [
                                    'priority' => 'high',
                                    'notification' => [
                                        'sound' => 'default',
                                    ],
                                ],
                            ],
                        ]
                    );

                if ($response->successful()) {
                    $sent++;
                    $device->forceFill([
                        'active' => true,
                        'last_seen_at' => now(),
                    ])->save();
                    continue;
                }

                $bodyResponse = $response->json();
                $status = (string) data_get($bodyResponse, 'error.status', '');
                $details = json_encode(data_get($bodyResponse, 'error.details', []));

                if (
                    in_array($status, ['NOT_FOUND', 'INVALID_ARGUMENT'], true)
                    || str_contains((string) $details, 'UNREGISTERED')
                ) {
                    $device->forceFill(['active' => false])->save();
                }

                Log::warning('FCM no pudo entregar una notificación MOTRIX.', [
                    'user_id' => $userId,
                    'status' => $response->status(),
                    'fcm_status' => $status,
                ]);
            } catch (Throwable $exception) {
                Log::warning('FCM falló sin interrumpir el flujo MOTRIX.', [
                    'user_id' => $userId,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    private function accessToken(): ?string
    {
        return Cache::remember(
            'motrix:fcm:access_token',
            now()->addMinutes(50),
            function () {
                $credentials = $this->credentials();

                if (! $credentials) {
                    return null;
                }

                $clientEmail = (string) ($credentials['client_email'] ?? '');
                $privateKey = (string) ($credentials['private_key'] ?? '');

                if ($clientEmail === '' || $privateKey === '') {
                    return null;
                }

                $now = time();
                $header = $this->base64Url(json_encode([
                    'alg' => 'RS256',
                    'typ' => 'JWT',
                ], JSON_UNESCAPED_SLASHES));
                $payload = $this->base64Url(json_encode([
                    'iss' => $clientEmail,
                    'scope' => self::SCOPE,
                    'aud' => self::TOKEN_URL,
                    'iat' => $now,
                    'exp' => $now + 3600,
                ], JSON_UNESCAPED_SLASHES));

                $unsigned = $header . '.' . $payload;
                $signature = '';
                $key = str_replace('\\n', "\n", $privateKey);

                $signed = openssl_sign(
                    $unsigned,
                    $signature,
                    $key,
                    OPENSSL_ALGO_SHA256
                );

                if (! $signed) {
                    return null;
                }

                $jwt = $unsigned . '.' . $this->base64Url($signature);

                try {
                    $response = Http::asForm()
                        ->timeout(8)
                        ->post(self::TOKEN_URL, [
                            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                            'assertion' => $jwt,
                        ]);

                    if (! $response->successful()) {
                        Log::warning('No se pudo obtener token OAuth para FCM.', [
                            'status' => $response->status(),
                        ]);
                        return null;
                    }

                    return $response->json('access_token');
                } catch (Throwable $exception) {
                    Log::warning('No se pudo conectar con OAuth de Firebase.', [
                        'message' => $exception->getMessage(),
                    ]);
                    return null;
                }
            }
        );
    }

    private function credentials(): ?array
    {
        $path = trim((string) config('services.firebase.credentials'));

        if ($path !== '') {
            $resolved = str_starts_with($path, DIRECTORY_SEPARATOR)
                ? $path
                : base_path($path);

            if (is_file($resolved)) {
                $decoded = json_decode((string) file_get_contents($resolved), true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        $projectId = trim((string) config('services.firebase.project_id'));
        $clientEmail = trim((string) config('services.firebase.client_email'));
        $privateKey = (string) config('services.firebase.private_key');

        if ($projectId === '' || $clientEmail === '' || trim($privateKey) === '') {
            return null;
        }

        return [
            'project_id' => $projectId,
            'client_email' => $clientEmail,
            'private_key' => $privateKey,
        ];
    }

    private function normalizarData(array $data): array
    {
        $normalizada = [];
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }

            if (is_bool($value)) {
                $normalizada[(string) $key] = $value ? '1' : '0';
            } elseif (is_scalar($value)) {
                $normalizada[(string) $key] = (string) $value;
            } else {
                $normalizada[(string) $key] = json_encode(
                    $value,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
            }
        }

        return $normalizada;
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
