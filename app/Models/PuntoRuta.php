<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PuntoRuta extends Model
{
    protected $table = 'puntos_ruta';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'direccion',
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
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'fecha_inactivacion' => 'datetime',
    ];

    /**
     * Empresa propietaria del punto de ruta.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(
            Empresa::class,
            'empresa_id'
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
     * Rutas donde este punto funciona como origen.
     */
    public function rutasComoOrigen(): HasMany
    {
        return $this->hasMany(
            Ruta::class,
            'punto_origen_id'
        );
    }

    /**
     * Rutas donde este punto funciona como destino.
     */
    public function rutasComoDestino(): HasMany
    {
        return $this->hasMany(
            Ruta::class,
            'punto_destino_id'
        );
    }

    /**
     * Determina si el punto se encuentra activo.
     */
    public function getEstaActivoAttribute(): bool
    {
        return $this->estado === 'activo';
    }

    /**
     * Determina si el punto participa en alguna ruta activa.
     */
    public function getTieneRutasActivasAttribute(): bool
    {
        return $this->rutasComoOrigen()
            ->where('estado', 'activo')
            ->exists()
            || $this->rutasComoDestino()
                ->where('estado', 'activo')
                ->exists();
    }
}