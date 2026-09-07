<?php

namespace App\Http\Controllers;

use App\Models\ImagenPersona;
use App\Models\Persona;
use Cloudinary\Cloudinary;
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
                'max:4096',
            ],
        ]);

        $subida = $this->subirImagenCloudinary(
            $request->file('imagen'),
            'motrix/personas'
        );

        return response()->json([
            'mensaje' =>
                'Imagen subida correctamente.',
            'ruta' => $subida['ruta'],
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
                'max:4096',
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
                    $subida = $this->subirImagenCloudinary(
                        $request->file('imagen'),
                        'motrix/personas'
                    );

                    $ruta = $subida['ruta'];

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
            if ($ruta) {
                $this->eliminarArchivoImagen(
                    $ruta
                );
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
                'max:4096',
            ],
        ]);

        $subida = $this->subirImagenCloudinary(
            $request->file('imagen'),
            'motrix/personas'
        );

        $imagen =
            ImagenPersona::create([
                'ruta' => $subida['ruta'],
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

        if ($imagen->ruta) {
            $this->eliminarArchivoImagen(
                $imagen->ruta
            );
        }

        $imagen->delete();

        return response()->json([
            'mensaje' =>
                'Imagen eliminada correctamente.',
        ], 200);
    }

    private function subirImagenCloudinary(
        $archivo,
        string $carpeta
    ): array {
        $cloudinaryUrl = (string) config('cloudinary.url', '');

        if (trim($cloudinaryUrl) === '') {
            abort(
                500,
                'No se configuró CLOUDINARY_URL.'
            );
        }

        $cloudinary =
            new Cloudinary(
                $cloudinaryUrl
            );

        $resultado =
            $cloudinary
                ->uploadApi()
                ->upload(
                    $archivo->getRealPath(),
                    [
                        'folder' => $carpeta,
                        'resource_type' => 'image',
                    ]
                );

        $ruta =
            $resultado['secure_url']
            ?? $resultado['url']
            ?? null;

        if (! $ruta) {
            throw new \RuntimeException(
                'Cloudinary no devolvió una URL válida para la imagen.'
            );
        }

        return [
            'ruta' => $ruta,
            'public_id' =>
                $resultado['public_id']
                ?? null,
        ];
    }

    private function eliminarArchivoImagen(
        string $ruta
    ): void {
        if (
            str_starts_with(
                $ruta,
                'http://'
            )
            || str_starts_with(
                $ruta,
                'https://'
            )
        ) {
            $this->eliminarDesdeCloudinary(
                $ruta
            );
            return;
        }

        if (
            Storage::disk('public')
                ->exists($ruta)
        ) {
            Storage::disk('public')
                ->delete($ruta);
        }
    }

    private function eliminarDesdeCloudinary(
        string $ruta
    ): void {
        $publicId =
            $this->extraerPublicIdCloudinary(
                $ruta
            );

        if (! $publicId) {
            return;
        }

        $cloudinaryUrl = (string) config('cloudinary.url', '');

        if (trim($cloudinaryUrl) === '') {
            return;
        }

        $cloudinary =
            new Cloudinary(
                $cloudinaryUrl
            );

        $cloudinary
            ->uploadApi()
            ->destroy(
                $publicId,
                [
                    'resource_type' => 'image',
                ]
            );
    }

    private function extraerPublicIdCloudinary(
        string $ruta
    ): ?string {
        $partes =
            parse_url($ruta);

        $path =
            $partes['path']
            ?? null;

        if (! $path) {
            return null;
        }

        $segmentos =
            explode(
                '/',
                trim($path, '/')
            );

        $indiceUpload =
            array_search(
                'upload',
                $segmentos,
                true
            );

        if (
            $indiceUpload === false
            || ! isset(
                $segmentos[
                    $indiceUpload + 1
                ]
            )
        ) {
            return null;
        }

        $publicIdSegmentos =
            array_slice(
                $segmentos,
                $indiceUpload + 1
            );

        if (
            isset(
                $publicIdSegmentos[0]
            )
            && preg_match(
                '/^v\d+$/',
                $publicIdSegmentos[0]
            )
        ) {
            array_shift(
                $publicIdSegmentos
            );
        }

        if (empty($publicIdSegmentos)) {
            return null;
        }

        $ultimo =
            array_pop(
                $publicIdSegmentos
            );

        $ultimoSinExtension =
            pathinfo(
                $ultimo,
                PATHINFO_FILENAME
            );

        $publicIdSegmentos[] =
            $ultimoSinExtension;

        return implode(
            '/',
            $publicIdSegmentos
        );
    }

    private function resolverPersona(
        Request $request,
        int $id
    ): Persona {
        $rol = $this->rol(
            $request
        );

        if (
            in_array(
                $rol,
                [
                    'admin_general',
                    'admin_registro',
                ],
                true
            )
        ) {
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