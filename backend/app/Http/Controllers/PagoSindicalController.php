<?php

namespace App\Http\Controllers;

use App\Models\Mototaxista;
use App\Models\PagoSindical;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PagoSindicalController extends Controller
{
    private const TIPOS = [
        'Afiliación',
        'Inscripción',
        'Aporte',
        'Otro',
    ];

    private const ESTADOS = [
        'Pagado',
        'Pendiente',
        'Anulado',
    ];

    private const FORMAS = [
        'Efectivo',
        'QR',
        'Transferencia',
        'Otro',
    ];

    public function index(
        Request $request
    ): JsonResponse {
        $query = PagoSindical::query()
            ->with([
                'sindicato:id,nombre',
                'mototaxista:id,id_persona,id_sindicato,nro_chaleco,estado',
                'mototaxista.persona:id,nombre,apellidos,ci,telefono',
                'registradoPor:id,name,email',
            ]);

        $this->aplicarAlcanceSindicato(
            $request,
            $query
        );

        if ($request->filled('id_sindicato')) {
            $query->where(
                'id_sindicato',
                (int) $request->input(
                    'id_sindicato'
                )
            );
        }

        if ($request->filled('id_mototaxista')) {
            $query->where(
                'id_mototaxista',
                (int) $request->input(
                    'id_mototaxista'
                )
            );
        }

        if ($request->filled('tipo_pago')) {
            $query->where(
                'tipo_pago',
                $request->input('tipo_pago')
            );
        }

        if ($request->filled('estado_pago')) {
            $query->where(
                'estado_pago',
                $request->input('estado_pago')
            );
        }

        if ($request->filled('periodo')) {
            $query->where(
                'periodo',
                $request->input('periodo')
            );
        }

        $pagos = $query
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        return response()->json($pagos);
    }

    public function store(
        Request $request
    ): JsonResponse {
        $datos = $this->validarDatos(
            $request
        );

        $mototaxista = Mototaxista::query()
            ->with('sindicato:id,nombre')
            ->findOrFail(
                (int) $datos['id_mototaxista']
            );

        $this->autorizarMototaxista(
            $request,
            $mototaxista
        );

        $pago = PagoSindical::create([
            'id_sindicato' =>
                (int) $mototaxista->id_sindicato,
            'id_mototaxista' =>
                (int) $mototaxista->id,
            'tipo_pago' =>
                $datos['tipo_pago'],
            'monto' =>
                $datos['monto'],
            'fecha' =>
                $datos['fecha'],
            'periodo' =>
                $datos['periodo'] ?? null,
            'estado_pago' =>
                $datos['estado_pago'],
            'forma_pago' =>
                $datos['forma_pago'],
            'observacion' =>
                $datos['observacion'] ?? null,
            'registrado_por' =>
                $request->user()?->id,
        ]);

        return response()->json([
            'mensaje' =>
                'Pago sindical registrado correctamente.',
            'data' =>
                $this->cargarRelaciones($pago),
        ], 201);
    }

    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $pago = $this->buscarPagoAutorizado(
            $request,
            $id
        );

        if (
            $pago->estado_pago === 'Anulado'
        ) {
            return response()->json([
                'mensaje' =>
                    'Un pago anulado no puede editarse.',
            ], 409);
        }

        $datos = $this->validarDatos(
            $request,
            false
        );

        /*
         * Por trazabilidad, un registro financiero no se mueve
         * a otro mototaxista después de ser creado.
         */
        unset(
            $datos['id_mototaxista']
        );

        $pago->update([
            'tipo_pago' =>
                $datos['tipo_pago'],
            'monto' =>
                $datos['monto'],
            'fecha' =>
                $datos['fecha'],
            'periodo' =>
                $datos['periodo'] ?? null,
            'estado_pago' =>
                $datos['estado_pago'],
            'forma_pago' =>
                $datos['forma_pago'],
            'observacion' =>
                $datos['observacion'] ?? null,
        ]);

        return response()->json([
            'mensaje' =>
                'Pago sindical actualizado correctamente.',
            'data' =>
                $this->cargarRelaciones($pago),
        ]);
    }

    public function anular(
        Request $request,
        int $id
    ): JsonResponse {
        $pago = $this->buscarPagoAutorizado(
            $request,
            $id
        );

        if (
            $pago->estado_pago === 'Anulado'
        ) {
            return response()->json([
                'mensaje' =>
                    'Este pago ya se encuentra anulado.',
                'data' =>
                    $this->cargarRelaciones($pago),
            ]);
        }

        $datos = $request->validate([
            'motivo' => [
                'nullable',
                'string',
                'max:200',
            ],
        ]);

        $motivo = trim(
            (string) (
                $datos['motivo']
                ?? ''
            )
        );

        $observacionAnterior = trim(
            (string) (
                $pago->observacion
                ?? ''
            )
        );

        $textoAnulacion =
            $motivo !== ''
                ? "Anulado: {$motivo}"
                : 'Registro anulado.';

        $pago->update([
            'estado_pago' =>
                'Anulado',
            'observacion' =>
                trim(
                    $observacionAnterior
                    . (
                        $observacionAnterior !== ''
                            ? ' | '
                            : ''
                    )
                    . $textoAnulacion
                ),
        ]);

        return response()->json([
            'mensaje' =>
                'Pago sindical anulado correctamente.',
            'data' =>
                $this->cargarRelaciones($pago),
        ]);
    }

    private function validarDatos(
        Request $request,
        bool $requiereMototaxista = true
    ): array {
        return $request->validate([
            'id_mototaxista' => [
                $requiereMototaxista
                    ? 'required'
                    : 'sometimes',
                'integer',
                'exists:mototaxistas,id',
            ],
            'tipo_pago' => [
                'required',
                'string',
                Rule::in(self::TIPOS),
            ],
            'monto' => [
                'required',
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],
            'fecha' => [
                'required',
                'date',
            ],
            'periodo' => [
                'nullable',
                'string',
                'max:20',
            ],
            'estado_pago' => [
                'required',
                'string',
                Rule::in(self::ESTADOS),
            ],
            'forma_pago' => [
                'required',
                'string',
                Rule::in(self::FORMAS),
            ],
            'observacion' => [
                'nullable',
                'string',
                'max:255',
            ],
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

    private function autorizarMototaxista(
        Request $request,
        Mototaxista $mototaxista
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

        abort_unless(
            (int) $mototaxista->id_sindicato
                === $sindicatoId,
            403,
            'Solo puedes registrar pagos de mototaxistas de tu sindicato.'
        );
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

    private function buscarPagoAutorizado(
        Request $request,
        int $id
    ): PagoSindical {
        $query = PagoSindical::query()
            ->where('id', $id);

        $this->aplicarAlcanceSindicato(
            $request,
            $query
        );

        return $query->firstOrFail();
    }

    private function cargarRelaciones(
        PagoSindical $pago
    ): PagoSindical {
        return $pago->fresh([
            'sindicato:id,nombre',
            'mototaxista:id,id_persona,id_sindicato,nro_chaleco,estado',
            'mototaxista.persona:id,nombre,apellidos,ci,telefono',
            'registradoPor:id,name,email',
        ]);
    }
}
