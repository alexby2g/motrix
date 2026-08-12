<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'nickname',
        'email',
        'password',
        'role',
        'mototaxista_id',
        'pasajero_id',
        'persona_id',
        'federacion_id',
        'sindicato_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'mototaxista_id' => 'integer',
            'pasajero_id' => 'integer',
            'persona_id' => 'integer',
            'federacion_id' => 'integer',
            'sindicato_id' => 'integer',
        ];
    }

    public function persona(): BelongsTo
    {
        return $this->belongsTo(
            Persona::class,
            'persona_id'
        );
    }

    public function mototaxista(): BelongsTo
    {
        return $this->belongsTo(
            Mototaxista::class,
            'mototaxista_id'
        );
    }

    public function pasajero(): BelongsTo
    {
        return $this->belongsTo(
            Pasajero::class,
            'pasajero_id'
        );
    }

    public function federacion(): BelongsTo
    {
        return $this->belongsTo(
            Federacion::class,
            'federacion_id'
        );
    }

    public function sindicato(): BelongsTo
    {
        return $this->belongsTo(
            Sindicato::class,
            'sindicato_id'
        );
    }

    public function tieneRol(string ...$roles): bool
    {
        return in_array(
            strtolower(trim((string) $this->role)),
            array_map(
                static fn ($rol) =>
                    strtolower(trim((string) $rol)),
                $roles
            ),
            true
        );
    }
}
