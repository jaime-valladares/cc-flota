<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'empresa_id',
    'unidad_id',
    'periodo_vigencia_meses',
    'fecha_activacion',
    'fecha_vencimiento',
    'estado',
    'plantilla_puntos_seguridad',
    'observaciones',
    'creado_por',
    'actualizado_por',
    'fecha_inactivacion',
    'inactivado_por',
    'motivo_inactivacion',
])]
class Licencia extends Model
{
    /**
     * Cantidad de días utilizada para mostrar una licencia
     * como próxima a vencer.
     *
     * Este valor es únicamente informativo por ahora.
     * Las notificaciones se implementarán posteriormente.
     */
    public const DIAS_ALERTA_VENCIMIENTO = 30;

    /**
     * Nombre explícito de la tabla asociada al modelo.
     */
    protected $table = 'licencias';

    /**
     * Relación con la empresa asociada a la licencia.
     *
     * La empresa debe coincidir siempre con la empresa
     * propietaria de la unidad.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    /**
     * Relación uno a uno inversa con la unidad licenciada.
     */
    public function unidad(): BelongsTo
    {
        return $this->belongsTo(Unidad::class);
    }

    /**
     * Usuario que creó el registro.
     */
    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'creado_por'
        );
    }

    /**
     * Usuario que actualizó el registro por última vez.
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'actualizado_por'
        );
    }

    /**
     * Usuario que inactivó administrativamente la licencia.
     */
    public function inactivadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'inactivado_por'
        );
    }

    /**
     * Texto legible para el estado administrativo almacenado.
     *
     * Este valor no representa necesariamente la vigencia.
     * Una licencia puede estar administrativamente activa,
     * pero encontrarse vencida por fecha.
     */
    protected function estadoTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->estado) {
                'activa' => 'Activa',
                'inactiva' => 'Inactiva',
                default => 'No definido',
            }
        );
    }

    /**
     * Condición real de la licencia según estado y fechas.
     *
     * Valores posibles:
     * - inactiva
     * - pendiente_activacion
     * - vigente
     * - proxima_vencer
     * - vencida
     * - no_definida
     */
    protected function condicionVigencia(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->estado === 'inactiva') {
                    return 'inactiva';
                }

                if (
                    ! $this->fecha_activacion
                    || ! $this->fecha_vencimiento
                ) {
                    return 'no_definida';
                }

                $hoy = now()->startOfDay();

                $fechaActivacion = $this
                    ->fecha_activacion
                    ->copy()
                    ->startOfDay();

                $fechaVencimiento = $this
                    ->fecha_vencimiento
                    ->copy()
                    ->startOfDay();

                if ($fechaActivacion->gt($hoy)) {
                    return 'pendiente_activacion';
                }

                /*
                 * El día de vencimiento todavía se considera vigente.
                 * La licencia vence operativamente al día siguiente.
                 */
                if ($fechaVencimiento->lt($hoy)) {
                    return 'vencida';
                }

                $diasRestantes = (int) $hoy->diffInDays(
                    $fechaVencimiento,
                    false
                );

                if (
                    $diasRestantes
                    <= self::DIAS_ALERTA_VENCIMIENTO
                ) {
                    return 'proxima_vencer';
                }

                return 'vigente';
            }
        );
    }

    /**
     * Texto legible para la condición real de vigencia.
     */
    protected function condicionVigenciaTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match (
                $this->condicion_vigencia
            ) {
                'inactiva' => 'Inactiva',
                'pendiente_activacion' =>
                    'Pendiente de activación',
                'vigente' => 'Vigente',
                'proxima_vencer' =>
                    'Próxima a vencer',
                'vencida' => 'Vencida',
                default => 'No definida',
            }
        );
    }

    /**
     * Indica si la licencia está vigente en la fecha actual.
     *
     * Una licencia próxima a vencer todavía es vigente.
     */
    protected function estaVigente(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => in_array(
                $this->condicion_vigencia,
                [
                    'vigente',
                    'proxima_vencer',
                ],
                true
            )
        );
    }

    /**
     * Indica si la licencia todavía no ha iniciado.
     */
    protected function estaPendienteActivacion(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->condicion_vigencia
                === 'pendiente_activacion'
        );
    }

    /**
     * Indica si la licencia venció por fecha.
     */
    protected function estaVencida(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->condicion_vigencia
                === 'vencida'
        );
    }

    /**
     * Indica si la licencia está próxima a vencer.
     */
    protected function estaProximaVencer(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->condicion_vigencia
                === 'proxima_vencer'
        );
    }

    /**
     * Indica si la licencia fue inactivada administrativamente.
     */
    protected function estaInactiva(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->estado === 'inactiva'
        );
    }

    /**
     * Cantidad firmada de días respecto al vencimiento.
     *
     * Ejemplos:
     *  15  = vence en 15 días.
     *   0  = vence hoy.
     *  -4  = venció hace 4 días.
     */
    protected function diasParaVencimiento(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                if (! $this->fecha_vencimiento) {
                    return null;
                }

                return (int) now()
                    ->startOfDay()
                    ->diffInDays(
                        $this
                            ->fecha_vencimiento
                            ->copy()
                            ->startOfDay(),
                        false
                    );
            }
        );
    }

    /**
     * Cantidad de días transcurridos desde el vencimiento.
     *
     * Devuelve cero cuando la licencia todavía no ha vencido.
     */
    protected function diasVencida(): Attribute
    {
        return Attribute::make(
            get: function (): ?int {
                if (
                    is_null($this->dias_para_vencimiento)
                ) {
                    return null;
                }

                return $this->dias_para_vencimiento < 0
                    ? abs($this->dias_para_vencimiento)
                    : 0;
            }
        );
    }

    /**
     * Texto informativo relativo al vencimiento.
     */
    protected function vencimientoRelativoTexto(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if (
                    is_null($this->dias_para_vencimiento)
                ) {
                    return 'Fecha de vencimiento no definida';
                }

                if ($this->dias_para_vencimiento > 1) {
                    return sprintf(
                        'Vence en %d días',
                        $this->dias_para_vencimiento
                    );
                }

                if ($this->dias_para_vencimiento === 1) {
                    return 'Vence mañana';
                }

                if ($this->dias_para_vencimiento === 0) {
                    return 'Vence hoy';
                }

                if ($this->dias_vencida === 1) {
                    return 'Venció hace 1 día';
                }

                return sprintf(
                    'Venció hace %d días',
                    $this->dias_vencida
                );
            }
        );
    }

    /**
     * Indica si la licencia habilita la capa contractual
     * necesaria para operar la unidad.
     *
     * Esto no significa por sí solo que la unidad sea operable.
     * También deben cumplirse:
     *
     * - empresa activa;
     * - estado administrativo compatible;
     * - asignación inicial de marchamos completada.
     */
    protected function habilitaOperacion(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->esta_vigente
        );
    }

    /**
     * Texto explicativo sobre la capacidad habilitante
     * de la licencia.
     */
    protected function habilitacionOperacionTexto(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match (
                $this->condicion_vigencia
            ) {
                'vigente',
                'proxima_vencer' =>
                    'La licencia habilita la operación contractual de la unidad.',

                'pendiente_activacion' =>
                    'La licencia todavía no habilita la operación porque su fecha de activación no ha iniciado.',

                'vencida' =>
                    'La licencia no habilita la operación porque está vencida.',

                'inactiva' =>
                    'La licencia no habilita la operación porque fue inactivada administrativamente.',

                default =>
                    'No es posible determinar la habilitación operativa de la licencia.',
            }
        );
    }

    /**
     * Texto legible para el período de vigencia.
     */
    protected function periodoVigenciaTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match (
                (int) $this->periodo_vigencia_meses
            ) {
                3 => '3 meses',
                6 => '6 meses',
                12 => '12 meses',
                default => 'No definido',
            }
        );
    }

    /**
     * Texto legible para la plantilla de puntos de seguridad.
     */
    protected function plantillaPuntosSeguridadTexto(): Attribute
    {
        return Attribute::make(
            get: fn () => match (
                $this->plantilla_puntos_seguridad
            ) {
                'plantilla_1_tanque' =>
                    'Plantilla 1 tanque',
                'plantilla_2_tanques' =>
                    'Plantilla 2 tanques',
                'plantilla_3_tanques' =>
                    'Plantilla 3 tanques',
                default => 'No definida',
            }
        );
    }

    /**
     * Cantidad esperada de puntos de seguridad
     * según la plantilla asignada.
     *
     * Este valor no se almacena en licencias.
     */
    protected function cantidadPuntosSeguridadEsperados(): Attribute
    {
        return Attribute::make(
            get: fn () => match (
                $this->plantilla_puntos_seguridad
            ) {
                'plantilla_1_tanque' => 29,
                'plantilla_2_tanques' => 38,
                'plantilla_3_tanques' => 49,
                default => null,
            }
        );
    }

    /**
     * Conversión de tipos.
     */
    protected function casts(): array
    {
        return [
            'periodo_vigencia_meses' => 'integer',
            'fecha_activacion' => 'date',
            'fecha_vencimiento' => 'date',
            'fecha_inactivacion' => 'datetime',
        ];
    }
}