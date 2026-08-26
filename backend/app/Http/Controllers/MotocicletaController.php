<?php

namespace App\Http\Controllers;

use App\Http\Requests\MotocicletaRequest;
use App\Models\ImagenMotocicleta;
use App\Models\Motocicleta;
use App\Models\Mototaxista;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MotocicletaController extends Controller
{
    public function index(Request $request)
    {
        $datos = $request->validate([
            'paginated' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
            'soat' => ['nullable', 'string', 'max:20'],
            'placa' => ['nullable', 'string', 'max:20'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => [
                'nullable',
                'integer',
                'min:6',
                'max:60',
            ],
        ]);

        $consulta = $this->consultaVisible(
            $request
        );

        /*
         * Las estadísticas se calculan sobre todo el ámbito visible del
         * usuario, no solamente sobre la página que está viendo.
         */
        $baseVisible = clone $consulta;

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
                        $patron = '%' . $termino . '%';

                        $query
                            ->where('placa', 'like', $patron)
                            ->orWhere('modelo', 'like', $patron)
                            ->orWhere('color', 'like', $patron)
                            ->orWhere('chasis', 'like', $patron)
                            ->orWhereHas(
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
                );
            }
        }

        $soat = trim(
            (string) ($datos['soat'] ?? '')
        );

        if ($soat === 'con') {
            $consulta->where('tiene_soat', 1);
        } elseif ($soat === 'sin') {
            $consulta->where('tiene_soat', 0);
        }

        $placa = trim(
            (string) ($datos['placa'] ?? '')
        );

        if ($placa === 'con') {
            $consulta->where('tiene_placa', 1);
        } elseif ($placa === 'sin') {
            $consulta->where('tiene_placa', 0);
        }

        $consulta
            ->with([
                'imagenes',
                'mototaxista.persona.imagenes',
                'mototaxista.sindicato',
            ])
            ->orderByDesc('id');

        if (! $request->boolean('paginated')) {
            return response()->json(
                $consulta->get(),
                200
            );
        }

        $estadisticas = [
            'total' => (clone $baseVisible)->count(),
            'con_placa' => (clone $baseVisible)
                ->where('tiene_placa', 1)
                ->count(),
            'con_soat' => (clone $baseVisible)
                ->where('tiene_soat', 1)
                ->count(),
            'sin_soat' => (clone $baseVisible)
                ->where('tiene_soat', 0)
                ->count(),
        ];

        $paginador = $consulta->paginate(
            (int) ($datos['per_page'] ?? 12)
        );

        return response()->json([
            'data' => $paginador->items(),
            'meta' => [
                'current_page' => $paginador->currentPage(),
                'last_page' => $paginador->lastPage(),
                'per_page' => $paginador->perPage(),
                'total' => $paginador->total(),
                'from' => $paginador->firstItem(),
                'to' => $paginador->lastItem(),
                'stats' => $estadisticas,
            ],
        ], 200);
    }

    public function store(
        MotocicletaRequest $request
    ) {
        $datos = $this->normalizarDatos(
            $request->validated()
        );

        $this->autorizarMototaxista(
            $request,
            (int) $datos['id_mototaxista']
        );

        $motocicleta =
            Motocicleta::create(
                $datos
            );

        return response()->json(
            $this->cargarDetalle(
                $motocicleta
            ),
            201
        );
    }

    public function show(
        Request $request,
        $id
    ) {
        $motocicleta =
            $this->consultaVisible(
                $request
            )
                ->with([
                    'imagenes',
                    'mototaxista.persona.imagenes',
                    'mototaxista.sindicato',
                ])
                ->findOrFail($id);

        return response()->json(
            $motocicleta,
            200
        );
    }

    public function update(
        MotocicletaRequest $request,
        $id
    ) {
        $motocicleta =
            $this->consultaVisible(
                $request
            )->findOrFail($id);

        $datos = $this->normalizarDatos(
            $request->validated()
        );

        $this->autorizarMototaxista(
            $request,
            (int) $datos['id_mototaxista']
        );

        $motocicleta->update(
            $datos
        );

        return response()->json(
            $this->cargarDetalle(
                $motocicleta->fresh()
            ),
            200
        );
    }

    public function subirImagen(
        Request $request,
        $id
    ) {
        $motocicleta =
            $this->consultaVisible(
                $request
            )->findOrFail($id);

        $request->validate([
            'imagen' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
        ]);

        $archivo = $request->file('imagen');
        $rutaNueva = $archivo->store(
            'motocicletas',
            'public'
        );

        try {
            DB::transaction(
                function () use (
                    $motocicleta,
                    $archivo,
                    $rutaNueva
                ) {
                    $anteriores = $motocicleta
                        ->imagenes()
                        ->get();

                    $motocicleta
                        ->imagenes()
                        ->delete();

                    ImagenMotocicleta::create([
                        'ruta' => $rutaNueva,
                        'tipo' => strtolower(
                            (string) $archivo
                                ->getClientOriginalExtension()
                        ),
                        'id_motocicleta' =>
                            $motocicleta->id,
                    ]);

                    foreach ($anteriores as $imagen) {
                        if (
                            $imagen->ruta
                            && $imagen->ruta !== $rutaNueva
                            && Storage::disk('public')
                                ->exists($imagen->ruta)
                        ) {
                            Storage::disk('public')
                                ->delete($imagen->ruta);
                        }
                    }
                }
            );
        } catch (\Throwable $error) {
            if (
                Storage::disk('public')
                    ->exists($rutaNueva)
            ) {
                Storage::disk('public')
                    ->delete($rutaNueva);
            }

            throw $error;
        }

        return response()->json([
            'mensaje' =>
                'Fotografía de la motocicleta actualizada correctamente.',
            'data' => $this->cargarDetalle(
                $motocicleta->fresh()
            ),
        ], 200);
    }

    public function eliminarImagen(
        Request $request,
        $id
    ) {
        $imagen = ImagenMotocicleta::findOrFail(
            $id
        );

        $this->consultaVisible(
            $request
        )->findOrFail(
            $imagen->id_motocicleta
        );

        if (
            $imagen->ruta
            && Storage::disk('public')
                ->exists($imagen->ruta)
        ) {
            Storage::disk('public')
                ->delete($imagen->ruta);
        }

        $imagen->delete();

        return response()->json([
            'mensaje' =>
                'Fotografía eliminada correctamente.',
        ], 200);
    }

    public function destroy(
        Request $request,
        $id
    ) {
        $motocicleta =
            $this->consultaVisible(
                $request
            )
                ->with('imagenes')
                ->findOrFail($id);

        foreach ($motocicleta->imagenes as $imagen) {
            if (
                $imagen->ruta
                && Storage::disk('public')
                    ->exists($imagen->ruta)
            ) {
                Storage::disk('public')
                    ->delete($imagen->ruta);
            }
        }

        $motocicleta->delete();

        return response()->json([
            'mensaje' =>
                'Motocicleta eliminada correctamente.',
        ]);
    }

    private function cargarDetalle(
        Motocicleta $motocicleta
    ): Motocicleta {
        return $motocicleta->load([
            'imagenes',
            'mototaxista.persona.imagenes',
            'mototaxista.sindicato',
        ]);
    }

    private function normalizarDatos(
        array $datos
    ): array {
        $tienePlaca = (bool) (
            $datos['tiene_placa']
            ?? false
        );

        $datos['tiene_placa'] =
            $tienePlaca;

        $datos['tiene_soat'] =
            (bool) (
                $datos['tiene_soat']
                ?? false
            );

        if (! $tienePlaca) {
            $datos['placa'] = null;
        } elseif (isset($datos['placa'])) {
            $datos['placa'] = strtoupper(
                trim(
                    (string) $datos['placa']
                )
            );
        }

        if (isset($datos['chasis'])) {
            $datos['chasis'] = trim(
                (string) $datos['chasis']
            ) ?: null;
        }

        return $datos;
    }

    private function consultaVisible(
        Request $request
    ): Builder {
        $consulta =
            Motocicleta::query();

        if (
            $this->rol($request)
            === 'secretario'
        ) {
            $sindicatoId =
                $this->sindicatoUsuario(
                    $request
                );

            $consulta->whereHas(
                'mototaxista',
                function (
                    $query
                ) use (
                    $sindicatoId
                ) {
                    $query->where(
                        'id_sindicato',
                        $sindicatoId
                    );
                }
            );
        }

        return $consulta;
    }

    private function autorizarMototaxista(
        Request $request,
        int $mototaxistaId
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

        $pertenece =
            Mototaxista::query()
                ->where(
                    'id',
                    $mototaxistaId
                )
                ->where(
                    'id_sindicato',
                    $sindicatoId
                )
                ->exists();

        if (! $pertenece) {
            abort(
                403,
                'No puedes asignar una motocicleta a un mototaxista de otro sindicato.'
            );
        }
    }

    private function rol(
        Request $request
    ): string {
        return strtolower(
            trim(
                (string) (
                    $request->user()
                        ?->role
                    ?? ''
                )
            )
        );
    }

    private function sindicatoUsuario(
        Request $request
    ): int {
        $sindicatoId = (int) (
            $request->user()
                ?->sindicato_id
            ?? 0
        );

        if ($sindicatoId <= 0) {
            abort(
                403,
                'La cuenta de secretario no está vinculada a un sindicato.'
            );
        }

        return $sindicatoId;
    }
}
