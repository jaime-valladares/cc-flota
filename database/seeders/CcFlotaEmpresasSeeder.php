<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use RuntimeException;

class CcFlotaEmpresasSeeder extends Seeder
{
    /**
     * Crea las empresas base y registra sus perfiles
     * dentro del contexto compartido.
     */
    public function run(): void
    {
        $this->command?->info(
            'Creando empresas y perfiles operativos...'
        );

        $usuarioId =
            CcFlotaSeederContext::superUserId();

        $fechaInicio =
            CcFlotaSeederContext::fechaInicio();

        $perfiles =
            $this->expandirPerfiles();

        if (
            count($perfiles)
            !== CcFlotaSeederConfig::TOTAL_EMPRESAS
        ) {
            throw new RuntimeException(
                'La cantidad de perfiles expandidos no coincide '
                . 'con TOTAL_EMPRESAS.'
            );
        }

        $empresas = [];

        foreach (
            $perfiles
            as $indice => $perfil
        ) {
            $numero = $indice + 1;

            $empresa =
                $this->crearEmpresa(
                    numero: $numero,
                    perfil: $perfil,
                    usuarioId: $usuarioId,
                    fechaInicio: $fechaInicio
                );

            $claveEmpresa = sprintf(
                'empresas.%s.%02d',
                $perfil,
                $this->numeroDentroDelPerfil(
                    $perfiles,
                    $indice,
                    $perfil
                )
            );

            CcFlotaSeederContext::registrarReferencia(
                $claveEmpresa,
                $empresa->id
            );

            CcFlotaSeederContext::registrarReferencia(
                "empresas.por_numero.{$numero}",
                $empresa->id
            );

            CcFlotaSeederContext::registrarEscenario(
                "empresa.{$empresa->id}",
                [
                    'empresa_id' =>
                        $empresa->id,

                    'numero' =>
                        $numero,

                    'perfil' =>
                        $perfil,

                    'estado' =>
                        $empresa->estado,

                    'nombre_legal' =>
                        $empresa->nombre_legal,

                    'nombre_comercial' =>
                        $empresa->nombre_comercial,
                ]
            );

            $empresas[] = [
                'id' =>
                    $empresa->id,

                'numero' =>
                    $numero,

                'perfil' =>
                    $perfil,

                'estado' =>
                    $empresa->estado,
            ];
        }

        CcFlotaSeederContext::registrarReferencia(
            'empresas.todas',
            $empresas
        );

        CcFlotaSeederContext::registrarReferencia(
            'empresas.activas',
            array_values(
                array_filter(
                    $empresas,
                    fn (array $empresa): bool =>
                        $empresa['estado'] === 'activa'
                )
            )
        );

        CcFlotaSeederContext::registrarReferencia(
            'empresas.inactivas',
            array_values(
                array_filter(
                    $empresas,
                    fn (array $empresa): bool =>
                        $empresa['estado'] === 'inactiva'
                )
            )
        );

        $this->command?->line(
            'Empresas creadas: '
            . count($empresas)
        );

        $this->command?->line(
            'Empresas activas: '
            . count(
                CcFlotaSeederContext::referencia(
                    'empresas.activas'
                )
            )
        );

        $this->command?->line(
            'Empresas inactivas: '
            . count(
                CcFlotaSeederContext::referencia(
                    'empresas.inactivas'
                )
            )
        );
    }

    /**
     * Convierte la configuración de perfiles en una lista
     * con una entrada por empresa.
     */
    private function expandirPerfiles(): array
    {
        $perfiles = [];

        foreach (
            CcFlotaSeederConfig::PERFILES_EMPRESA
            as $codigo => $configuracion
        ) {
            $cantidad =
                (int) ($configuracion['cantidad'] ?? 0);

            for (
                $indice = 1;
                $indice <= $cantidad;
                $indice++
            ) {
                $perfiles[] = $codigo;
            }
        }

        return $perfiles;
    }

    /**
     * Determina la posición correlativa de una empresa
     * dentro de su perfil.
     */
    private function numeroDentroDelPerfil(
        array $perfiles,
        int $indiceActual,
        string $perfil
    ): int {
        $contador = 0;

        for (
            $indice = 0;
            $indice <= $indiceActual;
            $indice++
        ) {
            if ($perfiles[$indice] === $perfil) {
                $contador++;
            }
        }

        return $contador;
    }

