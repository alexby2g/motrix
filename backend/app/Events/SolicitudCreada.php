<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SolicitudCreada implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $solicitud;

    /**
     * Crear una nueva instancia del evento.
     */
    public function __construct($solicitud)
    {
        // Cargamos las relaciones para que el dashboard reciba el nombre del pasajero en vivo
        $this->solicitud = $solicitud->load(['pasajero.persona']);
    }

    /**
     * El canal público por donde se transmitirá.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('solicitudes'),
        ];
    }

    /**
     * El nombre con el que lo escuchará Quasar.
     */
    public function broadcastAs(): string
    {
        return 'SolicitudCreada';
    }
}