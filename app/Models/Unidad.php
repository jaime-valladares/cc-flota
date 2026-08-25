<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'empresa_id',
    'placa',
    'marca',
    'total_tanques',
    'cantidad_tanques_con_licencia',
    'capacidad_total',
    'capacidad_cubierta',
    'modelo_medicion',
    'estado',
    'creado_por',
    'actualizado_por',
    'fecha_inactivacion',
    'inactivado_por',
    'motivo_inactivacion',
])]
class Unidad extends Model
{
    /**
     * Nombre explícito de la tabla asociada al modelo.
     */
    protected $table = 'unidades';

    /*
    |--------------------------------------------------------------------------
    | Relaciones principales
    |--------------------------------------------------------------------------
    */

    /**
     * Empresa propietaria de la unidad.
     */
    public function empresa(): BelongsTo
    {
        return $this->belongsTo(
            Empresa::class,
            'empresa_id'
        );
    }

    /**
     * Licencia permanente asociada a la unidad.
     */
    public function licencia(): HasOne
    {
        return $this->hasOne(
            Licencia::class,
            'unidad_id'
        );
    }

    /**
     * Puntos de seguridad configurados para la unidad.
     */
    public function puntosSeguridad(): HasMany
    {
        return $this->hasMany(
            PuntoSeguridadUnidad::class,
            'unidad_id'
        )->orderBy('orden');
    }

    /**
     * Historial completo de marchamos asociados a la unidad.
     */
    public function marchamos(): HasMany
    {
        return $this->hasMany(
            Marchamo::class,
            'unidad_id'
        );
    }

    /**
     * Historial de eventos de reemplazo de marchamos.
     */
    public function reemplazoMarchamosEventos(): HasMany
    {
        return $this->hasMany(
            ReemplazoMarchamoEvento::class,
            'unidad_id'
        );
    }

    /**
     * Historial completo de abastecimientos de la unidad.
     */
    public function abastecimientos(): HasMany
    {
        return $this->hasMany(
            Abastecimiento::class,
            'unidad_id'
        )->orderByDesc('fecha_hora_abastecimiento');
    }

    /**
     * Abastecimientos registrados y vigentes de la unidad.
     */
    public function abastecimientosRegistrados(): HasMany
    {
        return $this->hasMany(
            Abastecimiento::class,
            'unidad_id'
        )
            ->where(
                'estado',
                Abastecimiento::ESTADO_REGISTRADO
            )
            ->orderByDesc('fecha_hora_abastecimiento');
    }

    /**
     * Último abastecimiento registrado y no anulado.
     */
    public function ultimoAbastecimientoRegistrado(): HasOne
    {
        return $this->hasOne(
            Abastecimiento::class,
            'unidad_id'
        )
            ->where(
                'estado',
                Abastecimiento::ESTADO_REGISTRADO
            )
            ->ofMany(
                [
                    'fecha_hora_abastecimiento' => 'max',
                    'id' => 'max',
                ]
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Auditoría
    |--------------------------------------------------------------------------
    */

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
     * Usuario que inactivó administrativamente la unidad.
     */
    public function inactivadoPor(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'inactivado_por'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Textos principales
    |--------------------------------------------------------------------------
    */

    /**
     * Texto legible para el modelo de medición.
     */
    protected function modeloMedicionTexto(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->modelo_medicion) {
                'galones_hora' =>
                    'Galones por hora',

                'kilometros_galon' =>
                    'Kilómetros por galón',

                'galones_viaje' =>
                    'Galones por viaje',

                default =>
                    'No definido',
            }
        );
    }

    /**
     * Texto legible para el estado administrativo.
     *
     * Este estado no representa por sí solo la disponibilidad operativa.
     */
    protected function estadoTexto(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match ($this->estado) {
                'registrada' =>
                    'Registrada',

                'activa' =>
                    'Activa',

                'inactiva' =>
                    'Inactiva',

                default =>
                    'No definido',
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Cobertura de marchamos
    |--------------------------------------------------------------------------
    */

    /**
     * Total de puntos activos que requieren marchamo.
     */
    protected function totalPuntosQueRequierenMarchamo(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->relationLoaded('puntosSeguridad')) {
                    return $this->puntosSeguridad
                        ->where('estado', 'activo')
                        ->where('requiere_marchamo', true)
                        ->count();
                }

                return $this->puntosSeguridad()
                    ->where('estado', 'activo')
                    ->where('requiere_marchamo', true)
                    ->count();
            }
        );
    }

