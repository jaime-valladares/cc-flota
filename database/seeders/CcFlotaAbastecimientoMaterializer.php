<?php

namespace Database\Seeders;

use App\Models\Abastecimiento;
use App\Models\AbastecimientoRuta;
use App\Models\AbastecimientoTanque;
use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\GasolineraExterna;
use App\Models\Marchamo;
use App\Models\Motorista;
use App\Models\MovimientoInventarioCombustible;
use App\Models\PuntoSeguridadUnidad;
use App\Models\ReemplazoMarchamoDetalle;
use App\Models\ReemplazoMarchamoEvento;
use App\Models\Ruta;
use App\Models\Tanque;
use App\Models\Unidad;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use RuntimeException;

final class CcFlotaAbastecimientoMaterializer
{
    /**
     * Materializa un abastecimiento planificado.
     *
     * Este materializador no inicia su propia transacción porque será
     * ejecutado dentro del recorrido cronológico operacional completo.
     */
    public static function crear(
        array $evento,
        int $usuarioId
    ): Abastecimiento {
        self::validarEvento($evento);

        $fechaOperacion =
            CarbonImmutable::parse(
                $evento['fecha_hora']
            );

        $empresa = Empresa::query()
            ->findOrFail(
                (int) $evento['empresa_id']
            );

        $unidad = Unidad::query()
            ->findOrFail(
                (int) $evento['unidad_id']
            );

        $motorista = Motorista::query()
            ->findOrFail(
                (int) $evento['motorista_id']
            );

        self::validarPertenencia(
            empresa: $empresa,
            unidad: $unidad,
            motorista: $motorista
        );

        $anterior = Abastecimiento::query()
            ->where(
                'unidad_id',
                $unidad->id
            )
            ->where(
                'estado',
                Abastecimiento::ESTADO_REGISTRADO
            )
            ->orderByDesc(
                'fecha_hora_abastecimiento'
            )
            ->orderByDesc('id')
            ->first();

        $lecturas =
            self::calcularLecturas(
                evento: $evento,
                unidad: $unidad,
                anterior: $anterior
            );

        $combustible =
            self::calcularCombustible(
                evento: $evento,
                unidad: $unidad,
                anterior: $anterior
            );

        $rutas =
            self::prepararRutas(
                evento: $evento,
                empresaId: $empresa->id
            );

        $origen =
            self::prepararOrigen(
                evento: $evento,
                empresaId: $empresa->id
            );

        $metricas =
            self::calcularMetricas(
                evento: $evento,
                lecturas: $lecturas,
                combustible: $combustible,
                rutas: $rutas
            );

        $puntosTapones =
            self::seleccionarTapones(
                unidad: $unidad,
                numeroOperacion:
                    (int) $evento['numero']
            );

        $abastecimiento =
            new Abastecimiento([
                'empresa_id' =>
                    $empresa->id,

                'unidad_id' =>
                    $unidad->id,

                'motorista_id' =>
                    $motorista->id,

                'abastecimiento_anterior_id' =>
                    $anterior?->id,

                'registrado_por' =>
                    $usuarioId,

                'empresa_nombre_snapshot' =>
                    $empresa->nombre_comercial
                    ?: $empresa->nombre_legal,

                'unidad_placa_snapshot' =>
                    $unidad->placa,

                'unidad_marca_snapshot' =>
                    $unidad->marca,

                'unidad_modelo_snapshot' =>
                    null,

                'motorista_nombre_snapshot' =>
                    trim(
                        $motorista->nombres
                        . ' '
                        . $motorista->apellidos
                    ),

                'motorista_licencia_snapshot' =>
                    $motorista->licencia,

                'fecha_hora_abastecimiento' =>
                    $fechaOperacion,

                'estado' =>
                    Abastecimiento::ESTADO_REGISTRADO,

                'modelo_medicion' =>
                    $unidad->modelo_medicion,

                /*
                 * Compatibilidad histórica: lectura_* refleja kilometraje.
                 */
                'lectura_actual' =>
                    $lecturas['kilometraje_actual'],

                'lectura_anterior' =>
                    $lecturas['kilometraje_anterior'],

                'diferencia_lectura' =>
                    $lecturas['diferencia_kilometraje'],

                'kilometraje_actual' =>
                    $lecturas['kilometraje_actual'],

                'kilometraje_anterior' =>
                    $lecturas['kilometraje_anterior'],

                'diferencia_kilometraje' =>
                    $lecturas['diferencia_kilometraje'],

                'horometro_actual' =>
                    $lecturas['horometro_actual'],

                'horometro_anterior' =>
                    $lecturas['horometro_anterior'],

                'diferencia_horometro' =>
                    $lecturas['diferencia_horometro'],

                'volumen_inicial' =>
                    $combustible['volumen_inicial'],

                'volumen_cargado' =>
                    $combustible['volumen_cargado'],

                'volumen_final' =>
                    $combustible['volumen_final'],

                'capacidad_cubierta_snapshot' =>
                    $combustible['capacidad_cubierta'],

                'volumen_final_anterior' =>
                    $combustible[
                        'volumen_final_anterior'
                    ],

                'combustible_consumido_ciclo' =>
                    $combustible[
                        'combustible_consumido'
                    ],

                'combustible_adicional_no_explicado' =>
                    $combustible[
                        'combustible_adicional'
                    ],

                'tipo_origen' =>
                    $origen['tipo'],

                'gasolinera_interna_id' =>
                    $origen[
                        'gasolinera_interna_id'
                    ],

                'gasolinera_externa_id' =>
                    $origen[
                        'gasolinera_externa_id'
                    ],

                'origen_nombre_snapshot' =>
                    $origen['nombre_snapshot'],

                'precio_galon' =>
                    $origen['precio_galon'],

                'total_pagado' =>
                    $origen['total_pagado'],

                'moneda' =>
                    $origen['moneda'],

                'total_rutas' =>
                    count($rutas),

                'kilometros_teoricos' =>
                    $metricas['kilometros_teoricos'],

                'galones_teoricos' =>
                    $metricas['galones_teoricos'],

                'galones_por_kilometro' =>
                    $metricas['galones_por_kilometro'],

                'kilometros_por_galon' =>
                    $metricas['kilometros_por_galon'],

                'galones_por_hora' =>
                    $metricas['galones_por_hora'],

                'diferencia_kilometros_teoricos' =>
                    $metricas[
                        'diferencia_kilometros_teoricos'
                    ],

                'diferencia_galones_teoricos' =>
                    $metricas[
                        'diferencia_galones_teoricos'
                    ],

                'total_tapones_abiertos' =>
                    $puntosTapones->count(),

                'total_marchamos_reemplazados' =>
                    $puntosTapones->count(),
            ]);

        $abastecimiento->created_at =
            $fechaOperacion;

        $abastecimiento->updated_at =
            $fechaOperacion;

        $abastecimiento->save();

        if (
            $origen['tipo']
            === Abastecimiento::ORIGEN_INTERNO
        ) {
            self::guardarSalidaInterna(
                abastecimiento: $abastecimiento,
                evento: $evento,
                fechaOperacion: $fechaOperacion,
                usuarioId: $usuarioId
            );
        }

        self::guardarRutas(
            abastecimiento: $abastecimiento,
            rutas: $rutas,
            fechaOperacion: $fechaOperacion
        );

        self::guardarMarchamos(
            abastecimiento: $abastecimiento,
            puntos: $puntosTapones,
            fechaOperacion: $fechaOperacion,
            usuarioId: $usuarioId
        );

        return $abastecimiento;
    }

