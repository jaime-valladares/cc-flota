<?php

namespace Database\Seeders;

use App\Models\Unidad;
use Carbon\CarbonImmutable;
use RuntimeException;

final class CcFlotaOperationalPlan
{
    /**
     * Construye una línea temporal única para recargas y abastecimientos.
     *
     * Este archivo todavía no inserta operaciones. Su responsabilidad es
     * producir un plan determinístico, cronológico y con inventario
     * proyectado válido para que el siguiente seeder pueda materializarlo.
     */
    public static function construir(): array
    {
        $unidades =
            CcFlotaSeederContext::referencia(
                'unidades.operables'
            );

        $motoristas =
            CcFlotaSeederContext::referencia(
                'motoristas.operables'
            );

        $gasolinerasInternas =
            CcFlotaSeederContext::referencia(
                'gasolineras_internas.activas'
            );

        $gasolinerasExternas =
            CcFlotaSeederContext::referencia(
                'gasolineras_externas.operables'
            );

        $tanques =
            CcFlotaSeederContext::referencia(
                'tanques.operables'
            );

        $rutas =
            CcFlotaSeederContext::referencia(
                'rutas.operables'
            );

        self::validarFuentes(
            unidades: $unidades,
            motoristas: $motoristas,
            gasolinerasInternas: $gasolinerasInternas,
            gasolinerasExternas: $gasolinerasExternas,
            tanques: $tanques
        );

        $indices = self::crearIndices(
            motoristas: $motoristas,
            gasolinerasInternas: $gasolinerasInternas,
            gasolinerasExternas: $gasolinerasExternas,
            tanques: $tanques,
            rutas: $rutas
        );

        $eventosBase = array_merge(
            self::planificarRecargas(
                gasolinerasInternas:
                    $gasolinerasInternas
            ),
            self::planificarAbastecimientos(
                unidades: $unidades
            )
        );

        usort(
            $eventosBase,
            function (
                array $izquierda,
                array $derecha
            ): int {
                $comparacionFecha =
                    $izquierda['fecha_hora']
                    <=> $derecha['fecha_hora'];

                if ($comparacionFecha !== 0) {
                    return $comparacionFecha;
                }

                /*
                 * Una recarga debe procesarse antes que un
                 * abastecimiento cuando comparten instante.
                 */
                $prioridad = [
                    'recarga' => 1,
                    'abastecimiento' => 2,
                ];

                return $prioridad[
                    $izquierda['tipo']
                ] <=> $prioridad[
                    $derecha['tipo']
                ];
            }
        );

        $inventarioProyectado = [];

        foreach (
            $tanques
            as $tanque
        ) {
            $inventarioProyectado[
                (int) $tanque['id']
            ] = 0.0;
        }

        $eventos = [];
        $secuenciaRecarga = 0;
        $secuenciaAbastecimiento = 0;

        foreach (
            $eventosBase
            as $eventoBase
        ) {
            if ($eventoBase['tipo'] === 'recarga') {
                $secuenciaRecarga++;

                $evento =
                    self::materializarRecarga(
                        eventoBase: $eventoBase,
                        numero: $secuenciaRecarga,
                        indices: $indices,
                        inventarioProyectado:
                            $inventarioProyectado
                    );
            } else {
                $secuenciaAbastecimiento++;

                $evento =
                    self::materializarAbastecimiento(
                        eventoBase: $eventoBase,
                        numero: $secuenciaAbastecimiento,
                        indices: $indices,
                        inventarioProyectado:
                            $inventarioProyectado
                    );
            }

            $eventos[] = $evento;
        }

        self::validarResultado(
            eventos: $eventos,
            inventarioProyectado:
                $inventarioProyectado,
            tanques: $tanques
        );

        $resumen = self::resumir(
            eventos: $eventos,
            inventarioProyectado:
                $inventarioProyectado
        );

        $plan = [
            'fecha_inicio' =>
                CcFlotaSeederContext::fechaInicio()
                    ->toDateString(),

            'fecha_fin' =>
                CcFlotaSeederContext::fechaFin()
                    ->toDateString(),

            'eventos' =>
                $eventos,

            'inventario_final_proyectado' =>
                $inventarioProyectado,

            'resumen' =>
                $resumen,
        ];

        CcFlotaSeederContext::registrarReferencia(
            'operacion.plan',
            $plan
        );

        CcFlotaSeederContext::registrarReferencia(
            'operacion.eventos',
            $eventos
        );

        CcFlotaSeederContext::registrarReferencia(
            'operacion.resumen',
            $resumen
        );

        return $plan;
    }

