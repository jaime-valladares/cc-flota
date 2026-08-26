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

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(
            Unidad::class,
            'unidad_id'
        );
    }

    /**
     * Marchamo que actualmente cubre este punto.
     *
     * Durante la asignación inicial puede representar un avance provisional.
     * Después de finalizar la asignación representa el marchamo oficial actual.
     */
    public function marchamoActual(): BelongsTo
    {
        return $this->belongsTo(
            Marchamo::class,
            'marchamo_actual_id'
        );
    }

    /**
     * Historial completo de marchamos asociados al punto.
     */
    public function marchamos(): HasMany
    {
        return $this->hasMany(
            Marchamo::class,
            'punto_seguridad_id'
        )->orderByDesc('fecha_activacion');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'creado_por'
        );
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actualizado_por'
        );
    }

    public function inactivadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'inactivado_por'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Textos legibles
    |--------------------------------------------------------------------------
    */

    protected function estadoTexto(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->estado) {
                'activo' => 'Activo',
                'inactivo' => 'Inactivo',
                default => 'No definido',
            }
        );
    }

    protected function estadoAsignacionTexto(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->estado_asignacion) {
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
            get: fn (): string => match ($this->plantilla_origen) {
                'plantilla_1_tanque' =>
                    'Plantilla 1 tanque',

                'plantilla_2_tanques' =>
                    'Plantilla 2 tanques',

                'plantilla_3_tanques' =>
                    'Plantilla 3 tanques',

                'extra' =>
                    'Punto extra',

                default =>
                    'No definida',
            }
        );
    }

    /**
     * Estado físico resumido del punto.
     *
     * No determina por sí solo que la unidad sea operable. La empresa,
     * la unidad y la licencia también deben estar habilitadas.
     */
    protected function coberturaTexto(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->estado !== 'activo') {
                    return 'Punto inactivo';
                }

                if (! $this->requiere_marchamo) {
                    return 'No requiere marchamo';
                }

                if (is_null($this->marchamo_actual_id)) {
                    return 'Pendiente de marchamo';
                }

                return 'Marchamo asignado';
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Indicadores funcionales
    |--------------------------------------------------------------------------
    */

    protected function estaActivo(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->estado === 'activo'
        );
    }

    protected function estaInactivo(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->estado === 'inactivo'
        );
    }

    protected function tieneMarchamoActual(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                ! is_null($this->marchamo_actual_id)
        );
    }

    protected function estaPendienteAsignacion(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->estado === 'activo'
                && $this->requiere_marchamo
                && is_null($this->marchamo_actual_id)
        );
    }

    protected function estaAsignado(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->estado === 'activo'
                && $this->requiere_marchamo
                && ! is_null($this->marchamo_actual_id)
                && in_array(
                    $this->estado_asignacion,
                    [
                        'asignado',
                        'corregido',
                    ],
                    true
                )
        );
    }

    /**
     * Indica que el punto posee cobertura física registrada.
     *
     * No significa que pueda reemplazarse. La elegibilidad de reemplazo
     * también depende de empresa activa, unidad activa, licencia vigente
     * y cobertura completa de todos los puntos.
     */
    protected function tieneCoberturaActual(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->estado === 'activo'
                && $this->requiere_marchamo
                && ! is_null($this->marchamo_actual_id)
        );
    }

    protected function requiereAsignacion(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->estado === 'activo'
                && $this->requiere_marchamo
                && is_null($this->marchamo_actual_id)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Conversiones
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'orden' =>
                'integer',

            'requiere_marchamo' =>
                'boolean',

            'fecha_inactivacion' =>
                'datetime',
        ];
    }
}
