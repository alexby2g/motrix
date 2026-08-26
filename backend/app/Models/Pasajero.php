<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class Pasajero extends Model
{
    protected $table = 'pasajeros';

    public $timestamps = false;

    protected $fillable = [
        'email',
        'password',
        'id_persona',
    ];

    /**
     * Nunca mostrar la contraseña en respuestas JSON.
     */
    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'id_persona' => 'integer',
    ];

    /**
     * Cifra la contraseña al crear o actualizar.
     * needsRehash evita cifrar dos veces una contraseña
     * que ya fue procesada por Laravel.
     */
    public function setPasswordAttribute($valor): void
    {
        if ($valor === null || $valor === '') {
            return;
        }

        $this->attributes['password'] = Hash::needsRehash($valor)
            ? Hash::make($valor)
            : $valor;
    }

    /**
     * Persona relacionada con el pasajero.
     */
    public function persona()
    {
        return $this->belongsTo(
            Persona::class,
            'id_persona'
        );
    }

    /**
     * Cuenta de acceso vinculada al pasajero.
     *
     * La autenticación se realiza contra la tabla users.
     * La columna password de pasajeros se conserva solo por
     * compatibilidad con datos históricos y no se usa para login.
     */
    public function usuarioPasajero()
    {
        return $this->hasOne(
            User::class,
            'pasajero_id'
        )->where(
            'role',
            'pasajero'
        );
    }

    /**
     * Solicitudes realizadas por el pasajero.
     */
    public function solicitudes()
    {
        return $this->hasMany(
            Solicitud::class,
            'id_pasajero'
        );
    }
}