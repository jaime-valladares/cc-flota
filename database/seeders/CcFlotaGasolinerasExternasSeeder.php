<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\GasolineraExterna;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CcFlotaGasolinerasExternasSeeder extends Seeder
{
    /**
     * Crea el catálogo de gasolineras externas por empresa.
     *
     * Cada registro representa una sucursal autorizada dentro
     * de la red de una compañía de combustible.
     */
    public function run(): void
    {
        $this->command?->info(
            'Creando gasolineras externas...'
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
            CcFlotaSeederConfig::
                TOTAL_GASOLINERAS_EXTERNAS;

        $distribucion =
            $this->distribucionPorEmpresa(
                cantidadEmpresas: count($empresas),
                totalGasolineras: $totalEsperado
            );

        $gasolineras = [];
        $contadorGlobal = 0;

        DB::transaction(
            function () use (
                $empresas,
                $distribucion,
                $usuarioId,
                &$gasolineras,
                &$contadorGlobal
            ): void {
                foreach (
                    $empresas
                    as $indiceEmpresa => $empresaReferencia
                ) {
                    $empresa = Empresa::query()
                        ->findOrFail(
                            (int) $empresaReferencia['id']
                        );

                    $cantidadEmpresa =
                        $distribucion[$indiceEmpresa];

                    for (
                        $numeroEmpresa = 1;
                        $numeroEmpresa <= $cantidadEmpresa;
                        $numeroEmpresa++
                    ) {
                        $contadorGlobal++;

                        $compania =
                            CcFlotaSeederConfig::
                                COMPANIAS_EXTERNAS[
                                    (
                                        $contadorGlobal
                                        + $indiceEmpresa
                                        - 1
                                    )
                                    % count(
                                        CcFlotaSeederConfig::
                                            COMPANIAS_EXTERNAS
                                    )
                                ];

                        $estado =
                            $this->estadoGasolinera(
                                empresa: $empresa,
                                numeroGlobal: $contadorGlobal
                            );

                        $gasolinera =
                            $this->crearGasolinera(
                                empresa: $empresa,
                                compania: $compania,
                                numeroGlobal: $contadorGlobal,
                                numeroEmpresa: $numeroEmpresa,
                                estado: $estado,
                                usuarioId: $usuarioId
                            );

                        $registro = [
                            'id' =>
                                $gasolinera->id,

                            'empresa_id' =>
                                $empresa->id,

                            'empresa_numero' =>
                                $empresaReferencia['numero'],

                            'numero_global' =>
                                $contadorGlobal,

                            'numero_empresa' =>
                                $numeroEmpresa,

                            'compania' =>
                                $gasolinera->compania,

                            'direccion' =>
                                $gasolinera->direccion,

                            'estado' =>
                                $gasolinera->estado,

                            'empresa_estado' =>
                                $empresa->estado,
                        ];

                        $gasolineras[] = $registro;

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'gasolineras_externas.'
                                    . 'por_numero.%02d',
                                    $contadorGlobal
                                ),
                                $gasolinera->id
                            );

                        CcFlotaSeederContext::
                            registrarReferencia(
                                sprintf(
                                    'gasolineras_externas.'
                                    . 'empresa_%02d.%02d',
                                    $empresaReferencia['numero'],
                                    $numeroEmpresa
                                ),
                                $gasolinera->id
                            );

                        CcFlotaSeederContext::
                            registrarEscenario(
                                "gasolinera_externa."
                                . $gasolinera->id,
                                $registro
                            );
                    }
                }
            },
            3
        );

        if (
            count($gasolineras)
            !== $totalEsperado
        ) {
            throw new RuntimeException(
                'La cantidad creada de gasolineras externas '
                . 'no coincide con la configuración.'
            );
        }

        $activas =
            array_values(
                array_filter(
                    $gasolineras,
                    fn (array $gasolinera): bool =>
                        $gasolinera['estado'] === 'activa'
                )
            );

        $inactivas =
            array_values(
                array_filter(
                    $gasolineras,
                    fn (array $gasolinera): bool =>
                        $gasolinera['estado'] === 'inactiva'
                )
            );

        $operables =
            array_values(
                array_filter(
                    $activas,
                    fn (array $gasolinera): bool =>
                        $gasolinera['empresa_estado']
                            === 'activa'
                )
            );

        CcFlotaSeederContext::registrarReferencia(
            'gasolineras_externas.todas',
            $gasolineras
        );

        CcFlotaSeederContext::registrarReferencia(
            'gasolineras_externas.activas',
            $activas
        );

        CcFlotaSeederContext::registrarReferencia(
            'gasolineras_externas.inactivas',
            $inactivas
        );

        CcFlotaSeederContext::registrarReferencia(
            'gasolineras_externas.operables',
            $operables
        );

        foreach (
            CcFlotaSeederConfig::COMPANIAS_EXTERNAS
            as $compania
        ) {
            CcFlotaSeederContext::
                registrarReferencia(
                    'gasolineras_externas.compania.'
                    . $this->claveCompania($compania),
                    array_values(
                        array_filter(
                            $gasolineras,
                            fn (array $gasolinera): bool =>
                                $gasolinera['compania']
                                === $compania
                        )
                    )
                );
        }

        if ($activas !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'gasolinera_externa.activa',
                $activas[0]
            );
        }

        if ($inactivas !== []) {
            CcFlotaSeederContext::registrarEscenario(
                'gasolinera_externa.inactiva',
                $inactivas[0]
            );
        }

        $historicaEmpresaInactiva =
            collect($inactivas)
                ->first(
                    fn (array $gasolinera): bool =>
                        $gasolinera['empresa_estado']
                        === 'inactiva'
                );

        if ($historicaEmpresaInactiva) {
            CcFlotaSeederContext::registrarEscenario(
                'gasolinera_externa.empresa_inactiva',
                $historicaEmpresaInactiva
            );
        }

        $this->command?->line(
            'Gasolineras externas creadas: '
            . count($gasolineras)
        );

        $this->command?->line(
            'Gasolineras externas activas: '
            . count($activas)
        );

        $this->command?->line(
            'Gasolineras externas inactivas: '
            . count($inactivas)
        );

        $this->command?->line(
            'Gasolineras externas operables: '
            . count($operables)
        );
    }

    /**
     * Distribuye el total de gasolineras entre empresas.
     *
     * Para 18 empresas y 45 gasolineras:
     * las primeras 9 reciben 3 y las últimas 9 reciben 2.
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
                'La distribución de gasolineras externas '
                . 'no es válida.'
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
     * Determina el estado administrativo de la sucursal.
     */
    private function estadoGasolinera(
        Empresa $empresa,
        int $numeroGlobal
    ): string {
        if ($empresa->estado === 'inactiva') {
            return 'inactiva';
        }

        /*
         * Cuatro sucursales inactivas adicionales permiten
         * validar consulta histórica y exclusión de selectores.
         */
        return in_array(
            $numeroGlobal,
            [7, 18, 31, 42],
            true
        )
            ? 'inactiva'
            : 'activa';
    }

    /**
     * Crea una sucursal externa.
     */
    private function crearGasolinera(
        Empresa $empresa,
        string $compania,
        int $numeroGlobal,
        int $numeroEmpresa,
        string $estado,
        int $usuarioId
    ): GasolineraExterna {
        $fechaCreacion =
            CcFlotaSeederContext::fechaInicio()
                ->subMonths(
                    3 + ($numeroGlobal % 22)
                )
                ->addDays(
                    ($numeroGlobal * 4) % 26
                )
                ->setTime(
                    7 + ($numeroGlobal % 9),
                    ($numeroGlobal * 13) % 60
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
                        30 + (($numeroGlobal * 19) % 330)
                    )
                    ->setTime(14, 25);

            if (
                $fechaInactivacion->lessThan(
                    $fechaCreacion
                )
            ) {
                $fechaInactivacion =
                    $fechaCreacion->addMonths(5);
            }

            $motivoInactivacion =
                $empresa->estado === 'inactiva'
                    ? 'Sucursal histórica asociada '
                        . 'a una empresa inactiva.'
                    : 'Sucursal retirada del catálogo '
                        . 'de abastecimiento autorizado.';

            $inactivadoPor = $usuarioId;
            $fechaActualizacion = $fechaInactivacion;
            $actualizadoPor = $usuarioId;
        }

        return GasolineraExterna::create([
            'empresa_id' =>
                $empresa->id,

            'direccion' =>
                $this->direccionSucursal(
                    numeroGlobal: $numeroGlobal,
                    numeroEmpresa: $numeroEmpresa
                ),

            'compania' =>
                $compania,

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
     * Genera direcciones únicas y útiles para reportería.
     */
    private function direccionSucursal(
        int $numeroGlobal,
        int $numeroEmpresa
    ): string {
        $ubicaciones = [
            'Boulevard del Ejército, Soyapango',
            'Carretera Panamericana, Santa Tecla',
            'Boulevard Los Próceres, San Salvador',
            'Carretera a Santa Ana, Colón',
            'Boulevard Venezuela, San Salvador',
            'Carretera del Litoral, La Libertad',
            'Avenida Roosevelt, San Miguel',
            'Carretera Troncal del Norte, Apopa',
            'Bypass de Usulután, Usulután',
            'Carretera a Sonsonate, Lourdes',
            'Boulevard Monseñor Romero, Antiguo Cuscatlán',
            'Carretera a Metapán, Santa Ana',
            'Ruta Militar, San Miguel',
            'Carretera a Zacatecoluca, Olocuilta',
            'Carretera a La Unión, Santa Rosa de Lima',
        ];

        $ubicacion =
            $ubicaciones[
                ($numeroGlobal - 1)
                % count($ubicaciones)
            ];

        return sprintf(
            '%s, sucursal autorizada %02d-%02d',
            $ubicacion,
            $numeroGlobal,
            $numeroEmpresa
        );
    }

    /**
     * Convierte el nombre de una compañía en una clave estable.
     */
    private function claveCompania(
        string $compania
    ): string {
        return strtolower(
            str_replace(
                [' ', '-', '.'],
                '_',
                $compania
            )
        );
    }
}