<?php

namespace App\Http\Controllers;

use App\Services\MotrixSubscriptionDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MotrixSubscriptionDashboardController extends Controller
{
    public function __construct(
        private readonly MotrixSubscriptionDashboardService $dashboardService
    ) {
    }

    public function resumen(
        Request $request
    ): JsonResponse {
        $datos = $request->validate([
            'periodo' => [
                'nullable',
                'regex:/^\d{4}-\d{2}$/',
            ],
            'id_sindicato' => [
                'nullable',
                'integer',
                'exists:sindicatos,id',
            ],
        ]);

        $rol = strtolower(
            trim(
                (string) (
                    $request->user()?->role
                    ?? ''
                )
            )
        );

        $periodo =
            $datos['periodo']
            ?? now()->format('Y-m');

        if ($rol === 'secretario') {
            $sindicatoId =
                (int) (
                    $request
                        ->user()
                        ?->sindicato_id
                    ?? 0
                );

            abort_if(
                $sindicatoId <= 0,
                403,
                'La cuenta de secretario no está vinculada a un sindicato.'
            );
        } else {
            $sindicatoId =
                isset(
                    $datos['id_sindicato']
                )
                    ? (int) $datos[
                        'id_sindicato'
                    ]
                    : null;
        }

        return response()->json([
            'data' =>
                $this->dashboardService
                    ->resumen(
                        $periodo,
                        $sindicatoId
                    ),
        ]);
    }
}
