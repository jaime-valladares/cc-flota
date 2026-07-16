<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReemplazoMarchamoEvento extends Model
{
    use HasFactory;

    public const ORIGEN_REEMPLAZO_GENERAL =
        'reemplazo_general';

    public const ORIGEN_ABASTECIMIENTO =
        'abastecimiento';

    public const MOTIVO_APERTURA_ABASTECIMIENTO =
        'apertura_abastecimiento';

    protected $table = 'reemplazo_marchamos_eventos';

    protected $fillable = [
        'empresa_id',
        'unidad_id',
        'abastecimiento_id',
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

    public function unidad(): BelongsTo
    {
        return $this->belongsTo(
            Unidad::class,
            'unidad_id'
        );
    }

    public function abastecimiento(): BelongsTo
    {
        return $this->belongsTo(
            Abastecimiento::class,
            'abastecimiento_id'
        );
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'registrado_por'
        );
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'anulado_por'
        );
    }

    /**
     * Reemplazos individuales incluidos en el evento.
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(
            ReemplazoMarchamoDetalle::class,
            'reemplazo_evento_id'
        )->orderBy('fecha_registro');
    }

    /*
    |--------------------------------------------------------------------------
    | Textos principales
    |--------------------------------------------------------------------------
    */

    protected function motivoReemplazoTexto(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->traducirMotivo(
                $this->motivo_reemplazo
            )
        );
    }

    protected function origenEventoTexto(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->origen_evento) {
                self::ORIGEN_REEMPLAZO_GENERAL =>
                    'Reemplazo general',

                self::ORIGEN_ABASTECIMIENTO =>
                    'Abastecimiento',

                default =>
                    'No definido',
            }
        );
    }

    protected function estadoTexto(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->estado) {
                'registrado' =>
                    'Registrado',

                'anulado' =>
                    'Anulado',

                default =>
                    'No definido',
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Motivos aplicados
    |--------------------------------------------------------------------------
    */

    protected function motivosAplicados(): Attribute
    {
        return Attribute::make(
            get: function (): array {
                if (! $this->relationLoaded('detalles')) {
                    return $this->motivo_reemplazo
                        ? [$this->motivo_reemplazo_texto]
                        : [];
                }

                return $this->detalles
                    ->map(function ($detalle) {
                        if (
                            ! $detalle->relationLoaded(
                                'marchamoAnterior'
                            )
                        ) {
                            return null;
                        }

                        return $detalle
                            ->marchamoAnterior
                            ?->motivo_desactivacion;
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }
        );
    }

    protected function motivosAplicadosTexto(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $motivos = collect(
                    $this->motivos_aplicados
                )
                    ->filter()
                    ->unique()
                    ->values();

                if ($motivos->isEmpty()) {
                    return $this->motivo_reemplazo_texto;
                }

                return $motivos->implode(', ');
            }
        );
    }

    protected function tieneMotivosMultiples(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                count($this->motivos_aplicados) > 1
        );
    }

    protected function clasificacionMotivoTexto(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->tiene_motivos_multiples) {
                    return 'Motivos múltiples';
                }

                return $this->motivos_aplicados_texto;
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Indicadores funcionales
    |--------------------------------------------------------------------------
    */

    protected function estaRegistrado(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->estado === 'registrado'
        );
    }

    protected function estaAnulado(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->estado === 'anulado'
        );
    }

    protected function esReemplazoGeneral(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->origen_evento
                === self::ORIGEN_REEMPLAZO_GENERAL
        );
    }

    protected function esAbastecimiento(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->origen_evento
                === self::ORIGEN_ABASTECIMIENTO
        );
    }

    protected function puedeConsultarse(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => in_array(
                $this->estado,
                [
                    'registrado',
                    'anulado',
                ],
                true
            )
        );
    }

    protected function cantidadDetalles(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->relationLoaded('detalles')) {
                    return $this->detalles->count();
                }

                return (int) $this->cantidad_reemplazos;
            }
        );
    }

    protected function cantidadConsistente(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                if (! $this->relationLoaded('detalles')) {
                    return true;
                }

                return (int) $this->cantidad_reemplazos
                    === $this->detalles->count();
            }
        );
    }

    protected function resumenTexto(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $cantidad = (int) $this->cantidad_reemplazos;

                $descripcionCantidad = $cantidad === 1
                    ? '1 marchamo reemplazado'
                    : "{$cantidad} marchamos reemplazados";

                return $descripcionCantidad
                    . ' · '
                    . $this->clasificacion_motivo_texto;
            }
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
            'fecha_registro' =>
                'datetime',

            'fecha_anulacion' =>
                'datetime',

            'cantidad_reemplazos' =>
                'integer',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Utilidades internas
    |--------------------------------------------------------------------------
    */

    private function traducirMotivo(
        ?string $motivo
    ): string {
        return match ($motivo) {
            'dano' =>
                'Daño',

            'desgaste' =>
                'Desgaste',

            'perdida' =>
                'Pérdida',

            'manipulacion_detectada' =>
                'Manipulación detectada',

            'correccion_instalacion' =>
                'Corrección de instalación',

            self::MOTIVO_APERTURA_ABASTECIMIENTO =>
                'Apertura por abastecimiento',

            default =>
                'No definido',
        };
    }
}