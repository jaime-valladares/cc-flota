<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenciaTanque extends Model
{
    protected $table = 'licencia_tanques';

    protected $fillable = [
        'licencia_id',
        'unidad_tanque_id',
        'numero_tanque_snapshot',
        'capacidad_snapshot',
    ];

    public function licencia(): BelongsTo
    {
        return $this->belongsTo(Licencia::class);
    }

    public function unidadTanque(): BelongsTo
    {
        return $this->belongsTo(UnidadTanque::class);
    }

    protected function casts(): array
    {
        return [
            'numero_tanque_snapshot' => 'integer',
            'capacidad_snapshot' => 'decimal:2',
        ];
    }
}
