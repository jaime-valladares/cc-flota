<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReemplazoMarchamoDetalle extends Model
{
    use HasFactory;

    protected $table = 'reemplazo_marchamos_detalle';

    protected $fillable = [
        'reemplazo_evento_id',
        'punto_seguridad_id',
        'marchamo_anterior_id',
        'marchamo_nuevo_id',
        'fecha_registro',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
    ];

    public function evento(): BelongsTo
    {
        return $this->belongsTo(ReemplazoMarchamoEvento::class, 'reemplazo_evento_id');
    }

    public function puntoSeguridad(): BelongsTo
    {
        return $this->belongsTo(PuntoSeguridadUnidad::class, 'punto_seguridad_id');
    }

    public function marchamoAnterior(): BelongsTo
    {
        return $this->belongsTo(Marchamo::class, 'marchamo_anterior_id');
    }

    public function marchamoNuevo(): BelongsTo
    {
        return $this->belongsTo(Marchamo::class, 'marchamo_nuevo_id');
    }
}