    /**
     * Valida la estructura mínima del evento.
     */
    private static function validarEvento(
        array $evento
    ): void {
        $campos = [
            'tipo',
            'numero',
            'fecha_hora',
            'empresa_id',
            'unidad_id',
            'motorista_id',
            'numero_ciclo',
            'es_linea_base',
            'modelo_medicion',
            'volumen_inicial',
            'volumen_cargado',
            'volumen_final',
            'origen',
            'detalles_tanque',
            'rutas',
        ];

        foreach (
            $campos
            as $campo
        ) {
            if (! array_key_exists($campo, $evento)) {
                throw new RuntimeException(
                    "El evento de abastecimiento no contiene "
                    . "el campo [{$campo}]."
                );
            }
        }

        if (
            $evento['tipo']
            !== 'abastecimiento'
        ) {
            throw new RuntimeException(
                'El materializador recibió un evento '
                . 'que no es un abastecimiento.'
            );
        }

        if (
            ! in_array(
                $evento['modelo_medicion'],
                [
                    Abastecimiento::
                        MODELO_KILOMETROS_GALON,

                    Abastecimiento::
                        MODELO_GALONES_HORA,

                    Abastecimiento::
                        MODELO_GALONES_VIAJE,
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El modelo de medición planificado '
                . 'no es válido.'
            );
        }

        if (
            (float) $evento['volumen_cargado']
            <= 0
        ) {
            throw new RuntimeException(
                'El abastecimiento debe contener '
                . 'un volumen cargado positivo.'
            );
        }
    }

    /**
     * Confirma relaciones multiempresa.
     */
    private static function validarPertenencia(
        Empresa $empresa,
        Unidad $unidad,
        Motorista $motorista
    ): void {
        if (
            (int) $unidad->empresa_id
            !== (int) $empresa->id
        ) {
            throw new RuntimeException(
                'La unidad no pertenece a la empresa '
                . 'del abastecimiento.'
            );
        }

        if (
            (int) $motorista->empresa_id
            !== (int) $empresa->id
        ) {
            throw new RuntimeException(
                'El motorista no pertenece a la empresa '
                . 'del abastecimiento.'
            );
        }
    }

    /**
     * Calcula kilometraje y horómetro de forma ascendente.
     */
    private static function calcularLecturas(
        array $evento,
        Unidad $unidad,
        ?Abastecimiento $anterior
    ): array {
        if (! $anterior) {
            $kilometrajeActual =
                round(
                    15000
                    + ($unidad->id * 137.5),
                    2
                );

            $horometroActual =
                $unidad->modelo_medicion
                    === Abastecimiento::
                        MODELO_GALONES_HORA
                        ? round(
                            700
                            + ($unidad->id * 4.25),
                            2
                        )
                        : null;

            return [
                'kilometraje_actual' =>
                    $kilometrajeActual,

                'kilometraje_anterior' =>
                    null,

                'diferencia_kilometraje' =>
                    null,

                'horometro_actual' =>
                    $horometroActual,

                'horometro_anterior' =>
                    null,

                'diferencia_horometro' =>
                    null,
            ];
        }

        $kilometrajeAnterior =
            (float) (
                $anterior->kilometraje_actual
                ?? $anterior->lectura_actual
            );

        $incrementoKilometraje =
            self::incrementoKilometraje(
                evento: $evento,
                unidad: $unidad
            );

        $kilometrajeActual =
            round(
                $kilometrajeAnterior
                + $incrementoKilometraje,
                2
            );

        $horometroAnterior = null;
        $horometroActual = null;
        $diferenciaHorometro = null;

        if (
            $unidad->modelo_medicion
            === Abastecimiento::
                MODELO_GALONES_HORA
        ) {
            $horometroAnterior =
                (float) $anterior->horometro_actual;

            $diferenciaHorometro =
                round(
                    5
                    + (
                        (
                            (int) $evento['numero']
                            * 7
                        ) % 18
                    )
                    + (
                        (
                            (int) $evento['numero']
                            % 4
                        ) * 0.25
                    ),
                    2
                );

            $horometroActual =
                round(
                    $horometroAnterior
                    + $diferenciaHorometro,
                    2
                );
        }

        return [
            'kilometraje_actual' =>
                $kilometrajeActual,

            'kilometraje_anterior' =>
                $kilometrajeAnterior,

            'diferencia_kilometraje' =>
                $incrementoKilometraje,

            'horometro_actual' =>
                $horometroActual,

            'horometro_anterior' =>
                $horometroAnterior,

            'diferencia_horometro' =>
                $diferenciaHorometro,
        ];
    }

    /**
     * Determina el incremento de kilometraje.
     */
    private static function incrementoKilometraje(
        array $evento,
        Unidad $unidad
    ): float {
        if (
            $unidad->modelo_medicion
            === Abastecimiento::
                MODELO_GALONES_VIAJE
            && $evento['rutas'] !== []
        ) {
            $total = 0.0;

            foreach (
                $evento['rutas']
                as $rutaPlan
            ) {
                $ruta = Ruta::query()
                    ->findOrFail(
                        (int) $rutaPlan['ruta_id']
                    );

                $factor =
                    $rutaPlan['tipo_recorrido']
                    === AbastecimientoRuta::
                        TIPO_IDA_VUELTA
                        ? 2
                        : 1;

                $total +=
                    (float)
                    $ruta->kilometros_estimados
                    * $factor;
            }

            return round(
                $total
                + (
                    (
                        (int) $evento['numero']
                        % 5
                    ) - 2
                ),
                2
            );
        }

        return round(
            80
            + (
                (
                    (int) $evento['numero']
                    * 19
                ) % 420
            )
            + (
                (
                    (int) $evento['numero']
                    % 4
                ) * 0.25
            ),
            2
        );
    }

    /**
     * Calcula cierre del ciclo y discrepancias.
     */
    private static function calcularCombustible(
        array $evento,
        Unidad $unidad,
        ?Abastecimiento $anterior
    ): array {
        $capacidad =
            round(
                (float) $unidad->capacidad_cubierta,
                2
            );

        $volumenInicial =
            round(
                (float) $evento['volumen_inicial'],
                2
            );

        $volumenCargado =
            round(
                (float) $evento['volumen_cargado'],
                2
            );

        $volumenFinal =
            round(
                $volumenInicial
                + $volumenCargado,
                2
            );

        if (
            $volumenFinal
            > $capacidad + 0.01
        ) {
            throw new RuntimeException(
                "El abastecimiento de la unidad "
                . "{$unidad->id} excede su capacidad cubierta."
            );
        }

        $volumenFinalAnterior =
            $anterior
                ? round(
                    (float) $anterior->volumen_final,
                    2
                )
                : null;

        $combustibleConsumido = null;
        $combustibleAdicional = 0.0;

        if (! is_null($volumenFinalAnterior)) {
            if (
                $volumenInicial
                <= $volumenFinalAnterior
            ) {
                $combustibleConsumido =
                    round(
                        $volumenFinalAnterior
                        - $volumenInicial,
                        2
                    );
            } else {
                $combustibleConsumido = 0.0;

                $combustibleAdicional =
                    round(
                        $volumenInicial
                        - $volumenFinalAnterior,
                        2
                    );
            }
        }

        return [
            'capacidad_cubierta' =>
                $capacidad,

            'volumen_inicial' =>
                $volumenInicial,

            'volumen_cargado' =>
                $volumenCargado,

            'volumen_final' =>
                $volumenFinal,

            'volumen_final_anterior' =>
                $volumenFinalAnterior,

            'combustible_consumido' =>
                $combustibleConsumido,

            'combustible_adicional' =>
                $combustibleAdicional,
        ];
    }

    /**
     * Prepara rutas con snapshots.
     */
    private static function prepararRutas(
        array $evento,
        int $empresaId
    ): array {
        $resultado = [];

        foreach (
            $evento['rutas']
            as $indice => $rutaPlan
        ) {
            $ruta = Ruta::query()
                ->with([
                    'puntoOrigen',
                    'puntoDestino',
                ])
                ->findOrFail(
                    (int) $rutaPlan['ruta_id']
                );

            if (
                (int) $ruta->empresa_id
                !== $empresaId
            ) {
                throw new RuntimeException(
                    'Una ruta planificada pertenece '
                    . 'a otra empresa.'
                );
            }

            $tipo =
                (string) $rutaPlan[
                    'tipo_recorrido'
                ];

            $factor =
                $tipo
                === AbastecimientoRuta::
                    TIPO_IDA_VUELTA
                    ? 2
                    : 1;

            $resultado[] = [
                'ruta' =>
                    $ruta,

                'orden' =>
                    $indice + 1,

                'tipo_recorrido' =>
                    $tipo,

                'factor_recorrido' =>
                    $factor,

                'kilometros_aplicados' =>
                    round(
                        (float)
                        $ruta->kilometros_estimados
                        * $factor,
                        2
                    ),

                'galones_aplicados' =>
                    round(
                        (float)
                        $ruta->galones_estimados
                        * $factor,
                        2
                    ),
            ];
        }

        return $resultado;
    }

    /**
     * Prepara datos del origen.
     */
    private static function prepararOrigen(
        array $evento,
        int $empresaId
    ): array {
        $origen = $evento['origen'];

        if (
            $origen['tipo']
            === Abastecimiento::ORIGEN_INTERNO
        ) {
            $gasolinera = Gasolinera::query()
                ->findOrFail(
                    (int) $origen[
                        'gasolinera_interna_id'
                    ]
                );

            if (
                (int) $gasolinera->empresa_id
                !== $empresaId
            ) {
                throw new RuntimeException(
                    'La gasolinera interna pertenece '
                    . 'a otra empresa.'
                );
            }

            return [
                'tipo' =>
                    Abastecimiento::ORIGEN_INTERNO,

                'gasolinera_interna_id' =>
                    $gasolinera->id,

                'gasolinera_externa_id' =>
                    null,

                'nombre_snapshot' =>
                    $gasolinera->nombre,

                'precio_galon' =>
                    null,

                'total_pagado' =>
                    null,

                'moneda' =>
                    null,
            ];
        }

        $gasolinera = GasolineraExterna::query()
            ->findOrFail(
                (int) $origen[
                    'gasolinera_externa_id'
                ]
            );

        if (
            (int) $gasolinera->empresa_id
            !== $empresaId
        ) {
            throw new RuntimeException(
                'La gasolinera externa pertenece '
                . 'a otra empresa.'
            );
        }

        return [
            'tipo' =>
                Abastecimiento::ORIGEN_EXTERNO,

            'gasolinera_interna_id' =>
                null,

            'gasolinera_externa_id' =>
                $gasolinera->id,

            'nombre_snapshot' =>
                $gasolinera->compania,

            'precio_galon' =>
                round(
                    (float) $origen['precio_galon'],
                    4
                ),

            'total_pagado' =>
                round(
                    (float) $origen['total_pagado'],
                    2
                ),

            'moneda' =>
                'USD',
        ];
    }

    /**
     * Calcula indicadores analíticos.
     */
    private static function calcularMetricas(
        array $evento,
        array $lecturas,
        array $combustible,
        array $rutas
    ): array {
        $consumido =
            $combustible[
                'combustible_consumido'
            ];

        $diferenciaKm =
            $lecturas[
                'diferencia_kilometraje'
            ];

        $diferenciaHoras =
            $lecturas[
                'diferencia_horometro'
            ];

        $kilometrosTeoricos = null;
        $galonesTeoricos = null;

        if ($rutas !== []) {
            $kilometrosTeoricos =
                round(
                    array_sum(
                        array_column(
                            $rutas,
                            'kilometros_aplicados'
                        )
                    ),
                    2
                );

            $galonesTeoricos =
                round(
                    array_sum(
                        array_column(
                            $rutas,
                            'galones_aplicados'
                        )
                    ),
                    2
                );
        }

        $galonesPorKilometro = null;
        $kilometrosPorGalon = null;
        $galonesPorHora = null;

        if (
            ! is_null($consumido)
            && $consumido > 0
            && ! is_null($diferenciaKm)
            && $diferenciaKm > 0
        ) {
            $galonesPorKilometro =
                round(
                    $consumido
                    / $diferenciaKm,
                    6
                );

            $kilometrosPorGalon =
                round(
                    $diferenciaKm
                    / $consumido,
                    6
                );
        }

        if (
            $evento['modelo_medicion']
            === Abastecimiento::
                MODELO_GALONES_HORA
            && ! is_null($consumido)
            && $consumido > 0
            && ! is_null($diferenciaHoras)
            && $diferenciaHoras > 0
        ) {
            $galonesPorHora =
                round(
                    $consumido
                    / $diferenciaHoras,
                    6
                );
        }

        return [
            'kilometros_teoricos' =>
                $kilometrosTeoricos,

            'galones_teoricos' =>
                $galonesTeoricos,

            'galones_por_kilometro' =>
                $galonesPorKilometro,

            'kilometros_por_galon' =>
                $kilometrosPorGalon,

            'galones_por_hora' =>
                $galonesPorHora,

            'diferencia_kilometros_teoricos' =>
                ! is_null($kilometrosTeoricos)
                && ! is_null($diferenciaKm)
                    ? round(
                        $diferenciaKm
                        - $kilometrosTeoricos,
                        2
                    )
                    : null,

            'diferencia_galones_teoricos' =>
                ! is_null($galonesTeoricos)
                && ! is_null($consumido)
                    ? round(
                        $consumido
                        - $galonesTeoricos,
                        2
                    )
                    : null,
        ];
    }

    /**
     * Selecciona únicamente tapones de depósitos.
     */
    private static function seleccionarTapones(
        Unidad $unidad,
        int $numeroOperacion
    ): Collection {
        $puntos = PuntoSeguridadUnidad::query()
            ->where(
                'unidad_id',
                $unidad->id
            )
            ->where(
                'estado',
                'activo'
            )
            ->where(
                'requiere_marchamo',
                true
            )
            ->where(
                'tipo_punto',
                'tapón'
            )
            ->where(
                'subgrupo',
                'Depósito'
            )
            ->whereNotNull(
                'marchamo_actual_id'
            )
            ->orderBy('orden')
            ->get();

        if ($puntos->isEmpty()) {
            throw new RuntimeException(
                "La unidad {$unidad->id} no posee "
                . 'tapones operables.'
            );
        }

        $cantidad = min(
            $puntos->count(),
            max(
                1,
                (int) $unidad->total_tanques
            )
        );

        $desplazamiento =
            ($numeroOperacion - 1)
            % $puntos->count();

        return $puntos
            ->slice($desplazamiento)
            ->concat(
                $puntos->slice(
                    0,
                    $desplazamiento
                )
            )
            ->take($cantidad)
            ->values();
    }

    /**
     * Guarda detalles y movimientos de salida interna.
     */
    private static function guardarSalidaInterna(
        Abastecimiento $abastecimiento,
        array $evento,
        CarbonImmutable $fechaOperacion,
        int $usuarioId
    ): void {
        if ($evento['detalles_tanque'] === []) {
            throw new RuntimeException(
                'Un abastecimiento interno requiere '
                . 'detalles de tanque.'
            );
        }

        foreach (
            $evento['detalles_tanque']
            as $indice => $detalle
        ) {
            $tanque = Tanque::query()
                ->whereKey(
                    (int) $detalle['tanque_id']
                )
                ->lockForUpdate()
                ->firstOrFail();

            $inventarioActual =
                round(
                    (float) $tanque->volumen_actual,
                    2
                );

            $inventarioEsperado =
                round(
                    (float) $detalle[
                        'volumen_anterior'
                    ],
                    2
                );

            if (
                abs(
                    $inventarioActual
                    - $inventarioEsperado
                ) > 0.01
            ) {
                throw new RuntimeException(
                    "Inventario divergente en tanque "
                    . "{$tanque->id} durante abastecimiento "
                    . "#{$abastecimiento->id}."
                );
            }

            $retiro =
                round(
                    (float) $detalle['galones'],
                    2
                );

            $resultante =
                round(
                    $inventarioActual - $retiro,
                    2
                );

            if ($resultante < -0.01) {
                throw new RuntimeException(
                    "El abastecimiento dejaría negativo "
                    . "el tanque {$tanque->id}."
                );
            }

            $detalleModelo =
                new AbastecimientoTanque([
                    'abastecimiento_id' =>
                        $abastecimiento->id,

                    'tanque_id' =>
                        $tanque->id,

                    'orden' =>
                        $indice + 1,

                    'tanque_nombre_snapshot' =>
                        $tanque->nombre,

                    'capacidad_total_snapshot' =>
                        $tanque->capacidad_total,

                    'volumen_minimo_alerta_snapshot' =>
                        $tanque->volumen_minimo_alerta,

                    'inventario_anterior' =>
                        $inventarioActual,

                    'galones_retirados' =>
                        $retiro,

                    'inventario_resultante' =>
                        $resultante,

                    'quedo_bajo_minimo' =>
                        $resultante
                        <= (float)
                            $tanque
                                ->volumen_minimo_alerta,
                ]);

            $detalleModelo->created_at =
                $fechaOperacion;

            $detalleModelo->updated_at =
                $fechaOperacion;

            $detalleModelo->save();

            MovimientoInventarioCombustible::create([
                'empresa_id' =>
                    $abastecimiento->empresa_id,

                'tanque_id' =>
                    $tanque->id,

                'abastecimiento_id' =>
                    $abastecimiento->id,

                'recarga_combustible_id' =>
                    null,

                'tipo_movimiento' =>
                    'salida_abastecimiento',

                'volumen_anterior' =>
                    $inventarioActual,

                'sentido_movimiento' =>
                    'salida',

                'volumen_movimiento' =>
                    $retiro,

                'volumen_resultante' =>
                    $resultante,

                'subtotal_compra' =>
                    null,

                'fecha_hora_movimiento' =>
                    $fechaOperacion,

                'observaciones' =>
                    null,

                'usuario_registra_id' =>
                    $usuarioId,

                'estado' =>
                    'registrado',

                'fecha_creacion' =>
                    $fechaOperacion,
            ]);

            $tanque->update([
                'volumen_actual' =>
                    max(0, $resultante),

                'fecha_actualizacion' =>
                    $fechaOperacion,

                'actualizado_por' =>
                    $usuarioId,
            ]);
        }
    }

    /**
     * Guarda snapshots de rutas.
     */
    private static function guardarRutas(
        Abastecimiento $abastecimiento,
        array $rutas,
        CarbonImmutable $fechaOperacion
    ): void {
        foreach (
            $rutas
            as $procesada
        ) {
            /** @var Ruta $ruta */
            $ruta = $procesada['ruta'];

            $detalle =
                new AbastecimientoRuta([
                    'abastecimiento_id' =>
                        $abastecimiento->id,

                    'ruta_id' =>
                        $ruta->id,

                    'orden' =>
                        $procesada['orden'],

                    'tipo_recorrido' =>
                        $procesada[
                            'tipo_recorrido'
                        ],

                    'factor_recorrido' =>
                        $procesada[
                            'factor_recorrido'
                        ],

                    'ruta_nombre_snapshot' =>
                        $ruta->ruta,

                    'punto_origen_id' =>
                        $ruta->punto_origen_id,

                    'punto_destino_id' =>
                        $ruta->punto_destino_id,

                    'punto_origen_nombre_snapshot' =>
                        $ruta->puntoOrigen->nombre,

                    'punto_destino_nombre_snapshot' =>
                        $ruta->puntoDestino->nombre,

                    'kilometros_base_snapshot' =>
                        $ruta->kilometros_estimados,

                    'galones_base_snapshot' =>
                        $ruta->galones_estimados,

                    'kilometros_aplicados' =>
                        $procesada[
                            'kilometros_aplicados'
                        ],

                    'galones_aplicados' =>
                        $procesada[
                            'galones_aplicados'
                        ],
                ]);

            $detalle->created_at =
                $fechaOperacion;

            $detalle->updated_at =
                $fechaOperacion;

            $detalle->save();
        }
    }

    /**
     * Reemplaza los marchamos de los tapones abiertos.
     */
    private static function guardarMarchamos(
        Abastecimiento $abastecimiento,
        Collection $puntos,
        CarbonImmutable $fechaOperacion,
        int $usuarioId
    ): void {
        $evento =
            new ReemplazoMarchamoEvento([
                'empresa_id' =>
                    $abastecimiento->empresa_id,

                'unidad_id' =>
                    $abastecimiento->unidad_id,

                'abastecimiento_id' =>
                    $abastecimiento->id,

                'motivo_reemplazo' =>
                    ReemplazoMarchamoEvento::
                        MOTIVO_APERTURA_ABASTECIMIENTO,

                'cantidad_reemplazos' =>
                    $puntos->count(),

                'origen_evento' =>
                    ReemplazoMarchamoEvento::
                        ORIGEN_ABASTECIMIENTO,

                'estado' =>
                    'registrado',

                'fecha_registro' =>
                    $fechaOperacion,

                'registrado_por' =>
                    $usuarioId,
            ]);

        $evento->created_at =
            $fechaOperacion;

        $evento->updated_at =
            $fechaOperacion;

        $evento->save();

        foreach (
            $puntos
            as $punto
        ) {
            /** @var PuntoSeguridadUnidad $punto */
            $punto = PuntoSeguridadUnidad::query()
                ->whereKey($punto->id)
                ->lockForUpdate()
                ->firstOrFail();

            $anterior = Marchamo::query()
                ->whereKey(
                    $punto->marchamo_actual_id
                )
                ->where(
                    'estado',
                    'activo'
                )
                ->where(
                    'activo_actual',
                    true
                )
                ->lockForUpdate()
                ->first();

            if (! $anterior) {
                throw new RuntimeException(
                    "El punto {$punto->id} no posee "
                    . 'un marchamo activo válido.'
                );
            }

            $anterior->update([
                'estado' =>
                    'reemplazado',

                'activo_actual' =>
                    null,

                'fecha_desactivacion' =>
                    $fechaOperacion,

                'motivo_desactivacion' =>
                    'Apertura por abastecimiento',

                'actualizado_por' =>
                    $usuarioId,
            ]);

            $codigo =
                CcFlotaSeederContext::codigoNumerico(
                    'marchamos',
                    7,
                    CcFlotaSeederConfig::
                        MARCHAMO_INICIO
                );

            $nuevo = Marchamo::create([
                'empresa_id' =>
                    $abastecimiento->empresa_id,

                'unidad_id' =>
                    $abastecimiento->unidad_id,

                'punto_seguridad_id' =>
                    $punto->id,

                'codigo_marchamo' =>
                    $codigo,

                'fecha_activacion' =>
                    $fechaOperacion,

                'estado' =>
                    'activo',

                'activo_actual' =>
                    true,

                'fecha_desactivacion' =>
                    null,

                'motivo_desactivacion' =>
                    null,

                'origen_creacion' =>
                    'abastecimiento',

                'creado_por' =>
                    $usuarioId,

                'actualizado_por' =>
                    $usuarioId,
            ]);

            $punto->update([
                'marchamo_actual_id' =>
                    $nuevo->id,

                'estado_asignacion' =>
                    'asignado',

                'actualizado_por' =>
                    $usuarioId,
            ]);

            $detalle =
                new ReemplazoMarchamoDetalle([
                    'reemplazo_evento_id' =>
                        $evento->id,

                    'punto_seguridad_id' =>
                        $punto->id,

                    'marchamo_anterior_id' =>
                        $anterior->id,

                    'marchamo_nuevo_id' =>
                        $nuevo->id,

                    'fecha_registro' =>
                        $fechaOperacion,
                ]);

            $detalle->created_at =
                $fechaOperacion;

            $detalle->updated_at =
                $fechaOperacion;

            $detalle->save();
        }
    }
}