    /**
     * Valida que todas las fuentes mínimas estén disponibles.
     */
    private static function validarFuentes(
        array $unidades,
        array $motoristas,
        array $gasolinerasInternas,
        array $gasolinerasExternas,
        array $tanques
    ): void {
        $fuentes = [
            'unidades operables' =>
                $unidades,

            'motoristas operables' =>
                $motoristas,

            'gasolineras internas activas' =>
                $gasolinerasInternas,

            'gasolineras externas operables' =>
                $gasolinerasExternas,

            'tanques operables' =>
                $tanques,
        ];

        foreach (
            $fuentes
            as $nombre => $datos
        ) {
            if ($datos === []) {
                throw new RuntimeException(
                    "No existen {$nombre} para construir "
                    . 'el plan operacional.'
                );
            }
        }
    }

    /**
     * Crea índices rápidos agrupados por empresa y gasolinera.
     */
    private static function crearIndices(
        array $motoristas,
        array $gasolinerasInternas,
        array $gasolinerasExternas,
        array $tanques,
        array $rutas
    ): array {
        return [
            'motoristas_empresa' =>
                self::agruparPor(
                    $motoristas,
                    'empresa_id'
                ),

            'internas_empresa' =>
                self::agruparPor(
                    $gasolinerasInternas,
                    'empresa_id'
                ),

            'externas_empresa' =>
                self::agruparPor(
                    $gasolinerasExternas,
                    'empresa_id'
                ),

            'tanques_gasolinera' =>
                self::agruparPor(
                    $tanques,
                    'gasolinera_id'
                ),

            'rutas_empresa' =>
                self::agruparPor(
                    $rutas,
                    'empresa_id'
                ),
        ];
    }

    /**
     * Agrupa registros por una clave.
     */
    private static function agruparPor(
        array $registros,
        string $clave
    ): array {
        $resultado = [];

        foreach (
            $registros
            as $registro
        ) {
            $valor = (int) $registro[$clave];

            $resultado[$valor] ??= [];
            $resultado[$valor][] = $registro;
        }

        return $resultado;
    }

    /**
     * Planifica exactamente la cantidad objetivo de recargas.
     */
    private static function planificarRecargas(
        array $gasolinerasInternas
    ): array {
        $total =
            CcFlotaSeederConfig::
                TOTAL_RECARGAS_OBJETIVO;

        $eventos = [];
        $cantidadGasolineras =
            count($gasolinerasInternas);

        for (
            $indice = 0;
            $indice < $total;
            $indice++
        ) {
            $gasolinera =
                $gasolinerasInternas[
                    $indice % $cantidadGasolineras
                ];

            $fecha =
                self::fechaDistribuida(
                    indice: $indice,
                    total: $total,
                    desplazamientoMinutos:
                        ($indice * 37) % 480
                );

            $eventos[] = [
                'tipo' =>
                    'recarga',

                'fecha_hora' =>
                    $fecha,

                'empresa_id' =>
                    (int) $gasolinera['empresa_id'],

                'gasolinera_id' =>
                    (int) $gasolinera['id'],
            ];
        }

        return $eventos;
    }

