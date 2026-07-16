<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'abastecimiento_id',
    'ruta_id',
    'orden',

    'tipo_recorrido',
    'factor_recorrido',

    'ruta_nombre_snapshot',

    'punto_origen_id',
    'punto_destino_id',

    'punto_origen_nombre_snapshot',
    'punto_destino_nombre_snapshot',

    'kilometros_base_snapshot',
    'galones_base_snapshot',

    'kilometros_aplicados',
    'galones_aplicados',
])]
class AbastecimientoRuta extends Model
{
    use HasFactory;

    public const TIPO_IDA = 'ida';

    public const TIPO_IDA_VUELTA = 'ida_vuelta';

    /**
     * Tabla asociada al modelo.
     */
    protected $table = 'abastecimiento_rutas';

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function abastecimiento(): BelongsTo
    {
        return $this->belongsTo(
            Abastecimiento::class,
            'abastecimiento_id'
        );
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(
            Ruta::class,
            'ruta_id'
        );
    }

    public function puntoOrigen(): BelongsTo
    {
        return $this->belongsTo(
            PuntoRuta::class,
            'punto_origen_id'
        );
    }

    public function puntoDestino(): BelongsTo
    {
        return $this->belongsTo(
            PuntoRuta::class,
            'punto_destino_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Estados y descripciones
    |--------------------------------------------------------------------------
    */

    public function esIda(): bool
    {
        return $this->tipo_recorrido === self::TIPO_IDA;
    }

    public function esIdaVuelta(): bool
    {
        return $this->tipo_recorrido === self::TIPO_IDA_VUELTA;
    }

    public function getTipoRecorridoTextoAttribute(): string
    {
        return match ($this->tipo_recorrido) {
            self::TIPO_IDA => 'Ida',
            self::TIPO_IDA_VUELTA => 'Ida y vuelta',
            default => 'No definido',
        };
    }

    public function getRecorridoTextoAttribute(): string
    {
        return trim(
            $this->punto_origen_nombre_snapshot
            . ' — '
            . $this->punto_destino_nombre_snapshot
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
            'orden' => 'integer',
            'factor_recorrido' => 'integer',

            'kilometros_base_snapshot' => 'decimal:2',
            'galones_base_snapshot' => 'decimal:2',

            'kilometros_aplicados' => 'decimal:2',
            'galones_aplicados' => 'decimal:2',
        ];
    }
}