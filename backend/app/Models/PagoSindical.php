<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PagoSindical extends Model
{
    protected $table = 'pagos_sindicales';

    protected $fillable = [
        'id_sindicato',
        'id_mototaxista',
        'tipo_pago',
        'monto',
        'fecha',
        'periodo',
        'estado_pago',
        'forma_pago',
        'observacion',
        'registrado_por',
    ];

    protected $casts = [
        'monto' => 'decimal:2',
        'fecha' => 'date:Y-m-d',
    ];

    public function sindicato()
    {
        return $this->belongsTo(
            Sindicato::class,
            'id_sindicato'
        );
    }

    public function mototaxista()
    {
        return $this->belongsTo(
            Mototaxista::class,
            'id_mototaxista'
        );
    }

    public function registradoPor()
    {
        return $this->belongsTo(
            User::class,
            'registrado_por'
        );
    }
}