    /**
     * Planifica exactamente la cantidad objetivo de abastecimientos,
     * respetando el mínimo y máximo por unidad.
     */
    private static function planificarAbastecimientos(
        array $unidades
    ): array {
        $total =
            CcFlotaSeederConfig::
                TOTAL_ABASTECIMIENTOS_OBJETIVO;

        $minimo =
            CcFlotaSeederConfig::
                ABASTECIMIENTOS_POR_UNIDAD['minimo'];

        $maximo =
            CcFlotaSeederConfig::
                ABASTECIMIENTOS_POR_UNIDAD['maximo'];

        $cantidadUnidades = count($unidades);

        if (
            $cantidadUnidades * $minimo > $total
            || $cantidadUnidades * $maximo < $total
        ) {
            throw new RuntimeException(
                'La cantidad objetivo de abastecimientos '
                . 'no puede distribuirse entre las unidades '
                . 'operables respetando el mínimo y máximo.'
            );
        }

        $cantidades = array_fill(
            0,
            $cantidadUnidades,
            $minimo
        );

        $pendientes =
            $total
            - ($cantidadUnidades * $minimo);

        $cursor = 0;

        while ($pendientes > 0) {
            if ($cantidades[$cursor] < $maximo) {
                $cantidades[$cursor]++;
                $pendientes--;
            }

            $cursor =
                ($cursor + 1)
                % $cantidadUnidades;
        }

        $eventos = [];
        $secuenciaGlobal = 0;

        foreach (
            $unidades
            as $indiceUnidad => $unidad
        ) {
            $cantidadUnidad =
                $cantidades[$indiceUnidad];

            for (
                $numeroCiclo = 1;
                $numeroCiclo <= $cantidadUnidad;
                $numeroCiclo++
            ) {
                $secuenciaGlobal++;

                $fecha =
                    self::fechaParaUnidad(
                        indiceUnidad: $indiceUnidad,
                        cantidadUnidades: $cantidadUnidades,
                        numeroCiclo: $numeroCiclo,
                        cantidadCiclos: $cantidadUnidad
                    );

                $eventos[] = [
                    'tipo' =>
                        'abastecimiento',

                    'fecha_hora' =>
                        $fecha,

                    'empresa_id' =>
                        (int) $unidad['empresa_id'],

                    'unidad' =>
                        $unidad,

                    'numero_ciclo' =>
                        $numeroCiclo,

                    'es_linea_base' =>
                        $numeroCiclo === 1,

                    'secuencia_global' =>
                        $secuenciaGlobal,
                ];
            }
        }

        return $eventos;
    }

    /**
     * Distribuye una fecha dentro de todo el período.
     */
    private static function fechaDistribuida(
        int $indice,
        int $total,
        int $desplazamientoMinutos
    ): CarbonImmutable {
        $inicio =
            CcFlotaSeederContext::fechaInicio()
                ->startOfDay();

        $fin =
            CcFlotaSeederContext::fechaFin()
                ->endOfDay();

        $segundosTotales =
            $fin->diffInSeconds(
                $inicio
            );

        $fraccion =
            ($indice + 1)
            / ($total + 1);

        $segundos =
            (int) floor(
                $segundosTotales * $fraccion
            );

        return $inicio
            ->addSeconds($segundos)
            ->addMinutes(
                $desplazamientoMinutos
            );
    }

    /**
     * Distribuye cronológicamente los ciclos de una unidad.
     */
    private static function fechaParaUnidad(
        int $indiceUnidad,
        int $cantidadUnidades,
        int $numeroCiclo,
        int $cantidadCiclos
    ): CarbonImmutable {
        $inicio =
            CcFlotaSeederContext::fechaInicio()
                ->addDays(
                    $indiceUnidad % 11
                )
                ->setTime(
                    7 + ($indiceUnidad % 8),
                    ($indiceUnidad * 11) % 60
                );

        $fin =
            CcFlotaSeederContext::fechaFin()
                ->subDays(
                    ($cantidadUnidades - $indiceUnidad)
                    % 9
                )
                ->setTime(
                    8 + ($indiceUnidad % 7),
                    ($indiceUnidad * 17) % 60
                );

        $segundosTotales =
            $fin->diffInSeconds(
                $inicio
            );

        $fraccion =
            $cantidadCiclos === 1
                ? 0.5
                : ($numeroCiclo - 1)
                    / ($cantidadCiclos - 1);

        return $inicio->addSeconds(
            (int) floor(
                $segundosTotales * $fraccion
            )
        );
    }

