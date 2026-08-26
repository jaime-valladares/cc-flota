<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventarioCombustible extends Model
{
    protected $table = 'movimientos_inventario_combustible';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'tanque_id',
        'abastecimiento_id',
        'recarga_combustible_id',
        'tipo_movimiento',
        'volumen_anterior',
        'sentido_movimiento',
        'volumen_movimiento',
        'volumen_resultante',
        'valor_inventario_anterior',
        'valor_movimiento',
        'valor_inventario_resultante',
        'costo_unitario_aplicado',
        'subtotal_compra',
        'fecha_hora_movimiento',
        'observaciones',
        'usuario_registra_id',
        'estado',
        'fecha_creacion',
        'fecha_actualizacion',
        'actualizado_por',
        'fecha_anulacion',
        'anulado_por',
        'motivo_anulacion',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(
            Empresa::class,
            'empresa_id'
        );
    }

    public function tanque(): BelongsTo
    {
        return $this->belongsTo(
            Tanque::class,
            'tanque_id'
        );
    }

    /**
     * Abastecimiento de unidad que originó esta salida de inventario.
     */
    public function abastecimiento(): BelongsTo
    {
        return $this->belongsTo(
            Abastecimiento::class,
            'abastecimiento_id'
        );
    }

    /**
     * Recarga de combustible que originó esta entrada de inventario.
     */
    public function recargaCombustible(): BelongsTo
    {
        return $this->belongsTo(
            RecargaCombustible::class,
            'recarga_combustible_id'
        );
    }

    public function usuarioRegistra(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'usuario_registra_id'
        );
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actualizado_por'
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
    | Estados funcionales
    |--------------------------------------------------------------------------
    */

    public function perteneceAAbastecimiento(): bool
    {
        return ! is_null(
            $this->abastecimiento_id
        );
    }

    public function perteneceARecarga(): bool
    {
        return ! is_null(
            $this->recarga_combustible_id
        );
    }

    public function esEntradaRecarga(): bool
    {
        return $this->tipo_movimiento
            === 'entrada_recarga';
    }

    public function esSalidaAbastecimiento(): bool
    {
        return $this->tipo_movimiento
            === 'salida_abastecimiento';
    }

    public function estaRegistrado(): bool
    {
        return $this->estado === 'registrado';
    }

    public function estaAnulado(): bool
    {
        return $this->estado === 'anulado';
    }

    /*
    |--------------------------------------------------------------------------
    | Conversiones
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'volumen_anterior' => 'decimal:2',
            'volumen_movimiento' => 'decimal:2',
            'volumen_resultante' => 'decimal:2',
            'valor_inventario_anterior' => 'decimal:8',
            'valor_movimiento' => 'decimal:8',
            'valor_inventario_resultante' => 'decimal:8',
            'costo_unitario_aplicado' => 'decimal:8',
            'subtotal_compra' => 'decimal:2',

            'fecha_hora_movimiento' => 'datetime',
            'fecha_creacion' => 'datetime',
            'fecha_actualizacion' => 'datetime',
            'fecha_anulacion' => 'datetime',
        ];
    }
}
