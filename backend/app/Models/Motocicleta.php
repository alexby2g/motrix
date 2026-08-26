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
        'tiene_placa',
        'tiene_soat',
        'id_mototaxista',
    ];

    protected $casts = [
        'tiene_placa' => 'boolean',
        'tiene_soat' => 'boolean',
        'id_mototaxista' => 'integer',
    ];

    public $timestamps = false;

    public function mototaxista()
    {
        return $this->belongsTo(
            Mototaxista::class,
            'id_mototaxista'
        );
    }

    public function imagenes()
    {
        return $this->hasMany(
            ImagenMotocicleta::class,
            'id_motocicleta'
        )->orderBy('id');
    }
}
