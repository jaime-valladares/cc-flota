<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'empresa_id',
    'unidad_id',
    'periodo_vigencia_meses',
    'fecha_activacion',
    'fecha_vencimiento',
    'estado',
    'plantilla_puntos_seguridad',
    'creado_por',
    'actualizado_por',
    'fecha_inactivacion',
    'inactivado_por',
    'motivo_inactivacion',
])]
class Licencia extends Model
{
    /**
     * Nombre explícito de la tabla asociada al modelo.
     */
    protected $table = 'licencias';

    /**
     * Relación con la empresa propietaria de la licencia.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relación con la unidad asociada a la licencia.
     */
    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class);
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
     * Usuario que inactivó la licencia.
     */
    public function inactivadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inactivado_por');
    }

    /**
     * Texto legible para el estado de la licencia.
     */
    protected function estadoTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->estado) {
                'activa' => 'Activa',
                'inactiva' => 'Inactiva',
                default => 'No definido',
            }
        );
    }

    /**
     * Texto legible para el período de vigencia.
     */
    protected function periodoVigenciaTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ((int) $this->periodo_vigencia_meses) {
                3 => '3 meses',
                6 => '6 meses',
                12 => '12 meses',
                default => 'No definido',
            }
        );
    }

    /**
     * Texto legible para la plantilla de puntos de seguridad.
     */
    protected function plantillaPuntosSeguridadTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->plantilla_puntos_seguridad) {
                'plantilla_1_tanque' => 'Plantilla 1 tanque',
                'plantilla_2_tanques' => 'Plantilla 2 tanques',
                'plantilla_3_tanques' => 'Plantilla 3 tanques',
                default => 'No definida',
            }
        );
    }

    /**
     * Cantidad esperada de puntos de seguridad según la plantilla.
     *
     * Este valor no se guarda en la tabla licencias. Se deriva de la plantilla
     * para evitar duplicar información que luego vivirá en puntos_seguridad_unidad.
     */
    protected function cantidadPuntosSeguridadEsperados(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->plantilla_puntos_seguridad) {
                'plantilla_1_tanque' => 29,
                'plantilla_2_tanques' => 38,
                'plantilla_3_tanques' => 49,
                default => null,
            }
        );
    }

    /**
     * Conversión de tipos.
     */
    protected function casts(): array
    {
        return [
            'periodo_vigencia_meses' => 'integer',
            'fecha_activacion' => 'date',
            'fecha_vencimiento' => 'date',
            'fecha_inactivacion' => 'datetime',
        ];
    }
}