<?php

namespace App\Http\Controllers;

use App\Services\MotrixSubscriptionAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MotrixSubscriptionAutomationController extends Controller
{
    public function __construct(
        private readonly MotrixSubscriptionAutomationService $automationService
    ) {
    }

    public function sincronizar(
        Request $request
    ): JsonResponse {
        $resultado =
            $this->automationService->ejecutar(
                'manual',
                $request->user()?->id
            );

        if ($resultado['ocupada'] ?? false) {
            return response()->json([
                'mensaje' => $resultado['mensaje'],
                'data' => $resultado,
            ], 409);
        }

        $fallida = in_array(
            $resultado['estado'] ?? '',
            ['Fallida', 'No configurada'],
            true
        );

        return response()->json([
            'mensaje' => $fallida
                ? ($resultado['mensaje']
                    ?? 'No se pudo completar la automatización MOTRIX.')
                : 'Suscripciones MOTRIX sincronizadas y alertas procesadas correctamente.',
            'data' => $resultado,
        ], $fallida ? 500 : 200);
    }

    public function estado(): JsonResponse
    {
        return response()->json([
            'data' =>
                $this->automationService->estado(),
        ]);
    }
}
