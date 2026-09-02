<?php

namespace App\Events;

use App\Models\AlertaSuscripcionMotrix;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AlertaSuscripcionMotrixPublicada implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public AlertaSuscripcionMotrix $alerta
    ) {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'conductor.'
                . $this->alerta->id_mototaxista
                . '.suscripcion'
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'AlertaSuscripcionMotrixPublicada';
    }

    public function broadcastWith(): array
    {
        return [
            'alerta' => [
                'id' => (int) $this->alerta->id,
                'suscripcion_id' =>
                    (int) $this->alerta->suscripcion_id,
                'pago_suscripcion_id' =>
                    $this->alerta->pago_suscripcion_id
                        ? (int) $this->alerta->pago_suscripcion_id
                        : null,
                'id_mototaxista' =>
                    (int) $this->alerta->id_mototaxista,
                'tipo' => $this->alerta->tipo,
                'titulo' => $this->alerta->titulo,
                'mensaje' => $this->alerta->mensaje,
                'canal' => $this->alerta->canal,
                'estado' => $this->alerta->estado,
                'programada_para' =>
                    $this->alerta->programada_para?->toIso8601String(),
                'enviada_en' =>
                    $this->alerta->enviada_en?->toIso8601String(),
            ],
        ];
    }
}
