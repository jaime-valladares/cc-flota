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
        'tipo_movimiento',
        'volumen_anterior',
        'sentido_movimiento',
        'volumen_movimiento',
        'volumen_resultante',
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

    protected $casts = [
        'volumen_anterior' => 'decimal:2',
        'volumen_movimiento' => 'decimal:2',
        'volumen_resultante' => 'decimal:2',
        'fecha_hora_movimiento' => 'datetime',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'fecha_anulacion' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function tanque(): BelongsTo
    {
        return $this->belongsTo(Tanque::class, 'tanque_id');
    }

    public function usuarioRegistra(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_registra_id');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }
}