<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Role;
use App\Models\RolPermiso;
use Illuminate\Database\Seeder;

class RolPermisosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $matrizPermisos = [
            'DIESEL_SUPER_ADMIN' => 'ALL',

            'DIESEL_ADMIN' => [
                'motoristas.consultar',
                'motoristas.administrar',
                'motoristas.editar',
                'motoristas.inactivar',
                'motoristas.reactivar',

                'rutas.consultar',
                'rutas.administrar',
                'rutas.editar',
                'rutas.inactivar',
                'rutas.reactivar',

                'puntos_ruta.consultar',
                'puntos_ruta.administrar',
                'puntos_ruta.editar',
                'puntos_ruta.inactivar',
                'puntos_ruta.reactivar',

                'gasolineras_externas.consultar',
                'gasolineras_externas.administrar',
                'gasolineras_externas.editar',
                'gasolineras_externas.inactivar',
                'gasolineras_externas.reactivar',

                'gasolineras.consultar',
                'gasolineras.administrar',
                'gasolineras.editar',
                'gasolineras.inactivar',
                'gasolineras.reactivar',
                'tanques.administrar',
                'tanques.crear',
                'tanques.editar',
                'tanques.inactivar',
                'tanques.reactivar',
                'recargas_tanques.registrar',
                'recargas_tanques.anular',

                'marchamos.consultar',
                'marchamos.administrar',
                'marchamos.reemplazar',

                'licencias.consultar',
                'licencias.administrar',
                'licencias.editar',
                'licencias.inactivar',
                'licencias.reactivar',

                'unidades.consultar',
                'unidades.administrar',
                'unidades.editar',
                'unidades.inactivar',
                'unidades.reactivar',

                'usuarios.consultar',
                'usuarios.administrar',
                'usuarios.editar',
                'usuarios.inactivar',
                'usuarios.reactivar',

                'empresas.consultar',
                'empresas.administrar',
                'empresas.editar',
                'empresas.inactivar',
                'empresas.reactivar',




                'puntos_seguridad.ver',
                'puntos_seguridad.crear',
                'puntos_seguridad.actualizar',
                'puntos_seguridad.inactivar',
                'puntos_seguridad.reactivar',





                'abastecimientos.ver',
                'abastecimientos.corregir',
                'abastecimientos.anular',

                'reemplazos.ver',
                'reemplazos.crear',
                'reemplazos.corregir',
                'reemplazos.anular',

                'inventario.ver',
                'inventario.ajustar',

                'auditoria.panel_operativo.ver',
                'auditoria.abastecimientos.ver',
                'auditoria.marchamos.ver',
                'analisis.kilometraje.ver',
                'analisis.consumo_unidad.ver',
                'analisis.rutas.ver',
            ],

            'DIESEL_TECNICO' => [
                'motoristas.consultar',

                'rutas.consultar',

                'puntos_ruta.consultar',

                'gasolineras_externas.consultar',

                'gasolineras.consultar',
                'recargas_tanques.registrar',
                'recargas_tanques.anular',

                'marchamos.consultar',
                'marchamos.administrar',
                'marchamos.reemplazar',

                'unidades.consultar',

                'empresas.consultar',


                'puntos_seguridad.ver',
                'puntos_seguridad.crear',
                'puntos_seguridad.actualizar',


                'reemplazos.ver',
                'reemplazos.crear',

                'abastecimientos.ver',


                'analisis.kilometraje.ver',
                'analisis.consumo_unidad.ver',
                'analisis.rutas.ver',
            ],

            'DIESEL_AUDITOR' => [
                'motoristas.consultar',

                'rutas.consultar',

                'puntos_ruta.consultar',

                'gasolineras_externas.consultar',

                'gasolineras.consultar',

                'marchamos.consultar',

                'licencias.consultar',

                'unidades.consultar',

                'usuarios.consultar',

                'empresas.consultar',

                'abastecimientos.ver',
                'auditoria.panel_operativo.ver',
                'auditoria.abastecimientos.ver',
                'auditoria.marchamos.ver',
                'analisis.kilometraje.ver',
                'analisis.consumo_unidad.ver',
                'analisis.rutas.ver',
            ],

            'EMPRESA_ADMIN' => [
                'motoristas.consultar',
                'motoristas.administrar',
                'motoristas.crear',
                'motoristas.editar',
                'motoristas.inactivar',
                'motoristas.reactivar',

                'rutas.consultar',
                'rutas.administrar',
                'rutas.crear',
                'rutas.editar',
                'rutas.inactivar',
                'rutas.reactivar',

                'puntos_ruta.consultar',
                'puntos_ruta.administrar',
                'puntos_ruta.crear',
                'puntos_ruta.editar',
                'puntos_ruta.inactivar',
                'puntos_ruta.reactivar',

                'gasolineras_externas.consultar',
                'gasolineras_externas.administrar',
                'gasolineras_externas.crear',
                'gasolineras_externas.editar',
                'gasolineras_externas.inactivar',
                'gasolineras_externas.reactivar',

                'gasolineras.consultar',
                'gasolineras.administrar',
                'gasolineras.crear',
                'gasolineras.editar',
                'gasolineras.inactivar',
                'gasolineras.reactivar',
                'tanques.administrar',
                'tanques.crear',
                'tanques.editar',
                'tanques.inactivar',
                'tanques.reactivar',
                'recargas_tanques.registrar',
                'recargas_tanques.anular',

                'marchamos.consultar',
                'marchamos.administrar',
                'marchamos.reemplazar',

                'licencias.consultar',

                'unidades.consultar',
                'unidades.administrar',
                'unidades.editar',
                'unidades.inactivar',
                'unidades.reactivar',

                'usuarios.consultar',
                'usuarios.administrar',
                'usuarios.crear',
                'usuarios.editar',
                'usuarios.inactivar',
                'usuarios.reactivar',






                'abastecimientos.ver',
                'abastecimientos.crear',
                'abastecimientos.corregir',
                'abastecimientos.anular',

                'reemplazos.ver',
                'reemplazos.crear',
                'reemplazos.corregir',
                'reemplazos.anular',

                'inventario.ver',
                'inventario.ajustar',


                'analisis.kilometraje.ver',
                'analisis.consumo_unidad.ver',
                'analisis.rutas.ver',
            ],

            'EMPRESA_SUPERVISOR' => [
                'motoristas.consultar',
                'motoristas.administrar',
                'motoristas.editar',
                'motoristas.inactivar',
                'motoristas.reactivar',

                'rutas.consultar',
                'rutas.administrar',
                'rutas.editar',
                'rutas.inactivar',
                'rutas.reactivar',

                'puntos_ruta.consultar',
                'puntos_ruta.administrar',
                'puntos_ruta.editar',
                'puntos_ruta.inactivar',
                'puntos_ruta.reactivar',

                'gasolineras_externas.consultar',
                'gasolineras_externas.administrar',
                'gasolineras_externas.editar',
                'gasolineras_externas.inactivar',
                'gasolineras_externas.reactivar',

                'gasolineras.consultar',
                'gasolineras.administrar',
                'gasolineras.editar',
                'gasolineras.inactivar',
                'gasolineras.reactivar',
                'tanques.administrar',
                'tanques.crear',
                'tanques.editar',
                'tanques.inactivar',
                'tanques.reactivar',
                'recargas_tanques.registrar',
                'recargas_tanques.anular',

                'marchamos.consultar',
                'marchamos.administrar',
                'marchamos.reemplazar',

                'licencias.consultar',

                'unidades.consultar',
                'unidades.administrar',
                'unidades.editar',
                'unidades.inactivar',
                'unidades.reactivar',

                'usuarios.consultar',
                'usuarios.administrar',
                'usuarios.editar',
                'usuarios.inactivar',
                'usuarios.reactivar',




                'abastecimientos.ver',
                'abastecimientos.crear',
                'abastecimientos.corregir',

                'reemplazos.ver',
                'reemplazos.crear',

                'inventario.ver',


                'analisis.kilometraje.ver',
                'analisis.consumo_unidad.ver',
                'analisis.rutas.ver',
            ],

            'EMPRESA_OPERADOR' => [
                'motoristas.consultar',

                'rutas.consultar',

                'puntos_ruta.consultar',

                'gasolineras_externas.consultar',

                'gasolineras.consultar',
                'recargas_tanques.registrar',
                'recargas_tanques.anular',

                'marchamos.consultar',
                'marchamos.administrar',
                'marchamos.reemplazar',

                'unidades.consultar',



                'abastecimientos.ver',
                'abastecimientos.crear',

                'reemplazos.ver',
                'reemplazos.crear',

                'inventario.ver',


                'analisis.kilometraje.ver',
                'analisis.consumo_unidad.ver',
                'analisis.rutas.ver',
            ],

            'EMPRESA_AUDITOR' => [
                'motoristas.consultar',

                'rutas.consultar',

                'puntos_ruta.consultar',

                'gasolineras_externas.consultar',

                'gasolineras.consultar',

                'marchamos.consultar',

                'licencias.consultar',

                'unidades.consultar',

                'usuarios.consultar',

                'abastecimientos.ver',
                'auditoria.panel_operativo.ver',
                'auditoria.abastecimientos.ver',
                'auditoria.marchamos.ver',
                'analisis.kilometraje.ver',
                'analisis.consumo_unidad.ver',
                'analisis.rutas.ver',
            ],
        ];

        RolPermiso::query()->delete();

        foreach ($matrizPermisos as $codigoRol => $permisosAsignados) {
            $role = Role::where('codigo', $codigoRol)->firstOrFail();

            if ($permisosAsignados === 'ALL') {
                $permisos = Permiso::where('estado', 'activo')->get();
            } else {
                $permisos = Permiso::whereIn('codigo', $permisosAsignados)
                    ->where('estado', 'activo')
                    ->get();

                $codigosEncontrados = $permisos->pluck('codigo')->all();
                $codigosFaltantes = array_diff($permisosAsignados, $codigosEncontrados);

                if (! empty($codigosFaltantes)) {
                    throw new \RuntimeException(
                        'Permisos no encontrados para el rol ' . $codigoRol . ': ' . implode(', ', $codigosFaltantes)
                    );
                }
            }

            foreach ($permisos as $permiso) {
                RolPermiso::updateOrCreate(
                    [
                        'rol_id' => $role->id,
                        'permiso_id' => $permiso->id,
                    ],
                    [
                        'fecha_creacion' => now(),
                    ]
                );
            }
        }
    }
}