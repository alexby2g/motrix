<?php

namespace App\Events;

use App\Models\Solicitud;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SolicitudCreada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $solicitud;

    public function __construct(Solicitud $solicitud)
    {
        $solicitud->loadMissing([
            'pasajero.persona',
            'mototaxista.persona.imagenes',
            'mototaxista.sindicato',
        ]);

        $this->solicitud = $solicitud->toArray();
    }

    public function broadcastOn(): array
    {
        return $this->canalesParticipantes();
    }

    public function broadcastAs(): string
    {
        return 'SolicitudCreada';
    }

    public function broadcastWith(): array
    {
        return [
            'solicitud' => $this->solicitud,
        ];
    }

    private function canalesParticipantes(): array
    {
        $canales = [
            new PrivateChannel('administracion.solicitudes'),
        ];

        $pasajeroId = (int) ($this->solicitud['id_pasajero'] ?? 0);
        $mototaxistaId = (int) ($this->solicitud['mototaxista_id'] ?? 0);

        if ($pasajeroId > 0) {
            $canales[] = new PrivateChannel(
                'pasajero.' . $pasajeroId . '.solicitudes'
            );
        }

        if ($mototaxistaId > 0) {
            $canales[] = new PrivateChannel(
                'conductor.' . $mototaxistaId . '.solicitudes'
            );
        }

        return $canales;
    }
}
