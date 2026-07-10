<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ruta extends Model
{
    protected $table = 'rutas';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'punto_origen_id',
        'punto_destino_id',
        'ruta',
        'kilometros_estimados',
        'galones_estimados',
        'estado',
        'fecha_creacion',
        'creado_por',
        'fecha_actualizacion',
        'actualizado_por',
        'fecha_inactivacion',
        'inactivado_por',
        'motivo_inactivacion',
    ];

    protected $casts = [
        'kilometros_estimados' => 'decimal:2',
        'galones_estimados' => 'decimal:2',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'fecha_inactivacion' => 'datetime',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id');
    }

    public function puntoOrigen(): BelongsTo
    {
        return $this->belongsTo(PuntoRuta::class, 'punto_origen_id');
    }

    public function puntoDestino(): BelongsTo
    {
        return $this->belongsTo(PuntoRuta::class, 'punto_destino_id');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }

    public function inactivadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inactivado_por');
    }
}