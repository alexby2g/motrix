<?php

namespace App\Http\Controllers;

use App\Models\Federacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class FederacionController extends Controller
{
    public function index(
        Request $request
    ): JsonResponse {
        $consulta = Federacion::query()
            ->withCount('sindicatos')
            ->orderBy('nombre');

        if (
            strtolower(
                trim(
                    (string) $request->user()?->role
                )
            ) === 'secretario'
        ) {
            $federacionId = (int) (
                $request->user()?->federacion_id
                ?? 0
            );

            if ($federacionId <= 0) {
                return response()->json([]);
            }

            $consulta->where(
                'id',
                $federacionId
            );
        }

        return response()->json(
            $consulta->get()
        );
    }

    public function show(
        Request $request,
        int $id
    ): JsonResponse {
        $consulta = Federacion::query()
            ->with([
                'sindicatos' => function ($query) {
                    $query
                        ->withCount('mototaxistas')
                        ->orderBy('nombre');
                },
            ]);

        if (
            strtolower(
                trim(
                    (string) $request->user()?->role
                )
            ) === 'secretario'
        ) {
            $federacionId = (int) (
                $request->user()?->federacion_id
                ?? 0
            );

            if (
                $federacionId <= 0
                || $federacionId !== $id
            ) {
                return response()->json([
                    'message' =>
                        'No puedes consultar otra federación.',
                ], 403);
            }

            $consulta->where(
                'id',
                $federacionId
            );
        }

        $federacion = $consulta->find($id);

        if (! $federacion) {
            return response()->json([
                'message' => 'Federación no encontrada.',
            ], 404);
        }

        return response()->json($federacion);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:150',
                Rule::unique('federaciones', 'nombre'),
            ],
        ], $this->mensajesValidacion());

        $federacion = Federacion::create([
            'nombre' => trim($datos['nombre']),
        ]);

        return response()->json([
            'message' => 'Federación creada correctamente.',
            'data' => $federacion,
        ], 201);
    }

    public function update(
        Request $request,
        int $id
    ): JsonResponse {
        $federacion = Federacion::find($id);

        if (! $federacion) {
            return response()->json([
                'message' => 'Federación no encontrada.',
            ], 404);
        }

        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'min:3',
                'max:150',
                Rule::unique('federaciones', 'nombre')
                    ->ignore($federacion->id),
            ],
        ], $this->mensajesValidacion());

        $federacion->update([
            'nombre' => trim($datos['nombre']),
        ]);

        return response()->json([
            'message' => 'Federación actualizada correctamente.',
            'data' => $federacion->fresh(),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $federacion = Federacion::query()
            ->withCount('sindicatos')
            ->find($id);

        if (! $federacion) {
            return response()->json([
                'message' => 'Federación no encontrada.',
            ], 404);
        }

        if ($federacion->sindicatos_count > 0) {
            return response()->json([
                'message' => 'No se puede eliminar la federación porque todavía tiene sindicatos asociados.',
            ], 409);
        }

        if ($federacion->logo) {
            Storage::disk('public')
                ->delete($federacion->logo);
        }

        $federacion->delete();

        return response()->json([
            'message' => 'Federación eliminada correctamente.',
        ]);
    }

    public function subirLogo(
        Request $request,
        int $id
    ): JsonResponse {
        $request->validate([
            'logo' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ], [
            'logo.required' => 'Selecciona una imagen.',
            'logo.image' => 'El archivo debe ser una imagen.',
            'logo.mimes' => 'Solo se permiten JPG, JPEG, PNG o WEBP.',
            'logo.max' => 'La imagen no puede superar los 2 MB.',
        ]);

        $federacion = Federacion::find($id);

        if (! $federacion) {
            return response()->json([
                'message' => 'Federación no encontrada.',
            ], 404);
        }

        if ($federacion->logo) {
            Storage::disk('public')
                ->delete($federacion->logo);
        }

        $ruta = $request->file('logo')
            ->store(
                'federaciones/logos',
                'public'
            );

        $federacion->logo = $ruta;
        $federacion->save();

        return response()->json([
            'message' => 'Logo actualizado correctamente.',
            'data' => $federacion,
        ]);
    }

    private function mensajesValidacion(): array
    {
        return [
            'nombre.required' => 'El nombre de la federación es obligatorio.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'nombre.max' => 'El nombre no puede superar 150 caracteres.',
            'nombre.unique' => 'Ya existe una federación con ese nombre.',
        ];
    }
}
