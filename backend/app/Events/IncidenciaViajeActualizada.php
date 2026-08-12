<?php

namespace App\Events;

use App\Models\IncidenciaViaje;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IncidenciaViajeActualizada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public IncidenciaViaje $incidencia
    ) {
        $this->incidencia->load([
            'solicitud.pasajero.persona',
            'solicitud.mototaxista.persona',
            'solicitud.mototaxista.sindicato',
        ]);
    }

    public function broadcastOn(): array
    {
        $canales = [
            new PrivateChannel(
                'administracion.incidencias'
            ),
            new PrivateChannel(
                'viajes.incidencias.'
                . $this->incidencia->solicitud_id
            ),
        ];

        $sindicatoId = (int) (
            $this->incidencia
                ->solicitud
                ?->mototaxista
                ?->id_sindicato
            ?? 0
        );

        if ($sindicatoId > 0) {
            $canales[] = new PrivateChannel(
                'sindicato.'
                . $sindicatoId
                . '.incidencias'
            );
        }

        return $canales;
    }

    public function broadcastAs(): string
    {
        return 'IncidenciaViajeActualizada';
    }

    public function broadcastWith(): array
    {
        return [
            'incidencia' =>
                $this->incidencia
                    ->toArray(),
        ];
    }
}
