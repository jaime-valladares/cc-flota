<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnidadTanque extends Model
{
    protected $table = 'unidad_tanques';

    protected $fillable = [
        'unidad_id',
        'numero',
        'capacidad',
        'cubierto_por_licencia',
    ];

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class, 'unidad_id');
    }

    protected function casts(): array
    {
        return [
            'numero' => 'integer',
            'capacidad' => 'decimal:2',
            'cubierto_por_licencia' => 'boolean',
        ];
    }
}
