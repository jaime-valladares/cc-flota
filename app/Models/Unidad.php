<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'empresa_id',
    'placa',
    'marca',
    'total_tanques',
    'cantidad_tanques_con_licencia',
    'capacidad_total',
    'capacidad_cubierta',
    'modelo_medicion',
    'estado',
    'creado_por',
    'actualizado_por',
    'fecha_inactivacion',
    'inactivado_por',
    'motivo_inactivacion',
])]
class Unidad extends Model
{
    /**
     * Nombre explícito de la tabla asociada al modelo.
     */
    protected $table = 'unidades';

    /**
     * Relación con la empresa propietaria de la unidad.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relación con la licencia permanente de la unidad.
     *
     * En CC-Flota V1, una unidad puede tener como máximo una licencia,
     * asociada a su placa de circulación.
     */
    public function licencia(): HasOne
    {
        return $this->hasOne(Licencia::class);
    }

    /**
    * Puntos de seguridad configurados para la unidad.
    */
    public function puntosSeguridad(): HasMany
    {
        return $this->hasMany(PuntoSeguridadUnidad::class, 'unidad_id')
            ->orderBy('orden');
    }

    /**
    * Historial de marchamos asociados a la unidad.
    */
    public function marchamos(): HasMany
    {
        return $this->hasMany(Marchamo::class, 'unidad_id');
    }

    /**
     * Usuario que creó el registro.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    /**
     * Usuario que actualizó el registro por última vez.
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    /**
     * Usuario que inactivó la unidad.
     */
    public function inactivadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inactivado_por');
    }

    /**
     * Texto legible para el modelo de medición.
     */
    protected function modeloMedicionTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->modelo_medicion) {
                'galones_hora' => 'Galones por hora',
                'galones_kilometro' => 'Galones por kilómetro',
                'galones_viaje' => 'Galones por viaje',
                default => 'No definido',
            }
        );
    }

    /**
     * Texto legible para el estado.
     */
    protected function estadoTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->estado) {
                'registrada' => 'Registrada',
                'activa' => 'Activa',
                'inactiva' => 'Inactiva',
                default => 'No definido',
            }
        );
    }

    /**
     * Conversión de tipos.
     */
    protected function casts(): array
    {
        return [
            'total_tanques' => 'integer',
            'cantidad_tanques_con_licencia' => 'integer',
            'capacidad_total' => 'decimal:2',
            'capacidad_cubierta' => 'decimal:2',
            'fecha_inactivacion' => 'datetime',
        ];
    }
}