    /**
     * Crea una empresa coherente con el perfil asignado.
     */
    private function crearEmpresa(
        int $numero,
        string $perfil,
        int $usuarioId,
        CarbonImmutable $fechaInicio
    ): Empresa {
        $estado =
            $perfil === 'historicos_inactivos'
                ? 'inactiva'
                : 'activa';

        $fechaCreacion =
            $fechaInicio
                ->subMonths(
                    CcFlotaDeterministicGenerator::entero(
                        2,
                        24
                    )
                )
                ->addDays(
                    CcFlotaDeterministicGenerator::entero(
                        0,
                        25
                    )
                )
                ->setTime(
                    CcFlotaDeterministicGenerator::entero(
                        7,
                        16
                    ),
                    CcFlotaDeterministicGenerator::entero(
                        0,
                        59
                    )
                );

        $fechaInactivacion = null;
        $motivoInactivacion = null;
        $inactivadoPor = null;

        if ($estado === 'inactiva') {
            $fechaInactivacion =
                CcFlotaSeederContext::fechaFin()
                    ->subMonths(2)
                    ->subDays(5)
                    ->setTime(15, 30);

            $motivoInactivacion =
                'Cierre histórico de operaciones de la empresa.';

            $inactivadoPor = $usuarioId;
        }

        $nombresComerciales = [
            'Transportes Horizonte',
            'Logística Cuscatlán',
            'Carga Centroamericana',
            'Operaciones del Pacífico',
            'Flota Metropolitana',
            'Servicios Viales del Norte',
            'Transporte Industrial Salvadoreño',
            'Distribución Regional',
            'Movilidad Empresarial',
            'Carga Segura',
            'Operaciones La Libertad',
            'Transportes Occidente',
            'Logística Oriental',
            'Flota Nacional',
            'Servicios de Transporte Unido',
            'Operaciones Ruta Verde',
            'Transporte Estratégico',
            'Corporación de Movilidad',
        ];

        $nombreComercial =
            $nombresComerciales[$numero - 1]
            ?? sprintf(
                'Empresa Operativa %02d',
                $numero
            );

        $nombreLegal =
            $nombreComercial
            . ', S.A. de C.V.';

        return Empresa::create([
            'nombre_legal' =>
                $nombreLegal,

            'nombre_comercial' =>
                $nombreComercial,

            'nit' =>
                $this->generarNit($numero),

            'direccion' =>
                $this->generarDireccion(
                    $numero
                ),

            'telefono_empresa' =>
                sprintf(
                    '2500-%04d',
                    1000 + $numero
                ),

            'correo_empresa' =>
                sprintf(
                    'empresa%02d@ccflota.demo',
                    $numero
                ),

            'poc_nombre' =>
                sprintf(
                    'Contacto Operativo %02d',
                    $numero
                ),

            'poc_email' =>
                sprintf(
                    'contacto%02d@ccflota.demo',
                    $numero
                ),

            'poc_telefono' =>
                sprintf(
                    '7000-%04d',
                    2000 + $numero
                ),

            'estado' =>
                $estado,

            'fecha_creacion' =>
                $fechaCreacion,

            'creado_por' =>
                $usuarioId,

            'fecha_actualizacion' =>
                $estado === 'inactiva'
                    ? $fechaInactivacion
                    : null,

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
     * Genera un NIT único y legible.
     */
    private function generarNit(
        int $numero
    ): string {
        return sprintf(
            '0614-%06d-%03d-%d',
            100000 + $numero,
            100 + $numero,
            $numero % 10
        );
    }

    /**
     * Genera direcciones variadas para reportería.
     */
    private function generarDireccion(
        int $numero
    ): string {
        $ubicaciones = [
            'San Salvador',
            'Santa Tecla',
            'Antiguo Cuscatlán',
            'Soyapango',
            'Apopa',
            'Santa Ana',
            'Sonsonate',
            'Ahuachapán',
            'La Libertad',
            'Zacatecoluca',
            'San Vicente',
            'Cojutepeque',
            'Usulután',
            'San Miguel',
            'La Unión',
            'Chalatenango',
            'Sensuntepeque',
            'Metapán',
        ];

        $ubicacion =
            $ubicaciones[$numero - 1]
            ?? 'San Salvador';

        return sprintf(
            'Centro operativo %02d, %s, El Salvador',
            $numero,
            $ubicacion
        );
    }
}