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

    /**
     * Motivo principal almacenado en el evento.
     *
     * En operaciones con varios motivos corresponde al motivo principal
     * utilizado para clasificar la transacción. Los motivos específicos se
     * obtienen desde cada detalle y su marchamo anterior.
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
                'reemplazo_general' =>
                    'Reemplazo general',

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

    /**
     * Lista de motivos realmente utilizados en los reemplazos individuales.
     *
     * Para aprovechar este atributo sin consultas adicionales, se recomienda
     * cargar:
     *
     * detalles.marchamoAnterior
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

    /**
     * Texto resumido para mostrar todos los motivos de la operación.
     */
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

    /**
     * Indica si la transacción incluyó motivos diferentes.
     */
    protected function tieneMotivosMultiples(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                count($this->motivos_aplicados) > 1
        );
    }

    /**
     * Descripción adecuada para consultas y auditoría.
     */
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

    /**
     * Cantidad real de detalles cargados.
     *
     * Si la relación no está cargada se utiliza la cantidad registrada
     * originalmente en el evento.
     */
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

    /**
     * Verifica que la cantidad registrada coincida con los detalles cargados.
     */
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

    /**
     * Resumen breve del evento.
     */
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

            default =>
                'No definido',
        };
    }
}