    /**
     * Materializa una recarga proyectada.
     */
    private static function materializarRecarga(
        array $eventoBase,
        int $numero,
        array $indices,
        array &$inventarioProyectado
    ): array {
        $empresaOriginalId =
            (int) $eventoBase['empresa_id'];

        $gasolineraPreferidaId =
            (int) $eventoBase['gasolinera_id'];

        /*
         * La planificación base distribuye recargas de forma uniforme,
         * pero el consumo interno real puede variar entre empresas.
         *
         * Se intenta primero la empresa y gasolinera originalmente
         * planificadas. Si todos sus tanques están llenos, la recarga
         * se reasigna determinísticamente a otra gasolinera interna
         * activa con espacio disponible. La empresa del evento también
         * se actualiza para conservar integridad funcional.
         */
        $gasolinerasCandidatas = [];

        $empresasOrdenadas =
            array_keys(
                $indices['internas_empresa']
            );

        sort($empresasOrdenadas);

        usort(
            $empresasOrdenadas,
            fn (int $a, int $b): int =>
                (
                    $a === $empresaOriginalId
                        ? 0
                        : 1
                )
                <=>
                (
                    $b === $empresaOriginalId
                        ? 0
                        : 1
                )
                ?: ($a <=> $b)
        );

        foreach (
            $empresasOrdenadas
            as $empresaCandidataId
        ) {
            $gasolinerasEmpresa =
                $indices['internas_empresa'][
                    $empresaCandidataId
                ] ?? [];

            usort(
                $gasolinerasEmpresa,
                fn (array $a, array $b): int =>
                    (
                        (int) $a['id']
                        === $gasolineraPreferidaId
                            ? 0
                            : 1
                    )
                    <=>
                    (
                        (int) $b['id']
                        === $gasolineraPreferidaId
                            ? 0
                            : 1
                    )
                    ?: (
                        (int) $a['id']
                        <=>
                        (int) $b['id']
                    )
            );

            foreach (
                $gasolinerasEmpresa
                as $gasolinera
            ) {
                $gasolinerasCandidatas[] =
                    $gasolinera;
            }
        }

        if ($gasolinerasCandidatas === []) {
            throw new RuntimeException(
                'No existen gasolineras internas activas '
                . 'para materializar recargas.'
            );
        }

        $precioGalon =
            round(
                CcFlotaSeederConfig::
                    PRECIOS_INTERNOS['minimo']
                + (
                    (($numero * 17) % 176)
                    / 100
                ),
                4
            );

        $gasolineraSeleccionada = null;
        $detalles = [];

        foreach (
            $gasolinerasCandidatas
            as $gasolinera
        ) {
            $tanques =
                $indices['tanques_gasolinera'][
                    (int) $gasolinera['id']
                ] ?? [];

            if ($tanques === []) {
                continue;
            }

            /*
             * Se rotan los tanques para no intentar siempre
             * llenar primero los mismos depósitos.
             */
            $desplazamiento =
                ($numero - 1)
                % count($tanques);

            $tanquesRotados =
                array_merge(
                    array_slice(
                        $tanques,
                        $desplazamiento
                    ),
                    array_slice(
                        $tanques,
                        0,
                        $desplazamiento
                    )
                );

            $cantidadTanques = min(
                count($tanquesRotados),
                1 + (($numero - 1) % 3)
            );

            $tanquesSeleccionados =
                array_slice(
                    $tanquesRotados,
                    0,
                    $cantidadTanques
                );

            $detallesCandidatos = [];

            foreach (
                $tanquesSeleccionados
                as $tanque
            ) {
                $tanqueId =
                    (int) $tanque['id'];

                $capacidad =
                    (float) $tanque['capacidad_total'];

                $anterior =
                    round(
                        $inventarioProyectado[$tanqueId],
                        2
                    );

                $espacio =
                    round(
                        $capacidad - $anterior,
                        2
                    );

                if ($espacio <= 0) {
                    continue;
                }

                $porcentajeLlenado =
                    55 + (($numero + $tanqueId) % 36);

                $galones =
                    round(
                        min(
                            $espacio,
                            $capacidad
                            * ($porcentajeLlenado / 100)
                        ),
                        2
                    );

                if ($galones <= 0) {
                    continue;
                }

                $resultante =
                    round(
                        $anterior + $galones,
                        2
                    );

                $detallesCandidatos[] = [
                    'tanque_id' =>
                        $tanqueId,

                    'volumen_anterior' =>
                        $anterior,

                    'galones' =>
                        $galones,

                    'volumen_resultante' =>
                        $resultante,

                    'subtotal_compra' =>
                        round(
                            $galones * $precioGalon,
                            2
                        ),
                ];
            }

            if ($detallesCandidatos !== []) {
                $gasolineraSeleccionada =
                    $gasolinera;

                $detalles =
                    $detallesCandidatos;

                break;
            }
        }

        if (
            is_null($gasolineraSeleccionada)
            || $detalles === []
        ) {
            throw new RuntimeException(
                "No existe espacio disponible en ningún tanque "
                . "interno para materializar la recarga "
                . "planificada #{$numero}."
            );
        }

        $empresaId =
            (int) $gasolineraSeleccionada['empresa_id'];

        /*
         * Algunas recargas válidas se marcan para anulación completa.
         * El materializador registrará la entrada y su reversión.
         */
        $seraAnulada =
            $numero % 47 === 0;

        $totalGalones = 0.0;

        foreach (
            $detalles
            as $detalle
        ) {
            $tanqueId =
                (int) $detalle['tanque_id'];

            $inventarioProyectado[$tanqueId] =
                $seraAnulada
                    ? (float) $detalle[
                        'volumen_anterior'
                    ]
                    : (float) $detalle[
                        'volumen_resultante'
                    ];

            $totalGalones +=
                (float) $detalle['galones'];
        }

        $fechaAnulacion =
            $seraAnulada
                ? $eventoBase['fecha_hora']
                    ->addMinutes(12)
                : null;

        return [
            'tipo' =>
                'recarga',

            'numero' =>
                $numero,

            'fecha_hora' =>
                $eventoBase['fecha_hora'],

            'empresa_id' =>
                $empresaId,

            'gasolinera_id' =>
                (int) $gasolineraSeleccionada['id'],

            'precio_galon' =>
                $precioGalon,

            'detalles' =>
                $detalles,

            'total_galones' =>
                round($totalGalones, 2),

            'total_compra' =>
                round(
                    $totalGalones * $precioGalon,
                    2
                ),

            'estado_plan' =>
                $seraAnulada
                    ? 'anulado'
                    : 'registrado',

            'fecha_anulacion_plan' =>
                $fechaAnulacion,

            'motivo_anulacion_plan' =>
                $seraAnulada
                    ? 'Corrección integral de una recarga '
                        . 'registrada incorrectamente.'
                    : null,
        ];
    }

