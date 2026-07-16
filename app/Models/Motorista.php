<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Motorista extends Model
{
    protected $table = 'motoristas';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'nombres',
        'apellidos',
        'licencia',
        'telefono',
        'estado',
        'fecha_creacion',
        'creado_por',
        'fecha_actualizacion',
        'actualizado_por',
        'fecha_inactivacion',
        'inactivado_por',
        'motivo_inactivacion',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(
            Empresa::class,
            'empresa_id'
        );
    }

    /**
     * Historial completo de abastecimientos en los que
     * este motorista fue registrado como responsable.
     */
    public function abastecimientos(): HasMany
    {
        return $this->hasMany(
            Abastecimiento::class,
            'motorista_id'
        )->orderByDesc('fecha_hora_abastecimiento');
    }

    /**
     * Abastecimientos registrados y no anulados
     * asociados al motorista.
     */
    public function abastecimientosRegistrados(): HasMany
    {
        return $this->hasMany(
            Abastecimiento::class,
            'motorista_id'
        )
            ->where(
                'estado',
                Abastecimiento::ESTADO_REGISTRADO
            )
            ->orderByDesc('fecha_hora_abastecimiento');
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'creado_por'
        );
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actualizado_por'
        );
    }

    public function inactivadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'inactivado_por'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Estados funcionales
    |--------------------------------------------------------------------------
    */

    public function estaActivo(): bool
    {
        return $this->estado === 'activo';
    }

    public function estaInactivo(): bool
    {
        return $this->estado === 'inactivo';
    }

    /*
    |--------------------------------------------------------------------------
    | Atributos calculados
    |--------------------------------------------------------------------------
    */

    public function getNombreCompletoAttribute(): string
    {
        return trim(
            $this->nombres
            . ' '
            . $this->apellidos
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Conversiones
    |--------------------------------------------------------------------------
    */

    protected function casts(): array
    {
        return [
            'fecha_creacion' =>
                'datetime',

            'fecha_actualizacion' =>
                'datetime',

            'fecha_inactivacion' =>
                'datetime',
        ];
    }
}