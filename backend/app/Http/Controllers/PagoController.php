<?php

namespace App\Http\Controllers;

use App\Models\Pago;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    /**
     * Listar pagos con toda la información requerida por PagosPage.vue.
     */
    public function index()
    {
        $pagos = Pago::with([
            'servicio.mototaxista.persona',
            'servicio.solicitud.pasajero.persona',
            'servicio.solicitud.mototaxista.persona',
        ])
            ->orderByDesc('id')
            ->get();

        return response()->json($pagos, 200);
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
                'in:Efectivo,QR,Transferencia,Transferencia / QR',
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
                'in:Efectivo,QR,Transferencia,Transferencia / QR',
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
