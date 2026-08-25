<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\Unidad;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CcFlotaUnidadesLicenciasSeeder extends Seeder
{
    /**
     * Crea las unidades y sus licencias de forma determinística.
     *
     * La cobertura de marchamos se construirá en el siguiente seeder.
     */
    public function run(): void
    {
        $this->command?->info(
            'Creando unidades y licencias...'
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

        $totalEsperado =
            CcFlotaSeederConfig::TOTAL_UNIDADES;

        if (
            $totalEsperado % count($empresas) !== 0
        ) {
            throw new RuntimeException(
                'TOTAL_UNIDADES debe poder distribuirse '
                . 'uniformemente entre las empresas.'
            );
        }

        $unidadesPorEmpresa =
            intdiv(
                $totalEsperado,
                count($empresas)
            );

        $modelos =
            $this->crearDistribucionExacta(
                CcFlotaSeederConfig::DISTRIBUCION_MODELOS,
                $totalEsperado
            );

        $plantillas =
            $this->crearDistribucionExacta(
                CcFlotaSeederConfig::DISTRIBUCION_PLANTILLAS,
                $totalEsperado
            );

        $estadosUnidad =
            $this->crearDistribucionExacta(
                CcFlotaSeederConfig::DISTRIBUCION_ESTADOS_UNIDAD,
                $totalEsperado
            );

        $condicionesLicencia =
            $this->crearDistribucionExacta(
                CcFlotaSeederConfig::DISTRIBUCION_LICENCIAS,
                $totalEsperado
            );

        $unidades = [];
        $licencias = [];
        $contadorGlobal = 0;

        DB::transaction(
            function () use (
                $empresas,
                $unidadesPorEmpresa,
                $modelos,
                $plantillas,
                $estadosUnidad,
                $condicionesLicencia,
                $usuarioId,
                &$unidades,
                &$licencias,
                &$contadorGlobal
            ): void {
                foreach (
                    $empresas
                    as $empresaReferencia
                ) {
                    $empresa = Empresa::query()
                        ->findOrFail(
                            (int) $empresaReferencia['id']
                        );

                    for (
                        $indiceEmpresa = 1;
                        $indiceEmpresa <= $unidadesPorEmpresa;
                        $indiceEmpresa++
                    ) {
                        $contadorGlobal++;

                        $modelo =
                            $modelos[$contadorGlobal - 1];

                        $plantilla =
                            $plantillas[$contadorGlobal - 1];

                        $estadoUnidad =
                            $estadosUnidad[$contadorGlobal - 1];

                        $condicionLicencia =
                            $condicionesLicencia[$contadorGlobal - 1];

                        /*
                         * Toda unidad de una empresa inactiva debe quedar
                         * administrativamente inactiva.
                         */
                        if ($empresa->estado === 'inactiva') {
                            $estadoUnidad = 'inactiva';
                        }

                        /*
                         * Una unidad registrada se utiliza como escenario
                         * pendiente de activación y no debe aparecer como
                         * plenamente operativa.
                         */
                        if (
                            $condicionLicencia === 'sin_licencia'
                            && $estadoUnidad === 'activa'
                        ) {
                            $estadoUnidad = 'registrada';
                        }

                        $unidad =
                            $this->crearUnidad(
                                empresa: $empresa,
                                numeroGlobal: $contadorGlobal,
                                numeroEmpresa: $indiceEmpresa,
                                modelo: $modelo,
                                plantilla: $plantilla,
                                estado: $estadoUnidad,
                                usuarioId: $usuarioId
                            );

                        $licencia = null;

                        if (
                            $condicionLicencia
                            !== 'sin_licencia'
                        ) {
                            $licencia =
                                $this->crearLicencia(
                                    empresa: $empresa,
                                    unidad: $unidad,
                                    plantilla: $plantilla,
                                    condicion: $condicionLicencia,
                                    usuarioId: $usuarioId
                                );
                        }

                        $registroUnidad = [
                            'id' =>
                                $unidad->id,

                            'empresa_id' =>
                                $empresa->id,

                            'numero_global' =>
                                $contadorGlobal,

                            'numero_empresa' =>
                                $indiceEmpresa,

                            'placa' =>
                                $unidad->placa,

                            'modelo_medicion' =>
                                $unidad->modelo_medicion,

                            'plantilla' =>
                                $plantilla,

                            'estado' =>
                                $unidad->estado,

                            'condicion_licencia' =>
                                $condicionLicencia,

                            'licencia_id' =>
                                $licencia?->id,

                            'total_tanques' =>
                                $unidad->total_tanques,

                            'capacidad_total' =>
                                (float) $unidad->capacidad_total,

                            'capacidad_cubierta' =>
                                (float) $unidad->capacidad_cubierta,
                        ];

                        $unidades[] = $registroUnidad;

                        if ($licencia) {
                            $licencias[] = [
                                'id' =>
                                    $licencia->id,

                                'empresa_id' =>
                                    $empresa->id,

                                'unidad_id' =>
                                    $unidad->id,

                                'condicion' =>
                                    $condicionLicencia,

                                'estado' =>
                                    $licencia->estado,

                                'fecha_activacion' =>
                                    $licencia
                                        ->fecha_activacion
                                        ->toDateString(),

                                'fecha_vencimiento' =>
                                    $licencia
                                        ->fecha_vencimiento
                                        ->toDateString(),

                                'plantilla' =>
                                    $licencia
                                        ->plantilla_puntos_seguridad,
                            ];
                        }

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'unidades.por_numero.%03d',
                                    $contadorGlobal
                                ),
                                $unidad->id
                            );

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'unidades.empresa_%02d.%02d',
                                    $empresaReferencia['numero'],
                                    $indiceEmpresa
                                ),
                                $unidad->id
                            );

                        CcFlotaSeederContext::
                            registrarEscenario(
                                "unidad.{$unidad->id}",
                                $registroUnidad
                            );
                    }
                }
            },
            3
        );

        if (count($unidades) !== $totalEsperado) {
            throw new RuntimeException(
                'La cantidad creada de unidades no coincide '
                . 'con TOTAL_UNIDADES.'
            );
        }

        $this->registrarColecciones(
            $unidades,
            $licencias
        );

        $this->command?->line(
            'Unidades creadas: '
            . count($unidades)
        );

        $this->command?->line(
            'Licencias creadas: '
            . count($licencias)
        );

        $this->command?->line(
            'Unidades sin licencia: '
            . count(
                CcFlotaSeederContext::referencia(
                    'unidades.sin_licencia'
                )
            )
        );

        $this->command?->line(
            'Licencias vigentes: '
            . count(
                CcFlotaSeederContext::referencia(
                    'licencias.vigentes'
                )
            )
        );

        $this->command?->line(
            'Licencias vencidas: '
            . count(
                CcFlotaSeederContext::referencia(
                    'licencias.vencidas'
                )
            )
        );
    }

    /**
     * Crea una distribución con exactamente la cantidad solicitada.
     *
     * Los residuos derivados de porcentajes se asignan a las primeras
     * categorías para garantizar que el total final sea exacto.
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
                'Se intentó utilizar una distribución '
                . 'que no suma 100.'
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
     * Crea una unidad con capacidades coherentes con su plantilla.
     */
    private function crearUnidad(
        Empresa $empresa,
        int $numeroGlobal,
        int $numeroEmpresa,
        string $modelo,
        string $plantilla,
        string $estado,
        int $usuarioId
    ): Unidad {
        $totalTanques = match ($plantilla) {
            'plantilla_1_tanque' => 1,
            'plantilla_2_tanques' => 2,
            'plantilla_3_tanques' => 3,
            default => throw new RuntimeException(
                "Plantilla no reconocida: {$plantilla}"
            ),
        };

        $capacidadPorTanque =
            CcFlotaDeterministicGenerator::elegir(
                [
                    50.00,
                    60.00,
                    75.00,
                    80.00,
                    100.00,
                ]
            );

        $capacidadTotal = round(
            $capacidadPorTanque * $totalTanques,
            2
        );

        /*
         * Algunas licencias cubren menos tanques que la unidad posee.
         * Ese escenario se utilizará luego para pruebas administrativas,
         * pero la capacidad cubierta nunca puede superar la total.
         */
        $tanquesCubiertos =
            CcFlotaDeterministicGenerator::probabilidad(8)
                && $totalTanques > 1
                    ? $totalTanques - 1
                    : $totalTanques;

        $capacidadCubierta = round(
            $capacidadPorTanque * $tanquesCubiertos,
            2
        );

        $fechaInactivacion = null;
        $motivoInactivacion = null;
        $inactivadoPor = null;

        if ($estado === 'inactiva') {
            $fechaInactivacion =
                CcFlotaSeederContext::fechaFin()
                    ->subDays(
                        CcFlotaDeterministicGenerator::entero(
                            20,
                            220
                        )
                    )
                    ->setTime(
                        14,
                        CcFlotaDeterministicGenerator::entero(
                            0,
                            59
                        )
                    );

            $motivoInactivacion =
                'Unidad conservada como registro histórico '
                . 'para análisis y auditoría.';

            $inactivadoPor = $usuarioId;
        }

        return Unidad::create([
            'empresa_id' =>
                $empresa->id,

            'placa' =>
                sprintf(
                    'CCF-%04d',
                    CcFlotaSeederConfig::PLACA_INICIO
                    + $numeroGlobal
                ),

            'marca' =>
                CcFlotaSeederConfig::MARCAS_UNIDAD[
                    ($numeroGlobal - 1)
                    % count(
                        CcFlotaSeederConfig::MARCAS_UNIDAD
                    )
                ],

            'total_tanques' =>
                $totalTanques,

            'cantidad_tanques_con_licencia' =>
                $tanquesCubiertos,

            'capacidad_total' =>
                $capacidadTotal,

            'capacidad_cubierta' =>
                $capacidadCubierta,

            'modelo_medicion' =>
                $modelo,

            'estado' =>
                $estado,

            'creado_por' =>
                $usuarioId,

            'actualizado_por' =>
                $estado === 'inactiva'
                    ? $usuarioId
                    : null,

            'fecha_inactivacion' =>
                $fechaInactivacion,

            'inactivado_por' =>
                $inactivadoPor,

            'motivo_inactivacion' =>
                $motivoInactivacion,
        ]);
    }

    /**
     * Crea una licencia con fechas coherentes con su condición.
     */
    private function crearLicencia(
        Empresa $empresa,
        Unidad $unidad,
        string $plantilla,
        string $condicion,
        int $usuarioId
    ): Licencia {
        $fechaFin =
            CcFlotaSeederContext::fechaFin();

        $periodo = match (
            CcFlotaDeterministicGenerator::entero(
                1,
                3
            )
        ) {
            1 => 3,
            2 => 6,
            default => 12,
        };

        $estado = 'activa';
        $fechaInactivacion = null;
        $inactivadoPor = null;
        $motivoInactivacion = null;

        [
            $fechaActivacion,
            $fechaVencimiento,
        ] = match ($condicion) {
            'vigente' => [
                $fechaFin->subMonths(
                    CcFlotaDeterministicGenerator::entero(
                        1,
                        min(5, $periodo)
                    )
                ),
                $fechaFin->addMonths(
                    CcFlotaDeterministicGenerator::entero(
                        2,
                        10
                    )
                ),
            ],

            'vencida' => [
                $fechaFin->subMonths(
                    $periodo + 3
                ),
                $fechaFin->subDays(
                    CcFlotaDeterministicGenerator::entero(
                        15,
                        180
                    )
                ),
            ],

            'futura' => [
                $fechaFin->addDays(
                    CcFlotaDeterministicGenerator::entero(
                        15,
                        90
                    )
                ),
                $fechaFin
                    ->addDays(
                        CcFlotaDeterministicGenerator::entero(
                            15,
                            90
                        )
                    )
                    ->addMonths($periodo),
            ],

            'inactiva' => [
                $fechaFin->subMonths(
                    CcFlotaDeterministicGenerator::entero(
                        1,
                        5
                    )
                ),
                $fechaFin->addMonths($periodo),
            ],

            default => throw new RuntimeException(
                "Condición de licencia no reconocida: {$condicion}"
            ),
        };

        /*
         * En futuras fechas generamos ambas a partir de una sola base
         * para garantizar que vencimiento sea posterior a activación.
         */
        if ($condicion === 'futura') {
            $fechaActivacion =
                $fechaFin->addDays(
                    CcFlotaDeterministicGenerator::entero(
                        15,
                        90
                    )
                );

            $fechaVencimiento =
                $fechaActivacion->addMonths($periodo);
        }

        if ($condicion === 'inactiva') {
            $estado = 'inactiva';

            $fechaInactivacion =
                $fechaFin
                    ->subDays(
                        CcFlotaDeterministicGenerator::entero(
                            5,
                            120
                        )
                    )
                    ->setTime(11, 30);

            $inactivadoPor = $usuarioId;

            $motivoInactivacion =
                'Licencia inactivada para escenario '
                . 'de control administrativo.';
        }

        return Licencia::create([
            'empresa_id' =>
                $empresa->id,

            'unidad_id' =>
                $unidad->id,

            'periodo_vigencia_meses' =>
                $periodo,

            'fecha_activacion' =>
                $fechaActivacion->toDateString(),

            'fecha_vencimiento' =>
                $fechaVencimiento->toDateString(),

            'estado' =>
                $estado,

            'plantilla_puntos_seguridad' =>
                $plantilla,

            'creado_por' =>
                $usuarioId,

            'actualizado_por' =>
                $estado === 'inactiva'
                    ? $usuarioId
                    : null,

            'fecha_inactivacion' =>
                $fechaInactivacion,

            'inactivado_por' =>
                $inactivadoPor,

            'motivo_inactivacion' =>
                $motivoInactivacion,
        ]);
    }

    /**
     * Registra las colecciones que utilizarán los seeders posteriores.
     */
    private function registrarColecciones(
        array $unidades,
        array $licencias
    ): void {
        CcFlotaSeederContext::registrarReferencia(
            'unidades.todas',
            $unidades
        );

        foreach (
            [
                'activa',
                'inactiva',
                'registrada',
            ]
            as $estado
        ) {
            CcFlotaSeederContext::registrarReferencia(
                "unidades.{$estado}s",
                array_values(
                    array_filter(
                        $unidades,
                        fn (array $unidad): bool =>
                            $unidad['estado'] === $estado
                    )
                )
            );
        }

        foreach (
            [
                'kilometros_galon',
                'galones_hora',
                'galones_viaje',
            ]
            as $modelo
        ) {
            CcFlotaSeederContext::registrarReferencia(
                "unidades.modelo.{$modelo}",
                array_values(
                    array_filter(
                        $unidades,
                        fn (array $unidad): bool =>
                            $unidad['modelo_medicion']
                            === $modelo
                    )
                )
            );
        }

        foreach (
            [
                'plantilla_1_tanque',
                'plantilla_2_tanques',
                'plantilla_3_tanques',
            ]
            as $plantilla
        ) {
            CcFlotaSeederContext::registrarReferencia(
                "unidades.plantilla.{$plantilla}",
                array_values(
                    array_filter(
                        $unidades,
                        fn (array $unidad): bool =>
                            $unidad['plantilla']
                            === $plantilla
                    )
                )
            );
        }

        CcFlotaSeederContext::registrarReferencia(
            'unidades.sin_licencia',
            array_values(
                array_filter(
                    $unidades,
                    fn (array $unidad): bool =>
                        $unidad['condicion_licencia']
                        === 'sin_licencia'
                )
            )
        );

        CcFlotaSeederContext::registrarReferencia(
            'licencias.todas',
            $licencias
        );

        $mapa = [
            'vigente' => 'vigentes',
            'vencida' => 'vencidas',
            'futura' => 'futuras',
            'inactiva' => 'inactivas',
        ];

        foreach (
            $mapa
            as $condicion => $plural
        ) {
            CcFlotaSeederContext::registrarReferencia(
                "licencias.{$plural}",
                array_values(
                    array_filter(
                        $licencias,
                        fn (array $licencia): bool =>
                            $licencia['condicion']
                            === $condicion
                    )
                )
            );
        }

        /*
         * Esta lista es preliminar. La disponibilidad total solo se
         * alcanzará después de crear puntos de seguridad y marchamos.
         */
        CcFlotaSeederContext::registrarReferencia(
            'unidades.candidatas_operables',
            array_values(
                array_filter(
                    $unidades,
                    fn (array $unidad): bool =>
                        $unidad['estado'] === 'activa'
                        && $unidad['condicion_licencia']
                            === 'vigente'
                )
            )
        );
    }
}