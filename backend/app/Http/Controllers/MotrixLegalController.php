<?php

namespace App\Http\Controllers;

use App\Models\MotrixLegalAcceptance;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MotrixLegalController extends Controller
{
    public function documents(): JsonResponse
    {
        return response()->json([
            'data' => [
                'terms' => config('motrix_legal.terms'),
                'privacy' => config('motrix_legal.privacy'),
            ],
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        $role = strtolower(trim((string) $user->role));

        if (! in_array($role, ['pasajero', 'conductor'], true)) {
            return response()->json([
                'data' => [
                    'required' => false,
                    'accepted' => true,
                    'role' => $role,
                    'terms_version' => config('motrix_legal.terms.version'),
                    'privacy_version' => config('motrix_legal.privacy.version'),
                ],
            ]);
        }

        if (! Schema::hasTable('motrix_legal_acceptances')) {
            return response()->json([
                'message' => 'La tabla de aceptaciones legales todavía no fue instalada.',
                'code' => 'LEGAL_MIGRATION_REQUIRED',
            ], 503);
        }

        $termsVersion = (string) config('motrix_legal.terms.version');
        $privacyVersion = (string) config('motrix_legal.privacy.version');

        $acceptance = MotrixLegalAcceptance::query()
            ->where('user_id', $user->id)
            ->where('terms_version', $termsVersion)
            ->where('privacy_version', $privacyVersion)
            ->latest('accepted_at')
            ->first();

        return response()->json([
            'data' => [
                'required' => true,
                'accepted' => (bool) $acceptance,
                'role' => $role,
                'terms_version' => $termsVersion,
                'privacy_version' => $privacyVersion,
                'accepted_at' => $acceptance?->accepted_at?->toIso8601String(),
            ],
        ]);
    }

    public function accept(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'message' => 'No autenticado.',
            ], 401);
        }

        $role = strtolower(trim((string) $user->role));

        if (! in_array($role, ['pasajero', 'conductor'], true)) {
            return response()->json([
                'message' => 'Este consentimiento corresponde a pasajeros y conductores.',
            ], 422);
        }

        if (! Schema::hasTable('motrix_legal_acceptances')) {
            return response()->json([
                'message' => 'La tabla de aceptaciones legales todavía no fue instalada.',
                'code' => 'LEGAL_MIGRATION_REQUIRED',
            ], 503);
        }

        $termsVersion = (string) config('motrix_legal.terms.version');
        $privacyVersion = (string) config('motrix_legal.privacy.version');

        $data = $request->validate([
            'accepted_terms' => ['required', 'accepted'],
            'accepted_privacy' => ['required', 'accepted'],
            'terms_version' => [
                'required',
                'string',
                Rule::in([$termsVersion]),
            ],
            'privacy_version' => [
                'required',
                'string',
                Rule::in([$privacyVersion]),
            ],
            'channel' => [
                'nullable',
                'string',
                Rule::in([
                    'web',
                    'pwa',
                    'android',
                    'ios',
                ]),
            ],
        ], [
            'accepted_terms.accepted' => 'Debes aceptar los Términos y Condiciones.',
            'accepted_privacy.accepted' => 'Debes aceptar la Política de Privacidad.',
            'terms_version.in' => 'La versión de los Términos y Condiciones ya no es la vigente.',
            'privacy_version.in' => 'La versión de la Política de Privacidad ya no es la vigente.',
        ]);

        $acceptance = MotrixLegalAcceptance::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'terms_version' => $termsVersion,
                'privacy_version' => $privacyVersion,
            ],
            [
                'role_at_acceptance' => $role,
                'accepted_at' => now(),
                'channel' => $data['channel'] ?? 'web',
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr(
                    (string) $request->userAgent(),
                    0,
                    2000
                ),
            ]
        );

        return response()->json([
            'message' => 'Términos y Política de Privacidad aceptados correctamente.',
            'data' => [
                'accepted' => true,
                'terms_version' => $termsVersion,
                'privacy_version' => $privacyVersion,
                'accepted_at' => $acceptance->accepted_at?->toIso8601String(),
            ],
        ]);
    }
}