    /**
     * Materializa un abastecimiento proyectado.
     */
    private static function materializarAbastecimiento(
        array $eventoBase,
        int $numero,
        array $indices,
        array &$inventarioProyectado
    ): array {
        $unidad =
            $eventoBase['unidad'];

        /*
         * Blindaje defensivo:
         * el contexto puede contener una referencia resumida de la unidad.
         * Cuando falte cualquiera de los atributos operativos, se completa
         * directamente desde la base de datos antes de calcular el evento.
         */
        $unidadId =
            (int) (
                $unidad['id']
                ?? $unidad['unidad_id']
                ?? 0
            );

        if ($unidadId <= 0) {
            throw new RuntimeException(
                'El evento de abastecimiento no contiene '
                . 'un identificador de unidad válido.'
            );
        }

        $camposOperativos = [
            'empresa_id',
            'modelo_medicion',
            'total_tanques',
            'capacidad_total',
            'capacidad_cubierta',
        ];

        $requiereHidratacion = false;

        foreach (
            $camposOperativos
            as $campo
        ) {
            if (! array_key_exists($campo, $unidad)) {
                $requiereHidratacion = true;
                break;
            }
        }

        if ($requiereHidratacion) {
            $unidadModelo =
                Unidad::query()
                    ->findOrFail($unidadId);

            $unidad = array_merge(
                $unidad,
                [
                    'id' =>
                        $unidadModelo->id,

                    'unidad_id' =>
                        $unidadModelo->id,

                    'empresa_id' =>
                        $unidadModelo->empresa_id,

                    'placa' =>
                        $unidadModelo->placa,

                    'modelo_medicion' =>
                        $unidadModelo->modelo_medicion,

                    'total_tanques' =>
                        (int) $unidadModelo->total_tanques,

                    'capacidad_total' =>
                        (float) $unidadModelo->capacidad_total,

                    'capacidad_cubierta' =>
                        (float) $unidadModelo->capacidad_cubierta,
                ]
            );
        }

        $empresaId =
            (int) $unidad['empresa_id'];

        $motoristas =
            $indices['motoristas_empresa'][
                $empresaId
            ] ?? [];

        if ($motoristas === []) {
            throw new RuntimeException(
                "La empresa {$empresaId} no posee "
                . 'motoristas operables.'
            );
        }

        $motorista =
            $motoristas[
                ($numero - 1)
                % count($motoristas)
            ];

        $capacidad =
            (float) $unidad['capacidad_cubierta'];

        $volumenInicial =
            round(
                $capacidad
                * (
                    0.08
                    + (
                        (($numero * 7) % 29)
                        / 100
                    )
                ),
                2
            );

        $volumenCargado =
            round(
                min(
                    $capacidad - $volumenInicial,
                    $capacidad
                    * (
                        0.35
                        + (
                            (($numero * 11) % 31)
                            / 100
                        )
                    )
                ),
                2
            );

        $preferenciaInterna =
            (($numero * 13) % 100)
            < CcFlotaSeederConfig::
                DISTRIBUCION_ORIGEN['interno'];

        $internas =
            $indices['internas_empresa'][
                $empresaId
            ] ?? [];

        $externas =
            $indices['externas_empresa'][
                $empresaId
            ] ?? [];

        $origen = null;
        $detallesTanque = [];

        if (
            $preferenciaInterna
            && $internas !== []
        ) {
            [
                $origen,
                $detallesTanque,
            ] = self::intentarOrigenInterno(
                numero: $numero,
                internas: $internas,
                indices: $indices,
                galonesNecesarios: $volumenCargado,
                inventarioProyectado:
                    $inventarioProyectado
            );
        }

        if (is_null($origen)) {
            if ($externas === []) {
                throw new RuntimeException(
                    "La empresa {$empresaId} no posee "
                    . 'un origen de combustible disponible.'
                );
            }

            $gasolineraExterna =
                $externas[
                    ($numero - 1)
                    % count($externas)
                ];

            $precio =
                round(
                    CcFlotaSeederConfig::
                        PRECIOS_EXTERNOS['minimo']
                    + (
                        (($numero * 23) % 181)
                        / 100
                    ),
                    4
                );

            $origen = [
                'tipo' =>
                    'externo',

                'gasolinera_externa_id' =>
                    (int) $gasolineraExterna['id'],

                'precio_galon' =>
                    $precio,

                'total_pagado' =>
                    round(
                        $volumenCargado * $precio,
                        2
                    ),
            ];
        }

        $rutasPlan = [];

        if (
            $unidad['modelo_medicion']
            === 'galones_viaje'
            && ! $eventoBase['es_linea_base']
        ) {
            $rutasEmpresa =
                $indices['rutas_empresa'][
                    $empresaId
                ] ?? [];

            if ($rutasEmpresa === []) {
                throw new RuntimeException(
                    "La empresa {$empresaId} no posee "
                    . 'rutas operables para una unidad '
                    . 'de galones por viaje.'
                );
            }

            $cantidadRutas =
                1 + (($numero - 1) % 3);

            for (
                $indice = 0;
                $indice < $cantidadRutas;
                $indice++
            ) {
                $ruta =
                    $rutasEmpresa[
                        ($numero + $indice)
                        % count($rutasEmpresa)
                    ];

                $rutasPlan[] = [
                    'ruta_id' =>
                        (int) $ruta['id'],

                    'tipo_recorrido' =>
                        ($numero + $indice) % 3 === 0
                            ? 'ida_vuelta'
                            : 'ida',
                ];
            }
        }

        return [
            'tipo' =>
                'abastecimiento',

            'numero' =>
                $numero,

            'fecha_hora' =>
                $eventoBase['fecha_hora'],

            'empresa_id' =>
                $empresaId,

            'unidad_id' =>
                (int) $unidad['id'],

            'motorista_id' =>
                (int) $motorista['id'],

            'numero_ciclo' =>
                (int) $eventoBase['numero_ciclo'],

            'es_linea_base' =>
                (bool) $eventoBase['es_linea_base'],

            'modelo_medicion' =>
                $unidad['modelo_medicion'],

            'volumen_inicial' =>
                $volumenInicial,

            'volumen_cargado' =>
                $volumenCargado,

            'volumen_final' =>
                round(
                    $volumenInicial
                    + $volumenCargado,
                    2
                ),

            'origen' =>
                $origen,

            'detalles_tanque' =>
                $detallesTanque,

            'rutas' =>
                $rutasPlan,
        ];
    }

