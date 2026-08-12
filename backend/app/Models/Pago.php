<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';

    public $timestamps = false;

    protected $fillable = [
        'monto',
        'metodo',
        'estado',
        'id_servicio',
    ];

    protected $casts = [
        'id' => 'integer',
        'id_servicio' => 'integer',
        'monto' => 'decimal:2',
    ];

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
