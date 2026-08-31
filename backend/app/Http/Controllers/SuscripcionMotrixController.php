<?php

namespace App\Http\Controllers;

use App\Models\Mototaxista;
use App\Models\PlanSuscripcionMotrix;
use App\Models\SuscripcionMotrix;
use App\Services\MotrixSubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SuscripcionMotrixController extends Controller
{
    public function __construct(
        private readonly MotrixSubscriptionService $subscriptionService
    ) {
    }

    public function planes(
        Request $request
    ): JsonResponse {
        $datos = $request->validate([
            'incluir_inactivos' => [
                'nullable',
                'boolean',
            ],
        ]);

        $query = PlanSuscripcionMotrix::query()
            ->orderBy('monto')
            ->orderBy('id');

        $incluirInactivos =
            $request->boolean('incluir_inactivos')
            && $this->rol($request) === 'admin_general';

        if (! $incluirInactivos) {
            $query->where('activo', true);
        }

        return response()->json([
            'data' => $query->get(),
        ]);
    }

    public function storePlan(
        Request $request
    ): JsonResponse {
        $datos = $this->validarPlan($request);

        $plan = PlanSuscripcionMotrix::create([
            ...$datos,
            'creado_por' => $request->user()?->id,
            'actualizado_por' => $request->user()?->id,
        ]);

        return response()->json([
            'mensaje' =>
                'Plan de suscripción MOTRIX creado correctamente.',
            'data' => $plan,
        ], 201);
    }

    public function updatePlan(
        Request $request,
        int $id
    ): JsonResponse {
        $plan =
            PlanSuscripcionMotrix::query()
                ->findOrFail($id);

        $datos =
            $this->validarPlan(
                $request,
                false
            );

        $datos['actualizado_por'] =
            $request->user()?->id;

        $plan->update($datos);

        return response()->json([
            'mensaje' =>
                'Plan de suscripción MOTRIX actualizado correctamente.',
            'data' => $plan->fresh(),
        ]);
    }

    public function index(
        Request $request
    ): JsonResponse {
        $datos = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
            'id_sindicato' => [
                'nullable',
                'integer',
                'exists:sindicatos,id',
            ],
            'estado' => [
                'nullable',
                'string',
                'max:50',
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

        $query = SuscripcionMotrix::query()
            ->with([
                'plan',
                'sindicato:id,nombre',
                'mototaxista:id,id_persona,id_sindicato,nro_chaleco,telefono,estado',
                'mototaxista.persona:id,nombre,apellidos,ci,telefono',
            ]);

        $this->aplicarAlcanceSindicato(
            $request,
            $query
        );

        if (
            isset($datos['id_sindicato'])
            && $this->rol($request) !== 'secretario'
        ) {
            $query->where(
                'id_sindicato',
                (int) $datos['id_sindicato']
            );
        }

        $texto = trim(
            (string) ($datos['q'] ?? '')
        );

        if ($texto !== '') {
            $terminos = array_values(
                array_filter(
                    preg_split('/\s+/u', $texto) ?: []
                )
            );

            foreach (
                array_slice($terminos, 0, 5)
                as $termino
            ) {
                $patron = '%' . $termino . '%';

                $query->where(
                    function ($consulta) use (
                        $patron
                    ) {
                        $consulta
                            ->whereHas(
                                'mototaxista',
                                function ($mototaxista) use (
                                    $patron
                                ) {
                                    $mototaxista
                                        ->where(
                                            'nro_chaleco',
                                            'like',
                                            $patron
                                        )
                                        ->orWhere(
                                            'telefono',
                                            'like',
                                            $patron
                                        )
                                        ->orWhereHas(
                                            'persona',
                                            function ($persona) use (
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
                            )
                            ->orWhereHas(
                                'sindicato',
                                function ($sindicato) use (
                                    $patron
                                ) {
                                    $sindicato->where(
                                        'nombre',
                                        'like',
                                        $patron
                                    );
                                }
                            );
                    }
                );
            }
        }

        if (
            isset($datos['estado'])
            && trim((string) $datos['estado']) !== ''
            && trim((string) $datos['estado']) !== 'Todos'
        ) {
            $query->where(
                'estado',
                trim((string) $datos['estado'])
            );
        }

        $paginador = $query
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->paginate(
                (int) ($datos['per_page'] ?? 12)
            );

        $items = collect(
            $paginador->items()
        )->map(
            fn (SuscripcionMotrix $suscripcion) =>
                $this->serializarSuscripcion(
                    $suscripcion
                )
        )->values();

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
                'from' =>
                    $paginador->firstItem(),
                'to' =>
                    $paginador->lastItem(),
            ],
        ]);
    }

    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $query = SuscripcionMotrix::query()
            ->where('id', $id)
            ->with([
                'plan',
                'sindicato:id,nombre',
                'mototaxista:id,id_persona,id_sindicato,nro_chaleco,telefono,estado',
                'mototaxista.persona:id,nombre,apellidos,ci,telefono',
                'pagos' => function ($pagos) {
                    $pagos
                        ->orderByDesc('fecha_vencimiento')
                        ->orderByDesc('id')
                        ->limit(24);
                },
            ]);

        $this->aplicarAlcanceSindicato(
            $request,
            $query
        );

        $suscripcion = $query->firstOrFail();

        return response()->json([
            'data' =>
                $this->serializarSuscripcion(
                    $suscripcion,
                    true
                ),
        ]);
    }

    public function configurar(
        Request $request
    ): JsonResponse {
        $datos = $request->validate([
            'id_mototaxista' => [
                'required',
                'integer',
                'exists:mototaxistas,id',
            ],
            'plan_id' => [
                'required',
                'integer',
                'exists:planes_suscripcion_motrix,id',
            ],
            'fecha_inicio' => [
                'required',
                'date',
            ],
        ]);

        $mototaxista = Mototaxista::query()
            ->with([
                'persona:id,nombre,apellidos,ci',
                'sindicato:id,nombre',
            ])
            ->findOrFail(
                (int) $datos['id_mototaxista']
            );

        $this->autorizarMototaxista(
            $request,
            $mototaxista
        );

        $plan = PlanSuscripcionMotrix::query()
            ->where('activo', true)
            ->findOrFail(
                (int) $datos['plan_id']
            );

        $inicio = Carbon::parse(
            $datos['fecha_inicio']
        )->startOfDay();

        $vencimiento = $this->subscriptionService
            ->proximoVencimiento(
                $inicio,
                (int) $plan->duracion_meses
            )
            ->subDay()
            ->startOfDay();

        $suscripcion = DB::transaction(
            function () use (
                $request,
                $mototaxista,
                $plan,
                $inicio,
                $vencimiento
            ) {
                $suscripcion =
                    SuscripcionMotrix::query()
                        ->where(
                            'id_mototaxista',
                            $mototaxista->id
                        )
                        ->first();

                if (! $suscripcion) {
                    $suscripcion =
                        new SuscripcionMotrix();

                    $suscripcion->creado_por =
                        $request->user()?->id;
                }

                $suscripcion->fill([
                    'id_mototaxista' =>
                        (int) $mototaxista->id,
                    'id_sindicato' =>
                        (int) $mototaxista->id_sindicato,
                    'plan_id' =>
                        (int) $plan->id,
                    'fecha_inicio' =>
                        $inicio->toDateString(),
                    'fecha_vencimiento' =>
                        $vencimiento->toDateString(),
                    'estado' =>
                        MotrixSubscriptionService::ESTADO_ACTIVA,
                    'renovacion_automatica' =>
                        false,
                    'suspendida_en' =>
                        null,
                    'motivo_suspension' =>
                        null,
                    'actualizado_por' =>
                        $request->user()?->id,
                ]);

                $suscripcion->save();

                return $suscripcion;
            }
        );

        $suscripcion->load([
            'plan',
            'sindicato:id,nombre',
            'mototaxista:id,id_persona,id_sindicato,nro_chaleco,telefono,estado',
            'mototaxista.persona:id,nombre,apellidos,ci,telefono',
        ]);

        return response()->json([
            'mensaje' =>
                'Suscripción MOTRIX configurada correctamente.',
            'data' =>
                $this->serializarSuscripcion(
                    $suscripcion
                ),
        ], 201);
    }

    public function miSuscripcion(
        Request $request
    ): JsonResponse {
        $mototaxistaId = (int) (
            $request->user()?->mototaxista_id
            ?? 0
        );

        abort_if(
            $mototaxistaId <= 0,
            403,
            'La cuenta de conductor no está vinculada a un mototaxista.'
        );

        $suscripcion = SuscripcionMotrix::query()
            ->where(
                'id_mototaxista',
                $mototaxistaId
            )
            ->with([
                'plan',
                'sindicato:id,nombre',
                'pagos' => function ($pagos) {
                    $pagos
                        ->orderByDesc('fecha_vencimiento')
                        ->orderByDesc('id')
                        ->limit(12);
                },
            ])
            ->first();

        if (! $suscripcion) {
            return response()->json([
                'configurada' => false,
                'mensaje' =>
                    'Todavía no tienes una suscripción MOTRIX configurada.',
                'data' => null,
            ]);
        }

        return response()->json([
            'configurada' => true,
            'data' =>
                $this->serializarSuscripcion(
                    $suscripcion,
                    true
                ),
        ]);
    }

    private function validarPlan(
        Request $request,
        bool $crear = true
    ): array {
        $requerido =
            $crear
                ? 'required'
                : 'sometimes';

        return $request->validate([
            'nombre' => [
                $requerido,
                'string',
                'min:3',
                'max:100',
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:500',
            ],
            'monto' => [
                $requerido,
                'numeric',
                'min:0.01',
                'max:99999999.99',
            ],
            'duracion_meses' => [
                $requerido,
                'integer',
                'min:1',
                'max:24',
            ],
            'dias_gracia' => [
                $requerido,
                'integer',
                'min:0',
                'max:30',
            ],
            'aviso_dias_antes' => [
                $requerido,
                'integer',
                'min:0',
                'max:30',
            ],
            'activo' => [
                $crear
                    ? 'nullable'
                    : 'sometimes',
                'boolean',
            ],
        ]);
    }

    private function serializarSuscripcion(
        SuscripcionMotrix $suscripcion,
        bool $incluirPagos = false
    ): array {
        $estadoCalculado =
            $this->subscriptionService
                ->estadoActual(
                    $suscripcion
                );

        $fechaVencimiento =
            $suscripcion->fecha_vencimiento;

        $diasRestantes = 0;

        if ($fechaVencimiento) {
            $diasRestantes = now()
                ->startOfDay()
                ->diffInDays(
                    Carbon::parse(
                        $fechaVencimiento
                    )->startOfDay(),
                    false
                );
        }

        $data = [
            'id' =>
                (int) $suscripcion->id,
            'id_mototaxista' =>
                (int) $suscripcion->id_mototaxista,
            'id_sindicato' =>
                (int) $suscripcion->id_sindicato,
            'plan_id' =>
                (int) $suscripcion->plan_id,
            'fecha_inicio' =>
                $suscripcion->fecha_inicio?->toDateString(),
            'fecha_vencimiento' =>
                $suscripcion->fecha_vencimiento?->toDateString(),
            'estado' =>
                $suscripcion->estado,
            'estado_calculado' =>
                $estadoCalculado,
            'dias_restantes' =>
                $diasRestantes,
            'renovacion_automatica' =>
                (bool) $suscripcion->renovacion_automatica,
            'plan' =>
                $suscripcion->plan,
            'sindicato' =>
                $suscripcion->sindicato,
            'mototaxista' =>
                $suscripcion->relationLoaded('mototaxista')
                    ? $suscripcion->mototaxista
                    : null,
        ];

        if ($incluirPagos) {
            $data['pagos'] =
                $suscripcion->relationLoaded('pagos')
                    ? $suscripcion->pagos
                    : [];
        }

        return $data;
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
            'Solo puedes administrar suscripciones MOTRIX de mototaxistas de tu sindicato.'
        );
    }
}
