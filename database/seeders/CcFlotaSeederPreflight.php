<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Schema;
use RuntimeException;

final class CcFlotaSeederPreflight
{
    /**
     * Ejecuta una revisión estructural no destructiva.
     *
     * Debe llamarse antes del ResetCcFlotaDataSeeder para evitar
     * limpiar la base cuando falta una clase, tabla o columna requerida.
     */
    public static function validarEstructura(): array
    {
        $errores = [];

        self::validarClases(
            errores: $errores
        );

        self::validarTablasYColumnas(
            errores: $errores
        );

        self::validarConfiguracion(
            errores: $errores
        );

        if ($errores !== []) {
            throw new RuntimeException(
                "La revisión previa del seeder encontró "
                . count($errores)
                . " problema(s):\n- "
                . implode("\n- ", $errores)
            );
        }

        return [
            'clases_validadas' =>
                count(self::clasesRequeridas()),

            'tablas_validadas' =>
                count(self::estructuraRequerida()),

            'estado' =>
                'correcto',
        ];
    }

    /**
     * Valida que todas las clases utilizadas puedan cargarse
     * mediante PSR-4.
     */
    private static function validarClases(
        array &$errores
    ): void {
        foreach (
            self::clasesRequeridas()
            as $clase
        ) {
            if (! class_exists($clase)) {
                $errores[] =
                    "No puede cargarse la clase [{$clase}]. "
                    . 'Verifica el nombre del archivo, namespace '
                    . 'y composer dump-autoload.';
            }
        }
    }

    /**
     * Valida las tablas y columnas que usan directamente
     * los seeders y materializadores.
     */
    private static function validarTablasYColumnas(
        array &$errores
    ): void {
        foreach (
            self::estructuraRequerida()
            as $tabla => $columnas
        ) {
            if (! Schema::hasTable($tabla)) {
                $errores[] =
                    "No existe la tabla [{$tabla}].";

                continue;
            }

            foreach (
                $columnas
                as $columna
            ) {
                if (
                    ! Schema::hasColumn(
                        $tabla,
                        $columna
                    )
                ) {
                    $errores[] =
                        "Falta la columna [{$tabla}.{$columna}].";
                }
            }
        }
    }

    /**
     * Confirma que la configuración central sea válida.
     */
    private static function validarConfiguracion(
        array &$errores
    ): void {
        try {
            $erroresConfiguracion =
                CcFlotaSeederConfig::validar();

            foreach (
                $erroresConfiguracion
                as $error
            ) {
                $errores[] =
                    'Configuración: ' . $error;
            }
        } catch (\Throwable $error) {
            $errores[] =
                'No fue posible validar CcFlotaSeederConfig: '
                . $error->getMessage();
        }
    }

    /**
     * Clases indispensables para la reconstrucción.
     */
    private static function clasesRequeridas(): array
    {
        return [
            /*
             * Modelos.
             */
            \App\Models\User::class,
            \App\Models\Empresa::class,
            \App\Models\Unidad::class,
            \App\Models\Licencia::class,
            \App\Models\PuntoSeguridadUnidad::class,
            \App\Models\Marchamo::class,
            \App\Models\ReemplazoMarchamoEvento::class,
            \App\Models\ReemplazoMarchamoDetalle::class,
            \App\Models\Motorista::class,
            \App\Models\Gasolinera::class,
            \App\Models\Tanque::class,
            \App\Models\GasolineraExterna::class,
            \App\Models\PuntoRuta::class,
            \App\Models\Ruta::class,
            \App\Models\RecargaCombustible::class,
            \App\Models\MovimientoInventarioCombustible::class,
            \App\Models\Abastecimiento::class,
            \App\Models\AbastecimientoTanque::class,
            \App\Models\AbastecimientoRuta::class,

            /*
             * Infraestructura del seeder.
             */
            ResetCcFlotaDataSeeder::class,
            CcFlotaSeederContext::class,
            CcFlotaSeederConfig::class,
            CcFlotaDeterministicGenerator::class,
            CcFlotaEmpresasSeeder::class,
            CcFlotaUnidadesLicenciasSeeder::class,
            CcFlotaPuntosMarchamosSeeder::class,
            CcFlotaMotoristasSeeder::class,
            CcFlotaGasolinerasTanquesSeeder::class,
            CcFlotaGasolinerasExternasSeeder::class,
            CcFlotaPuntosRutasSeeder::class,
            CcFlotaOperationalPlan::class,
            CcFlotaRecargaMaterializer::class,
            CcFlotaAbastecimientoMaterializer::class,
            CcFlotaOperacionHistoricaSeeder::class,

            /*
             * Catálogo de puntos.
             */
            \App\Support\PlantillasPuntosSeguridad::class,
        ];
    }

