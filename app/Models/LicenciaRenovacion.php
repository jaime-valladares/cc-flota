<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'licencia_id',
    'fecha_vencimiento_anterior',
    'periodo_agregado_meses',
    'fecha_vencimiento_nueva',
    'renovado_por',
])]
class LicenciaRenovacion extends Model
{
    protected $table = 'licencia_renovaciones';

    public function licencia(): BelongsTo
    {
        return $this->belongsTo(Licencia::class);
    }

    public function renovadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'renovado_por');
    }

    protected function casts(): array
    {
        return [
            'periodo_agregado_meses' => 'integer',
            'fecha_vencimiento_anterior' => 'date',
            'fecha_vencimiento_nueva' => 'date',
        ];
    }
}