    /**
     * Total de puntos activos que ya tienen marchamo asignado.
     */
    protected function totalPuntosConMarchamoAsignado(): Attribute
    {
        return Attribute::make(
            get: function (): int {
                if ($this->relationLoaded('puntosSeguridad')) {
                    return $this->puntosSeguridad
                        ->where('estado', 'activo')
                        ->where('requiere_marchamo', true)
                        ->whereNotNull('marchamo_actual_id')
                        ->count();
                }

                return $this->puntosSeguridad()
                    ->where('estado', 'activo')
                    ->where('requiere_marchamo', true)
                    ->whereNotNull('marchamo_actual_id')
                    ->count();
            }
        );
    }

    /**
     * Total de puntos activos que todavía no tienen marchamo asignado.
     */
    protected function totalPuntosPendientesMarchamo(): Attribute
    {
        return Attribute::make(
            get: fn (): int => max(
                0,
                $this->total_puntos_que_requieren_marchamo
                    - $this->total_puntos_con_marchamo_asignado
            )
        );
    }

    /**
     * Indica si la asignación inicial de marchamos está completa.
     */
    protected function asignacionInicialMarchamosCompleta(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                $totalRequeridos =
                    $this->total_puntos_que_requieren_marchamo;

                if ($totalRequeridos === 0) {
                    return false;
                }

                return $this->total_puntos_con_marchamo_asignado
                    === $totalRequeridos;
            }
        );
    }

    /**
     * Texto resumido de la asignación inicial.
     */
    protected function asignacionInicialTexto(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                if ($this->asignacion_inicial_marchamos_completa) {
                    return sprintf(
                        '%d de %d puntos con marchamo asignado',
                        $this->total_puntos_con_marchamo_asignado,
                        $this->total_puntos_que_requieren_marchamo
                    );
                }

                if (
                    $this->total_puntos_que_requieren_marchamo
                    === 0
                ) {
                    return 'Los puntos de seguridad todavía no han sido generados.';
                }

                return sprintf(
                    '%d de %d puntos con marchamo asignado; %d pendientes',
                    $this->total_puntos_con_marchamo_asignado,
                    $this->total_puntos_que_requieren_marchamo,
                    $this->total_puntos_pendientes_marchamo
                );
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Disponibilidad operativa
    |--------------------------------------------------------------------------
    */

    /**
     * Código interno de disponibilidad operativa.
     *
     * Prioridad:
     * 1. Empresa inactiva.
     * 2. Unidad inactiva.
     * 3. Sin licencia.
     * 4. Licencia inactiva.
     * 5. Licencia pendiente de activación.
     * 6. Licencia vencida.
     * 7. Asignación inicial pendiente.
     * 8. Unidad registrada.
     * 9. Operable.
     */
    protected function disponibilidadOperativa(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                $this->loadMissing([
                    'empresa',
                    'licencia',
                ]);

                if (
                    ! $this->empresa
                    || $this->empresa->estado !== 'activa'
                ) {
                    return 'empresa_inactiva';
                }

                if ($this->estado === 'inactiva') {
                    return 'unidad_inactiva';
                }

                if (! $this->licencia) {
                    return 'sin_licencia';
                }

                if ($this->licencia->estado === 'inactiva') {
                    return 'licencia_inactiva';
                }

                if (
                    $this->licencia
                        ->esta_pendiente_activacion
                ) {
                    return 'licencia_pendiente_activacion';
                }

                if ($this->licencia->esta_vencida) {
                    return 'licencia_vencida';
                }

                if (
                    ! $this
                        ->asignacion_inicial_marchamos_completa
                ) {
                    return 'asignacion_inicial_pendiente';
                }

                if ($this->estado === 'registrada') {
                    return 'pendiente_activacion_operativa';
                }

                if ($this->estado === 'activa') {
                    return 'operable';
                }

                return 'no_definida';
            }
        );
    }

    /**
     * Texto breve para mostrar la disponibilidad operativa.
     */
    protected function disponibilidadOperativaTexto(): Attribute
    {
        return Attribute::make(
            get: fn (): string => match (
                $this->disponibilidad_operativa
            ) {
                'empresa_inactiva' =>
                    'Bloqueada por empresa inactiva',

                'unidad_inactiva' =>
                    'Unidad inactiva',

                'sin_licencia' =>
                    'Sin licencia',

                'licencia_inactiva' =>
                    'Bloqueada por licencia inactiva',

                'licencia_pendiente_activacion' =>
                    'Licencia pendiente de activación',

                'licencia_vencida' =>
                    'Bloqueada por licencia vencida',

                'asignacion_inicial_pendiente' =>
                    'Asignación inicial pendiente',

                'pendiente_activacion_operativa' =>
                    'Pendiente de activación operativa',

                'operable' =>
                    'Operable',

                default =>
                    'Disponibilidad no definida',
            }
        );
    }

    /**
     * Explicación completa de la disponibilidad operativa.
     */
    protected function disponibilidadOperativaDescripcion(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                return match (
                    $this->disponibilidad_operativa
                ) {
                    'empresa_inactiva' =>
                        'La unidad permanece disponible únicamente para consulta histórica porque su empresa está inactiva.',

                    'unidad_inactiva' =>
                        'La unidad fue inactivada administrativamente y no puede participar en operaciones.',

                    'sin_licencia' =>
                        'La unidad todavía no tiene una licencia registrada y no puede iniciar su configuración de puntos de seguridad.',

                    'licencia_inactiva' =>
                        'La unidad está bloqueada operativamente porque su licencia fue inactivada administrativamente.',

                    'licencia_pendiente_activacion' =>
                        sprintf(
                            'La licencia todavía no ha iniciado. Su fecha de activación es %s.',
                            $this->licencia?->fecha_activacion
                                ?->format('d/m/Y')
                                ?? 'no registrada'
                        ),

                    'licencia_vencida' =>
                        sprintf(
                            'La unidad está bloqueada operativamente porque su licencia venció el %s.',
                            $this->licencia?->fecha_vencimiento
                                ?->format('d/m/Y')
                                ?? 'día no registrado'
                        ),

                    'asignacion_inicial_pendiente' =>
                        'La licencia está vigente, pero Diesel Cop todavía debe completar la asignación inicial de marchamos.',

                    'pendiente_activacion_operativa' =>
                        'La licencia y los marchamos están completos, pero la unidad continúa registrada y pendiente de activación operativa.',

                    'operable' =>
                        'La empresa, la unidad, la licencia y la asignación inicial de marchamos cumplen las condiciones operativas.',

                    default =>
                        'No fue posible determinar la disponibilidad operativa de la unidad.',
                };
            }
        );
    }

    /**
     * Indica si la unidad puede participar en procesos operativos normales.
     */
    protected function esOperable(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->disponibilidad_operativa
                === 'operable'
        );
    }

    /**
     * Indica si la unidad puede participar en la configuración inicial.
     */
    protected function puedeRecibirAsignacionInicial(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                $this->disponibilidad_operativa
                === 'asignacion_inicial_pendiente'
        );
    }

    /**
     * Indica si la unidad está bloqueada específicamente por su licencia.
     */
    protected function bloqueadaPorLicencia(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => in_array(
                $this->disponibilidad_operativa,
                [
                    'sin_licencia',
                    'licencia_inactiva',
                    'licencia_pendiente_activacion',
                    'licencia_vencida',
                ],
                true
            )
        );
    }

    /**
     * Indica si la unidad debe mantenerse únicamente para consulta.
     */
    protected function soloConsulta(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => in_array(
                $this->disponibilidad_operativa,
                [
                    'empresa_inactiva',
                    'unidad_inactiva',
                    'licencia_inactiva',
                    'licencia_pendiente_activacion',
                    'licencia_vencida',
                ],
                true
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Abastecimientos
    |--------------------------------------------------------------------------
    */

    /**
     * Indica si la unidad tiene al menos un abastecimiento registrado.
     */
    protected function tieneAbastecimientosRegistrados(): Attribute
    {
        return Attribute::make(
            get: function (): bool {
                if (
                    $this->relationLoaded(
                        'abastecimientosRegistrados'
                    )
                ) {
                    return $this
                        ->abastecimientosRegistrados
                        ->isNotEmpty();
                }

                return $this
                    ->abastecimientosRegistrados()
                    ->exists();
            }
        );
    }

    /**
     * Indica si el próximo abastecimiento será la línea base inicial.
     */
    protected function requiereAbastecimientoInicial(): Attribute
    {
        return Attribute::make(
            get: fn (): bool =>
                ! $this->tiene_abastecimientos_registrados
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
            'total_tanques' =>
                'integer',

            'cantidad_tanques_con_licencia' =>
                'integer',

            'capacidad_total' =>
                'decimal:2',

            'capacidad_cubierta' =>
                'decimal:2',

            'fecha_inactivacion' =>
                'datetime',
        ];
    }
}
