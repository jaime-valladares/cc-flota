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

    private int $gasolinerasExternasPorEmpresa = 10;

    private int $puntosRutaPorEmpresa = 12;

    private int $rutasPorEmpresa = 20;

    private int $motoristasPorEmpresa = 15;

    private int $recargasHistoricasPorGasolinera = 2;

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

    private array $companiasGasolinerasExternas = [
        'UNO',
        'Puma Energy',
        'Texaco',
        'DLC',
        'Alba Petróleos',
        'Shell',
        'Bandera Blanca',
        'Servicentro Local',
        'Estación Ruta Norte',
        'Estación Ruta Sur',
    ];

    private array $nombresMotoristas = [
        'Carlos',
        'Miguel',
        'José',
        'Luis',
        'Roberto',
        'Francisco',
        'Mario',
        'Jorge',
        'Óscar',
        'Ricardo',
        'Manuel',
        'Eduardo',
        'Nelson',
        'Héctor',
        'Rafael',
    ];

    private array $apellidosMotoristas = [
        'Martínez',
        'Hernández',
        'García',
        'López',
        'Rodríguez',
        'Pérez',
        'Ramírez',
        'Flores',
        'Vásquez',
        'Morales',
        'Castro',
        'Reyes',
        'Cruz',
        'Gómez',
        'Rivas',
    ];

    private array $puntosRutaBase = [
        ['nombre' => 'Centro de distribución', 'direccion' => 'Boulevard del Ejército, zona industrial Soyapango'],
        ['nombre' => 'Bodega principal', 'direccion' => 'Carretera Panamericana, tramo San Martín'],
        ['nombre' => 'Cliente zona norte', 'direccion' => 'Carretera Troncal del Norte, km 12'],
        ['nombre' => 'Cliente zona sur', 'direccion' => 'Autopista a Comalapa, km 18'],
        ['nombre' => 'Puerto de carga', 'direccion' => 'Terminal logística Acajutla'],
        ['nombre' => 'Centro urbano', 'direccion' => 'Alameda Juan Pablo II, San Salvador'],
        ['nombre' => 'Ruta oriente', 'direccion' => 'Carretera Panamericana hacia San Miguel'],
        ['nombre' => 'Ruta occidente', 'direccion' => 'Carretera hacia Santa Ana'],
        ['nombre' => 'Planta cliente A', 'direccion' => 'Zona industrial La Laguna, Antiguo Cuscatlán'],
        ['nombre' => 'Planta cliente B', 'direccion' => 'Parque industrial El Progreso'],
        ['nombre' => 'Punto de control intermedio', 'direccion' => 'Desvío principal hacia Apopa'],
        ['nombre' => 'Retorno operativo', 'direccion' => 'Anillo periférico, punto de retorno autorizado'],
    ];

    public function run(): void
    {
        $usuarioResponsableId = $this->resolverUsuarioResponsableId();

        if (! $usuarioResponsableId) {
            $this->command?->error('No se encontró ningún usuario para usar como responsable de creación.');

            return;
        }

        $this->marchamoSecuencia = 0;

        $this->command?->warn('Limpiando datos operativos actuales sin tocar users, roles, permisos ni rol_permisos...');

        $this->limpiarDatosOperativos();

        $this->command?->info('Generando información demo integral de CC-Flota...');

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
            'movimientos_carga_inicial' => 0,
            'recargas_combustible' => 0,
            'movimientos_recarga' => 0,
            'gasolineras_externas' => 0,
            'puntos_ruta' => 0,
            'rutas' => 0,
            'motoristas' => 0,
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
            $indiceGlobalMotorista = 0;

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
                    $tanquesGasolinera = [];

                    for ($tanqueNumero = 1; $tanqueNumero <= $cantidadTanquesGasolinera; $tanqueNumero++) {
                        $indiceGlobalTanque++;

                        $inventario = $this->datosInventarioTanque($indiceGlobalTanque);

                        $tanqueId = $this->crearTanque(
                            gasolineraId: $gasolineraId,
                            tanqueNumero: $tanqueNumero,
                            inventario: $inventario,
                            usuarioResponsableId: $usuarioResponsableId,
                            fechaBase: $fechaBase
                        );

                        $totales['tanques']++;

                        $this->crearMovimientoCargaInicial(
                            empresaId: $empresaId,
                            tanqueId: $tanqueId,
                            inventario: $inventario,
                            usuarioResponsableId: $usuarioResponsableId,
                            fechaBase: $fechaBase->copy()->subDays(18)
                        );

                        $totales['movimientos_carga_inicial']++;

                        $tanquesGasolinera[] = [
                            'id' => $tanqueId,
                            'inventario' => $inventario,
                        ];
                    }

                    $resultadoRecargas = $this->crearRecargasHistoricasGasolinera(
                        empresaId: $empresaId,
                        gasolineraId: $gasolineraId,
                        tanquesGasolinera: $tanquesGasolinera,
                        indiceGlobalGasolinera: $indiceGlobalGasolinera,
                        usuarioResponsableId: $usuarioResponsableId,
                        fechaBase: $fechaBase
                    );

                    $totales['recargas_combustible'] += $resultadoRecargas['recargas'];
                    $totales['movimientos_recarga'] += $resultadoRecargas['movimientos'];
                }

                for ($gasolineraExternaNumero = 1; $gasolineraExternaNumero <= $this->gasolinerasExternasPorEmpresa; $gasolineraExternaNumero++) {
                    $this->crearGasolineraExterna(
                        empresaId: $empresaId,
                        empresaNumero: $empresaNumero,
                        gasolineraExternaNumero: $gasolineraExternaNumero,
                        usuarioResponsableId: $usuarioResponsableId,
                        fechaBase: $fechaBase
                    );

                    $totales['gasolineras_externas']++;
                }

                $puntosRutaEmpresa = [];

                for ($puntoRutaNumero = 1; $puntoRutaNumero <= $this->puntosRutaPorEmpresa; $puntoRutaNumero++) {
                    $puntoRutaId = $this->crearPuntoRuta(
                        empresaId: $empresaId,
                        empresaNumero: $empresaNumero,
                        puntoRutaNumero: $puntoRutaNumero,
                        usuarioResponsableId: $usuarioResponsableId,
                        fechaBase: $fechaBase
                    );

                    if ($puntoRutaId) {
                        $puntosRutaEmpresa[] = $puntoRutaId;
                        $totales['puntos_ruta']++;
                    }
                }

                $totales['rutas'] += $this->crearRutasEmpresa(
                    empresaId: $empresaId,
                    puntosRutaEmpresa: $puntosRutaEmpresa,
                    usuarioResponsableId: $usuarioResponsableId,
                    fechaBase: $fechaBase
                );

                for ($motoristaNumero = 1; $motoristaNumero <= $this->motoristasPorEmpresa; $motoristaNumero++) {
                    $indiceGlobalMotorista++;

                    $this->crearMotorista(
                        empresaId: $empresaId,
                        empresaNumero: $empresaNumero,
                        motoristaNumero: $motoristaNumero,
                        indiceGlobalMotorista: $indiceGlobalMotorista,
                        usuarioResponsableId: $usuarioResponsableId,
                        fechaBase: $fechaBase
                    );

                    $totales['motoristas']++;
                }
            }
        });

        $this->command?->info('Seeder demo integral completado correctamente.');
        $this->command?->line("Empresas creadas: {$totales['empresas']}");
        $this->command?->line("Unidades creadas: {$totales['unidades']}");
        $this->command?->line("Licencias creadas: {$totales['licencias']}");
        $this->command?->line("Puntos de seguridad creados: {$totales['puntos']}");
        $this->command?->line("Marchamos creados: {$totales['marchamos']}");
        $this->command?->line("Gasolineras internas creadas: {$totales['gasolineras']}");
        $this->command?->line("Tanques creados: {$totales['tanques']}");
        $this->command?->line("Movimientos de carga inicial creados: {$totales['movimientos_carga_inicial']}");
        $this->command?->line("Recargas históricas creadas: {$totales['recargas_combustible']}");
        $this->command?->line("Movimientos de recarga creados: {$totales['movimientos_recarga']}");
        $this->command?->line("Gasolineras externas creadas: {$totales['gasolineras_externas']}");
        $this->command?->line("Puntos de ruta creados: {$totales['puntos_ruta']}");
        $this->command?->line("Rutas creadas: {$totales['rutas']}");
        $this->command?->line("Motoristas creados: {$totales['motoristas']}");
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
                'recargas_combustible',
                'abastecimientos',
                'tanques',
                'gasolineras',
                'gasolineras_externas',
                'rutas',
                'puntos_ruta',
                'motoristas',
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
        int $gasolineraId,
        int $tanqueNumero,
        array $inventario,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): int {
        return (int) DB::table('tanques')->insertGetId([
            'gasolinera_id' => $gasolineraId,
            'nombre' => sprintf('Tanque %d', $tanqueNumero),
            'capacidad_total' => $inventario['capacidad_total'],
            'volumen_actual' => $inventario['volumen_inicial'],
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
        array $inventario,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): void {
        DB::table('movimientos_inventario_combustible')->insert([
            'empresa_id' => $empresaId,
            'tanque_id' => $tanqueId,
            'abastecimiento_id' => null,
            'recarga_combustible_id' => null,
            'tipo_movimiento' => 'carga_inicial',
            'volumen_anterior' => 0,
            'sentido_movimiento' => 'entrada',
            'volumen_movimiento' => $inventario['volumen_inicial'],
            'volumen_resultante' => $inventario['volumen_inicial'],
            'subtotal_compra' => null,
            'fecha_hora_movimiento' => $fechaBase,
            'observaciones' => sprintf(
                'Carga inicial generada por seeder demo. Escenario objetivo de inventario: %s.',
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

    private function crearRecargasHistoricasGasolinera(
        int $empresaId,
        int $gasolineraId,
        array $tanquesGasolinera,
        int $indiceGlobalGasolinera,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): array {
        if (! Schema::hasTable('recargas_combustible')) {
            return [
                'recargas' => 0,
                'movimientos' => 0,
            ];
        }

        $recargasCreadas = 0;
        $movimientosCreados = 0;

        for ($recargaNumero = 1; $recargaNumero <= $this->recargasHistoricasPorGasolinera; $recargaNumero++) {
            $fechaRecarga = $fechaBase->copy()
                ->subDays(14 - ($recargaNumero * 5))
                ->setTime(8 + (($indiceGlobalGasolinera + $recargaNumero) % 6), 15 + (($indiceGlobalGasolinera * 3) % 40), 0);

            $precioGalon = $this->precioGalonParaRecarga($indiceGlobalGasolinera, $recargaNumero);
            $movimientosPendientes = [];

            foreach ($tanquesGasolinera as $tanqueMeta) {
                $tanque = DB::table('tanques')
                    ->where('id', $tanqueMeta['id'])
                    ->first();

                if (! $tanque) {
                    continue;
                }

                $volumenActual = round((float) $tanque->volumen_actual, 2);
                $volumenObjetivo = round((float) $tanqueMeta['inventario']['volumen_objetivo'], 2);
                $diferenciaPendiente = round($volumenObjetivo - $volumenActual, 2);

                if ($diferenciaPendiente <= 0) {
                    continue;
                }

                $volumenMovimiento = $recargaNumero < $this->recargasHistoricasPorGasolinera
                    ? round($diferenciaPendiente * 0.55, 2)
                    : $diferenciaPendiente;

                if ($volumenMovimiento <= 0) {
                    continue;
                }

                $volumenResultante = round($volumenActual + $volumenMovimiento, 2);

                if ($volumenResultante > (float) $tanque->capacidad_total) {
                    $volumenMovimiento = round((float) $tanque->capacidad_total - $volumenActual, 2);
                    $volumenResultante = round($volumenActual + $volumenMovimiento, 2);
                }

                if ($volumenMovimiento <= 0) {
                    continue;
                }

                $movimientosPendientes[] = [
                    'tanque_id' => (int) $tanque->id,
                    'volumen_anterior' => $volumenActual,
                    'volumen_movimiento' => $volumenMovimiento,
                    'volumen_resultante' => $volumenResultante,
                    'subtotal_compra' => round($volumenMovimiento * $precioGalon, 2),
                ];
            }

            if (empty($movimientosPendientes)) {
                continue;
            }

            $totalGalones = round(array_sum(array_column($movimientosPendientes, 'volumen_movimiento')), 2);
            $totalCompra = round($totalGalones * $precioGalon, 2);

            $recargaId = (int) DB::table('recargas_combustible')->insertGetId([
                'empresa_id' => $empresaId,
                'gasolinera_id' => $gasolineraId,
                'precio_galon' => $precioGalon,
                'total_galones' => $totalGalones,
                'total_compra' => $totalCompra,
                'fecha_hora_recarga' => $fechaRecarga,
                'observaciones' => sprintf('Recarga histórica demo %d generada por seeder.', $recargaNumero),
                'usuario_registra_id' => $usuarioResponsableId,
                'estado' => 'registrado',
                'fecha_creacion' => $fechaRecarga,
                'fecha_actualizacion' => null,
                'actualizado_por' => null,
                'fecha_anulacion' => null,
                'anulado_por' => null,
                'motivo_anulacion' => null,
            ]);

            $recargasCreadas++;

            foreach ($movimientosPendientes as $movimiento) {
                DB::table('tanques')
                    ->where('id', $movimiento['tanque_id'])
                    ->update([
                        'volumen_actual' => $movimiento['volumen_resultante'],
                        'fecha_actualizacion' => $fechaRecarga,
                        'actualizado_por' => $usuarioResponsableId,
                    ]);

                DB::table('movimientos_inventario_combustible')->insert([
                    'empresa_id' => $empresaId,
                    'tanque_id' => $movimiento['tanque_id'],
                    'abastecimiento_id' => null,
                    'recarga_combustible_id' => $recargaId,
                    'tipo_movimiento' => 'entrada_recarga',
                    'volumen_anterior' => $movimiento['volumen_anterior'],
                    'sentido_movimiento' => 'entrada',
                    'volumen_movimiento' => $movimiento['volumen_movimiento'],
                    'volumen_resultante' => $movimiento['volumen_resultante'],
                    'subtotal_compra' => $movimiento['subtotal_compra'],
                    'fecha_hora_movimiento' => $fechaRecarga,
                    'observaciones' => sprintf('Movimiento de recarga histórica demo asociado a recarga %d.', $recargaId),
                    'usuario_registra_id' => $usuarioResponsableId,
                    'estado' => 'registrado',
                    'fecha_creacion' => $fechaRecarga,
                    'fecha_actualizacion' => null,
                    'actualizado_por' => null,
                    'fecha_anulacion' => null,
                    'anulado_por' => null,
                    'motivo_anulacion' => null,
                ]);

                $movimientosCreados++;
            }
        }

        return [
            'recargas' => $recargasCreadas,
            'movimientos' => $movimientosCreados,
        ];
    }

    private function crearGasolineraExterna(
        int $empresaId,
        int $empresaNumero,
        int $gasolineraExternaNumero,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): void {
        if (! Schema::hasTable('gasolineras_externas')) {
            return;
        }

        $compania = $this->companiasGasolinerasExternas[($gasolineraExternaNumero - 1) % count($this->companiasGasolinerasExternas)];

        DB::table('gasolineras_externas')->insert([
            'empresa_id' => $empresaId,
            'compania' => $compania,
            'direccion' => sprintf('Estación externa %02d, corredor operativo Empresa Demo %03d', $gasolineraExternaNumero, $empresaNumero),
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

    private function crearPuntoRuta(
        int $empresaId,
        int $empresaNumero,
        int $puntoRutaNumero,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): ?int {
        if (! Schema::hasTable('puntos_ruta')) {
            return null;
        }

        $puntoBase = $this->puntosRutaBase[($puntoRutaNumero - 1) % count($this->puntosRutaBase)];

        return (int) DB::table('puntos_ruta')->insertGetId([
            'empresa_id' => $empresaId,
            'nombre' => sprintf('%s %03d-%02d', $puntoBase['nombre'], $empresaNumero, $puntoRutaNumero),
            'direccion' => sprintf('%s, referencia Empresa Demo %03d', $puntoBase['direccion'], $empresaNumero),
            'estado' => 'activo',
            'fecha_creacion' => $fechaBase,
            'creado_por' => $usuarioResponsableId,
            'fecha_actualizacion' => null,
            'actualizado_por' => null,
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
        ]);
    }

    private function crearRutasEmpresa(
        int $empresaId,
        array $puntosRutaEmpresa,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): int {
        if (! Schema::hasTable('rutas') || count($puntosRutaEmpresa) < 2) {
            return 0;
        }

        $puntosRuta = DB::table('puntos_ruta')
            ->whereIn('id', $puntosRutaEmpresa)
            ->where('empresa_id', $empresaId)
            ->where('estado', 'activo')
            ->orderBy('id')
            ->get(['id', 'nombre'])
            ->values();

        if ($puntosRuta->count() < 2) {
            return 0;
        }

        $rutasCreadas = 0;
        $paresUsados = [];
        $cantidadPuntos = $puntosRuta->count();

        for ($rutaNumero = 1; $rutaNumero <= $this->rutasPorEmpresa; $rutaNumero++) {
            $origenIndice = ($rutaNumero - 1) % $cantidadPuntos;
            $saltos = 1 + (($rutaNumero - 1) % ($cantidadPuntos - 1));
            $destinoIndice = ($origenIndice + $saltos) % $cantidadPuntos;

            $origen = $puntosRuta[$origenIndice];
            $destino = $puntosRuta[$destinoIndice];
            $llavePar = $origen->id . '-' . $destino->id;

            if (isset($paresUsados[$llavePar])) {
                continue;
            }

            $paresUsados[$llavePar] = true;

            $kilometrosEstimados = $this->kilometrosEstimadosRuta($rutaNumero, $origenIndice, $destinoIndice);
            $rendimientoEstimado = $this->rendimientoEstimadoRuta($rutaNumero);
            $galonesEstimados = round($kilometrosEstimados / $rendimientoEstimado, 2);

            DB::table('rutas')->insert([
                'empresa_id' => $empresaId,
                'punto_origen_id' => $origen->id,
                'punto_destino_id' => $destino->id,
                'ruta' => trim($origen->nombre) . ' - ' . trim($destino->nombre),
                'kilometros_estimados' => $kilometrosEstimados,
                'galones_estimados' => max($galonesEstimados, 0.01),
                'estado' => 'activo',
                'fecha_creacion' => $fechaBase,
                'creado_por' => $usuarioResponsableId,
                'fecha_actualizacion' => null,
                'actualizado_por' => null,
                'fecha_inactivacion' => null,
                'inactivado_por' => null,
                'motivo_inactivacion' => null,
            ]);

            $rutasCreadas++;
        }

        return $rutasCreadas;
    }

    private function crearMotorista(
        int $empresaId,
        int $empresaNumero,
        int $motoristaNumero,
        int $indiceGlobalMotorista,
        int $usuarioResponsableId,
        Carbon $fechaBase
    ): void {
        if (! Schema::hasTable('motoristas')) {
            return;
        }

        $nombre = $this->nombresMotoristas[($motoristaNumero - 1) % count($this->nombresMotoristas)];
        $apellido = $this->apellidosMotoristas[($indiceGlobalMotorista - 1) % count($this->apellidosMotoristas)];

        DB::table('motoristas')->insert([
            'empresa_id' => $empresaId,
            'nombres' => $nombre,
            'apellidos' => sprintf('%s Demo %03d', $apellido, $empresaNumero),
            'licencia' => $this->generarLicenciaMotorista($empresaNumero, $motoristaNumero),
            'telefono' => sprintf('7%03d-%04d', ($empresaNumero * 10 + $motoristaNumero) % 1000, (3000 + $indiceGlobalMotorista) % 10000),
            'estado' => 'activo',
            'fecha_creacion' => $fechaBase,
            'creado_por' => $usuarioResponsableId,
            'fecha_actualizacion' => null,
            'actualizado_por' => null,
            'fecha_inactivacion' => null,
            'inactivado_por' => null,
            'motivo_inactivacion' => null,
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

        $volumenObjetivo = match ($escenario) {
            'saludable' => round($capacidadTotal * 0.72, 2),
            'medio' => round($capacidadTotal * 0.48, 2),
            'cerca_alerta' => round($volumenMinimoAlerta + ($capacidadTotal * 0.04), 2),
            'bajo_alerta' => round($volumenMinimoAlerta * 0.72, 2),
            'casi_lleno' => round($capacidadTotal * 0.93, 2),
            default => round($capacidadTotal * 0.60, 2),
        };

        $volumenInicial = match ($escenario) {
            'saludable' => round($capacidadTotal * 0.45, 2),
            'medio' => round($capacidadTotal * 0.30, 2),
            'cerca_alerta' => round($volumenMinimoAlerta + ($capacidadTotal * 0.01), 2),
            'bajo_alerta' => $volumenObjetivo,
            'casi_lleno' => round($capacidadTotal * 0.70, 2),
            default => round($capacidadTotal * 0.40, 2),
        };

        $volumenMinimoAlerta = min($volumenMinimoAlerta, $capacidadTotal - 1);
        $volumenObjetivo = min($volumenObjetivo, $capacidadTotal);
        $volumenInicial = min($volumenInicial, $volumenObjetivo);

        return [
            'capacidad_total' => $capacidadTotal,
            'volumen_inicial' => $volumenInicial,
            'volumen_objetivo' => $volumenObjetivo,
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

    private function precioGalonParaRecarga(int $indiceGlobalGasolinera, int $recargaNumero): float
    {
        return round(3.65 + ((($indiceGlobalGasolinera + $recargaNumero) % 9) * 0.08), 4);
    }

    private function kilometrosEstimadosRuta(int $rutaNumero, int $origenIndice, int $destinoIndice): float
    {
        $distanciaBase = 18 + (($rutaNumero * 7) % 95);
        $ajustePorPuntos = abs($destinoIndice - $origenIndice) * 6.5;

        return round($distanciaBase + $ajustePorPuntos, 2);
    }

    private function rendimientoEstimadoRuta(int $rutaNumero): float
    {
        return match (($rutaNumero - 1) % 5) {
            0 => 5.20,
            1 => 5.80,
            2 => 6.40,
            3 => 7.10,
            default => 4.90,
        };
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

    private function generarLicenciaMotorista(int $empresaNumero, int $motoristaNumero): string
    {
        return sprintf('%03d%02d%09d', $empresaNumero, $motoristaNumero, ($empresaNumero * 100) + $motoristaNumero);
    }
}