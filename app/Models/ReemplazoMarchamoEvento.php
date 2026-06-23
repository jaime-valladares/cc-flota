<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReemplazoMarchamoEvento extends Model
{
    use HasFactory;

    protected $table = 'reemplazo_marchamos_eventos';

    protected $fillable = [
        'empresa_id',
        'unidad_id',
        'motivo_reemplazo',
        'cantidad_reemplazos',
        'origen_evento',
        'estado',
        'fecha_registro',
        'registrado_por',
        'fecha_anulacion',
        'anulado_por',
        'motivo_anulacion',
    ];

    protected $casts = [
        'fecha_registro' => 'datetime',
        'fecha_anulacion' => 'datetime',
        'cantidad_reemplazos' => 'integer',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(ReemplazoMarchamoDetalle::class, 'reemplazo_evento_id');
    }

    public function getMotivoReemplazoTextoAttribute(): string
    {
        return match ($this->motivo_reemplazo) {
            'dano' => 'Daño',
            'desgaste' => 'Desgaste',
            'perdida' => 'Pérdida',
            'manipulacion_detectada' => 'Manipulación detectada',
            'correccion_instalacion' => 'Corrección de instalación',
            default => 'No definido',
        };
    }

    public function getOrigenEventoTextoAttribute(): string
    {
        return match ($this->origen_evento) {
            'reemplazo_general' => 'Reemplazo general',
            default => 'No definido',
        };
    }

    public function getEstadoTextoAttribute(): string
    {
        return match ($this->estado) {
            'registrado' => 'Registrado',
            'anulado' => 'Anulado',
            default => 'No definido',
        };
    }

    public function getEstaRegistradoAttribute(): bool
    {
        return $this->estado === 'registrado';
    }

    public function getEstaAnuladoAttribute(): bool
    {
        return $this->estado === 'anulado';
    }
}