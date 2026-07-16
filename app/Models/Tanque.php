<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tanque extends Model
{
    protected $table = 'tanques';

    public $timestamps = false;

    protected $fillable = [
        'gasolinera_id',
        'nombre',
        'capacidad_total',
        'volumen_actual',
        'volumen_minimo_alerta',
        'estado',
        'fecha_creacion',
        'creado_por',
        'fecha_actualizacion',
        'actualizado_por',
        'fecha_inactivacion',
        'inactivado_por',
        'motivo_inactivacion',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function gasolinera(): BelongsTo
    {
        return $this->belongsTo(
            Gasolinera::class,
            'gasolinera_id'
        );
    }

    /**
     * Historial completo de movimientos de inventario del tanque.
     */
    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(
            MovimientoInventarioCombustible::class,
            'tanque_id'
        )->orderBy('fecha_hora_movimiento');
    }

    /**
     * Detalles de abastecimientos internos en los que
     * este tanque fue utilizado como origen.
     */
    public function abastecimientoTanques(): HasMany
    {
        return $this->hasMany(
            AbastecimientoTanque::class,
            'tanque_id'
        )->orderBy('created_at');
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
    | Estados funcionales
    |--------------------------------------------------------------------------
    */

    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function estaInactivo(): bool
    {
        return $this->estado === 'inactivo';
    }

    public function estaBajoAlerta(): bool
    {
        return (float) $this->volumen_actual
            <= (float) $this->volumen_minimo_alerta;
    }

    public function tieneInventarioDisponible(): bool
    {
        return (float) $this->volumen_actual > 0;
    }

    /**
     * Porcentaje actual de inventario respecto de la capacidad instalada.
     */
    public function porcentajeDisponible(): float
    {
        if ((float) $this->capacidad_total <= 0) {
            return 0;
        }

        return round(
            (
                (float) $this->volumen_actual
                / (float) $this->capacidad_total
            ) * 100,
            2
        );
    }

    /**
     * Espacio disponible para recibir combustible.
     */
    public function capacidadDisponible(): float
    {
        return max(
            (float) $this->capacidad_total
                - (float) $this->volumen_actual,
            0
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
            'capacidad_total' => 'decimal:2',
            'volumen_actual' => 'decimal:2',
            'volumen_minimo_alerta' => 'decimal:2',

            'fecha_creacion' => 'datetime',
            'fecha_actualizacion' => 'datetime',
            'fecha_inactivacion' => 'datetime',
        ];
    }
}