    /**
     * Intenta retirar combustible de una estación interna.
     */
    private static function intentarOrigenInterno(
        int $numero,
        array $internas,
        array $indices,
        float $galonesNecesarios,
        array &$inventarioProyectado
    ): array {
        $cantidadInternas =
            count($internas);

        for (
            $intento = 0;
            $intento < $cantidadInternas;
            $intento++
        ) {
            $gasolinera =
                $internas[
                    ($numero + $intento)
                    % $cantidadInternas
                ];

            $tanques =
                $indices['tanques_gasolinera'][
                    (int) $gasolinera['id']
                ] ?? [];

            $disponible = array_sum(
                array_map(
                    fn (array $tanque): float =>
                        (float)
                        $inventarioProyectado[
                            (int) $tanque['id']
                        ],
                    $tanques
                )
            );

            if ($disponible < $galonesNecesarios) {
                continue;
            }

            $pendiente =
                round(
                    $galonesNecesarios,
                    2
                );

            $detalles = [];

            foreach (
                $tanques
                as $tanque
            ) {
                if ($pendiente <= 0) {
                    break;
                }

                $tanqueId =
                    (int) $tanque['id'];

                $anterior =
                    round(
                        $inventarioProyectado[
                            $tanqueId
                        ],
                        2
                    );

                if ($anterior <= 0) {
                    continue;
                }

                $retiro =
                    round(
                        min(
                            $pendiente,
                            $anterior
                        ),
                        2
                    );

                $resultante =
                    round(
                        $anterior - $retiro,
                        2
                    );

                $inventarioProyectado[
                    $tanqueId
                ] = $resultante;

                $detalles[] = [
                    'tanque_id' =>
                        $tanqueId,

                    'volumen_anterior' =>
                        $anterior,

                    'galones' =>
                        $retiro,

                    'volumen_resultante' =>
                        $resultante,

                    'quedo_bajo_minimo' =>
                        $resultante
                        <= (float)
                            $tanque[
                                'volumen_minimo_alerta'
                            ],
                ];

                $pendiente =
                    round(
                        $pendiente - $retiro,
                        2
                    );
            }

            if ($pendiente > 0) {
                throw new RuntimeException(
                    'El cálculo de inventario interno '
                    . 'no pudo completar el retiro proyectado.'
                );
            }

            return [
                [
                    'tipo' =>
                        'interno',

                    'gasolinera_interna_id' =>
                        (int) $gasolinera['id'],
                ],
                $detalles,
            ];
        }

        return [
            null,
            [],
        ];
    }

