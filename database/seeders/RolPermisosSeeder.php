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
                'reportes.gestion-combustible-motorista.consultar',
                'reportes.rendimiento-km-galon.consultar',
                'reportes.unidades.ficha',
                'auditoria.consultar',
                'analisis.consultar',

                'abastecimientos.consultar',

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

                'reemplazos.ver',
                'reemplazos.crear',
                'reemplazos.corregir',
                'reemplazos.anular',

                'inventario.ver',
                'inventario.ajustar',

            ],

            'DIESEL_TECNICO' => [
                'abastecimientos.consultar',
                'abastecimientos.registrar',

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

            ],

            'DIESEL_AUDITOR' => [
                'reportes.gestion-combustible-motorista.consultar',
                'reportes.rendimiento-km-galon.consultar',
                'reportes.unidades.ficha',
                'auditoria.consultar',
                'analisis.consultar',

                'abastecimientos.consultar',

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

            ],

            'EMPRESA_ADMIN' => [
                'reportes.gestion-combustible-motorista.consultar',
                'reportes.rendimiento-km-galon.consultar',
                'reportes.unidades.ficha',
                'auditoria.consultar',
                'analisis.consultar',

                'abastecimientos.consultar',
                'abastecimientos.registrar',

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

                'reemplazos.ver',
                'reemplazos.crear',
                'reemplazos.corregir',
                'reemplazos.anular',

                'inventario.ver',
                'inventario.ajustar',

            ],

            'EMPRESA_SUPERVISOR' => [
                'reportes.gestion-combustible-motorista.consultar',
                'reportes.rendimiento-km-galon.consultar',
                'reportes.unidades.ficha',
                'auditoria.consultar',
                'analisis.consultar',

                'abastecimientos.consultar',

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

                'reemplazos.ver',
                'reemplazos.crear',

                'inventario.ver',

            ],

            'EMPRESA_OPERADOR' => [
                'abastecimientos.consultar',
                'abastecimientos.registrar',

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

                'reemplazos.ver',
                'reemplazos.crear',

                'inventario.ver',

            ],

            'EMPRESA_AUDITOR' => [
                'reportes.gestion-combustible-motorista.consultar',
                'reportes.rendimiento-km-galon.consultar',
                'reportes.unidades.ficha',
                'auditoria.consultar',
                'analisis.consultar',

                'abastecimientos.consultar',

                'motoristas.consultar',

                'rutas.consultar',

                'puntos_ruta.consultar',

                'gasolineras_externas.consultar',

                'gasolineras.consultar',

                'marchamos.consultar',

                'licencias.consultar',

                'unidades.consultar',

                'usuarios.consultar',

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
                        'Permisos no encontrados para el rol '.$codigoRol.': '.implode(', ', $codigosFaltantes)
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
