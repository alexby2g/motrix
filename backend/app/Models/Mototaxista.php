<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mototaxista extends Model
{
    protected $table = 'mototaxistas';

    public $timestamps = false;

    protected $fillable = [
        'nro_chaleco',
        'codigo_qr',
        'qr_pago_ruta',
        'qr_pago_metodo',
        'qr_pago_titular',
        'qr_pago_actualizado_en',
        'telefono',
        'estado',
        'documentacion_en_regla',
        'aportes_al_dia',
        'estado_sindical',
        'motivo_estado_sindical',
        'estado_sindical_actualizado_en',
        'disponible',
        'latitud',
        'longitud',
        'ultima_conexion',
        'id_persona',
        'id_sindicato',
    ];

    protected $casts = [
        'documentacion_en_regla' => 'boolean',
        'aportes_al_dia' => 'boolean',
        'estado_sindical_actualizado_en' => 'datetime',
        'qr_pago_actualizado_en' => 'datetime',
        'disponible' => 'boolean',
        'latitud' => 'float',
        'longitud' => 'float',
        'ultima_conexion' => 'datetime',
        'id_persona' => 'integer',
        'id_sindicato' => 'integer',
    ];

    public function persona()
    {
        return $this->belongsTo(
            Persona::class,
            'id_persona'
        );
    }

    public function sindicato()
    {
        return $this->belongsTo(
            Sindicato::class,
            'id_sindicato'
        );
    }

    public function motocicletas()
    {
        return $this->hasMany(
            Motocicleta::class,
            'id_mototaxista'
        );
    }

    public function solicitudes()
    {
        return $this->hasMany(
            Solicitud::class,
            'mototaxista_id'
        );
    }

    public function servicios()
    {
        return $this->hasMany(
            Servicio::class,
            'id_mototaxista'
        );
    }

    public function usuarioConductor()
    {
        return $this->hasOne(
            User::class,
            'mototaxista_id'
        )->where('role', 'conductor');
    }

    public function calificaciones()
    {
        return $this->hasMany(
            Solicitud::class,
            'mototaxista_id'
        )->whereNotNull('calificacion');
    }

    public function habilitadoSindicalmente(): bool
    {
        return $this->estado === 'Activo'
            && (bool) $this->documentacion_en_regla
            && (bool) $this->aportes_al_dia
            && $this->estado_sindical === 'Habilitado';
    }

    public function motivoInhabilitacionSindical(): ?string
    {
        if ($this->estado !== 'Activo') {
            return 'Registro administrativo no activo';
        }

        if ($this->estado_sindical === 'Expulsado') {
            return $this->motivo_estado_sindical ?: 'Afiliación sindical expulsada';
        }

        $motivos = [];

        if (! (bool) $this->documentacion_en_regla) {
            $motivos[] = 'documentación incompleta';
        }

        if (! (bool) $this->aportes_al_dia) {
            $motivos[] = 'aportes pendientes';
        }

        if ($motivos !== []) {
            return implode(' y ', $motivos);
        }

        if ($this->estado_sindical !== 'Habilitado') {
            return $this->motivo_estado_sindical ?: 'Afiliación sindical no habilitada';
        }

        return null;
    }
}
