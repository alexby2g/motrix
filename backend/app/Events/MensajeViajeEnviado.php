<?php

namespace App\Events;

use App\Models\MensajeViaje;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MensajeViajeEnviado implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public MensajeViaje $mensaje
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'viajes.chat.' . $this->mensaje->solicitud_id
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MensajeViajeEnviado';
    }

    public function broadcastWith(): array
    {
        return [
            'mensaje' => [
                'id' => (int) $this->mensaje->id,
                'solicitud_id' => (int) $this->mensaje->solicitud_id,
                'remitente_usuario_id' => $this->mensaje->remitente_usuario_id
                    ? (int) $this->mensaje->remitente_usuario_id
                    : null,
                'remitente_tipo' => $this->mensaje->remitente_tipo,
                'remitente_nombre' => $this->mensaje->remitente_nombre,
                'mensaje' => $this->mensaje->mensaje,
                'leido_pasajero_en' => $this->formatearFecha(
                    $this->mensaje->leido_pasajero_en
                ),
                'leido_conductor_en' => $this->formatearFecha(
                    $this->mensaje->leido_conductor_en
                ),
                'creado_en' => $this->formatearFecha(
                    $this->mensaje->creado_en
                ),
            ],
        ];
    }

    private function formatearFecha($fecha): ?string
    {
        return $fecha
            ? $fecha->format('Y-m-d H:i:s')
            : null;
    }
}
