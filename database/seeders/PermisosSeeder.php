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
                'codigo' => 'usuarios.consultar',
                'modulo' => 'usuarios',
                'accion' => 'consultar',
                'nombre' => 'Consultar usuarios',
                'descripcion' => 'Permite acceder a la consulta informativa de usuarios según el alcance autorizado.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'usuarios.administrar',
                'modulo' => 'usuarios',
                'accion' => 'administrar',
                'nombre' => 'Administrar usuarios',
                'descripcion' => 'Permite acceder al listado administrativo y a la ficha de usuarios administrables.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'usuarios.crear',
                'modulo' => 'usuarios',
                'accion' => 'crear',
                'nombre' => 'Crear usuarios',
                'descripcion' => 'Permite registrar usuarios dentro del alcance autorizado.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'usuarios.editar',
                'modulo' => 'usuarios',
                'accion' => 'editar',
                'nombre' => 'Editar usuarios',
                'descripcion' => 'Permite modificar usuarios activos dentro del alcance autorizado.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'usuarios.inactivar',
                'modulo' => 'usuarios',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar usuarios',
                'descripcion' => 'Permite inactivar usuarios administrables sin eliminarlos físicamente.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'usuarios.reactivar',
                'modulo' => 'usuarios',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar usuarios',
                'descripcion' => 'Permite reactivar usuarios administrables previamente inactivos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Unidades
            // -------------------------------------------------------------
            [
                'codigo' => 'unidades.consultar',
                'modulo' => 'unidades',
                'accion' => 'consultar',
                'nombre' => 'Consultar unidades',
                'descripcion' => 'Permite acceder a la consulta informativa de unidades según el alcance autorizado.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'unidades.administrar',
                'modulo' => 'unidades',
                'accion' => 'administrar',
                'nombre' => 'Administrar unidades',
                'descripcion' => 'Permite acceder al listado administrativo y a la ficha administrativa de unidades.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'unidades.crear',
                'modulo' => 'unidades',
                'accion' => 'crear',
                'nombre' => 'Crear unidades',
                'descripcion' => 'Permite registrar nuevas unidades dentro del alcance autorizado.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'unidades.editar',
                'modulo' => 'unidades',
                'accion' => 'editar',
                'nombre' => 'Editar unidades',
                'descripcion' => 'Permite modificar unidades que cumplen las condiciones administrativas y operativas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'unidades.inactivar',
                'modulo' => 'unidades',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar unidades',
                'descripcion' => 'Permite inactivar unidades administrables sin eliminarlas físicamente.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'unidades.reactivar',
                'modulo' => 'unidades',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar unidades',
                'descripcion' => 'Permite reactivar unidades administrables previamente inactivas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Licencias
            // -------------------------------------------------------------
            [
                'codigo' => 'licencias.consultar',
                'modulo' => 'licencias',
                'accion' => 'consultar',
                'nombre' => 'Consultar licencias',
                'descripcion' => 'Permite consultar licencias según el alcance autorizado.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'licencias.administrar',
                'modulo' => 'licencias',
                'accion' => 'administrar',
                'nombre' => 'Administrar licencias',
                'descripcion' => 'Permite acceder al listado administrativo y a la ficha de licencias.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'licencias.crear',
                'modulo' => 'licencias',
                'accion' => 'crear',
                'nombre' => 'Crear licencias',
                'descripcion' => 'Permite registrar licencias y generar los puntos de seguridad iniciales.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'licencias.editar',
                'modulo' => 'licencias',
                'accion' => 'editar',
                'nombre' => 'Editar licencias',
                'descripcion' => 'Permite modificar una licencia activa y no vencida.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'licencias.inactivar',
                'modulo' => 'licencias',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar licencias',
                'descripcion' => 'Permite inactivar licencias preservando su historial.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'licencias.reactivar',
                'modulo' => 'licencias',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar o renovar licencias',
                'descripcion' => 'Permite reactivar licencias inactivas o renovar licencias vencidas.',
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
                'codigo' => 'marchamos.consultar',
                'modulo' => 'marchamos',
                'accion' => 'consultar',
                'nombre' => 'Consultar marchamos',
                'descripcion' => 'Permite consultar cobertura e historial de marchamos según el alcance autorizado.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'marchamos.administrar',
                'modulo' => 'marchamos',
                'accion' => 'administrar',
                'nombre' => 'Administrar marchamos',
                'descripcion' => 'Permite acceder al listado administrativo y al formulario de reemplazos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'marchamos.reemplazar',
                'modulo' => 'marchamos',
                'accion' => 'reemplazar',
                'nombre' => 'Reemplazar marchamos',
                'descripcion' => 'Permite registrar uno o varios reemplazos conservando el historial completo.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'marchamos.asignar_inicial',
                'modulo' => 'marchamos',
                'accion' => 'asignar_inicial',
                'nombre' => 'Realizar asignación inicial de marchamos',
                'descripcion' => 'Permite guardar avances y finalizar la asignación inicial de marchamos.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Gasolineras internas
            // -------------------------------------------------------------
            [
                'codigo' => 'gasolineras.consultar',
                'modulo' => 'gasolineras',
                'accion' => 'consultar',
                'nombre' => 'Consultar gasolineras internas',
                'descripcion' => 'Permite consultar gasolineras internas según el alcance autorizado.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras.administrar',
                'modulo' => 'gasolineras',
                'accion' => 'administrar',
                'nombre' => 'Administrar gasolineras internas',
                'descripcion' => 'Permite acceder al listado administrativo y a la ficha de gasolineras internas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras.crear',
                'modulo' => 'gasolineras',
                'accion' => 'crear',
                'nombre' => 'Crear gasolineras internas',
                'descripcion' => 'Permite registrar una gasolinera interna con su inventario inicial.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras.editar',
                'modulo' => 'gasolineras',
                'accion' => 'editar',
                'nombre' => 'Editar gasolineras internas',
                'descripcion' => 'Permite modificar los datos administrativos de una gasolinera interna activa.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras.inactivar',
                'modulo' => 'gasolineras',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar gasolineras internas',
                'descripcion' => 'Permite inactivar una gasolinera interna sin eliminar sus tanques ni su historial.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras.reactivar',
                'modulo' => 'gasolineras',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar gasolineras internas',
                'descripcion' => 'Permite reactivar una gasolinera interna previamente inactiva.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Gestión de tanques
            // -------------------------------------------------------------
            [
                'codigo' => 'tanques.administrar',
                'modulo' => 'tanques',
                'accion' => 'administrar',
                'nombre' => 'Administrar tanques',
                'descripcion' => 'Permite consultar el listado administrativo y acceder a la ficha de tanques.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'tanques.crear',
                'modulo' => 'tanques',
                'accion' => 'crear',
                'nombre' => 'Crear tanques',
                'descripcion' => 'Permite registrar tanques adicionales en una gasolinera interna activa.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'tanques.editar',
                'modulo' => 'tanques',
                'accion' => 'editar',
                'nombre' => 'Editar tanques',
                'descripcion' => 'Permite modificar los datos controlados de un tanque activo.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'tanques.inactivar',
                'modulo' => 'tanques',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar tanques',
                'descripcion' => 'Permite inactivar un tanque preservando su inventario e historial.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'tanques.reactivar',
                'modulo' => 'tanques',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar tanques',
                'descripcion' => 'Permite reactivar un tanque previamente inactivo.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Recarga de tanques
            // -------------------------------------------------------------
            [
                'codigo' => 'recargas_tanques.registrar',
                'modulo' => 'recargas_tanques',
                'accion' => 'registrar',
                'nombre' => 'Registrar recargas de tanques',
                'descripcion' => 'Permite consultar tanques recargables y registrar una recarga indivisible en uno o varios tanques.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'recargas_tanques.anular',
                'modulo' => 'recargas_tanques',
                'accion' => 'anular',
                'nombre' => 'Anular recargas de tanques',
                'descripcion' => 'Permite anular completamente una recarga y revertir el inventario de todos los tanques involucrados.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Gasolineras externas
            // -------------------------------------------------------------
            [
                'codigo' => 'gasolineras_externas.consultar',
                'modulo' => 'gasolineras_externas',
                'accion' => 'consultar',
                'nombre' => 'Consultar gasolineras externas',
                'descripcion' => 'Permite consultar gasolineras externas según el alcance autorizado.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras_externas.administrar',
                'modulo' => 'gasolineras_externas',
                'accion' => 'administrar',
                'nombre' => 'Administrar gasolineras externas',
                'descripcion' => 'Permite acceder al listado administrativo y a la ficha de gasolineras externas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras_externas.crear',
                'modulo' => 'gasolineras_externas',
                'accion' => 'crear',
                'nombre' => 'Crear gasolineras externas',
                'descripcion' => 'Permite registrar gasolineras externas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras_externas.editar',
                'modulo' => 'gasolineras_externas',
                'accion' => 'editar',
                'nombre' => 'Editar gasolineras externas',
                'descripcion' => 'Permite modificar una gasolinera externa activa.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras_externas.inactivar',
                'modulo' => 'gasolineras_externas',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar gasolineras externas',
                'descripcion' => 'Permite inactivar una gasolinera externa sin eliminar su historial.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'gasolineras_externas.reactivar',
                'modulo' => 'gasolineras_externas',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar gasolineras externas',
                'descripcion' => 'Permite reactivar una gasolinera externa previamente inactiva.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Puntos de ruta
            // -------------------------------------------------------------
            [
                'codigo' => 'puntos_ruta.consultar',
                'modulo' => 'puntos_ruta',
                'accion' => 'consultar',
                'nombre' => 'Consultar puntos de ruta',
                'descripcion' => 'Permite consultar puntos de ruta según el alcance autorizado.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'puntos_ruta.administrar',
                'modulo' => 'puntos_ruta',
                'accion' => 'administrar',
                'nombre' => 'Administrar puntos de ruta',
                'descripcion' => 'Permite acceder al listado administrativo y a la ficha de puntos de ruta.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'puntos_ruta.crear',
                'modulo' => 'puntos_ruta',
                'accion' => 'crear',
                'nombre' => 'Crear puntos de ruta',
                'descripcion' => 'Permite registrar nuevos puntos de ruta.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'puntos_ruta.editar',
                'modulo' => 'puntos_ruta',
                'accion' => 'editar',
                'nombre' => 'Editar puntos de ruta',
                'descripcion' => 'Permite modificar puntos de ruta activos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'puntos_ruta.inactivar',
                'modulo' => 'puntos_ruta',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar puntos de ruta',
                'descripcion' => 'Permite inactivar puntos de ruta cuando no participan en rutas activas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'puntos_ruta.reactivar',
                'modulo' => 'puntos_ruta',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar puntos de ruta',
                'descripcion' => 'Permite reactivar puntos de ruta previamente inactivos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Rutas
            // -------------------------------------------------------------
            [
                'codigo' => 'rutas.consultar',
                'modulo' => 'rutas',
                'accion' => 'consultar',
                'nombre' => 'Consultar rutas',
                'descripcion' => 'Permite consultar rutas según el alcance autorizado.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'rutas.administrar',
                'modulo' => 'rutas',
                'accion' => 'administrar',
                'nombre' => 'Administrar rutas',
                'descripcion' => 'Permite acceder al listado administrativo y a la ficha de rutas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'rutas.crear',
                'modulo' => 'rutas',
                'accion' => 'crear',
                'nombre' => 'Crear rutas',
                'descripcion' => 'Permite registrar nuevas rutas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'rutas.editar',
                'modulo' => 'rutas',
                'accion' => 'editar',
                'nombre' => 'Editar rutas',
                'descripcion' => 'Permite modificar rutas activas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'rutas.inactivar',
                'modulo' => 'rutas',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar rutas',
                'descripcion' => 'Permite inactivar rutas sin eliminar su historial.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'rutas.reactivar',
                'modulo' => 'rutas',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar rutas',
                'descripcion' => 'Permite reactivar rutas cuando su empresa y ambos puntos están activos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Motoristas
            // -------------------------------------------------------------
            [
                'codigo' => 'motoristas.consultar',
                'modulo' => 'motoristas',
                'accion' => 'consultar',
                'nombre' => 'Consultar motoristas',
                'descripcion' => 'Permite consultar motoristas según el alcance autorizado.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'motoristas.administrar',
                'modulo' => 'motoristas',
                'accion' => 'administrar',
                'nombre' => 'Administrar motoristas',
                'descripcion' => 'Permite acceder al listado administrativo y a la ficha de motoristas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'motoristas.crear',
                'modulo' => 'motoristas',
                'accion' => 'crear',
                'nombre' => 'Crear motoristas',
                'descripcion' => 'Permite registrar nuevos motoristas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'motoristas.editar',
                'modulo' => 'motoristas',
                'accion' => 'editar',
                'nombre' => 'Editar motoristas',
                'descripcion' => 'Permite modificar motoristas activos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'motoristas.inactivar',
                'modulo' => 'motoristas',
                'accion' => 'inactivar',
                'nombre' => 'Inactivar motoristas',
                'descripcion' => 'Permite inactivar motoristas sin eliminar su historial.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'motoristas.reactivar',
                'modulo' => 'motoristas',
                'accion' => 'reactivar',
                'nombre' => 'Reactivar motoristas',
                'descripcion' => 'Permite reactivar motoristas previamente inactivos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Abastecimientos
            // -------------------------------------------------------------
            [
                'codigo' => 'abastecimientos.consultar',
                'modulo' => 'abastecimientos',
                'accion' => 'consultar',
                'nombre' => 'Consultar abastecimientos',
                'descripcion' => 'Permite consultar el historial de abastecimientos y sus fichas.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'abastecimientos.administrar',
                'modulo' => 'abastecimientos',
                'accion' => 'administrar',
                'nombre' => 'Administrar abastecimientos (legacy)',
                'descripcion' => 'Permiso legacy conservado como registro histórico; no controla ninguna función activa.',
                'alcance' => 'ambos',
                'estado' => 'inactivo',
            ],
            [
                'codigo' => 'abastecimientos.registrar',
                'modulo' => 'abastecimientos',
                'accion' => 'registrar',
                'nombre' => 'Registrar abastecimientos',
                'descripcion' => 'Permite localizar unidades operativas y registrar nuevos abastecimientos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'abastecimientos.modificar',
                'modulo' => 'abastecimientos',
                'accion' => 'modificar',
                'nombre' => 'Modificar abastecimientos',
                'descripcion' => 'Permite modificar atómicamente el último abastecimiento vigente de una unidad.',
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
                'codigo' => 'auditoria.consultar',
                'modulo' => 'auditoria',
                'accion' => 'consultar',
                'nombre' => 'Consultar auditoría',
                'descripcion' => 'Permite consultar las vistas informacionales de control operativo, abastecimientos y marchamos.',
                'alcance' => 'ambos',
                'estado' => 'activo',
            ],

            // -------------------------------------------------------------
            // Análisis
            // -------------------------------------------------------------
            [
                'codigo' => 'analisis.consultar',
                'modulo' => 'analisis',
                'accion' => 'consultar',
                'nombre' => 'Consultar análisis',
                'descripcion' => 'Permite consultar las vistas informacionales de rendimientos, consumo por unidad y rutas.',
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
                'usuarios.ver',
                'usuarios.actualizar',
                'usuarios.cambiar_rol',
                'unidades.ver',
                'unidades.actualizar',
                'licencias.ver',
                'licencias.provisionar',
                'licencias.asignar',
                'licencias.suspender',
                'marchamos.ver',
                'marchamos.ver_historial',
                'marchamos.reemplazar_abastecimiento',
                'marchamos.reemplazar_dano_desgaste',
                'gasolineras.ver',
                'gasolineras.actualizar',
                'tanques.ver',
                'tanques.actualizar',
            ])
            ->get();

        foreach ($permisosEmpresasObsoletos as $permisoObsoleto) {
            $permisoObsoleto->update([
                'estado' => 'inactivo',
                'fecha_actualizacion' => now(),
                'fecha_inactivacion' => now(),
                'motivo_inactivacion' => 'Permiso reemplazado por el mapeo funcional definitivo de los módulos Empresas, Usuarios, Unidades, Licencias, Marchamos, Gasolineras Internas, Gasolineras Externas, Puntos de Ruta, Rutas, Motoristas, Abastecimientos, Auditoría y Análisis.',
            ]);
        }
    }
}
