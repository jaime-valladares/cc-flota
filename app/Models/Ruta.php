<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ruta extends Model
{
    protected $table = 'rutas';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'punto_origen_id',
        'punto_destino_id',
        'ruta',
        'kilometros_estimados',
        'galones_estimados',
        'estado',
        'fecha_creacion',
        'creado_por',
        'fecha_actualizacion',
        'actualizado_por',
        'fecha_inactivacion',
        'inactivado_por',
        'motivo_inactivacion',
    ];

    protected $casts = [
        'kilometros_estimados' => 'decimal:2',
        'galones_estimados' => 'decimal:2',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'fecha_inactivacion' => 'datetime',
    ];

    /**
     * Empresa propietaria de la ruta.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(
            Empresa::class,
            'empresa_id'
        );
    }

    /**
     * Punto que quedó registrado como origen.
     *
     * La ruta es funcionalmente bidireccional, por lo que este campo
     * conserva únicamente el orden utilizado al crear el registro.
     */
    public function puntoOrigen(): BelongsTo
    {
        return $this->belongsTo(
            PuntoRuta::class,
            'punto_origen_id'
        );
    }

    /**
     * Punto que quedó registrado como destino.
     *
     * La combinación inversa representa la misma ruta y es bloqueada
     * por las reglas funcionales del módulo.
     */
    public function puntoDestino(): BelongsTo
    {
        return $this->belongsTo(
            PuntoRuta::class,
            'punto_destino_id'
        );
    }

    /**
     * Usuario que creó el registro.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'creado_por'
        );
    }

    /**
     * Usuario que realizó la última actualización.
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actualizado_por'
        );
    }

    /**
     * Usuario que inactivó el registro.
     */
    public function inactivadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'inactivado_por'
        );
    }

    /**
     * Determina si la ruta se encuentra activa.
     */
    public function getEstaActivaAttribute(): bool
    {
        return $this->estado === 'activo';
    }

    /**
     * Calcula el rendimiento estimado sin almacenarlo.
     */
    public function getRendimientoEstimadoAttribute(): ?float
    {
        $kilometros = (float) $this->kilometros_estimados;
        $galones = (float) $this->galones_estimados;

        if ($galones <= 0) {
            return null;
        }

        return round(
            $kilometros / $galones,
            2
        );
    }
}