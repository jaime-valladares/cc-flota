<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'unidad_id',
    'orden',
    'codigo_punto',
    'grupo',
    'subgrupo',
    'nombre_punto',
    'descripcion',
    'posicion_tanque',
    'tipo_punto',
    'requiere_marchamo',
    'plantilla_origen',
    'criterio_origen',
    'estado_asignacion',
    'marchamo_actual_id',
    'estado',
    'creado_por',
    'actualizado_por',
    'fecha_inactivacion',
    'inactivado_por',
    'motivo_inactivacion',
])]
class PuntoSeguridadUnidad extends Model
{
    protected $table = 'puntos_seguridad_unidad';

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function marchamoActual(): BelongsTo
    {
        return $this->belongsTo(Marchamo::class, 'marchamo_actual_id');
    }

    public function marchamos(): HasMany
    {
        return $this->hasMany(Marchamo::class, 'punto_seguridad_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function inactivadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inactivado_por');
    }

    protected function estadoTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->estado) {
                'activo' => 'Activo',
                'inactivo' => 'Inactivo',
                default => 'No definido',
            }
        );
    }

    protected function estadoAsignacionTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->estado_asignacion) {
                'pendiente' => 'Pendiente',
                'asignado' => 'Asignado',
                'corregido' => 'Corregido',
                default => 'No definido',
            }
        );
    }

    protected function plantillaOrigenTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->plantilla_origen) {
                'plantilla_1_tanque' => 'Plantilla 1 tanque',
                'plantilla_2_tanques' => 'Plantilla 2 tanques',
                'plantilla_3_tanques' => 'Plantilla 3 tanques',
                default => 'No definida',
            }
        );
    }

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'requiere_marchamo' => 'boolean',
            'fecha_inactivacion' => 'datetime',
        ];
    }
}