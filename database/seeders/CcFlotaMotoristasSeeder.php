<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Motorista;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CcFlotaMotoristasSeeder extends Seeder
{
    /**
     * Crea motoristas activos e inactivos distribuidos
     * uniformemente entre las empresas.
     */
    public function run(): void
    {
        $this->command?->info(
            'Creando motoristas y escenarios históricos...'
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
            CcFlotaSeederConfig::TOTAL_MOTORISTAS;

        if (
            $totalEsperado % count($empresas) !== 0
        ) {
            throw new RuntimeException(
                'TOTAL_MOTORISTAS debe poder distribuirse '
                . 'uniformemente entre las empresas.'
            );
        }

        $motoristasPorEmpresa =
            intdiv(
                $totalEsperado,
                count($empresas)
            );

        $estados =
            $this->crearDistribucionExacta(
                CcFlotaSeederConfig::
                    DISTRIBUCION_MOTORISTAS,
                $totalEsperado
            );

        $motoristas = [];
        $contadorGlobal = 0;

        DB::transaction(
            function () use (
                $empresas,
                $motoristasPorEmpresa,
                $estados,
                $usuarioId,
                &$motoristas,
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
                        $indiceEmpresa <= $motoristasPorEmpresa;
                        $indiceEmpresa++
                    ) {
                        $contadorGlobal++;

                        $estado =
                            $estados[$contadorGlobal - 1];

                        /*
                         * Los motoristas de una empresa inactiva deben
                         * permanecer históricos y no operables.
                         */
                        if ($empresa->estado === 'inactiva') {
                            $estado = 'inactivo';
                        }

                        $motorista =
                            $this->crearMotorista(
                                empresa: $empresa,
                                numeroGlobal: $contadorGlobal,
                                numeroEmpresa: $indiceEmpresa,
                                estado: $estado,
                                usuarioId: $usuarioId
                            );

                        $registro = [
                            'id' =>
                                $motorista->id,

                            'empresa_id' =>
                                $empresa->id,

                            'empresa_numero' =>
                                $empresaReferencia['numero'],

                            'numero_global' =>
                                $contadorGlobal,

                            'numero_empresa' =>
                                $indiceEmpresa,

                            'nombre_completo' =>
                                $motorista->nombre_completo,

                            'licencia' =>
                                $motorista->licencia,

                            'telefono' =>
                                $motorista->telefono,

                            'estado' =>
                                $motorista->estado,

                            'empresa_estado' =>
                                $empresa->estado,
                        ];

                        $motoristas[] = $registro;

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'motoristas.por_numero.%03d',
                                    $contadorGlobal
                                ),
                                $motorista->id
                            );

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'motoristas.empresa_%02d.%02d',
                                    $empresaReferencia['numero'],
                                    $indiceEmpresa
                                ),
                                $motorista->id
                            );

                        CcFlotaSeederContext::
                            registrarEscenario(
                                "motorista.{$motorista->id}",
                                $registro
                            );
                    }
                }
            },
            3
        );

        if (
            count($motoristas)
            !== $totalEsperado
        ) {
            throw new RuntimeException(
                'La cantidad creada de motoristas no coincide '
                . 'con TOTAL_MOTORISTAS.'
            );
        }

        $activos = array_values(
            array_filter(
                $motoristas,
                fn (array $motorista): bool =>
                    $motorista['estado'] === 'activo'
            )
        );

        $inactivos = array_values(
            array_filter(
                $motoristas,
                fn (array $motorista): bool =>
                    $motorista['estado'] === 'inactivo'
            )
        );

        $operables = array_values(
            array_filter(
                $activos,
                fn (array $motorista): bool =>
                    $motorista['empresa_estado'] === 'activa'
            )
        );

        CcFlotaSeederContext::registrarReferencia(
            'motoristas.todos',
            $motoristas
        );

        CcFlotaSeederContext::registrarReferencia(
            'motoristas.activos',
            $activos
        );

        CcFlotaSeederContext::registrarReferencia(
            'motoristas.inactivos',
            $inactivos
        );

        CcFlotaSeederContext::registrarReferencia(
            'motoristas.operables',
            $operables
        );

        if ($activos !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'motorista.activo',
                $activos[0]
            );
        }

        if ($inactivos !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'motorista.inactivo',
                $inactivos[0]
            );
        }

        $historicoEmpresaInactiva =
            collect($inactivos)
                ->first(
                    fn (array $motorista): bool =>
                        $motorista['empresa_estado']
                        === 'inactiva'
                );

        if ($historicoEmpresaInactiva) {
            CcFlotaSeederContext::registrarEscenario(
                'motorista.empresa_inactiva',
                $historicoEmpresaInactiva
            );
        }

        $this->command?->line(
            'Motoristas creados: '
            . count($motoristas)
        );

        $this->command?->line(
            'Motoristas activos: '
            . count($activos)
        );

        $this->command?->line(
            'Motoristas inactivos: '
            . count($inactivos)
        );

        $this->command?->line(
            'Motoristas operables: '
            . count($operables)
        );
    }

    /**
     * Crea una distribución con cantidad final exacta.
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
                'La distribución de motoristas no suma 100.'
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
     * Crea un motorista individual.
     */
    private function crearMotorista(
        Empresa $empresa,
        int $numeroGlobal,
        int $numeroEmpresa,
        string $estado,
        int $usuarioId
    ): Motorista {
        [
            $nombres,
            $apellidos,
        ] = $this->nombreMotorista(
            $numeroGlobal
        );

        $fechaCreacion =
            $this->fechaCreacion(
                $numeroGlobal
            );

        $fechaInactivacion = null;
        $motivoInactivacion = null;
        $inactivadoPor = null;
        $fechaActualizacion = null;
        $actualizadoPor = null;

        if ($estado === 'inactivo') {
            $fechaInactivacion =
                $this->fechaInactivacion(
                    $numeroGlobal
                );

            if (
                $fechaInactivacion->lessThan(
                    $fechaCreacion
                )
            ) {
                $fechaInactivacion =
                    $fechaCreacion->addMonths(3);
            }

            $motivoInactivacion =
                $empresa->estado === 'inactiva'
                    ? 'Motorista histórico perteneciente '
                        . 'a una empresa inactiva.'
                    : $this->motivoInactivacion(
                        $numeroGlobal
                    );

            $inactivadoPor = $usuarioId;
            $fechaActualizacion = $fechaInactivacion;
            $actualizadoPor = $usuarioId;
        }

        return Motorista::create([
            'empresa_id' =>
                $empresa->id,

            'nombres' =>
                $nombres,

            'apellidos' =>
                $apellidos,

            'licencia' =>
                sprintf(
                    'LIC-%010d',
                    CcFlotaSeederConfig::
                        LICENCIA_MOTORISTA_INICIO
                    + $numeroGlobal
                ),

            'telefono' =>
                sprintf(
                    '7%03d-%04d',
                    (
                        100
                        + (
                            $numeroGlobal % 900
                        )
                    ),
                    (
                        1000
                        + (
                            ($numeroGlobal * 37) % 9000
                        )
                    )
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
     * Genera nombres variados y determinísticos.
     */
    private function nombreMotorista(
        int $numero
    ): array {
        $nombres = [
            'Carlos Alberto',
            'José Manuel',
            'Luis Fernando',
            'Miguel Ángel',
            'Óscar Armando',
            'Francisco Javier',
            'Roberto Antonio',
            'Mario Ernesto',
            'Jorge Alexander',
            'Ricardo Enrique',
            'Ana María',
            'María Fernanda',
            'Claudia Beatriz',
            'Patricia Elena',
            'Sandra Carolina',
            'Verónica Isabel',
            'Rosa Margarita',
            'Daniela Alejandra',
        ];

        $apellidos = [
            'Hernández López',
            'Martínez García',
            'Rodríguez Pérez',
            'González Ramírez',
            'López Flores',
            'Rivera Mejía',
            'Castro Aguilar',
            'Vásquez Romero',
            'Morales Cruz',
            'Chávez Ortiz',
            'Reyes Molina',
            'Ramírez Escobar',
            'Flores Alvarado',
            'Gómez Portillo',
            'Méndez Salazar',
            'Torres Rivas',
            'Pineda Guardado',
            'Aguilar Meléndez',
        ];

        return [
            $nombres[
                ($numero - 1)
                % count($nombres)
            ],

            $apellidos[
                (($numero - 1) * 5)
                % count($apellidos)
            ],
        ];
    }

    /**
     * Fecha histórica de creación.
     */
    private function fechaCreacion(
        int $numero
    ): CarbonImmutable {
        return CcFlotaSeederContext::fechaInicio()
            ->subMonths(
                2 + ($numero % 18)
            )
            ->addDays(
                ($numero * 3) % 24
            )
            ->setTime(
                7 + ($numero % 9),
                ($numero * 11) % 60
            );
    }

    /**
     * Fecha histórica de inactivación.
     */
    private function fechaInactivacion(
        int $numero
    ): CarbonImmutable {
        return CcFlotaSeederContext::fechaFin()
            ->subDays(
                30 + (($numero * 13) % 360)
            )
            ->setTime(
                10 + ($numero % 6),
                ($numero * 17) % 60
            );
    }

    /**
     * Motivo funcional de inactivación.
     */
    private function motivoInactivacion(
        int $numero
    ): string {
        $motivos = [
            'Finalización de relación laboral.',
            'Cambio de funciones dentro de la empresa.',
            'Licencia de conducción no renovada.',
            'Retiro voluntario del motorista.',
            'Suspensión administrativa definitiva.',
        ];

        return $motivos[
            ($numero - 1)
            % count($motivos)
        ];
    }
}