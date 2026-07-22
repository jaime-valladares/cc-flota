<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class CcFlotaCompleteSeeder extends Seeder
{
    /**
     * Reconstruye integralmente los datos funcionales
     * y operativos de CC-Flota.
     */
    public function run(): void
    {
        $this->command?->newLine();

        $this->command?->info(
            'Iniciando revisión previa no destructiva...'
        );

        /*
        |--------------------------------------------------------------------------
        | Revisión previa no destructiva
        |--------------------------------------------------------------------------
        |
        | Esta validación ocurre antes de limpiar cualquier dato.
        | Si falta una clase, tabla o columna, el proceso se detiene aquí.
        |
        */

        $preflight =
            CcFlotaSeederPreflight::validarEstructura();

        $this->command?->info(
            'Revisión previa completada correctamente.'
        );

        $this->command?->line(
            'Clases validadas: '
            . $preflight['clases_validadas']
        );

        $this->command?->line(
            'Tablas validadas: '
            . $preflight['tablas_validadas']
        );

        /*
        |--------------------------------------------------------------------------
        | Limpieza controlada
        |--------------------------------------------------------------------------
        */

        $this->command?->newLine();

        $this->command?->info(
            'Iniciando reconstrucción integral de CC-Flota...'
        );

        $this->call([
            ResetCcFlotaDataSeeder::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Recuperar superusuario conservado
        |--------------------------------------------------------------------------
        */

        $superUser = User::query()
            ->where('estado', 'activo')
            ->whereNull('empresa_id')
            ->whereHas(
                'role',
                function ($query): void {
                    $query->where(
                        'codigo',
                        'DIESEL_SUPER_ADMIN'
                    );
                }
            )
            ->firstOrFail();

        /*
        |--------------------------------------------------------------------------
        | Inicializar y validar contexto
        |--------------------------------------------------------------------------
        */

        CcFlotaSeederContext::inicializar(
            $superUser->id
        );

        $erroresConfiguracion =
            CcFlotaSeederConfig::validar();

        if ($erroresConfiguracion !== []) {
            throw new RuntimeException(
                'Configuración inválida del seeder: '
                . implode(
                    ' | ',
                    $erroresConfiguracion
                )
            );
        }

        CcFlotaDeterministicGenerator::reiniciar();

        $this->command?->info(
            'Configuración maestra validada correctamente.'
        );

        /*
        |--------------------------------------------------------------------------
        | Datos maestros
        |--------------------------------------------------------------------------
        */

        $this->call([
            CcFlotaEmpresasSeeder::class,
            CcFlotaUnidadesLicenciasSeeder::class,
            CcFlotaPuntosMarchamosSeeder::class,
            CcFlotaMotoristasSeeder::class,
            CcFlotaGasolinerasTanquesSeeder::class,
            CcFlotaGasolinerasExternasSeeder::class,
            CcFlotaPuntosRutasSeeder::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Planificación y materialización operacional
        |--------------------------------------------------------------------------
        */

        $plan =
            CcFlotaOperationalPlan::construir();

        $this->command?->info(
            'Plan operativo cronológico construido correctamente.'
        );

        $this->command?->line(
            'Eventos planificados: '
            . $plan['resumen']['total_eventos']
        );

        $this->call([
            CcFlotaOperacionHistoricaSeeder::class,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Resumen final
        |--------------------------------------------------------------------------
        */

        $resultado =
            CcFlotaSeederContext::referencia(
                'operacion.materializada'
            );

        $this->command?->newLine();

        $this->command?->info(
            'Reconstrucción integral completada correctamente.'
        );

        $this->command?->line(
            'Superusuario conservado: #'
            . CcFlotaSeederContext::superUserId()
        );

        $this->command?->line(
            'Historia operativa: '
            . CcFlotaSeederContext::fechaInicio()
                ->format('d/m/Y')
            . ' → '
            . CcFlotaSeederContext::fechaFin()
                ->format('d/m/Y')
        );

        $resumenes = [
            'Empresas' =>
                count(
                    CcFlotaSeederContext::referencia(
                        'empresas.todas'
                    )
                ),

            'Unidades' =>
                count(
                    CcFlotaSeederContext::referencia(
                        'unidades.todas'
                    )
                ),

            'Motoristas' =>
                count(
                    CcFlotaSeederContext::referencia(
                        'motoristas.todos'
                    )
                ),

            'Gasolineras internas' =>
                count(
                    CcFlotaSeederContext::referencia(
                        'gasolineras_internas.todas'
                    )
                ),

            'Tanques' =>
                count(
                    CcFlotaSeederContext::referencia(
                        'tanques.todos'
                    )
                ),

            'Gasolineras externas' =>
                count(
                    CcFlotaSeederContext::referencia(
                        'gasolineras_externas.todas'
                    )
                ),

            'Puntos de ruta' =>
                count(
                    CcFlotaSeederContext::referencia(
                        'puntos_ruta.todos'
                    )
                ),

            'Rutas' =>
                count(
                    CcFlotaSeederContext::referencia(
                        'rutas.todas'
                    )
                ),

            'Recargas' =>
                $resultado['recargas'] ?? 0,

            'Abastecimientos' =>
                $resultado['abastecimientos'] ?? 0,

            'Movimientos de inventario' =>
                $resultado[
                    'movimientos_inventario'
                ] ?? 0,

            'Reemplazos de marchamos' =>
                $resultado[
                    'detalles_marchamos'
                ] ?? 0,
        ];

        foreach (
            $resumenes
            as $etiqueta => $valor
        ) {
            $this->command?->line(
                $etiqueta . ': ' . $valor
            );
        }

        $this->command?->newLine();

        $this->command?->info(
            'La base quedó preparada para análisis, '
            . 'reportería y auditoría.'
        );
    }
}