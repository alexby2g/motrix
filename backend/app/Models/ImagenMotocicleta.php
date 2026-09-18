<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImagenMotocicleta extends Model
{
    protected $table = 'imagenes_motocicletas';

    public $timestamps = false;

    protected $fillable = [
        'ruta',
        'tipo',
        'id_motocicleta',
    ];

    public function motocicleta()
    {
        return $this->belongsTo(
            Motocicleta::class,
            'id_motocicleta'
        );
    }
}
