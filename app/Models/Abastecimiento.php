<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'empresa_id',
    'unidad_id',
    'motorista_id',
    'abastecimiento_anterior_id',
    'registrado_por',

    'empresa_nombre_snapshot',
    'unidad_placa_snapshot',
    'unidad_marca_snapshot',
    'unidad_modelo_snapshot',
    'motorista_nombre_snapshot',
    'motorista_licencia_snapshot',

    'fecha_hora_abastecimiento',
    'estado',
    'modelo_medicion',
    'rendimiento_teorico_km_galon_snapshot',
    'rendimiento_teorico_gal_hora_snapshot',

    /*
    |--------------------------------------------------------------------------
    | Campos históricos temporales
    |--------------------------------------------------------------------------
    */

    'lectura_actual',
    'lectura_anterior',
    'diferencia_lectura',

    /*
    |--------------------------------------------------------------------------
    | Kilometraje
    |--------------------------------------------------------------------------
    */

    'kilometraje_actual',
    'kilometraje_anterior',
    'diferencia_kilometraje',

    /*
    |--------------------------------------------------------------------------
    | Horómetro
    |--------------------------------------------------------------------------
    */

    'horometro_actual',
    'horometro_anterior',
    'diferencia_horometro',

    /*
    |--------------------------------------------------------------------------
    | Combustible
    |--------------------------------------------------------------------------
    */

    'volumen_inicial',
    'volumen_cargado',
    'volumen_final',
    'capacidad_cubierta_snapshot',

    'volumen_final_anterior',
    'combustible_consumido_ciclo',
    'combustible_adicional_no_explicado',
    'consumo_real_ciclo',
    'consumo_teorico_ciclo',
    'diferencia_galones_ciclo',
    'costo_combustible_consumido_ciclo',
    'valor_remanente_antes_carga_snapshot',

    /*
    |--------------------------------------------------------------------------
    | Origen
    |--------------------------------------------------------------------------
    */

    'tipo_origen',
    'gasolinera_interna_id',
    'gasolinera_externa_id',
    'origen_nombre_snapshot',

    'precio_galon',
    'total_pagado',
    'moneda',
    'valor_carga_snapshot',
    'costo_efectivo_carga_snapshot',
    'valor_abordo_resultante',
    'costo_promedio_abordo_resultante',

    /*
    |--------------------------------------------------------------------------
    | Rutas y rendimiento
    |--------------------------------------------------------------------------
    */

    'total_rutas',
    'total_viajes',
    'kilometros_teoricos',
    'galones_teoricos',

    'galones_por_kilometro',
    'kilometros_por_galon',
    'galones_por_hora',

    'diferencia_kilometros_teoricos',
    'diferencia_galones_teoricos',

    /*
    |--------------------------------------------------------------------------
    | Marchamos
    |--------------------------------------------------------------------------
    */

    'total_tapones_abiertos',
    'total_marchamos_reemplazados',

    /*
    |--------------------------------------------------------------------------
    | Anulación
    |--------------------------------------------------------------------------
    */

    'fecha_anulacion',
    'anulado_por',
    'motivo_anulacion',
])]
class Abastecimiento extends Model
{
    use HasFactory;

    /*
    |--------------------------------------------------------------------------
    | Estados
    |--------------------------------------------------------------------------
    */

    public const ESTADO_REGISTRADO = 'registrado';

    public const ESTADO_ANULADO = 'anulado';

    /*
    |--------------------------------------------------------------------------
    | Tipos de origen
    |--------------------------------------------------------------------------
    */

    public const ORIGEN_INTERNO = 'interno';

    public const ORIGEN_EXTERNO = 'externo';

    /*
    |--------------------------------------------------------------------------
    | Modelos de medición
    |--------------------------------------------------------------------------
    */

    public const MODELO_KILOMETROS_GALON =
        'kilometros_galon';

    public const MODELO_GALONES_HORA =
        'galones_hora';

    public const MODELO_GALONES_VIAJE =
        'galones_viaje';

