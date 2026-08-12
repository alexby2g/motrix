<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Federacion extends Model
{
    protected $table = 'federaciones';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'logo',
    ];

    public function sindicatos()
    {
        return $this->hasMany(
            Sindicato::class,
            'id_federacion'
        );
    }
}