    /**
     * Valida cantidades e inventarios finales.
     */
    private static function validarResultado(
        array $eventos,
        array $inventarioProyectado,
        array $tanques
    ): void {
        $recargas = 0;
        $abastecimientos = 0;

        foreach (
            $eventos
            as $evento
        ) {
            if ($evento['tipo'] === 'recarga') {
                $recargas++;
            } else {
                $abastecimientos++;
            }
        }

        if (
            $recargas
            !== CcFlotaSeederConfig::
                TOTAL_RECARGAS_OBJETIVO
        ) {
            throw new RuntimeException(
                'El plan no contiene la cantidad objetivo '
                . 'de recargas.'
            );
        }

        if (
            $abastecimientos
            !== CcFlotaSeederConfig::
                TOTAL_ABASTECIMIENTOS_OBJETIVO
        ) {
            throw new RuntimeException(
                'El plan no contiene la cantidad objetivo '
                . 'de abastecimientos.'
            );
        }

        $capacidades = [];

        foreach (
            $tanques
            as $tanque
        ) {
            $capacidades[
                (int) $tanque['id']
            ] = (float) $tanque['capacidad_total'];
        }

        foreach (
            $inventarioProyectado
            as $tanqueId => $volumen
        ) {
            if (
                $volumen < -0.005
                || $volumen
                    > $capacidades[$tanqueId] + 0.005
            ) {
                throw new RuntimeException(
                    "El tanque {$tanqueId} terminó "
                    . 'con inventario proyectado inválido.'
                );
            }
        }
    }

