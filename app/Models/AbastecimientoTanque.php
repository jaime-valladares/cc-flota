<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'abastecimiento_id',
    'tanque_id',
    'orden',

    'tanque_nombre_snapshot',
    'capacidad_total_snapshot',
    'volumen_minimo_alerta_snapshot',

    'inventario_anterior',
    'galones_retirados',
    'costo_promedio_galon_snapshot',
    'costo_total_snapshot',
    'inventario_resultante',

    'quedo_bajo_minimo',
])]
class AbastecimientoTanque extends Model
{
    use HasFactory;

    /**
     * Tabla asociada al modelo.
     */
    protected $table = 'abastecimiento_tanques';

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

    public function tanque(): BelongsTo
    {
        return $this->belongsTo(
            Tanque::class,
            'tanque_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Estados calculados
    |--------------------------------------------------------------------------
    */

    public function quedoBajoMinimo(): bool
    {
        return (bool) $this->quedo_bajo_minimo;
    }

    public function retiroTodoElInventario(): bool
    {
        return (float) $this->inventario_resultante === 0.0;
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

            'capacidad_total_snapshot' => 'decimal:2',
            'volumen_minimo_alerta_snapshot' => 'decimal:2',

            'inventario_anterior' => 'decimal:2',
            'galones_retirados' => 'decimal:2',
            'costo_promedio_galon_snapshot' => 'decimal:8',
            'costo_total_snapshot' => 'decimal:8',
            'inventario_resultante' => 'decimal:2',

            'quedo_bajo_minimo' => 'boolean',
        ];
    }
}
