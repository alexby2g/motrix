<?php

namespace App\Http\Controllers;

use App\Models\Federacion;
use App\Models\Sindicato;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SindicatoController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        $consulta = Sindicato::query()
            ->with('federacionEntidad')
            ->withCount('mototaxistas')
            ->orderByDesc('id');

        if (
            $this->rol($request)
            === 'secretario'
        ) {
            $consulta->where(
                'id',
                $this->sindicatoUsuario(
                    $request
                )
            );
        }

        $sindicatos = $consulta
            ->get()
            ->map(
                fn (Sindicato $sindicato) =>
                    $this->normalizar(
                        $sindicato
                    )
            );

        return response()->json(
            $sindicatos
        );
    }

    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $this->autorizarSindicato(
            $request,
            $id
        );

        $sindicato = Sindicato::query()
            ->with([
                'federacionEntidad',
                'mototaxistas.persona',
            ])
            ->withCount('mototaxistas')
            ->find($id);

        if (! $sindicato) {
            return response()->json([
                'message' =>
                    'Sindicato no encontrado.',
            ], 404);
        }

        return response()->json(
            $this->normalizar(
                $sindicato
            )
        );
    }

    public function store(
        Request $request
    ): JsonResponse {
        $datos = $this->validar(
            $request
        );

        $sindicato = Sindicato::create(
            $this->prepararDatos(
                $datos
            )
        );

        $sindicato->load(
            'federacionEntidad'
        );

        $sindicato->loadCount(
            'mototaxistas'
        );

        return response()->json([
            'message' =>
                'Sindicato creado correctamente.',
            'data' =>
                $this->normalizar(
                    $sindicato
                ),
        ], 201);
    }

    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $this->autorizarSindicato(
            $request,
            $id
        );

        $sindicato =
            Sindicato::find($id);

        if (! $sindicato) {
            return response()->json([
                'message' =>
                    'Sindicato no encontrado.',
            ], 404);
        }

        $datos = $this->validar(
            $request,
            $sindicato
        );

        if (
            $this->rol($request)
            === 'secretario'
        ) {
            /*
             * El secretario puede mantener los datos
             * operativos de su sindicato, pero no
             * cambiar su identidad ni moverlo a otra
             * federación.
             */
            $datos['nombre'] =
                $sindicato->nombre;

            $datos['id_federacion'] =
                $sindicato->id_federacion;
        }

        $sindicato->update(
            $this->prepararDatos(
                $datos
            )
        );

        $sindicato->load(
            'federacionEntidad'
        );

        $sindicato->loadCount(
            'mototaxistas'
        );

        return response()->json([
            'message' =>
                'Sindicato actualizado correctamente.',
            'data' =>
                $this->normalizar(
                    $sindicato
                ),
        ]);
    }

    public function destroy(
        int $id
    ): JsonResponse {
        $sindicato =
            Sindicato::query()
                ->withCount(
                    'mototaxistas'
                )
                ->find($id);

        if (! $sindicato) {
            return response()->json([
                'message' =>
                    'Sindicato no encontrado.',
            ], 404);
        }

        if (
            $sindicato
                ->mototaxistas_count
            > 0
        ) {
            return response()->json([
                'message' =>
                    'No se puede eliminar el sindicato porque tiene mototaxistas afiliados.',
            ], 409);
        }

        if ($sindicato->logo) {
            Storage::disk('public')
                ->delete(
                    $sindicato->logo
                );
        }

        $sindicato->delete();

        return response()->json([
            'message' =>
                'Sindicato eliminado correctamente.',
        ]);
    }

    public function subirLogo(
        Request $request,
        int $id
    ): JsonResponse {
        $this->autorizarSindicato(
            $request,
            $id
        );

        $request->validate([
            'logo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ], [
            'logo.required' =>
                'Selecciona una imagen.',
            'logo.image' =>
                'El archivo debe ser una imagen.',
            'logo.mimes' =>
                'Solo se permiten JPG, JPEG, PNG o WEBP.',
            'logo.max' =>
                'La imagen no puede superar los 2 MB.',
        ]);

        $sindicato =
            Sindicato::find($id);

        if (! $sindicato) {
            return response()->json([
                'message' =>
                    'Sindicato no encontrado.',
            ], 404);
        }

        if ($sindicato->logo) {
            Storage::disk('public')
                ->delete(
                    $sindicato->logo
                );
        }

        $ruta = $request
            ->file('logo')
            ->store(
                'sindicatos/logos',
                'public'
            );

        $sindicato->logo = $ruta;
        $sindicato->save();

        $sindicato->load(
            'federacionEntidad'
        );

        $sindicato->loadCount(
            'mototaxistas'
        );

        return response()->json([
            'message' =>
                'Logo actualizado correctamente.',
            'data' =>
                $this->normalizar(
                    $sindicato
                ),
        ]);
    }

    private function validar(
        Request $request,
        ?Sindicato $sindicato = null
    ): array {
        return $request->validate([
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:100',
                Rule::unique(
                    'sindicatos',
                    'nombre'
                )->ignore(
                    $sindicato?->id
                ),
            ],
            'id_federacion' => [
                'nullable',
                'integer',
                'exists:federaciones,id',
            ],
            'fecha_creacion' => [
                'nullable',
                'date',
            ],
            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);
    }

    private function prepararDatos(
        array $datos
    ): array {
        $federacion = null;

        if (
            ! empty(
                $datos['id_federacion']
            )
        ) {
            $federacion =
                Federacion::find(
                    $datos[
                        'id_federacion'
                    ]
                );
        }

        return [
            'nombre' =>
                trim(
                    $datos['nombre']
                ),
            'id_federacion' =>
                $datos['id_federacion']
                ?? null,
            'federacion' =>
                $federacion?->nombre,
            'fecha_creacion' =>
                $datos['fecha_creacion']
                ?? null,
            'direccion' =>
                isset(
                    $datos['direccion']
                )
                    ? trim(
                        (string)
                        $datos['direccion']
                    )
                    : null,
        ];
    }

    private function normalizar(
        Sindicato $sindicato
    ): array {
        $datos =
            $sindicato->toArray();

        $datos['federacion'] =
            $sindicato
                ->federacionEntidad
                ?->toArray();

        unset(
            $datos[
                'federacion_entidad'
            ]
        );

        unset(
            $datos[
                'federacion_relacion'
            ]
        );

        return $datos;
    }

    private function autorizarSindicato(
        Request $request,
        int $id
    ): void {
        if (
            $this->rol($request)
            !== 'secretario'
        ) {
            return;
        }

        if (
            $this->sindicatoUsuario(
                $request
            ) !== $id
        ) {
            abort(
                403,
                'No puedes administrar otro sindicato.'
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
