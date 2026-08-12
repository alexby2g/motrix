<?php

namespace App\Http\Controllers;

use App\Models\ImagenPersona;
use App\Models\Persona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ImagenController extends Controller
{
    public function subirImagen(
        Request $request
    ) {
        $request->validate([
            'imagen' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif,webp,bmp',
                'max:2048',
            ],
        ]);

        $ruta = $request
            ->file('imagen')
            ->store(
                'personas',
                'public'
            );

        return response()->json([
            'mensaje' =>
                'Imagen subida correctamente.',
            'ruta' => $ruta,
        ], 200);
    }

    public function registrarPersona(
        Request $request
    ) {
        $datos = $request->validate([
            'ci' => [
                'required',
                'string',
                'max:20',
                'unique:personas,ci',
            ],
            'nombre' => [
                'required',
                'string',
                'min:2',
                'max:100',
            ],
            'apellidos' => [
                'nullable',
                'string',
                'max:100',
            ],
            'telefono' => [
                'nullable',
                'string',
                'max:20',
            ],
            'direccion' => [
                'nullable',
                'string',
                'max:255',
            ],
            'imagen' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif,webp,bmp',
                'max:2048',
            ],
        ]);

        $ruta = null;

        try {
            return DB::transaction(
                function () use (
                    $request,
                    $datos,
                    &$ruta
                ) {
                    $ruta = $request
                        ->file('imagen')
                        ->store(
                            'personas',
                            'public'
                        );

                    $personaDatos = [
                        'ci' => $datos['ci'],
                        'nombre' =>
                            $datos['nombre'],
                        'apellidos' =>
                            $datos['apellidos']
                            ?? null,
                        'telefono' =>
                            $datos['telefono']
                            ?? null,
                        'direccion' =>
                            $datos['direccion']
                            ?? null,
                    ];

                    if (
                        $this->rol($request)
                        === 'secretario'
                    ) {
                        $personaDatos[
                            'sindicato_registro_id'
                        ] =
                            $this->sindicatoUsuario(
                                $request
                            );
                    }

                    $persona =
                        Persona::create(
                            $personaDatos
                        );

                    $imagen =
                        ImagenPersona::create([
                            'ruta' => $ruta,
                            'tipo' => $request
                                ->file('imagen')
                                ->getClientOriginalExtension(),
                            'id_persona' =>
                                $persona->id,
                        ]);

                    return response()->json([
                        'mensaje' =>
                            'Persona e imagen registradas correctamente.',
                        'persona' =>
                            $persona->load(
                                'imagenes'
                            ),
                        'imagen' =>
                            $imagen,
                    ], 201);
                }
            );
        } catch (\Throwable $error) {
            if (
                $ruta
                && Storage::disk('public')
                    ->exists($ruta)
            ) {
                Storage::disk('public')
                    ->delete($ruta);
            }

            throw $error;
        }
    }

    public function agregarImagenPersona(
        Request $request,
        $id
    ) {
        $persona =
            $this->resolverPersona(
                $request,
                (int) $id
            );

        $request->validate([
            'imagen' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,gif,webp,bmp',
                'max:2048',
            ],
        ]);

        $ruta = $request
            ->file('imagen')
            ->store(
                'personas',
                'public'
            );

        $imagen =
            ImagenPersona::create([
                'ruta' => $ruta,
                'tipo' => $request
                    ->file('imagen')
                    ->getClientOriginalExtension(),
                'id_persona' =>
                    $persona->id,
            ]);

        return response()->json([
            'mensaje' =>
                'Fotografía agregada correctamente.',
            'imagen' => $imagen,
        ], 201);
    }

    public function destroy(
        Request $request,
        $id
    ) {
        $imagen =
            ImagenPersona::with(
                'persona'
            )->find($id);

        if (! $imagen) {
            return response()->json([
                'mensaje' =>
                    'Imagen no encontrada.',
            ], 404);
        }

        $this->resolverPersona(
            $request,
            (int) $imagen->id_persona
        );

        if (
            $imagen->ruta
            && Storage::disk('public')
                ->exists(
                    $imagen->ruta
                )
        ) {
            Storage::disk('public')
                ->delete(
                    $imagen->ruta
                );
        }

        $imagen->delete();

        return response()->json([
            'mensaje' =>
                'Imagen eliminada correctamente.',
        ], 200);
    }

    private function resolverPersona(
        Request $request,
        int $id
    ): Persona {
        $rol = $this->rol(
            $request
        );

        if ($rol === 'admin_general') {
            return Persona::findOrFail(
                $id
            );
        }

        if (
            $rol === 'admin_servicios'
        ) {
            return Persona::query()
                ->where('id', $id)
                ->where(
                    function ($query) {
                        $query
                            ->whereHas(
                                'pasajero'
                            )
                            ->orWhereDoesntHave(
                                'mototaxista'
                            );
                    }
                )
                ->firstOrFail();
        }

        if ($rol === 'secretario') {
            $sindicatoId =
                $this->sindicatoUsuario(
                    $request
                );

            return Persona::query()
                ->where('id', $id)
                ->where(
                    function ($query) use (
                        $sindicatoId
                    ) {
                        $query
                            ->where(
                                'sindicato_registro_id',
                                $sindicatoId
                            )
                            ->orWhereHas(
                                'mototaxista',
                                function (
                                    $mototaxista
                                ) use (
                                    $sindicatoId
                                ) {
                                    $mototaxista
                                        ->where(
                                            'id_sindicato',
                                            $sindicatoId
                                        );
                                }
                            );
                    }
                )
                ->firstOrFail();
        }

        abort(
            403,
            'No tienes autorización para administrar imágenes de personas.'
        );
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
