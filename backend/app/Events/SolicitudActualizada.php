<?php

namespace App\Events;

use App\Models\Solicitud;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SolicitudActualizada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $solicitud;

    public string $tipo;

    /**
     * Publica el estado actualizado del viaje.
     *
     * El frontend filtra el evento usando id_pasajero y, además,
     * vuelve a validar la información mediante las rutas autenticadas.
     */
    public function __construct(
        Solicitud $solicitud,
        string $tipo = 'estado_actualizado'
    ) {
        $solicitud->loadMissing([
            'pasajero.persona',
            'mototaxista.persona',
        ]);

        $this->solicitud = $solicitud->toArray();
        $this->tipo = $tipo;
    }

    /**
     * Se conserva el canal público que MOTRIX ya utiliza con Reverb.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('solicitudes'),
        ];
    }

    /**
     * Nombre utilizado desde Laravel Echo.
     */
    public function broadcastAs(): string
    {
        return 'SolicitudActualizada';
    }

    /**
     * Estructura explícita enviada al frontend.
     */
    public function broadcastWith(): array
    {
        return [
            'solicitud' => $this->solicitud,
            'tipo' => $this->tipo,
        ];
    }
}
