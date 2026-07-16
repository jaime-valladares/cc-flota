<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gasolinera extends Model
{
    protected $table = 'gasolineras';

    public $timestamps = false;

    protected $fillable = [
        'empresa_id',
        'nombre',
        'direccion',
        'encargado',
        'telefono',
        'correo',
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
     * Todos los tanques asociados a la gasolinera.
     */
    public function tanques(): HasMany
    {
        return $this->hasMany(
            Tanque::class,
            'gasolinera_id'
        );
    }

    /**
     * Tanques activos disponibles para operaciones.
     */
    public function tanquesActivos(): HasMany
    {
        return $this->hasMany(
            Tanque::class,
            'gasolinera_id'
        )->where(
            'estado',
            'activo'
        );
    }

    /**
     * Historial completo de abastecimientos internos
     * originados en esta gasolinera.
     */
    public function abastecimientos(): HasMany
    {
        return $this->hasMany(
            Abastecimiento::class,
            'gasolinera_interna_id'
        )->orderByDesc(
            'fecha_hora_abastecimiento'
        );
    }

    /**
     * Abastecimientos internos registrados y no anulados.
     */
    public function abastecimientosRegistrados(): HasMany
    {
        return $this->hasMany(
            Abastecimiento::class,
            'gasolinera_interna_id'
        )
            ->where(
                'estado',
                Abastecimiento::ESTADO_REGISTRADO
            )
            ->orderByDesc(
                'fecha_hora_abastecimiento'
            );
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

    public function estaActiva(): bool
    {
        return $this->estado === 'activa';
    }

    public function estaInactiva(): bool
    {
        return $this->estado === 'inactiva';
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