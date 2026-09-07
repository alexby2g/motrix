<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;

class MotrixComprobanteStorageService
{
    public function subir(
        UploadedFile $archivo
    ): string {
        $cloudinaryUrl = trim(
            (string) config('cloudinary.url', '')
        );

        abort_if(
            $cloudinaryUrl === '',
            500,
            'No se configuró CLOUDINARY_URL.'
        );

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
                        'folder' =>
                            'motrix/comprobantes/liquidaciones',
                        'resource_type' =>
                            'auto',
                    ]
                );

        $url =
            $resultado['secure_url']
            ?? $resultado['url']
            ?? null;

        if (! $url) {
            throw new \RuntimeException(
                'Cloudinary no devolvió una URL válida para el comprobante.'
            );
        }

        return (string) $url;
    }
}
