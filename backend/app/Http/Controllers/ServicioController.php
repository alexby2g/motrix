<?php

namespace App\Http\Controllers;

use App\Http\Requests\ServicioRequest;
use App\Models\Servicio;
use Illuminate\Http\Request;

class ServicioController extends Controller
{
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

        $consulta = Servicio::query();

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
                                'estado',
                                'like',
                                $patron
                            )
                            ->orWhereHas(
                                'solicitud',
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
                                'mototaxista.persona',
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
                'solicitud.pasajero.persona',
                'mototaxista.persona',
            ])
            ->orderByDesc('id');

        if (! $request->boolean('paginated')) {
            return response()->json(
                $consulta->get(),
                200
            );
        }

        $base = Servicio::query();

        $estadisticas = [
            'total' =>
                (clone $base)->count(),
            'activos' =>
                (clone $base)
                    ->whereIn(
                        'estado',
                        [
                            'Activo',
                            'En Curso',
                        ]
                    )
                    ->count(),
            'finalizados' =>
                (clone $base)
                    ->where(
                        'estado',
                        'Finalizado'
                    )
                    ->count(),
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

    public function opcionesPago(
        Request $request
    ) {
        $datos = $request->validate([
            'q' => [
                'nullable',
                'string',
                'max:100',
            ],
            'include_id' => [
                'nullable',
                'integer',
                'exists:servicios,id',
            ],
        ]);

        $texto = trim(
            (string) ($datos['q'] ?? '')
        );

        $includeId = isset($datos['include_id'])
            ? (int) $datos['include_id']
            : null;

        $consulta = Servicio::query()
            ->with([
                'mototaxista.persona',
                'solicitud',
            ])
            ->where(
                function ($query) use (
                    $includeId
                ) {
                    $query->whereDoesntHave(
                        'pago'
                    );

                    if ($includeId) {
                        $query->orWhere(
                            'id',
                            $includeId
                        );
                    }
                }
            );

        if ($texto !== '') {
            $consulta->where(
                function ($query) use (
                    $texto
                ) {
                    $patron =
                        '%' . $texto . '%';

                    if (ctype_digit($texto)) {
                        $query->orWhere(
                            'id',
                            (int) $texto
                        );
                    }

                    $query
                        ->orWhereHas(
                            'mototaxista.persona',
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
                        )
                        ->orWhereHas(
                            'solicitud',
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
                        );
                }
            );
        }

        return response()->json(
            $consulta
                ->orderByDesc('id')
                ->limit(15)
                ->get(),
            200
        );
    }

    public function store(ServicioRequest $request)
    {
        $servicio = Servicio::create(
            $request->validated()
        );

        return response()->json(
            $servicio->load([
                'solicitud.pasajero.persona',
                'mototaxista.persona',
            ]),
            201
        );
    }

    public function show($id)
    {
        return Servicio::query()
            ->with([
                'solicitud.pasajero.persona',
                'mototaxista.persona',
                'pago',
            ])
            ->findOrFail($id);
    }

    public function update(
        ServicioRequest $request,
        $id
    ) {
        $servicio = Servicio::findOrFail($id);

        $servicio->update(
            $request->validated()
        );

        return response()->json(
            $servicio
                ->fresh()
                ->load([
                    'solicitud.pasajero.persona',
                    'mototaxista.persona',
                ]),
            200
        );
    }

    public function destroy($id)
    {
        $servicio = Servicio::query()
            ->with('pago')
            ->findOrFail($id);

        if ($servicio->pago) {
            return response()->json([
                'mensaje' =>
                    'No se puede eliminar un servicio que ya tiene un pago registrado.',
            ], 409);
        }

        $servicio->delete();

        return response()->json([
            'mensaje' =>
                'Servicio eliminado correctamente.',
        ], 200);
    }
}