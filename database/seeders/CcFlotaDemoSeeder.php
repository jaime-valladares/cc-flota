<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\PlantillasPuntosSeguridad;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CcFlotaDemoSeeder extends Seeder
{
    private int $cantidadEmpresas = 35;

    private int $unidadesPorEmpresa = 20;

    private int $gasolinerasPorEmpresa = 10;

    private int $marchamoSecuencia = 0;

    private array $plantillas = [
        'plantilla_1_tanque',
        'plantilla_2_tanques',
        'plantilla_3_tanques',
    ];

    private array $modelosMedicion = [
        'galones_kilometro',
        'galones_hora',
        'galones_viaje',
    ];

    private array $marcas = [
        'Freightliner',
        'Kenworth',
        'International',
        'Mack',
        'Volvo',
        'Peterbilt',
        'Isuzu',
        'Hino',
    ];

    private array $capacidadesTanques = [
        3000,
        4500,
        5000,
        6500,
        8000,
        10000,
        12000,
    ];

    public function run(): void
    {
        $usuarioResponsableId = $this->resolverUsuarioResponsableId();

        if (! $usuarioResponsableId) {
            $this->command?->error('No se encontró ningún usuario para usar como responsable de creación.');

            return;
        }

        $this->marchamoSecuencia = 0;

        $this->command?->warn('Limpiando datos operativos actuales sin tocar la tabla users...');

        $this->limpiarDatosOperativos();

        $this->command?->info('Generando información demo de CC-Flota...');

        $fechaBase = Carbon::now();
        $fechaActivacion = Carbon::today();
        $fechaVencimiento = Carbon::today()->addMonths(12);

        $totales = [
            'empresas' => 0,
            'unidades' => 0,
            'licencias' => 0,
            'puntos' => 0,
            'marchamos' => 0,
            'gasolineras' => 0,
            'tanques' => 0,
            'movimientos_inventario' => 0,
        ];

        DB::transaction(function () use (
            $usuarioResponsableId,
            $fechaBase,
            $fechaActivacion,
            $fechaVencimiento,
            &$totales
        ) {
            $indiceGlobalUnidad = 0;
            $indiceGlobalGasolinera = 0;
            $indiceGlobalTanque = 0;

            for ($empresaNumero = 1; $empresaNumero <= $this->cantidadEmpresas; $empresaNumero++) {
                $empresaId = $this->crearEmpresa(
                    empresaNumero: $empresaNumero,
                    usuarioResponsableId: $usuarioResponsableId,
                    fechaBase: $fechaBase
                );

                $totales['empresas']++;

                for ($unidadNumero = 1; $unidadNumero <= $this->unidadesPorEmpresa; $unidadNumero++) {
                    $indiceGlobalUnidad++;

                    $plantilla = $this->plantillaParaUnidad($indiceGlobalUnidad);
                    $cantidadTanques = $this->cantidadTanquesParaPlantilla($plantilla);

                    $unidadId = $this->crearUnidad(
                        empresaId: $empresaId,
                        empresaNumero: $empresaNumero,
                        unidadNumero: $unidadNumero,
                        indiceGlobalUnidad: $indiceGlobalUnidad,
                        cantidadTanques: $cantidadTanques,
                        usuarioResponsableId: $usuarioResponsableId,
                        fechaBase: $fechaBase
                    );

                    $totales['unidades']++;

                    $this->crearLicencia(
                        empresaId: $empresaId,
                        unidadId: $unidadId,
                        plantilla: $plantilla,
                        usuarioResponsableId: $usuarioResponsableId,
                        fechaActivacion: $fechaActivacion,
                        fechaVencimiento: $fechaVencimiento,
                        fechaBase: $fechaBase
                    );

                    $totales['licencias']++;

                    $resultadoPuntos = $this->crearPuntosYMarchamos(
                        empresaId: $empresaId,
                        unidadId: $unidadId,
                        plantilla: $plantilla,
                        usuarioResponsableId: $usuarioResponsableId,
                        fechaBase: $fechaBase
                    );

                    $totales['puntos'] += $resultadoPuntos['puntos'];
                    $totales['marchamos'] += $resultadoPuntos['marchamos'];
                }

                for ($gasolineraNumero = 1; $gasolineraNumero <= $this->gasolinerasPorEmpresa; $gasolineraNumero++) {
                    $indiceGlobalGasolinera++;

                    $gasolineraId = $this->crearGasolinera(
                        empresaId: $empresaId,
                        empresaNumero: $empresaNumero,
                        gasolineraNumero: $gasolineraNumero,
                        usuarioResponsableId: $usuarioResponsableId,
                        fechaBase: $fechaBase
                    );

                    $totales['gasolineras']++;

                    $cantidadTanquesGasolinera = $this->cantidadTanquesParaGasolinera($indiceGlobalGasolinera);

                    for ($tanqueNumero = 1; $tanqueNumero <= $cantidadTanquesGasolinera; $tanqueNumero++) {
                        $indiceGlobalTanque++;

                        $tanqueId = $this->crearTanque(
                            empresaId: $empresaId,
                            gasolineraId: $gasolineraId,
                            empresaNumero: $empresaNumero,
                            gasolineraNumero: $gasolineraNumero,
                            tanqueNumero: $tanqueNumero,
                            indiceGlobalTanque: $indiceGlobalTanque,
                            usuarioResponsableId: $usuarioResponsableId,
                            fechaBase: $fechaBase
                        );

                        $totales['tanques']++;

                        $this->crearMovimientoCargaInicial(
                            empresaId: $empresaId,
                            tanqueId: $tanqueId,
                            indiceGlobalTanque: $indiceGlobalTanque,
                            usuarioResponsableId: $usuarioResponsableId,
                            fechaBase: $fechaBase
                        );

                        $totales['movimientos_inventario']++;
                    }
                }
            }
        });

        $this->command?->info('Seeder demo completado correctamente.');
        $this->command?->line("Empresas creadas: {$totales['empresas']}");
        $this->command?->line("Unidades creadas: {$totales['unidades']}");
        $this->command?->line("Licencias creadas: {$totales['licencias']}");
        $this->command?->line("Puntos creados: {$totales['puntos']}");
        $this->command?->line("Marchamos creados: {$totales['marchamos']}");
        $this->command?->line("Gasolineras creadas: {$totales['gasolineras']}");
        $this->command?->line("Tanques creados: {$totales['tanques']}");
        $this->command?->line("Movimientos iniciales de inventario creados: {$totales['movimientos_inventario']}");
    }

    private function limpiarDatosOperativos(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            /*
             * Limpieza de tablas operativas y dependientes.
             *
             * No se toca users.
             * No se tocan roles, permisos ni rol_permisos.
             * truncateSiExiste permite ejecutar este seeder aunque algunos módulos futuros aún no existan.
             */
            $tablas = [
                'abastecimiento_marchamos',
                'movimientos_inventario_combustible',
                'abastecimientos',
                'tanques',
                'gasolineras',
                'reemplazo_marchamos_detalle',
                'reemplazo_marchamos_eventos',
                'marchamos',
                'puntos_seguridad_unidad',
                'licencias',
                'unidades',
                'empresas',
            ];

            foreach ($tablas as $tabla) {
                $this->truncateSiExiste($tabla);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function truncateSiExiste(string $tabla): void
    {
        if (! Schema::hasTable($tabla)) {
            return;
        }

        DB::table($tabla)->truncate();
    }

    private function resolverUsuarioResponsableId(): ?int
    {
        return User::query()
            ->where('tipo_usuario', 'diesel_cop')
            ->orderBy('id')
            ->value('id')
            ?? User::query()->orderBy('id')->value('id');
    }

    private function crearEmpresa(int $empresaNumero, int $usuarioResponsableId, Carbon $fechaBase): int
    {
        return (int) DB::table('empresas')->insertGetId([
            'nombre_legal' => sprintf('Empresa Demo %03d, S.A. de C.V.', $empresaNumero),
            'nombre_comercial' => sprintf('Flota Demo %03d', $empresaNumero),
            'nit' => $this->generarNit($empresaNumero),
            'direccion' => sprintf('Centro operativo demo %03d, San Salvador, El Salvador', $empresaNumero),
            'telefono_empresa' => sprintf('2500-%04d', $empresaNumero),
            'correo_empresa' => sprintf('empresa%03d@demo.ccflota.test', $empresaNumero),
            'poc_nombre' => sprintf('Contacto Empresa Demo %03d', $empresaNumero),
            'poc_email' => sprintf('contacto%03d@demo.ccflota.test', $empresaNumero),
            'poc_telefono' => sprintf('7000-%04d', $empresaNumero),
            'estado' => 'activa',
            'fecha_creacion' => $fechaBase,
            'creado_por' => $usuarioResponsableId,
            'fecha_actualizacion' => null,
            'actualizado_por' => null,
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
        ]);
    }

    private function crearUnidad(
        int $empresaId,
        int $empresaNumero,
        int $unidadNumero,
        int $indiceGlobalUnidad,
        int $cantidadTanques,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): int {
        $capacidadPorTanque = 120;
        $capacidadTotal = $cantidadTanques * $capacidadPorTanque;

        return (int) DB::table('unidades')->insertGetId([
            'empresa_id' => $empresaId,
            'placa' => sprintf('DEMO-%03d-%02d', $empresaNumero, $unidadNumero),
            'marca' => $this->marcas[($indiceGlobalUnidad - 1) % count($this->marcas)],
            'total_tanques' => $cantidadTanques,
            'cantidad_tanques_con_licencia' => $cantidadTanques,
            'capacidad_total' => $capacidadTotal,
            'capacidad_cubierta' => $capacidadTotal,
            'modelo_medicion' => $this->modelosMedicion[($indiceGlobalUnidad - 1) % count($this->modelosMedicion)],
            'estado' => 'activa',
            'creado_por' => $usuarioResponsableId,
            'actualizado_por' => null,
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'created_at' => $fechaBase,
            'updated_at' => $fechaBase,
        ]);
    }

    private function crearLicencia(
        int $empresaId,
        int $unidadId,
        string $plantilla,
        int $usuarioResponsableId,
        Carbon $fechaActivacion,
        Carbon $fechaVencimiento,
        Carbon $fechaBase
    ): void {
        DB::table('licencias')->insert([
            'empresa_id' => $empresaId,
            'unidad_id' => $unidadId,
            'periodo_vigencia_meses' => 12,
            'fecha_activacion' => $fechaActivacion->toDateString(),
            'fecha_vencimiento' => $fechaVencimiento->toDateString(),
            'estado' => 'activa',
            'plantilla_puntos_seguridad' => $plantilla,
            'creado_por' => $usuarioResponsableId,
            'actualizado_por' => null,
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
            'created_at' => $fechaBase,
            'updated_at' => $fechaBase,
        ]);
    }

    private function crearPuntosYMarchamos(
        int $empresaId,
        int $unidadId,
        string $plantilla,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): array {
        $puntosPlantilla = PlantillasPuntosSeguridad::porPlantilla($plantilla);

        $registrosPuntos = [];

        foreach ($puntosPlantilla as $punto) {
            $registrosPuntos[] = [
                'unidad_id' => $unidadId,
                'orden' => $punto['orden'],
                'codigo_punto' => $punto['codigo_punto'] ?? null,
                'grupo' => $punto['grupo'] ?? null,
                'subgrupo' => $punto['subgrupo'] ?? null,
                'nombre_punto' => $punto['nombre_punto'],
                'descripcion' => null,
                'posicion_tanque' => $punto['posicion_tanque'] ?? null,
                'tipo_punto' => $punto['tipo_punto'] ?? null,
                'requiere_marchamo' => (bool) ($punto['requiere_marchamo'] ?? true),
                'plantilla_origen' => $plantilla,
                'criterio_origen' => $punto['criterio_origen'] ?? null,
                'estado_asignacion' => 'pendiente',
                'marchamo_actual_id' => null,
                'estado' => 'activo',
                'creado_por' => $usuarioResponsableId,
                'actualizado_por' => null,
                'fecha_inactivacion' => null,
                'inactivado_por' => null,
                'motivo_inactivacion' => null,
                'created_at' => $fechaBase,
                'updated_at' => $fechaBase,
            ];
        }

        DB::table('puntos_seguridad_unidad')->insert($registrosPuntos);

        $puntosCreados = DB::table('puntos_seguridad_unidad')
            ->where('unidad_id', $unidadId)
            ->orderBy('orden')
            ->get([
                'id',
                'requiere_marchamo',
            ]);

        $totalMarchamos = 0;

        foreach ($puntosCreados as $puntoCreado) {
            if (! $puntoCreado->requiere_marchamo) {
                continue;
            }

            $marchamoId = (int) DB::table('marchamos')->insertGetId([
                'empresa_id' => $empresaId,
                'unidad_id' => $unidadId,
                'punto_seguridad_id' => $puntoCreado->id,
                'codigo_marchamo' => $this->siguienteCodigoMarchamo(),
                'fecha_activacion' => $fechaBase,
                'estado' => 'activo',
                'activo_actual' => 1,
                'fecha_desactivacion' => null,
                'motivo_desactivacion' => null,
                'origen_creacion' => 'asignacion_inicial',
                'creado_por' => $usuarioResponsableId,
                'actualizado_por' => null,
                'created_at' => $fechaBase,
                'updated_at' => $fechaBase,
            ]);

            DB::table('puntos_seguridad_unidad')
                ->where('id', $puntoCreado->id)
                ->update([
                    'marchamo_actual_id' => $marchamoId,
                    'estado_asignacion' => 'asignado',
                    'updated_at' => $fechaBase,
                ]);

            $totalMarchamos++;
        }

        return [
            'puntos' => count($puntosPlantilla),
            'marchamos' => $totalMarchamos,
        ];
    }

    private function crearGasolinera(
        int $empresaId,
        int $empresaNumero,
        int $gasolineraNumero,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): int {
        return (int) DB::table('gasolineras')->insertGetId([
            'empresa_id' => $empresaId,
            'nombre' => sprintf('Gasolinera Demo %03d-%02d', $empresaNumero, $gasolineraNumero),
            'direccion' => sprintf('Plantel de combustible %02d, Empresa Demo %03d, El Salvador', $gasolineraNumero, $empresaNumero),
            'encargado' => sprintf('Encargado Gasolinera %03d-%02d', $empresaNumero, $gasolineraNumero),
            'telefono' => sprintf('2510-%04d', ($empresaNumero * 100) + $gasolineraNumero),
            'correo' => sprintf('gasolinera%03d_%02d@demo.ccflota.test', $empresaNumero, $gasolineraNumero),
            'estado' => 'activa',
            'fecha_creacion' => $fechaBase,
            'creado_por' => $usuarioResponsableId,
            'fecha_actualizacion' => null,
            'actualizado_por' => null,
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
        ]);
    }

    private function crearTanque(
        int $empresaId,
        int $gasolineraId,
        int $empresaNumero,
        int $gasolineraNumero,
        int $tanqueNumero,
        int $indiceGlobalTanque,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): int {
        $inventario = $this->datosInventarioTanque($indiceGlobalTanque);

        return (int) DB::table('tanques')->insertGetId([
            'gasolinera_id' => $gasolineraId,
            'nombre' => sprintf('Tanque %d', $tanqueNumero),
            'capacidad_total' => $inventario['capacidad_total'],
            'volumen_actual' => $inventario['volumen_actual'],
            'volumen_minimo_alerta' => $inventario['volumen_minimo_alerta'],
            'estado' => 'activo',
            'inactivado_por_gasolinera' => false,
            'fecha_creacion' => $fechaBase,
            'creado_por' => $usuarioResponsableId,
            'fecha_actualizacion' => null,
            'actualizado_por' => null,
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
        ]);
    }

    private function crearMovimientoCargaInicial(
        int $empresaId,
        int $tanqueId,
        int $indiceGlobalTanque,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): void {
        $inventario = $this->datosInventarioTanque($indiceGlobalTanque);

        DB::table('movimientos_inventario_combustible')->insert([
            'empresa_id' => $empresaId,
            'tanque_id' => $tanqueId,
            'abastecimiento_id' => null,
            'tipo_movimiento' => 'carga_inicial',
            'volumen_anterior' => 0,
            'sentido_movimiento' => 'entrada',
            'volumen_movimiento' => $inventario['volumen_actual'],
            'volumen_resultante' => $inventario['volumen_actual'],
            'fecha_hora_movimiento' => $fechaBase,
            'observaciones' => sprintf(
                'Carga inicial generada por seeder demo. Escenario de inventario: %s.',
                $inventario['escenario']
            ),
            'usuario_registra_id' => $usuarioResponsableId,
            'estado' => 'registrado',
            'fecha_creacion' => $fechaBase,
            'fecha_actualizacion' => null,
            'actualizado_por' => null,
            'fecha_anulacion' => null,
            'anulado_por' => null,
            'motivo_anulacion' => null,
        ]);
    }

    private function datosInventarioTanque(int $indiceGlobalTanque): array
    {
        $capacidadTotal = $this->capacidadesTanques[($indiceGlobalTanque - 1) % count($this->capacidadesTanques)];
        $escenario = $this->escenarioInventario($indiceGlobalTanque);

        $volumenMinimoAlerta = match ($escenario) {
            'bajo_alerta' => round($capacidadTotal * 0.20, 2),
            'cerca_alerta' => round($capacidadTotal * 0.18, 2),
            'medio' => round($capacidadTotal * 0.22, 2),
            'casi_lleno' => round($capacidadTotal * 0.15, 2),
            default => round($capacidadTotal * 0.20, 2),
        };

        $volumenActual = match ($escenario) {
            'saludable' => round($capacidadTotal * 0.72, 2),
            'medio' => round($capacidadTotal * 0.48, 2),
            'cerca_alerta' => round($volumenMinimoAlerta + ($capacidadTotal * 0.04), 2),
            'bajo_alerta' => round($volumenMinimoAlerta * 0.72, 2),
            'casi_lleno' => round($capacidadTotal * 0.93, 2),
            default => round($capacidadTotal * 0.60, 2),
        };

        $volumenActual = min($volumenActual, $capacidadTotal);
        $volumenMinimoAlerta = min($volumenMinimoAlerta, $capacidadTotal - 1);

        return [
            'capacidad_total' => $capacidadTotal,
            'volumen_actual' => $volumenActual,
            'volumen_minimo_alerta' => $volumenMinimoAlerta,
            'escenario' => $escenario,
        ];
    }

    private function escenarioInventario(int $indiceGlobalTanque): string
    {
        return match (($indiceGlobalTanque - 1) % 10) {
            0, 1, 2 => 'saludable',
            3, 4 => 'medio',
            5, 6 => 'cerca_alerta',
            7, 8 => 'bajo_alerta',
            default => 'casi_lleno',
        };
    }

    private function plantillaParaUnidad(int $indiceGlobalUnidad): string
    {
        return $this->plantillas[($indiceGlobalUnidad - 1) % count($this->plantillas)];
    }

    private function cantidadTanquesParaPlantilla(string $plantilla): int
    {
        return match ($plantilla) {
            'plantilla_1_tanque' => 1,
            'plantilla_2_tanques' => 2,
            'plantilla_3_tanques' => 3,
            default => 1,
        };
    }

    private function cantidadTanquesParaGasolinera(int $indiceGlobalGasolinera): int
    {
        return (($indiceGlobalGasolinera - 1) % 3) + 1;
    }

    private function siguienteCodigoMarchamo(): string
    {
        $codigo = str_pad((string) $this->marchamoSecuencia, 7, '0', STR_PAD_LEFT);

        $this->marchamoSecuencia++;

        return $codigo;
    }

    private function generarNit(int $empresaNumero): string
    {
        return sprintf('0614-%06d-%03d-%d', $empresaNumero, 100 + $empresaNumero, $empresaNumero % 10);
    }
}