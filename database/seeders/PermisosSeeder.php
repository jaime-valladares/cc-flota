<?php

namespace Database\Seeders;

use App\Models\Permiso;
use Illuminate\Database\Seeder;

class PermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permisos = [
            // -------------------------------------------------------------
            // Empresas
            // -------------------------------------------------------------
            [
                'codigo' => 'empresas.consultar',
                'modulo' => 'empresas',
                'accion' => 'consultar',
                'nombre' => 'Consultar empresas',
                'descripcion' => 'Permite acceder a la consulta informativa de empresas en sus vistas interna y externa.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'empresas.administrar',
                'modulo' => 'empresas',
                'accion' => 'administrar',
                'nombre' => 'Administrar empresas',
                'descripcion' => 'Permite acceder al listado administrativo y a la ficha administrativa de empresas.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'empresas.crear',
                'modulo' => 'empresas',
                'accion' => 'crear',
                'nombre' => 'Crear empresas',
                'descripcion' => 'Permite registrar nuevas empresas cliente en CC-Flota.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'empresas.editar',
                'modulo' => 'empresas',
                'accion' => 'editar',
                'nombre' => 'Editar empresas',
                'descripcion' => 'Permite modificar la información administrativa de empresas activas.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'empresas.inactivar',
                'modulo' => 'empresas',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar empresas',
                'descripcion' => 'Permite inactivar empresas cliente sin eliminar físicamente sus registros.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'empresas.reactivar',
                'modulo' => 'empresas',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar empresas',
                'descripcion' => 'Permite reactivar empresas cliente previamente inactivadas.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Usuarios
            // -------------------------------------------------------------
            [
                'codigo' => 'usuarios.ver',
                'modulo' => 'usuarios',
                'accion' => 'ver',
                'nombre' => 'Ver usuarios',
                'descripcion' => 'Permite visualizar usuarios según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'usuarios.crear',
                'modulo' => 'usuarios',
                'accion' => 'crear',
                'nombre' => 'Crear usuarios',
                'descripcion' => 'Permite crear usuarios según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'usuarios.actualizar',
                'modulo' => 'usuarios',
                'accion' => 'actualizar',
                'nombre' => 'Actualizar usuarios',
                'descripcion' => 'Permite actualizar información de usuarios según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'usuarios.cambiar_rol',
                'modulo' => 'usuarios',
                'accion' => 'cambiar_rol',
                'nombre' => 'Cambiar rol de usuario',
                'descripcion' => 'Permite cambiar el rol asignado a usuarios según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'usuarios.inactivar',
                'modulo' => 'usuarios',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar usuarios',
                'descripcion' => 'Permite inactivar usuarios sin eliminarlos físicamente.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'usuarios.reactivar',
                'modulo' => 'usuarios',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar usuarios',
                'descripcion' => 'Permite reactivar usuarios previamente inactivos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Unidades
            // -------------------------------------------------------------
            [
                'codigo' => 'unidades.ver',
                'modulo' => 'unidades',
                'accion' => 'ver',
                'nombre' => 'Ver unidades',
                'descripcion' => 'Permite visualizar unidades según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'unidades.crear',
                'modulo' => 'unidades',
                'accion' => 'crear',
                'nombre' => 'Crear unidades',
                'descripcion' => 'Permite registrar unidades para empresas cliente.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'unidades.actualizar',
                'modulo' => 'unidades',
                'accion' => 'actualizar',
                'nombre' => 'Actualizar unidades',
                'descripcion' => 'Permite actualizar información de unidades registradas.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'unidades.inactivar',
                'modulo' => 'unidades',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar unidades',
                'descripcion' => 'Permite inactivar unidades sin eliminarlas físicamente.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'unidades.reactivar',
                'modulo' => 'unidades',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar unidades',
                'descripcion' => 'Permite reactivar unidades previamente inactivas.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Licencias
            // -------------------------------------------------------------
            [
                'codigo' => 'licencias.ver',
                'modulo' => 'licencias',
                'accion' => 'ver',
                'nombre' => 'Ver licencias',
                'descripcion' => 'Permite visualizar licencias según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'licencias.provisionar',
                'modulo' => 'licencias',
                'accion' => 'provisionar',
                'nombre' => 'Provisionar licencias',
                'descripcion' => 'Permite provisionar licencias para empresas cliente.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'licencias.asignar',
                'modulo' => 'licencias',
                'accion' => 'asignar',
                'nombre' => 'Asignar licencias',
                'descripcion' => 'Permite asignar licencias a unidades.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'licencias.suspender',
                'modulo' => 'licencias',
                'accion' => 'suspender',
                'nombre' => 'Suspender licencias',
                'descripcion' => 'Permite suspender licencias por decisión administrativa o comercial.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'licencias.inactivar',
                'modulo' => 'licencias',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar licencias',
                'descripcion' => 'Permite inactivar licencias preservando historial.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Puntos de seguridad
            // -------------------------------------------------------------
            [
                'codigo' => 'puntos_seguridad.ver',
                'modulo' => 'puntos_seguridad',
                'accion' => 'ver',
                'nombre' => 'Ver puntos de seguridad',
                'descripcion' => 'Permite visualizar puntos físicos de seguridad configurados en unidades.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'puntos_seguridad.crear',
                'modulo' => 'puntos_seguridad',
                'accion' => 'crear',
                'nombre' => 'Crear puntos de seguridad',
                'descripcion' => 'Permite configurar puntos físicos de seguridad en unidades.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'puntos_seguridad.actualizar',
                'modulo' => 'puntos_seguridad',
                'accion' => 'actualizar',
                'nombre' => 'Actualizar puntos de seguridad',
                'descripcion' => 'Permite actualizar la configuración de puntos físicos de seguridad.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'puntos_seguridad.inactivar',
                'modulo' => 'puntos_seguridad',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar puntos de seguridad',
                'descripcion' => 'Permite inactivar puntos de seguridad preservando historial.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'puntos_seguridad.reactivar',
                'modulo' => 'puntos_seguridad',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar puntos de seguridad',
                'descripcion' => 'Permite reactivar puntos de seguridad previamente inactivos.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Marchamos
            // -------------------------------------------------------------
            [
                'codigo' => 'marchamos.ver',
                'modulo' => 'marchamos',
                'accion' => 'ver',
                'nombre' => 'Ver marchamos',
                'descripcion' => 'Permite visualizar marchamos según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'marchamos.asignar_inicial',
                'modulo' => 'marchamos',
                'accion' => 'asignar_inicial',
                'nombre' => 'Asignar marchamos iniciales',
                'descripcion' => 'Permite registrar la asignación inicial de marchamos por parte de Diesel Cop.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'marchamos.reemplazar_abastecimiento',
                'modulo' => 'marchamos',
                'accion' => 'reemplazar_abastecimiento',
                'nombre' => 'Reemplazar marchamos por abastecimiento',
                'descripcion' => 'Permite registrar reemplazos de marchamos durante abastecimientos.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'marchamos.reemplazar_dano_desgaste',
                'modulo' => 'marchamos',
                'accion' => 'reemplazar_dano_desgaste',
                'nombre' => 'Reemplazar marchamos por daño o desgaste',
                'descripcion' => 'Permite registrar reemplazos de marchamos por daño, desgaste u otro motivo operativo.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'marchamos.ver_historial',
                'modulo' => 'marchamos',
                'accion' => 'ver_historial',
                'nombre' => 'Ver historial de marchamos',
                'descripcion' => 'Permite consultar el historial de marchamos instalados y reemplazados.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Gasolineras
            // -------------------------------------------------------------
            [
                'codigo' => 'gasolineras.ver',
                'modulo' => 'gasolineras',
                'accion' => 'ver',
                'nombre' => 'Ver gasolineras',
                'descripcion' => 'Permite visualizar gasolineras internas según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras.crear',
                'modulo' => 'gasolineras',
                'accion' => 'crear',
                'nombre' => 'Crear gasolineras',
                'descripcion' => 'Permite registrar gasolineras internas de una empresa cliente.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras.actualizar',
                'modulo' => 'gasolineras',
                'accion' => 'actualizar',
                'nombre' => 'Actualizar gasolineras',
                'descripcion' => 'Permite actualizar datos de gasolineras internas.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras.inactivar',
                'modulo' => 'gasolineras',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar gasolineras',
                'descripcion' => 'Permite inactivar gasolineras internas sin eliminarlas físicamente.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras.reactivar',
                'modulo' => 'gasolineras',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar gasolineras',
                'descripcion' => 'Permite reactivar gasolineras internas previamente inactivas.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Tanques
            // -------------------------------------------------------------
            [
                'codigo' => 'tanques.ver',
                'modulo' => 'tanques',
                'accion' => 'ver',
                'nombre' => 'Ver tanques',
                'descripcion' => 'Permite visualizar tanques internos según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'tanques.crear',
                'modulo' => 'tanques',
                'accion' => 'crear',
                'nombre' => 'Crear tanques',
                'descripcion' => 'Permite registrar tanques internos asociados a gasolineras.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'tanques.actualizar',
                'modulo' => 'tanques',
                'accion' => 'actualizar',
                'nombre' => 'Actualizar tanques',
                'descripcion' => 'Permite actualizar datos administrativos y operativos de tanques.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'tanques.inactivar',
                'modulo' => 'tanques',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar tanques',
                'descripcion' => 'Permite inactivar tanques sin eliminarlos físicamente.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'tanques.reactivar',
                'modulo' => 'tanques',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar tanques',
                'descripcion' => 'Permite reactivar tanques previamente inactivos.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Gasolineras externas
            // -------------------------------------------------------------
            [
                'codigo' => 'gasolineras_externas.ver',
                'modulo' => 'gasolineras_externas',
                'accion' => 'ver',
                'nombre' => 'Ver gasolineras externas',
                'descripcion' => 'Permite visualizar gasolineras externas según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Puntos de ruta
            // -------------------------------------------------------------
            [
                'codigo' => 'puntos_ruta.ver',
                'modulo' => 'puntos_ruta',
                'accion' => 'ver',
                'nombre' => 'Ver puntos de ruta',
                'descripcion' => 'Permite visualizar puntos de ruta según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Rutas
            // -------------------------------------------------------------
            [
                'codigo' => 'rutas.ver',
                'modulo' => 'rutas',
                'accion' => 'ver',
                'nombre' => 'Ver rutas',
                'descripcion' => 'Permite visualizar rutas según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Motoristas
            // -------------------------------------------------------------
            [
                'codigo' => 'motoristas.ver',
                'modulo' => 'motoristas',
                'accion' => 'ver',
                'nombre' => 'Ver motoristas',
                'descripcion' => 'Permite visualizar motoristas según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'motoristas.crear',
                'modulo' => 'motoristas',
                'accion' => 'crear',
                'nombre' => 'Crear motoristas',
                'descripcion' => 'Permite registrar motoristas de una empresa cliente.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'motoristas.actualizar',
                'modulo' => 'motoristas',
                'accion' => 'actualizar',
                'nombre' => 'Actualizar motoristas',
                'descripcion' => 'Permite actualizar datos de motoristas.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'motoristas.inactivar',
                'modulo' => 'motoristas',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar motoristas',
                'descripcion' => 'Permite inactivar motoristas sin eliminarlos físicamente.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'motoristas.reactivar',
                'modulo' => 'motoristas',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar motoristas',
                'descripcion' => 'Permite reactivar motoristas previamente inactivos.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Abastecimientos
            // -------------------------------------------------------------
            [
                'codigo' => 'abastecimientos.ver',
                'modulo' => 'abastecimientos',
                'accion' => 'ver',
                'nombre' => 'Ver abastecimientos',
                'descripcion' => 'Permite visualizar abastecimientos según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'abastecimientos.crear',
                'modulo' => 'abastecimientos',
                'accion' => 'crear',
                'nombre' => 'Registrar abastecimientos',
                'descripcion' => 'Permite registrar abastecimientos de unidades.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'abastecimientos.corregir',
                'modulo' => 'abastecimientos',
                'accion' => 'corregir',
                'nombre' => 'Corregir abastecimientos',
                'descripcion' => 'Permite corregir abastecimientos según reglas operativas y de auditoría.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'abastecimientos.anular',
                'modulo' => 'abastecimientos',
                'accion' => 'anular',
                'nombre' => 'Anular abastecimientos',
                'descripcion' => 'Permite anular abastecimientos preservando historial.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Reemplazos
            // -------------------------------------------------------------
            [
                'codigo' => 'reemplazos.ver',
                'modulo' => 'reemplazos',
                'accion' => 'ver',
                'nombre' => 'Ver reemplazos',
                'descripcion' => 'Permite visualizar eventos de reemplazo de marchamos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'reemplazos.crear',
                'modulo' => 'reemplazos',
                'accion' => 'crear',
                'nombre' => 'Registrar reemplazos',
                'descripcion' => 'Permite registrar eventos de reemplazo de marchamos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'reemplazos.corregir',
                'modulo' => 'reemplazos',
                'accion' => 'corregir',
                'nombre' => 'Corregir reemplazos',
                'descripcion' => 'Permite corregir eventos de reemplazo de marchamos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'reemplazos.anular',
                'modulo' => 'reemplazos',
                'accion' => 'anular',
                'nombre' => 'Anular reemplazos',
                'descripcion' => 'Permite anular eventos de reemplazo de marchamos preservando historial.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Inventario
            // -------------------------------------------------------------
            [
                'codigo' => 'inventario.ver',
                'modulo' => 'inventario',
                'accion' => 'ver',
                'nombre' => 'Ver inventario',
                'descripcion' => 'Permite visualizar inventario de combustible según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'inventario.ajustar',
                'modulo' => 'inventario',
                'accion' => 'ajustar',
                'nombre' => 'Ajustar inventario',
                'descripcion' => 'Permite registrar ajustes manuales controlados sobre inventario de combustible.',
                'alcance' => 'empresa',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Auditoría
            // -------------------------------------------------------------
            [
                'codigo' => 'auditoria.panel_operativo.ver',
                'modulo' => 'auditoria',
                'accion' => 'panel_operativo_ver',
                'nombre' => 'Ver control operativo de flota',
                'descripcion' => 'Permite visualizar el panel de control operativo de flota según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'auditoria.abastecimientos.ver',
                'modulo' => 'auditoria',
                'accion' => 'abastecimientos_ver',
                'nombre' => 'Ver auditoría de abastecimientos',
                'descripcion' => 'Permite visualizar la auditoría de abastecimientos según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'auditoria.marchamos.ver',
                'modulo' => 'auditoria',
                'accion' => 'marchamos_ver',
                'nombre' => 'Ver auditoría de marchamos',
                'descripcion' => 'Permite visualizar la auditoría de marchamos según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Análisis
            // -------------------------------------------------------------
            [
                'codigo' => 'analisis.kilometraje.ver',
                'modulo' => 'analisis',
                'accion' => 'kilometraje_ver',
                'nombre' => 'Ver análisis de kilometraje',
                'descripcion' => 'Permite visualizar el análisis de kilometraje según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'analisis.consumo_unidad.ver',
                'modulo' => 'analisis',
                'accion' => 'consumo_unidad_ver',
                'nombre' => 'Ver consumo por unidad',
                'descripcion' => 'Permite visualizar el análisis de consumo por unidad según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'analisis.rutas.ver',
                'modulo' => 'analisis',
                'accion' => 'rutas_ver',
                'nombre' => 'Ver análisis de rutas',
                'descripcion' => 'Permite visualizar el análisis de rutas según el alcance autorizado del rol.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
        ];

        foreach ($permisos as $permiso) {
            Permiso::updateOrCreate(
                ['codigo' => $permiso['codigo']],
                $permiso
            );
        }
        $permisosEmpresasObsoletos = Permiso::query()
            ->whereIn('codigo', [
                'empresas.ver',
                'empresas.actualizar',
            ])
            ->get();

        foreach ($permisosEmpresasObsoletos as $permisoObsoleto) {
            $permisoObsoleto->update([
                'estado' => 'inactivo',
                'fecha_actualizacion' => now(),
                'fecha_inactivacion' => now(),
                'motivo_inactivacion' => 'Permiso reemplazado por el mapeo funcional definitivo del módulo Empresas.',
            ]);
        }
    }
}