    /**
     * Estructura mínima utilizada por los archivos generados.
     */
    private static function estructuraRequerida(): array
    {
        return [
            'users' => [
                'id',
                'empresa_id',
                'rol_id',
                'tipo_usuario',
                'estado',
                'email',
            ],

            'roles' => [
                'id',
                'codigo',
            ],

            'empresas' => [
                'id',
                'nombre_legal',
                'nombre_comercial',
                'nit',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            'unidades' => [
                'id',
                'empresa_id',
                'placa',
                'marca',
                'total_tanques',
                'cantidad_tanques_con_licencia',
                'capacidad_total',
                'capacidad_cubierta',
                'modelo_medicion',
                'estado',
                'creado_por',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            'licencias' => [
                'id',
                'empresa_id',
                'unidad_id',
                'periodo_vigencia_meses',
                'fecha_activacion',
                'fecha_vencimiento',
                'estado',
                'plantilla_puntos_seguridad',
                'creado_por',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            'puntos_seguridad_unidad' => [
                'id',
                'unidad_id',
                'orden',
                'codigo_punto',
                'grupo',
                'subgrupo',
                'nombre_punto',
                'descripcion',
                'posicion_tanque',
                'tipo_punto',
                'requiere_marchamo',
                'plantilla_origen',
                'criterio_origen',
                'estado_asignacion',
                'marchamo_actual_id',
                'estado',
                'creado_por',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            'marchamos' => [
                'id',
                'empresa_id',
                'unidad_id',
                'punto_seguridad_id',
                'codigo_marchamo',
                'fecha_activacion',
                'estado',
                'activo_actual',
                'fecha_desactivacion',
                'motivo_desactivacion',
                'origen_creacion',
                'creado_por',
                'actualizado_por',
            ],

            'reemplazo_marchamos_eventos' => [
                'id',
                'empresa_id',
                'unidad_id',
                'abastecimiento_id',
                'motivo_reemplazo',
                'cantidad_reemplazos',
                'origen_evento',
                'estado',
                'fecha_registro',
                'registrado_por',
                'fecha_anulacion',
                'anulado_por',
                'motivo_anulacion',
                'created_at',
                'updated_at',
            ],

            'reemplazo_marchamos_detalle' => [
                'id',
                'reemplazo_evento_id',
                'punto_seguridad_id',
                'marchamo_anterior_id',
                'marchamo_nuevo_id',
                'fecha_registro',
                'created_at',
                'updated_at',
            ],

            'motoristas' => [
                'id',
                'empresa_id',
                'nombres',
                'apellidos',
                'licencia',
                'telefono',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            'gasolineras' => [
                'id',
                'empresa_id',
                'nombre',
                'direccion',
                'encargado',
                'telefono',
                'correo',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            'tanques' => [
                'id',
                'gasolinera_id',
                'nombre',
                'capacidad_total',
                'volumen_actual',
                'volumen_minimo_alerta',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            'gasolineras_externas' => [
                'id',
                'empresa_id',
                'direccion',
                'compania',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            'puntos_ruta' => [
                'id',
                'empresa_id',
                'nombre',
                'direccion',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            'rutas' => [
                'id',
                'empresa_id',
                'punto_origen_id',
                'punto_destino_id',
                'ruta',
                'kilometros_estimados',
                'galones_estimados',
                'estado',
                'fecha_creacion',
                'creado_por',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ],

            'recargas_combustible' => [
                'id',
                'empresa_id',
                'gasolinera_id',
                'precio_galon',
                'total_galones',
                'total_compra',
                'fecha_hora_recarga',
                'observaciones',
                'usuario_registra_id',
                'estado',
                'fecha_creacion',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_anulacion',
                'anulado_por',
                'motivo_anulacion',
            ],

            'movimientos_inventario_combustible' => [
                'id',
                'empresa_id',
                'tanque_id',
                'abastecimiento_id',
                'recarga_combustible_id',
                'tipo_movimiento',
                'volumen_anterior',
                'sentido_movimiento',
                'volumen_movimiento',
                'volumen_resultante',
                'subtotal_compra',
                'fecha_hora_movimiento',
                'observaciones',
                'usuario_registra_id',
                'estado',
                'fecha_creacion',
                'fecha_actualizacion',
                'actualizado_por',
                'fecha_anulacion',
                'anulado_por',
                'motivo_anulacion',
            ],

            'abastecimientos' => [
                'id',
                'empresa_id',
                'unidad_id',
                'motorista_id',
                'abastecimiento_anterior_id',
                'registrado_por',
                'empresa_nombre_snapshot',
                'unidad_placa_snapshot',
                'unidad_marca_snapshot',
                'unidad_modelo_snapshot',
                'motorista_nombre_snapshot',
                'motorista_licencia_snapshot',
                'fecha_hora_abastecimiento',
                'estado',
                'modelo_medicion',
                'lectura_actual',
                'lectura_anterior',
                'diferencia_lectura',
                'kilometraje_actual',
                'kilometraje_anterior',
                'diferencia_kilometraje',
                'horometro_actual',
                'horometro_anterior',
                'diferencia_horometro',
                'volumen_inicial',
                'volumen_cargado',
                'volumen_final',
                'capacidad_cubierta_snapshot',
                'volumen_final_anterior',
                'combustible_consumido_ciclo',
                'combustible_adicional_no_explicado',
                'tipo_origen',
                'gasolinera_interna_id',
                'gasolinera_externa_id',
                'origen_nombre_snapshot',
                'precio_galon',
                'total_pagado',
                'moneda',
                'total_rutas',
                'kilometros_teoricos',
                'galones_teoricos',
                'galones_por_kilometro',
                'kilometros_por_galon',
                'galones_por_hora',
                'diferencia_kilometros_teoricos',
                'diferencia_galones_teoricos',
                'total_tapones_abiertos',
                'total_marchamos_reemplazados',
                'fecha_anulacion',
                'anulado_por',
                'motivo_anulacion',
                'created_at',
                'updated_at',
            ],

            'abastecimiento_tanques' => [
                'id',
                'abastecimiento_id',
                'tanque_id',
                'orden',
                'tanque_nombre_snapshot',
                'capacidad_total_snapshot',
                'volumen_minimo_alerta_snapshot',
                'inventario_anterior',
                'galones_retirados',
                'inventario_resultante',
                'quedo_bajo_minimo',
                'created_at',
                'updated_at',
            ],

            'abastecimiento_rutas' => [
                'id',
                'abastecimiento_id',
                'ruta_id',
                'orden',
                'tipo_recorrido',
                'factor_recorrido',
                'ruta_nombre_snapshot',
                'punto_origen_id',
                'punto_destino_id',
                'punto_origen_nombre_snapshot',
                'punto_destino_nombre_snapshot',
                'kilometros_base_snapshot',
                'galones_base_snapshot',
                'kilometros_aplicados',
                'galones_aplicados',
                'created_at',
                'updated_at',
            ],
        ];
    }
}