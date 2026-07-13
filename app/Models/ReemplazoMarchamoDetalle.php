<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'reemplazo_evento_id',
    'punto_seguridad_id',
    'marchamo_anterior_id',
    'marchamo_nuevo_id',
    'fecha_registro',
])]
class ReemplazoMarchamoDetalle extends Model
{
    protected $table = 'reemplazo_marchamos_detalle';

    /*
    |--------------------------------------------------------------------------
    | Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * Evento general al que pertenece este reemplazo.
     */
    public function evento(): BelongsTo
    {
        return $this->belongsTo(
            ReemplazoMarchamoEvento::class,
            'reemplazo_evento_id'
        );
    }

    /**
     * Punto de seguridad afectado por el reemplazo.
     */
    public function puntoSeguridad(): BelongsTo
    {
        return $this->belongsTo(
            PuntoSeguridadUnidad::class,
            'punto_seguridad_id'
        );
    }

    /**
     * Marchamo retirado y convertido en histórico.
     */
    public function marchamoAnterior(): BelongsTo
    {
        return $this->belongsTo(
            Marchamo::class,
            'marchamo_anterior_id'
        );
    }

    /**
     * Nuevo marchamo instalado como actual.
     */
    public function marchamoNuevo(): BelongsTo
    {
        return $this->belongsTo(
            Marchamo::class,
            'marchamo_nuevo_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Información derivada
    |--------------------------------------------------------------------------
    */

    /**
     * Motivo específico aplicado a este reemplazo.
     *
     * Actualmente se obtiene del marchamo anterior, porque ese registro
     * conserva el motivo por el cual fue desactivado.
     */
    protected function motivoReemplazoTexto(): Attribute
    {
        return Attribute::make(
            get: fn (): string =>
                $this->marchamoAnterior?->motivo_desactivacion
                ?: 'No definido'
        );
    }

    /**
     * Código que dejó de estar activo.
     */
    protected function codigoAnterior(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->marchamoAnterior?->codigo_marchamo
        );
    }

    /**
     * Código que pasó a ser el marchamo actual.
     */
    protected function codigoNuevo(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string =>
                $this->marchamoNuevo?->codigo_marchamo
        );
    }

    /**
     * Nombre legible del punto reemplazado.
     */
    protected function puntoSeguridadTexto(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (! $this->puntoSeguridad) {
                    return 'Punto no disponible';
                }

                $texto = $this->puntoSeguridad->nombre_punto
                    ?: 'Punto sin nombre';

                if ($this->puntoSeguridad->codigo_punto) {
                    $texto .= ' · '
                        . $this->puntoSeguridad->codigo_punto;
                }

                return $texto;
            }
        );
    }

    /**
     * Resumen breve del reemplazo para consultas y auditoría.
     */
    protected function resumenTexto(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $codigoAnterior =
                    $this->codigo_anterior
                    ?: 'Sin código anterior';

                $codigoNuevo =
                    $this->codigo_nuevo
                    ?: 'Sin código nuevo';

                return $codigoAnterior
                    . ' → '
                    . $codigoNuevo;
            }
        );
    }

    /**
     * Confirma que el detalle posee las relaciones mínimas necesarias
     * para representar un reemplazo completo.
     */
    protected function estaCompleto(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                ! is_null($this->reemplazo_evento_id)
                && ! is_null($this->punto_seguridad_id)
                && ! is_null($this->marchamo_anterior_id)
                && ! is_null($this->marchamo_nuevo_id)
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
            'fecha_registro' => 'datetime',
        ];
    }
}