<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    public $timestamps = false;

    protected $fillable = [
        'monto',
        'monto_efectivo',
        'monto_qr',
        'metodo',
        'estado',
        'id_servicio',
    ];

    protected $casts = [
        'id' => 'integer',
        'id_servicio' => 'integer',
        'monto' => 'decimal:2',
        'monto_efectivo' => 'decimal:2',
        'monto_qr' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Pago $pago) {
            if ($pago->estado === 'Completado') {
                if (
                    !$pago->completado_en
                    && (
                        !$pago->exists
                        || (
                            $pago->isDirty('estado')
                            && $pago->getOriginal('estado') !== 'Completado'
                        )
                    )
                ) {
                    $pago->completado_en = now(
                        'America/La_Paz'
                    )->format('Y-m-d H:i:s');
                }
            } elseif ($pago->isDirty('estado')) {
                $pago->completado_en = null;
            }
        });
    }
    /**
     * Un pago pertenece a un servicio.
     */
    public function servicio()
    {
        return $this->belongsTo(
            Servicio::class,
            'id_servicio'
        );
    }
}