    /*
    |--------------------------------------------------------------------------
    | Relaciones principales
    |--------------------------------------------------------------------------
    */

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(
            Empresa::class,
            'empresa_id'
        );
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(
            Unidad::class,
            'unidad_id'
        );
    }

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(
            Motorista::class,
            'motorista_id'
        );
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por'
        );
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'anulado_por'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cadena de abastecimientos
    |--------------------------------------------------------------------------
    */

    public function abastecimientoAnterior(): BelongsTo
    {
        return $this->belongsTo(
            Abastecimiento::class,
            'abastecimiento_anterior_id'
        );
    }

    public function abastecimientosSiguientes(): HasMany
    {
        return $this->hasMany(
            Abastecimiento::class,
            'abastecimiento_anterior_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Origen del combustible
    |--------------------------------------------------------------------------
    */

    public function gasolineraInterna(): BelongsTo
    {
        return $this->belongsTo(
            Gasolinera::class,
            'gasolinera_interna_id'
        );
    }

    public function gasolineraExterna(): BelongsTo
    {
        return $this->belongsTo(
            GasolineraExterna::class,
            'gasolinera_externa_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Detalles de la operación
    |--------------------------------------------------------------------------
    */

    public function tanques(): HasMany
    {
        return $this->hasMany(
            AbastecimientoTanque::class,
            'abastecimiento_id'
        )->orderBy('orden');
    }

    public function rutas(): HasMany
    {
        return $this->hasMany(
            AbastecimientoRuta::class,
            'abastecimiento_id'
        )->orderBy('orden');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(
            MovimientoInventarioCombustible::class,
            'abastecimiento_id'
        )->orderBy('fecha_hora_movimiento');
    }

    public function reemplazoMarchamoEvento(): HasOne
    {
        return $this->hasOne(
            ReemplazoMarchamoEvento::class,
            'abastecimiento_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeRegistrados($query)
    {
        return $query->where(
            'estado',
            self::ESTADO_REGISTRADO
        );
    }

    public function scopeAnulados($query)
    {
        return $query->where(
            'estado',
            self::ESTADO_ANULADO
        );
    }

    public function scopeDeEmpresa(
        $query,
        int $empresaId
    ) {
        return $query->where(
            'empresa_id',
            $empresaId
        );
    }

    public function scopeDeUnidad(
        $query,
        int $unidadId
    ) {
        return $query->where(
            'unidad_id',
            $unidadId
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Estados calculados
    |--------------------------------------------------------------------------
    */

    public function estaRegistrado(): bool
    {
        return $this->estado
            === self::ESTADO_REGISTRADO;
    }

    public function estaAnulado(): bool
    {
        return $this->estado
            === self::ESTADO_ANULADO;
    }

    public function esOrigenInterno(): bool
    {
        return $this->tipo_origen
            === self::ORIGEN_INTERNO;
    }

    public function esOrigenExterno(): bool
    {
        return $this->tipo_origen
            === self::ORIGEN_EXTERNO;
    }

    public function esPrimerAbastecimiento(): bool
    {
        return $this->abastecimiento_anterior_id === null;
    }

    /**
     * Todas las unidades registran kilometraje,
     * independientemente de su modelo de rendimiento.
     */
    public function usaKilometraje(): bool
    {
        return true;
    }

    /**
     * Solo las unidades con modelo por horas
     * requieren una lectura adicional de horómetro.
     */
    public function usaHorometro(): bool
    {
        return $this->modelo_medicion
            === self::MODELO_GALONES_HORA;
    }

    public function requiereRutas(): bool
    {
        return $this->modelo_medicion
            === self::MODELO_GALONES_VIAJE
            && ! $this->esPrimerAbastecimiento();
    }

    /*
    |--------------------------------------------------------------------------
    | Conversiones
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'fecha_hora_abastecimiento' => 'datetime',
            'fecha_anulacion' => 'datetime',

            /*
            |--------------------------------------------------------------------------
            | Campos históricos temporales
            |--------------------------------------------------------------------------
            */

            'lectura_actual' => 'decimal:2',
            'lectura_anterior' => 'decimal:2',
            'diferencia_lectura' => 'decimal:2',

            /*
            |--------------------------------------------------------------------------
            | Kilometraje
            |--------------------------------------------------------------------------
            */

            'kilometraje_actual' => 'decimal:2',
            'kilometraje_anterior' => 'decimal:2',
            'diferencia_kilometraje' => 'decimal:2',

            /*
            |--------------------------------------------------------------------------
            | Horómetro
            |--------------------------------------------------------------------------
            */

            'horometro_actual' => 'decimal:2',
            'horometro_anterior' => 'decimal:2',
            'diferencia_horometro' => 'decimal:2',

            /*
            |--------------------------------------------------------------------------
            | Combustible
            |--------------------------------------------------------------------------
            */

            'volumen_inicial' => 'decimal:2',
            'volumen_cargado' => 'decimal:2',
            'volumen_final' => 'decimal:2',
            'capacidad_cubierta_snapshot' => 'decimal:2',

            'volumen_final_anterior' => 'decimal:2',
            'combustible_consumido_ciclo' => 'decimal:2',
            'combustible_adicional_no_explicado' => 'decimal:2',
            'consumo_real_ciclo' => 'decimal:2',
            'consumo_teorico_ciclo' => 'decimal:8',
            'diferencia_galones_ciclo' => 'decimal:8',
            'costo_combustible_consumido_ciclo' => 'decimal:8',
            'valor_remanente_antes_carga_snapshot' => 'decimal:8',

            /*
            |--------------------------------------------------------------------------
            | Compra externa
            |--------------------------------------------------------------------------
            */

            'precio_galon' => 'decimal:4',
            'total_pagado' => 'decimal:2',
            'valor_carga_snapshot' => 'decimal:8',
            'costo_efectivo_carga_snapshot' => 'decimal:8',
            'valor_abordo_resultante' => 'decimal:8',
            'costo_promedio_abordo_resultante' => 'decimal:8',
            'rendimiento_teorico_km_galon_snapshot' => 'decimal:4',
            'rendimiento_teorico_gal_hora_snapshot' => 'decimal:4',

            /*
            |--------------------------------------------------------------------------
            | Rutas
            |--------------------------------------------------------------------------
            */

            'total_rutas' => 'integer',
            'total_viajes' => 'integer',
            'kilometros_teoricos' => 'decimal:2',
            'galones_teoricos' => 'decimal:2',

            /*
            |--------------------------------------------------------------------------
            | Rendimiento
            |--------------------------------------------------------------------------
            */

            'galones_por_kilometro' => 'decimal:6',
            'kilometros_por_galon' => 'decimal:6',
            'galones_por_hora' => 'decimal:6',

            'diferencia_kilometros_teoricos' => 'decimal:2',
            'diferencia_galones_teoricos' => 'decimal:2',

            /*
            |--------------------------------------------------------------------------
            | Marchamos
            |--------------------------------------------------------------------------
            */

            'total_tapones_abiertos' => 'integer',
            'total_marchamos_reemplazados' => 'integer',
        ];
    }
}
