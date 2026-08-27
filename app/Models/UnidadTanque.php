<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function detallesLicencia(): HasMany
    {
        return $this->hasMany(LicenciaTanque::class, 'unidad_tanque_id');
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