    /**
     * Construye un resumen del plan.
     */
    private static function resumir(
        array $eventos,
        array $inventarioProyectado
    ): array {
        $resumen = [
            'total_eventos' =>
                count($eventos),

            'recargas' =>
                0,

            'recargas_registradas' =>
                0,

            'recargas_anuladas' =>
                0,

            'abastecimientos' =>
                0,

            'abastecimientos_internos' =>
                0,

            'abastecimientos_externos' =>
                0,

            'lineas_base' =>
                0,

            'rutas_planificadas' =>
                0,

            'inventario_final_total' =>
                round(
                    array_sum(
                        $inventarioProyectado
                    ),
                    2
                ),
        ];

        foreach (
            $eventos
            as $evento
        ) {
            if ($evento['tipo'] === 'recarga') {
                $resumen['recargas']++;

                if (
                    $evento['estado_plan']
                    === 'registrado'
                ) {
                    $resumen[
                        'recargas_registradas'
                    ]++;
                } else {
                    $resumen[
                        'recargas_anuladas'
                    ]++;
                }

                continue;
            }

            $resumen['abastecimientos']++;

            if ($evento['es_linea_base']) {
                $resumen['lineas_base']++;
            }

            if (
                $evento['origen']['tipo']
                === 'interno'
            ) {
                $resumen[
                    'abastecimientos_internos'
                ]++;
            } else {
                $resumen[
                    'abastecimientos_externos'
                ]++;
            }

            $resumen['rutas_planificadas'] +=
                count($evento['rutas']);
        }

        return $resumen;
    }
}