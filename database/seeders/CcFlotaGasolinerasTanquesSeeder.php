<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Gasolinera;
use App\Models\Tanque;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CcFlotaGasolinerasTanquesSeeder extends Seeder
{
    /**
     * Crea gasolineras internas y tanques.
     *
     * Los tanques nacen con inventario cero porque el inventario
     * histórico será construido posteriormente mediante recargas.
     */
    public function run(): void
    {
        $this->command?->info(
            'Creando gasolineras internas y tanques...'
        );

        $usuarioId =
            CcFlotaSeederContext::superUserId();

        $empresas =
            CcFlotaSeederContext::referencia(
                'empresas.todas'
            );

        if ($empresas === []) {
            throw new RuntimeException(
                'No existen empresas registradas en el contexto.'
            );
        }

        $totalGasolineras =
            CcFlotaSeederConfig::
                TOTAL_GASOLINERAS_INTERNAS;

        $distribucion =
            $this->distribucionPorEmpresa(
                cantidadEmpresas: count($empresas),
                totalGasolineras: $totalGasolineras
            );

        $condicionesObjetivo =
            $this->crearDistribucionExacta(
                CcFlotaSeederConfig::
                    DISTRIBUCION_TANQUES,
                72
            );

        $gasolineras = [];
        $tanques = [];
        $contadorGasolinera = 0;
        $contadorTanque = 0;

        DB::transaction(
            function () use (
                $empresas,
                $distribucion,
                $condicionesObjetivo,
                $usuarioId,
                &$gasolineras,
                &$tanques,
                &$contadorGasolinera,
                &$contadorTanque
            ): void {
                foreach (
                    $empresas
                    as $indiceEmpresa => $empresaReferencia
                ) {
                    $empresa = Empresa::query()
                        ->findOrFail(
                            (int) $empresaReferencia['id']
                        );

                    $cantidadGasolineras =
                        $distribucion[$indiceEmpresa];

                    for (
                        $numeroEmpresa = 1;
                        $numeroEmpresa <= $cantidadGasolineras;
                        $numeroEmpresa++
                    ) {
                        $contadorGasolinera++;

                        $estadoGasolinera =
                            $this->estadoGasolinera(
                                empresa: $empresa,
                                numeroGlobal:
                                    $contadorGasolinera
                            );

                        $gasolinera =
                            $this->crearGasolinera(
                                empresa: $empresa,
                                numeroGlobal:
                                    $contadorGasolinera,
                                numeroEmpresa:
                                    $numeroEmpresa,
                                estado:
                                    $estadoGasolinera,
                                usuarioId:
                                    $usuarioId
                            );

                        $cantidadTanques =
                            $contadorGasolinera <= 12
                                ? 3
                                : 2;

                        $registroGasolinera = [
                            'id' =>
                                $gasolinera->id,

                            'empresa_id' =>
                                $empresa->id,

                            'empresa_numero' =>
                                $empresaReferencia['numero'],

                            'numero_global' =>
                                $contadorGasolinera,

                            'numero_empresa' =>
                                $numeroEmpresa,

                            'nombre' =>
                                $gasolinera->nombre,

                            'estado' =>
                                $gasolinera->estado,

                            'cantidad_tanques' =>
                                $cantidadTanques,

                            'tanques' =>
                                [],
                        ];

                        for (
                            $numeroTanque = 1;
                            $numeroTanque <= $cantidadTanques;
                            $numeroTanque++
                        ) {
                            $contadorTanque++;

                            $condicionObjetivo =
                                $condicionesObjetivo[
                                    $contadorTanque - 1
                                ];

                            $estadoTanque =
                                $condicionObjetivo
                                    === 'inactivo'
                                        ? 'inactivo'
                                        : 'activo';

                            $tanque =
                                $this->crearTanque(
                                    gasolinera: $gasolinera,
                                    numeroGlobal:
                                        $contadorTanque,
                                    numeroTanque:
                                        $numeroTanque,
                                    estado:
                                        $estadoTanque,
                                    condicionObjetivo:
                                        $condicionObjetivo,
                                    usuarioId:
                                        $usuarioId
                                );

                            $registroTanque = [
                                'id' =>
                                    $tanque->id,

                                'empresa_id' =>
                                    $empresa->id,

                                'gasolinera_id' =>
                                    $gasolinera->id,

                                'gasolinera_numero' =>
                                    $contadorGasolinera,

                                'numero_global' =>
                                    $contadorTanque,

                                'numero_tanque' =>
                                    $numeroTanque,

                                'nombre' =>
                                    $tanque->nombre,

                                'capacidad_total' =>
                                    (float)
                                    $tanque->capacidad_total,

                                'volumen_actual' =>
                                    (float)
                                    $tanque->volumen_actual,

                                'volumen_minimo_alerta' =>
                                    (float)
                                    $tanque
                                        ->volumen_minimo_alerta,

                                'estado' =>
                                    $tanque->estado,

                                'gasolinera_estado' =>
                                    $gasolinera->estado,

                                'empresa_estado' =>
                                    $empresa->estado,

                                /*
                                 * Esta condición se materializará al
                                 * finalizar recargas y abastecimientos.
                                 */
                                'condicion_objetivo' =>
                                    $condicionObjetivo,
                            ];

                            $tanques[] =
                                $registroTanque;

                            $registroGasolinera[
                                'tanques'
                            ][] = $registroTanque;

                            CcFlotaSeederContext::
                                registrarReferencia(
                                    sprintf(
                                        'tanques.por_numero.%03d',
                                        $contadorTanque
                                    ),
                                    $tanque->id
                                );

                            CcFlotaSeederContext::
                                registrarEscenario(
                                    "tanque.{$tanque->id}",
                                    $registroTanque
                                );
                        }

                        $gasolineras[] =
                            $registroGasolinera;

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'gasolineras_internas.'
                                    . 'por_numero.%02d',
                                    $contadorGasolinera
                                ),
                                $gasolinera->id
                            );

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'gasolineras_internas.'
                                    . 'empresa_%02d.%02d',
                                    $empresaReferencia['numero'],
                                    $numeroEmpresa
                                ),
                                $gasolinera->id
                            );

                        CcFlotaSeederContext::
                            registrarEscenario(
                                "gasolinera_interna."
                                . $gasolinera->id,
                                $registroGasolinera
                            );
                    }
                }
            },
            3
        );

        if (
            count($gasolineras)
            !== $totalGasolineras
        ) {
            throw new RuntimeException(
                'La cantidad creada de gasolineras internas '
                . 'no coincide con la configuración.'
            );
        }

        if (count($tanques) !== 72) {
            throw new RuntimeException(
                'La cantidad creada de tanques no coincide '
                . 'con el diseño esperado de 72.'
            );
        }

        $gasolinerasActivas =
            array_values(
                array_filter(
                    $gasolineras,
                    fn (array $gasolinera): bool =>
                        $gasolinera['estado'] === 'activa'
                )
            );

        $gasolinerasInactivas =
            array_values(
                array_filter(
                    $gasolineras,
                    fn (array $gasolinera): bool =>
                        $gasolinera['estado'] === 'inactiva'
                )
            );

        $tanquesActivos =
            array_values(
                array_filter(
                    $tanques,
                    fn (array $tanque): bool =>
                        $tanque['estado'] === 'activo'
                )
            );

        $tanquesInactivos =
            array_values(
                array_filter(
                    $tanques,
                    fn (array $tanque): bool =>
                        $tanque['estado'] === 'inactivo'
                )
            );

        $tanquesOperables =
            array_values(
                array_filter(
                    $tanquesActivos,
                    fn (array $tanque): bool =>
                        $tanque['gasolinera_estado']
                            === 'activa'
                        && $tanque['empresa_estado']
                            === 'activa'
                )
            );

        CcFlotaSeederContext::registrarReferencia(
            'gasolineras_internas.todas',
            $gasolineras
        );

        CcFlotaSeederContext::registrarReferencia(
            'gasolineras_internas.activas',
            $gasolinerasActivas
        );

        CcFlotaSeederContext::registrarReferencia(
            'gasolineras_internas.inactivas',
            $gasolinerasInactivas
        );

        CcFlotaSeederContext::registrarReferencia(
            'tanques.todos',
            $tanques
        );

        CcFlotaSeederContext::registrarReferencia(
            'tanques.activos',
            $tanquesActivos
        );

        CcFlotaSeederContext::registrarReferencia(
            'tanques.inactivos',
            $tanquesInactivos
        );

        CcFlotaSeederContext::registrarReferencia(
            'tanques.operables',
            $tanquesOperables
        );

        foreach (
            array_keys(
                CcFlotaSeederConfig::
                    DISTRIBUCION_TANQUES
            )
            as $condicion
        ) {
            CcFlotaSeederContext::
                registrarReferencia(
                    "tanques.objetivo.{$condicion}",
                    array_values(
                        array_filter(
                            $tanques,
                            fn (array $tanque): bool =>
                                $tanque[
                                    'condicion_objetivo'
                                ] === $condicion
                        )
                    )
                );
        }

        if ($gasolinerasActivas !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'gasolinera_interna.activa',
                $gasolinerasActivas[0]
            );
        }

        if ($gasolinerasInactivas !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'gasolinera_interna.inactiva',
                $gasolinerasInactivas[0]
            );
        }

        if ($tanquesInactivos !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'tanque.inactivo',
                $tanquesInactivos[0]
            );
        }

        $this->command?->line(
            'Gasolineras internas creadas: '
            . count($gasolineras)
        );

        $this->command?->line(
            'Gasolineras internas activas: '
            . count($gasolinerasActivas)
        );

        $this->command?->line(
            'Gasolineras internas inactivas: '
            . count($gasolinerasInactivas)
        );

        $this->command?->line(
            'Tanques creados: '
            . count($tanques)
        );

        $this->command?->line(
            'Tanques operables: '
            . count($tanquesOperables)
        );

        $this->command?->line(
            'Tanques inactivos: '
            . count($tanquesInactivos)
        );
    }

    /**
     * Distribuye el total de gasolineras entre empresas.
     *
     * Para 18 empresas y 30 gasolineras:
     * las primeras 12 reciben 2 y las últimas 6 reciben 1.
     */
    private function distribucionPorEmpresa(
        int $cantidadEmpresas,
        int $totalGasolineras
    ): array {
        if (
            $cantidadEmpresas <= 0
            || $totalGasolineras < $cantidadEmpresas
        ) {
            throw new RuntimeException(
                'La distribución de gasolineras no es válida.'
            );
        }

        $base =
            intdiv(
                $totalGasolineras,
                $cantidadEmpresas
            );

        $residuo =
            $totalGasolineras
            % $cantidadEmpresas;

        $resultado = [];

        for (
            $indice = 0;
            $indice < $cantidadEmpresas;
            $indice++
        ) {
            $resultado[] =
                $base
                + ($indice < $residuo ? 1 : 0);
        }

        return $resultado;
    }

    /**
     * Crea una distribución final exacta.
     */
    private function crearDistribucionExacta(
        array $distribucion,
        int $cantidad
    ): array {
        if (
            ! CcFlotaSeederConfig::distribucionValida(
                $distribucion
            )
        ) {
            throw new RuntimeException(
                'La distribución de tanques no suma 100.'
            );
        }

        $resultado = [];
        $asignados = 0;
        $claves = array_keys($distribucion);

        foreach (
            $distribucion
            as $clave => $porcentaje
        ) {
            $cantidadCategoria =
                (int) floor(
                    ($cantidad * $porcentaje) / 100
                );

            for (
                $indice = 0;
                $indice < $cantidadCategoria;
                $indice++
            ) {
                $resultado[] = $clave;
            }

            $asignados += $cantidadCategoria;
        }

        $faltantes = $cantidad - $asignados;

        for (
            $indice = 0;
            $indice < $faltantes;
            $indice++
        ) {
            $resultado[] =
                $claves[
                    $indice % count($claves)
                ];
        }

        return CcFlotaDeterministicGenerator::mezclar(
            $resultado
        );
    }

    /**
     * Determina el estado administrativo de una gasolinera.
     */
    private function estadoGasolinera(
        Empresa $empresa,
        int $numeroGlobal
    ): string {
        if ($empresa->estado === 'inactiva') {
            return 'inactiva';
        }

        /*
         * Tres gasolineras inactivas adicionales para escenarios
         * administrativos e históricos.
         */
        return in_array(
            $numeroGlobal,
            [8, 19, 27],
            true
        )
            ? 'inactiva'
            : 'activa';
    }

    /**
     * Crea una gasolinera interna.
     */
    private function crearGasolinera(
        Empresa $empresa,
        int $numeroGlobal,
        int $numeroEmpresa,
        string $estado,
        int $usuarioId
    ): Gasolinera {
        $fechaCreacion =
            CcFlotaSeederContext::fechaInicio()
                ->subMonths(
                    4 + ($numeroGlobal % 20)
                )
                ->addDays(
                    ($numeroGlobal * 5) % 25
                )
                ->setTime(
                    7 + ($numeroGlobal % 8),
                    ($numeroGlobal * 9) % 60
                );

        $fechaInactivacion = null;
        $motivoInactivacion = null;
        $inactivadoPor = null;
        $fechaActualizacion = null;
        $actualizadoPor = null;

        if ($estado === 'inactiva') {
            $fechaInactivacion =
                CcFlotaSeederContext::fechaFin()
                    ->subDays(
                        45 + (($numeroGlobal * 17) % 300)
                    )
                    ->setTime(15, 20);

            if (
                $fechaInactivacion->lessThan(
                    $fechaCreacion
                )
            ) {
                $fechaInactivacion =
                    $fechaCreacion->addMonths(6);
            }

            $motivoInactivacion =
                $empresa->estado === 'inactiva'
                    ? 'Gasolinera histórica de una empresa inactiva.'
                    : 'Cierre administrativo de la estación interna.';

            $inactivadoPor = $usuarioId;
            $fechaActualizacion = $fechaInactivacion;
            $actualizadoPor = $usuarioId;
        }

        return Gasolinera::create([
            'empresa_id' =>
                $empresa->id,

            'nombre' =>
                sprintf(
                    'Estación Interna %02d-%02d',
                    $empresa->id,
                    $numeroEmpresa
                ),

            'direccion' =>
                sprintf(
                    'Plantel operativo %02d, área de combustible %02d',
                    $empresa->id,
                    $numeroEmpresa
                ),

            'encargado' =>
                sprintf(
                    'Encargado de Estación %02d',
                    $numeroGlobal
                ),

            'telefono' =>
                sprintf(
                    '2400-%04d',
                    3000 + $numeroGlobal
                ),

            'correo' =>
                sprintf(
                    'estacion%02d@ccflota.demo',
                    $numeroGlobal
                ),

            'estado' =>
                $estado,

            'fecha_creacion' =>
                $fechaCreacion,

            'creado_por' =>
                $usuarioId,

            'fecha_actualizacion' =>
                $fechaActualizacion,

            'actualizado_por' =>
                $actualizadoPor,

            'fecha_inactivacion' =>
                $fechaInactivacion,

            'inactivado_por' =>
                $inactivadoPor,

            'motivo_inactivacion' =>
                $motivoInactivacion,
        ]);
    }

    /**
     * Crea un tanque con inventario inicial cero.
     */
    private function crearTanque(
        Gasolinera $gasolinera,
        int $numeroGlobal,
        int $numeroTanque,
        string $estado,
        string $condicionObjetivo,
        int $usuarioId
    ): Tanque {
        $capacidad =
            CcFlotaSeederConfig::CAPACIDADES_TANQUE[
                ($numeroGlobal - 1)
                % count(
                    CcFlotaSeederConfig::
                        CAPACIDADES_TANQUE
                )
            ];

        $porcentajeMinimo =
            CcFlotaSeederConfig::
                PORCENTAJES_MINIMO_TANQUE[
                    ($numeroGlobal - 1)
                    % count(
                        CcFlotaSeederConfig::
                            PORCENTAJES_MINIMO_TANQUE
                    )
                ];

        $volumenMinimo = round(
            $capacidad
            * ($porcentajeMinimo / 100),
            2
        );

        $fechaCreacion =
            CarbonImmutable::parse(
                $gasolinera->fecha_creacion
            )->addDays($numeroTanque);

        $fechaInactivacion = null;
        $motivoInactivacion = null;
        $inactivadoPor = null;
        $fechaActualizacion = null;
        $actualizadoPor = null;

        if ($estado === 'inactivo') {
            $fechaInactivacion =
                CcFlotaSeederContext::fechaFin()
                    ->subDays(
                        20 + (($numeroGlobal * 7) % 220)
                    )
                    ->setTime(13, 40);

            if (
                $fechaInactivacion->lessThan(
                    $fechaCreacion
                )
            ) {
                $fechaInactivacion =
                    $fechaCreacion->addMonths(4);
            }

            $motivoInactivacion =
                'Tanque inactivado para mantenimiento definitivo.';

            $inactivadoPor = $usuarioId;
            $fechaActualizacion = $fechaInactivacion;
            $actualizadoPor = $usuarioId;
        }

        return Tanque::create([
            'gasolinera_id' =>
                $gasolinera->id,

            'nombre' =>
                sprintf(
                    'Tanque %02d',
                    $numeroTanque
                ),

            'capacidad_total' =>
                $capacidad,

            /*
             * El historial de inventario debe comenzar en cero.
             * Las recargas posteriores construirán el saldo real.
             */
            'volumen_actual' =>
                0.00,

            'volumen_minimo_alerta' =>
                $volumenMinimo,

            'estado' =>
                $estado,

            'fecha_creacion' =>
                $fechaCreacion,

            'creado_por' =>
                $usuarioId,

            'fecha_actualizacion' =>
                $fechaActualizacion,

            'actualizado_por' =>
                $actualizadoPor,

            'fecha_inactivacion' =>
                $fechaInactivacion,

            'inactivado_por' =>
                $inactivadoPor,

            'motivo_inactivacion' =>
                $motivoInactivacion,
        ]);
    }
}