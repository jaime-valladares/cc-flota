<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'empresa_id',
    'unidad_id',
    'punto_seguridad_id',
    'codigo_marchamo',
    'fecha_activacion',
    'estado',
    'activo_actual',
    'fecha_desactivacion',
    'motivo_desactivacion',
    'origen_creacion',
    'creado_por',
    'actualizado_por',
])]
class Marchamo extends Model
{
    protected $table = 'marchamos';

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    public function puntoSeguridad(): BelongsTo
    {
        return $this->belongsTo(PuntoSeguridadUnidad::class, 'punto_seguridad_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function reemplazosComoMarchamoAnterior(): HasMany
    {
        return $this->hasMany(ReemplazoMarchamoDetalle::class, 'marchamo_anterior_id');
    }

    public function reemplazosComoMarchamoNuevo(): HasMany
    {
        return $this->hasMany(ReemplazoMarchamoDetalle::class, 'marchamo_nuevo_id');
    }

    protected function estadoTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->estado) {
                'activo' => 'Activo',
                'reemplazado' => 'Reemplazado',
                'anulado' => 'Anulado',
                default => 'No definido',
            }
        );
    }

    protected function origenCreacionTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->origen_creacion) {
                'asignacion_inicial' => 'Asignación inicial',
                'abastecimiento' => 'Abastecimiento',
                'reemplazo_dano_desgaste' => 'Reemplazo por daño o desgaste',
                'correccion' => 'Corrección',
                default => 'No definido',
            }
        );
    }

    protected function casts(): array
    {
        return [
            'fecha_activacion' => 'datetime',
            'fecha_desactivacion' => 'datetime',
            'activo_actual' => 'integer',
        ];
    }
}