<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Motocicleta extends Model
{
    protected $table = 'motocicletas';

    protected $fillable = [
        'placa',
        'chasis',
        'modelo',
        'color',
        'tiene_soat',
        'id_mototaxista'
    ];

    protected $casts = [
        'tiene_soat' => 'boolean',
        'id_mototaxista' => 'integer',
    ];

    public $timestamps = false;

    // Relación: Una motocicleta pertenece a un Mototaxista
    public function mototaxista()
    {
        return $this->belongsTo(Mototaxista::class, 'id_mototaxista');
    }
}