<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PagoController extends Controller
{
    /**
     * Listar pagos con toda la información requerida por PagosPage.vue.
     */
    public function index(
        Request $request
    ) {
        $datos = $request->validate([
            'paginated' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:120'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $consulta = Pago::query();

        $texto = trim(
            (string) ($datos['q'] ?? '')
        );

        if ($texto !== '') {
            $terminos = array_values(
                array_filter(
                    preg_split('/\\s+/u', $texto) ?: []
                )
            );

            foreach (
                array_slice($terminos, 0, 5)
                as $termino
            ) {
                $consulta->where(
                    function ($query) use (
                        $termino
                    ) {
                        $patron =
                            '%' . $termino . '%';

                        $query
                            ->where(
                                'metodo',
                                'like',
                                $patron
                            )
                            ->orWhere(
                                'estado',
                                'like',
                                $patron
                            )
                            ->orWhereHas(
                                'servicio.solicitud',
                                function (
                                    $solicitud
                                ) use (
                                    $patron
                                ) {
                                    $solicitud
                                        ->where(
                                            'origen',
                                            'like',
                                            $patron
                                        )
                                        ->orWhere(
                                            'destino',
                                            'like',
                                            $patron
                                        );
                                }
                            )
                            ->orWhereHas(
                                'servicio.mototaxista.persona',
                                function (
                                    $persona
                                ) use (
                                    $patron
                                ) {
                                    $persona
                                        ->where(
                                            'nombre',
                                            'like',
                                            $patron
                                        )
                                        ->orWhere(
                                            'apellidos',
                                            'like',
                                            $patron
                                        )
                                        ->orWhere(
                                            'ci',
                                            'like',
                                            $patron
                                        );
                                }
                            );
                    }
                );
            }
        }

        $consulta
            ->with([
                'servicio.mototaxista.persona',
                'servicio.solicitud.pasajero.persona',
                'servicio.solicitud.mototaxista.persona',
            ])
            ->orderByDesc('id');

        if (! $request->boolean('paginated')) {
            return response()->json(
                $consulta->get(),
                200
            );
        }

        $completados = Pago::query()
            ->where('estado', 'Completado');

        $estadisticas = [
            'completados' =>
                (clone $completados)->count(),
            'total_recaudado' => (float) (
                (clone $completados)->sum('monto')
            ),
            'total_efectivo' => (float) (
                (clone $completados)
                    ->sum('monto_efectivo')
            ),
            'total_digital' => (float) (
                (clone $completados)
                    ->sum('monto_qr')
            ),
        ];

        $porPagina = (int) (
            $datos['per_page'] ?? 12
        );

        $paginador = $consulta->paginate(
            $porPagina
        );

        return response()->json([
            'data' => $paginador->items(),
            'meta' => [
                'current_page' =>
                    $paginador->currentPage(),
                'last_page' =>
                    $paginador->lastPage(),
                'per_page' =>
                    $paginador->perPage(),
                'total' =>
                    $paginador->total(),
                'from' =>
                    $paginador->firstItem(),
                'to' =>
                    $paginador->lastItem(),
                'stats' =>
                    $estadisticas,
            ],
        ], 200);
    }

    /**
     * Registrar un pago manual.
     *
     * Cada servicio debe tener como máximo un pago asociado.
     * El pago automático del viaje ya usa esta misma regla.
     */
    public function store(Request $request)
    {
        $datos = $request->validate([
            'monto' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'metodo' => [
                'required',
                'string',
                'max:50',
                'in:Efectivo,QR,Transferencia,Transferencia / QR,Mixto',
            ],
            'monto_efectivo' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'monto_qr' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'estado' => [
                'required',
                'string',
                'max:50',
                'in:Pendiente,Completado,Reembolsado',
            ],
            'id_servicio' => [
                'required',
                'integer',
                'exists:servicios,id',
            ],
        ]);

        $pagoExistente = Pago::query()
            ->where(
                'id_servicio',
                $datos['id_servicio']
            )
            ->first();

        if ($pagoExistente) {
            return response()->json([
                'mensaje' => (
                    'Este servicio ya tiene un pago registrado. '
                    . 'Edítalo en lugar de crear otro.'
                ),
                'pago' => $pagoExistente,
            ], 409);
        }

        $datos = $this->normalizarDesglosePago($datos);

        $pago = Pago::create($datos);

        return response()->json(
            $pago->load([
                'servicio.mototaxista.persona',
                'servicio.solicitud.pasajero.persona',
                'servicio.solicitud.mototaxista.persona',
            ]),
            201
        );
    }

    /**
     * Mostrar un pago.
     */
    public function show($id)
    {
        $pago = Pago::with([
            'servicio.mototaxista.persona',
            'servicio.solicitud.pasajero.persona',
            'servicio.solicitud.mototaxista.persona',
        ])->findOrFail($id);

        return response()->json($pago, 200);
    }

    /**
     * Actualizar un pago.
     */
    public function update(Request $request, $id)
    {
        $pago = Pago::findOrFail($id);

        $datos = $request->validate([
            'monto' => [
                'sometimes',
                'required',
                'numeric',
                'min:0.01',
            ],
            'metodo' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'in:Efectivo,QR,Transferencia,Transferencia / QR,Mixto',
            ],
            'monto_efectivo' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'monto_qr' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'estado' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'in:Pendiente,Completado,Reembolsado',
            ],
            'id_servicio' => [
                'sometimes',
                'required',
                'integer',
                'exists:servicios,id',
            ],
        ]);

        if (
            array_key_exists(
                'id_servicio',
                $datos
            )
        ) {
            $pagoDuplicado = Pago::query()
                ->where(
                    'id_servicio',
                    $datos['id_servicio']
                )
                ->where(
                    'id',
                    '<>',
                    $pago->id
                )
                ->first();

            if ($pagoDuplicado) {
                return response()->json([
                    'mensaje' => (
                        'El servicio seleccionado ya tiene '
                        . 'otro pago registrado.'
                    ),
                    'pago' => $pagoDuplicado,
                ], 409);
            }
        }

        $datos = $this->normalizarDesglosePago(
            $datos,
            $pago
        );

        $pago->update($datos);

        return response()->json(
            $pago->load([
                'servicio.mototaxista.persona',
                'servicio.solicitud.pasajero.persona',
                'servicio.solicitud.mototaxista.persona',
            ]),
            200
        );
    }

    private function normalizarDesglosePago(
        array $datos,
        ?Pago $pagoActual = null
    ): array {
        $monto = (float) (
            $datos['monto']
            ?? $pagoActual?->monto
            ?? 0
        );

        $metodo = (string) (
            $datos['metodo']
            ?? $pagoActual?->metodo
            ?? 'Efectivo'
        );

        if ($metodo === 'Mixto') {
            $efectivo = (float) (
                $datos['monto_efectivo']
                ?? $pagoActual?->monto_efectivo
                ?? 0
            );

            $qr = (float) (
                $datos['monto_qr']
                ?? $pagoActual?->monto_qr
                ?? 0
            );

            if ($efectivo <= 0 || $qr <= 0) {
                throw ValidationException::withMessages([
                    'metodo' => [
                        'En un pago mixto debe existir un monto mayor a cero en efectivo y otro en QR.',
                    ],
                ]);
            }

            if (abs(($efectivo + $qr) - $monto) > 0.01) {
                throw ValidationException::withMessages([
                    'monto' => [
                        'La suma de efectivo y QR debe ser igual al monto total del pago.',
                    ],
                ]);
            }

            $datos['monto_efectivo'] = round($efectivo, 2);
            $datos['monto_qr'] = round($qr, 2);

            return $datos;
        }

        if ($metodo === 'Efectivo') {
            $datos['monto_efectivo'] = round($monto, 2);
            $datos['monto_qr'] = 0.00;

            return $datos;
        }

        $datos['monto_efectivo'] = 0.00;
        $datos['monto_qr'] = round($monto, 2);

        return $datos;
    }

    /**
     * Eliminar un pago.
     */
    public function destroy($id)
    {
        $pago = Pago::findOrFail($id);
        $pago->delete();

        return response()->json([
            'mensaje' => 'Pago eliminado correctamente.',
        ], 200);
    }
}
