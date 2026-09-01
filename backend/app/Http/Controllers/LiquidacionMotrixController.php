<?php

namespace App\Http\Controllers;

use App\Models\LiquidacionMotrix;
use App\Models\TransferenciaLiquidacionMotrix;
use App\Services\MotrixComprobanteStorageService;
use App\Services\MotrixLiquidationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LiquidacionMotrixController extends Controller
{
    public function __construct(
        private readonly MotrixLiquidationService $liquidationService,
        private readonly MotrixComprobanteStorageService $storageService
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
                Rule::in([
                    MotrixLiquidationService::LIQUIDACION_PENDIENTE,
                    MotrixLiquidationService::LIQUIDACION_PARCIAL,
                    MotrixLiquidationService::LIQUIDACION_LIQUIDADA,
                ]),
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

        $query = LiquidacionMotrix::query()
            ->with([
                'sindicato:id,nombre',
                'registradoPor:id,name,email',
                'validadoPor:id,name,email',
            ])
            ->withCount([
                'detalles',
                'transferencias',
            ])
            ->withSum(
                [
                    'transferencias as monto_pendiente_validacion' =>
                        fn ($q) =>
                            $q->where(
                                'estado',
                                MotrixLiquidationService::TRANSFERENCIA_PENDIENTE
                            ),
                ],
                'monto'
            );

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

        if (! empty($datos['periodo'])) {
            $query->where(
                'periodo',
                $datos['periodo']
            );
        }

        if (! empty($datos['estado'])) {
            $query->where(
                'estado',
                $datos['estado']
            );
        }

        $paginador = $query
            ->orderByDesc('periodo')
            ->orderByDesc('id')
            ->paginate(
                (int) (
                    $datos['per_page']
                    ?? 20
                )
            );

        $items = collect(
            $paginador->items()
        )
            ->map(
                fn (LiquidacionMotrix $liquidacion) =>
                    $this->presentarResumenLiquidacion(
                        $liquidacion
                    )
            )
            ->values();

        return response()->json([
            'data' => $items,
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

    public function resumen(
        Request $request
    ): JsonResponse {
        $datos = $request->validate([
            'id_sindicato' => [
                'nullable',
                'integer',
                'exists:sindicatos,id',
            ],
            'periodo' => [
                'required',
                'regex:/^\d{4}-\d{2}$/',
            ],
        ]);

        $sindicatoId =
            $this->resolverSindicatoObjetivo(
                $request,
                $datos['id_sindicato']
                ?? null
            );

        return response()->json([
            'data' =>
                $this->liquidationService
                    ->resumenPeriodo(
                        $sindicatoId,
                        $datos['periodo']
                    ),
        ]);
    }

    public function preparar(
        Request $request
    ): JsonResponse {
        $datos = $request->validate([
            'id_sindicato' => [
                'nullable',
                'integer',
                'exists:sindicatos,id',
            ],
            'periodo' => [
                'required',
                'regex:/^\d{4}-\d{2}$/',
            ],
        ]);

        $sindicatoId =
            $this->resolverSindicatoObjetivo(
                $request,
                $datos['id_sindicato']
                ?? null
            );

        $liquidacion =
            $this->liquidationService
                ->sincronizarLiquidacion(
                    $sindicatoId,
                    $datos['periodo'],
                    $request->user()?->id
                );

        return response()->json([
            'mensaje' =>
                'Liquidación MOTRIX preparada y conciliada correctamente.',
            'data' =>
                $this->presentarLiquidacion(
                    $liquidacion
                ),
        ], 201);
    }

    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $query =
            LiquidacionMotrix::query()
                ->where('id', $id);

        $this->aplicarAlcanceSindicato(
            $request,
            $query
        );

        $liquidacion =
            $query->firstOrFail();

        $liquidacion =
            $this->liquidationService
                ->cargarRelaciones(
                    $liquidacion
                );

        return response()->json([
            'data' =>
                $this->presentarLiquidacion(
                    $liquidacion
                ),
        ]);
    }

    public function registrarTransferencia(
        Request $request,
        int $id
    ): JsonResponse {
        $datos = $request->validate([
            'monto' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],
            'forma_pago' => [
                'required',
                Rule::in([
                    'Transferencia',
                    'QR',
                    'Deposito',
                    'Efectivo',
                    'Otro',
                ]),
            ],
            'referencia' => [
                'nullable',
                'string',
                'max:150',
            ],
            'comprobante_url' => [
                'nullable',
                'url',
                'max:2000',
            ],
            'fecha_transferencia' => [
                'required',
                'date',
            ],
            'observacion' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $query =
            LiquidacionMotrix::query()
                ->where('id', $id);

        $this->aplicarAlcanceSindicato(
            $request,
            $query
        );

        $liquidacion =
            $query->firstOrFail();

        $transferencia =
            $this->liquidationService
                ->registrarTransferencia(
                    $liquidacion,
                    $datos,
                    $request->user()?->id
                );

        return response()->json([
            'mensaje' =>
                'Transferencia registrada. Queda pendiente de validación por MOTRIX.',
            'data' =>
                $transferencia,
        ], 201);
    }

    public function subirComprobante(
        Request $request
    ): JsonResponse {
        $datos = $request->validate([
            'comprobante' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,webp,pdf',
                'max:5120',
            ],
        ], [
            'comprobante.required' =>
                'Selecciona un comprobante.',
            'comprobante.mimes' =>
                'El comprobante debe ser JPG, JPEG, PNG, WEBP o PDF.',
            'comprobante.max' =>
                'El comprobante no puede superar los 5 MB.',
        ]);

        $url =
            $this->storageService
                ->subir(
                    $datos['comprobante']
                );

        return response()->json([
            'mensaje' =>
                'Comprobante almacenado correctamente.',
            'url' =>
                $url,
        ], 201);
    }

    public function validarTransferencia(
        Request $request,
        int $id
    ): JsonResponse {
        $datos = $request->validate([
            'observacion' => [
                'nullable',
                'string',
                'max:500',
            ],
        ]);

        $transferencia =
            TransferenciaLiquidacionMotrix::query()
                ->findOrFail($id);

        $transferencia =
            $this->liquidationService
                ->validarTransferencia(
                    $transferencia,
                    $request->user()?->id,
                    $datos['observacion']
                    ?? null
                );

        return response()->json([
            'mensaje' =>
                'Transferencia validada correctamente.',
            'data' =>
                $transferencia,
        ]);
    }

    public function observarTransferencia(
        Request $request,
        int $id
    ): JsonResponse {
        $datos = $request->validate([
            'observacion' => [
                'required',
                'string',
                'min:3',
                'max:500',
            ],
        ]);

        $transferencia =
            TransferenciaLiquidacionMotrix::query()
                ->findOrFail($id);

        $transferencia =
            $this->liquidationService
                ->observarTransferencia(
                    $transferencia,
                    $request->user()?->id,
                    $datos['observacion']
                );

        return response()->json([
            'mensaje' =>
                'Transferencia observada. El monto vuelve a quedar disponible para regularización.',
            'data' =>
                $transferencia,
        ]);
    }

    private function presentarResumenLiquidacion(
        LiquidacionMotrix $liquidacion
    ): array {
        $pendienteValidacion =
            (float) (
                $liquidacion
                    ->monto_pendiente_validacion
                ?? 0
            );

        $declarado =
            (float)
            $liquidacion->monto_declarado;

        $transferido =
            (float)
            $liquidacion->monto_transferido;

        return [
            'id' =>
                (int) $liquidacion->id,
            'id_sindicato' =>
                (int) $liquidacion
                    ->id_sindicato,
            'sindicato' =>
                $liquidacion
                    ->sindicato,
            'periodo' =>
                $liquidacion->periodo,
            'monto_declarado' =>
                number_format(
                    $declarado,
                    2,
                    '.',
                    ''
                ),
            'monto_transferido' =>
                number_format(
                    $transferido,
                    2,
                    '.',
                    ''
                ),
            'monto_pendiente_validacion' =>
                number_format(
                    $pendienteValidacion,
                    2,
                    '.',
                    ''
                ),
            'saldo_pendiente' =>
                number_format(
                    max(
                        0,
                        $declarado
                        - $transferido
                    ),
                    2,
                    '.',
                    ''
                ),
            'disponible_transferir' =>
                number_format(
                    max(
                        0,
                        $declarado
                        - $transferido
                        - $pendienteValidacion
                    ),
                    2,
                    '.',
                    ''
                ),
            'estado' =>
                $liquidacion->estado,
            'pagos_incluidos' =>
                (int) (
                    $liquidacion
                        ->detalles_count
                    ?? 0
                ),
            'transferencias_count' =>
                (int) (
                    $liquidacion
                        ->transferencias_count
                    ?? 0
                ),
            'registrado_por' =>
                $liquidacion
                    ->registradoPor,
            'validado_por' =>
                $liquidacion
                    ->validadoPor,
            'created_at' =>
                $liquidacion->created_at,
            'updated_at' =>
                $liquidacion->updated_at,
        ];
    }

    private function presentarLiquidacion(
        LiquidacionMotrix $liquidacion
    ): array {
        $liquidacion =
            $this->liquidationService
                ->cargarRelaciones(
                    $liquidacion
                );

        $datos =
            $liquidacion->toArray();

        $pendienteValidacion =
            (float)
            $liquidacion
                ->transferencias()
                ->where(
                    'estado',
                    MotrixLiquidationService::TRANSFERENCIA_PENDIENTE
                )
                ->sum('monto');

        $declarado =
            (float)
            $liquidacion
                ->monto_declarado;

        $transferido =
            (float)
            $liquidacion
                ->monto_transferido;

        $datos['saldo_pendiente'] =
            number_format(
                max(
                    0,
                    $declarado
                    - $transferido
                ),
                2,
                '.',
                ''
            );

        $datos[
            'monto_pendiente_validacion'
        ] =
            number_format(
                $pendienteValidacion,
                2,
                '.',
                ''
            );

        $datos[
            'disponible_transferir'
        ] =
            number_format(
                max(
                    0,
                    $declarado
                    - $transferido
                    - $pendienteValidacion
                ),
                2,
                '.',
                ''
            );

        $datos['pagos_incluidos'] =
            $liquidacion
                ->detalles
                ->count();

        return $datos;
    }

    private function resolverSindicatoObjetivo(
        Request $request,
        ?int $solicitado
    ): int {
        if (
            $this->rol($request)
            === 'secretario'
        ) {
            $propio =
                $this->sindicatoUsuario(
                    $request
                );

            if (
                $solicitado
                && $solicitado !== $propio
            ) {
                abort(
                    403,
                    'No puedes gestionar liquidaciones de otro sindicato.'
                );
            }

            return $propio;
        }

        abort_if(
            ! $solicitado,
            422,
            'Selecciona un sindicato.'
        );

        return (int) $solicitado;
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

        $query->where(
            'id_sindicato',
            $this->sindicatoUsuario(
                $request
            )
        );
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
        $sindicatoId =
            (int) (
                $request->user()
                    ?->sindicato_id
                ?? 0
            );

        abort_if(
            $sindicatoId <= 0,
            403,
            'La cuenta de secretario no está vinculada a un sindicato.'
        );

        return $sindicatoId;
    }
}
