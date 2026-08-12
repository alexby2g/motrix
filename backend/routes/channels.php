<?php

use App\Models\Solicitud;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel(
    'App.Models.User.{id}',
    function ($user, $id) {
        return (int) $user->id
            === (int) $id;
    }
);

Broadcast::channel(
    'viajes.chat.{solicitudId}',
    function (
        $user,
        $solicitudId
    ) {
        return autorizarParticipanteViaje(
            $user,
            (int) $solicitudId,
            true
        );
    }
);

Broadcast::channel(
    'viajes.incidencias.{solicitudId}',
    function (
        $user,
        $solicitudId
    ) {
        return autorizarParticipanteViaje(
            $user,
            (int) $solicitudId,
            true
        );
    }
);

/*
 * Canal global exclusivo del administrador general.
 */
Broadcast::channel(
    'administracion.incidencias',
    function ($user) {
        return strtolower(
            trim(
                (string) (
                    $user->role
                    ?? ''
                )
            )
        ) === 'admin_general';
    }
);

/*
 * Cada secretario escucha únicamente las incidencias
 * de su propio sindicato.
 */
Broadcast::channel(
    'sindicato.{sindicatoId}.incidencias',
    function (
        $user,
        $sindicatoId
    ) {
        $rol = strtolower(
            trim(
                (string) (
                    $user->role
                    ?? ''
                )
            )
        );

        if ($rol === 'admin_general') {
            return true;
        }

        return (
            $rol === 'secretario'
            && (int) (
                $user->sindicato_id
                ?? 0
            ) === (int) $sindicatoId
        );
    }
);

if (
    ! function_exists(
        'autorizarParticipanteViaje'
    )
) {
    function autorizarParticipanteViaje(
        $user,
        int $solicitudId,
        bool $permitirAdministrativo = false
    ): bool {
        $solicitud =
            Solicitud::query()
                ->select([
                    'id',
                    'id_pasajero',
                    'mototaxista_id',
                ])
                ->with([
                    'mototaxista:id,id_sindicato',
                ])
                ->find($solicitudId);

        if (! $solicitud) {
            return false;
        }

        $rol = strtolower(
            trim(
                (string) (
                    $user->role
                    ?? ''
                )
            )
        );

        if (
            $permitirAdministrativo
            && $rol === 'admin_general'
        ) {
            return true;
        }

        if (
            $permitirAdministrativo
            && $rol === 'secretario'
        ) {
            return (
                (int) (
                    $user->sindicato_id
                    ?? 0
                ) > 0
                && (int) (
                    $user->sindicato_id
                    ?? 0
                ) === (int) (
                    $solicitud
                        ->mototaxista
                        ?->id_sindicato
                    ?? 0
                )
            );
        }

        if ($rol === 'pasajero') {
            return (
                (int) (
                    $user->pasajero_id
                    ?? 0
                )
                === (int)
                    $solicitud
                        ->id_pasajero
            );
        }

        if ($rol === 'conductor') {
            return (
                (int) (
                    $user->mototaxista_id
                    ?? 0
                ) > 0
                && (int) (
                    $user->mototaxista_id
                    ?? 0
                )
                === (int) (
                    $solicitud
                        ->mototaxista_id
                    ?? 0
                )
            );
        }

        return false;
    }
}
