<?php

namespace App\Http\Controllers;

use App\Models\AlertaSuscripcionMotrix;
use App\Models\PagoSuscripcionMotrix;
use App\Models\SuscripcionMotrix;
use App\Services\MotrixSubscriptionBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PagoSuscripcionMotrixController extends Controller
{
    public function __construct(
        private readonly MotrixSubscriptionBillingService $billingService
    ) {
    }

    public function index(
        Request $request
    ): JsonResponse {
        $datos = $request->validate([
            'id_sindicato' => [
                'nullable',
                'integer',
                'exists:sindicatos,id',
            ],
            'periodo' => [
                'nullable',
                'regex:/^\d{4}-\d{2}$/',
            ],
            'estado' => [
                'nullable',
                'string',
                'max:30',
            ],
            'page' => [
                'nullable',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $query = PagoSuscripcionMotrix::query()
            ->with([
                'suscripcion.plan',
                'mototaxista:id,id_persona,id_sindicato,nro_chaleco,telefono,estado',
                'mototaxista.persona:id,nombre,apellidos,ci,telefono',
                'sindicato:id,nombre',
            ]);

        $this->aplicarAlcanceSindicato(
            $request,
            $query
        );

        if (
            isset($datos['id_sindicato'])
            && $this->rol($request)
                !== 'secretario'
        ) {
            $query->where(
                'id_sindicato',
                (int) $datos['id_sindicato']
            );
        }

        if (
            isset($datos['periodo'])
            && trim(
                (string) $datos['periodo']
            ) !== ''
        ) {
            $query->where(
                'periodo',
                $datos['periodo']
            );
        }

        if (
            isset($datos['estado'])
            && trim(
                (string) $datos['estado']
            ) !== ''
            && trim(
                (string) $datos['estado']
            ) !== 'Todos'
        ) {
            $query->where(
                'estado',
                trim(
                    (string) $datos['estado']
                )
            );
        }

        $paginador = $query
            ->orderByDesc('fecha_vencimiento')
            ->orderByDesc('id')
            ->paginate(
                (int) (
                    $datos['per_page']
                    ?? 20
                )
            );

        return response()->json([
            'data' =>
                $paginador->items(),
            'meta' => [
                'current_page' =>
                    $paginador->currentPage(),
                'last_page' =>
                    $paginador->lastPage(),
                'per_page' =>
                    $paginador->perPage(),
                'total' =>
                    $paginador->total(),
            ],
        ]);
    }

    public function generarRenovacion(
        Request $request,
        int $id
    ): JsonResponse {
        $query = SuscripcionMotrix::query()
            ->where('id', $id)
            ->with('plan');

        $this->aplicarAlcanceSindicato(
            $request,
            $query
        );

        $suscripcion =
            $query->firstOrFail();

        $pago =
            $this->billingService
                ->asegurarRenovacion(
                    $suscripcion
                );

        return response()->json([
            'mensaje' =>
                'Renovación MOTRIX preparada correctamente.',
            'data' =>
                $pago->fresh(),
        ], 201);
    }

    public function registrar(
        Request $request,
        int $id
    ): JsonResponse {
        $datos = $request->validate([
            'monto_pagado' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],
            'forma_pago' => [
                'required',
                Rule::in([
                    'Efectivo',
                    'QR',
                    'Transferencia',
                    'Deposito',
                    'Otro',
                ]),
            ],
            'canal_cobro' => [
                'required',
                Rule::in([
                    MotrixSubscriptionBillingService::CANAL_SINDICATO,
                    MotrixSubscriptionBillingService::CANAL_MOTRIX_DIRECTO,
                ]),
            ],
            'referencia_pago' => [
                'nullable',
                'string',
                'max:150',
            ],
            'comprobante_url' => [
                'nullable',
                'url',
                'max:2000',
            ],
            'observacion' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $query =
            PagoSuscripcionMotrix::query()
                ->where('id', $id);

        $this->aplicarAlcanceSindicato(
            $request,
            $query
        );

        $pago =
            $query->firstOrFail();

        $pago =
            $this->billingService
                ->registrarPago(
                    $pago,
                    $datos,
                    $request->user()?->id
                );

        return response()->json([
            'mensaje' =>
                'Pago de suscripción MOTRIX registrado correctamente.',
            'data' =>
                $pago,
        ]);
    }

    public function misPagos(
        Request $request
    ): JsonResponse {
        $mototaxistaId =
            $this->mototaxistaUsuario(
                $request
            );

        $pagos =
            PagoSuscripcionMotrix::query()
                ->where(
                    'id_mototaxista',
                    $mototaxistaId
                )
                ->orderByDesc(
                    'fecha_vencimiento'
                )
                ->orderByDesc('id')
                ->limit(24)
                ->get();

        return response()->json([
            'data' => $pagos,
        ]);
    }

    public function misAlertas(
        Request $request
    ): JsonResponse {
        $mototaxistaId =
            $this->mototaxistaUsuario(
                $request
            );

        $alertas =
            AlertaSuscripcionMotrix::query()
                ->where(
                    'id_mototaxista',
                    $mototaxistaId
                )
                ->whereIn(
                    'estado',
                    [
                        'Pendiente',
                        'Enviada',
                    ]
                )
                ->orderByDesc(
                    'programada_para'
                )
                ->orderByDesc('id')
                ->limit(20)
                ->get();

        return response()->json([
            'data' => $alertas,
        ]);
    }

    public function marcarAlertaLeida(
        Request $request,
        int $id
    ): JsonResponse {
        $mototaxistaId =
            $this->mototaxistaUsuario(
                $request
            );

        $alerta =
            AlertaSuscripcionMotrix::query()
                ->where('id', $id)
                ->where(
                    'id_mototaxista',
                    $mototaxistaId
                )
                ->firstOrFail();

        $alerta->update([
            'estado' =>
                'Leida',
            'leida_en' =>
                now(),
        ]);

        return response()->json([
            'mensaje' =>
                'Alerta marcada como leída.',
            'data' =>
                $alerta->fresh(),
        ]);
    }

    public function sincronizar(
        Request $request
    ): JsonResponse {
        $resultado =
            $this->billingService
                ->sincronizarTodas();

        return response()->json([
            'mensaje' =>
                'Suscripciones MOTRIX sincronizadas correctamente.',
            'data' =>
                $resultado,
        ]);
    }

    private function rol(
        Request $request
    ): string {
        return strtolower(
            trim(
                (string) (
                    $request->user()?->role
                    ?? ''
                )
            )
        );
    }

    private function sindicatoUsuario(
        Request $request
    ): int {
        return (int) (
            $request->user()?->sindicato_id
            ?? 0
        );
    }

    private function mototaxistaUsuario(
        Request $request
    ): int {
        $mototaxistaId = (int) (
            $request->user()?->mototaxista_id
            ?? 0
        );

        abort_if(
            $mototaxistaId <= 0,
            403,
            'La cuenta de conductor no está vinculada a un mototaxista.'
        );

        return $mototaxistaId;
    }

    private function aplicarAlcanceSindicato(
        Request $request,
        $query
    ): void {
        if (
            $this->rol($request)
            !== 'secretario'
        ) {
            return;
        }

        $sindicatoId =
            $this->sindicatoUsuario(
                $request
            );

        abort_if(
            $sindicatoId <= 0,
            403,
            'La cuenta de secretario no está vinculada a un sindicato.'
        );

        $query->where(
            'id_sindicato',
            $sindicatoId
        );
    }
}
