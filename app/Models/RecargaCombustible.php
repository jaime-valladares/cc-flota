<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecargaCombustible extends Model
{
    protected $table = 'recargas_combustible';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'gasolinera_id',
        'precio_galon',
        'total_galones',
        'total_compra',
        'fecha_hora_recarga',
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
        'precio_galon' => 'decimal:4',
        'total_galones' => 'decimal:2',
        'total_compra' => 'decimal:2',
        'fecha_hora_recarga' => 'datetime',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'fecha_anulacion' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function gasolinera(): BelongsTo
    {
        return $this->belongsTo(Gasolinera::class, 'gasolinera_id');
    }

    public function movimientosInventario(): HasMany
    {
        return $this->hasMany(MovimientoInventarioCombustible::class, 'recarga_combustible_id');
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