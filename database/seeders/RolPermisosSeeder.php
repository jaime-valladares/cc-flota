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
                'empresas.consultar',
                'empresas.administrar',
                'empresas.editar',
                'empresas.inactivar',
                'empresas.reactivar',

                'usuarios.ver',
                'usuarios.crear',
                'usuarios.actualizar',
                'usuarios.cambiar_rol',
                'usuarios.inactivar',
                'usuarios.reactivar',

                'unidades.ver',
                'unidades.crear',
                'unidades.actualizar',
                'unidades.inactivar',
                'unidades.reactivar',

                'licencias.ver',
                'licencias.provisionar',
                'licencias.asignar',
                'licencias.suspender',
                'licencias.inactivar',

                'puntos_seguridad.ver',
                'puntos_seguridad.crear',
                'puntos_seguridad.actualizar',
                'puntos_seguridad.inactivar',
                'puntos_seguridad.reactivar',

                'marchamos.ver',
                'marchamos.asignar_inicial',
                'marchamos.reemplazar_abastecimiento',
                'marchamos.reemplazar_dano_desgaste',
                'marchamos.ver_historial',

                'gasolineras.ver',
                'gasolineras.crear',
                'gasolineras.actualizar',
                'gasolineras.inactivar',
                'gasolineras.reactivar',

                'tanques.ver',
                'tanques.crear',
                'tanques.actualizar',
                'tanques.inactivar',
                'tanques.reactivar',

                'motoristas.ver',
                'motoristas.crear',
                'motoristas.actualizar',
                'motoristas.inactivar',
                'motoristas.reactivar',

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
                'empresas.consultar',

                'unidades.ver',
                'unidades.crear',
                'unidades.actualizar',

                'puntos_seguridad.ver',
                'puntos_seguridad.crear',
                'puntos_seguridad.actualizar',

                'marchamos.ver',
                'marchamos.asignar_inicial',
                'marchamos.reemplazar_dano_desgaste',
                'marchamos.ver_historial',

                'reemplazos.ver',
                'reemplazos.crear',

                'gasolineras.ver',
                'tanques.ver',
                'motoristas.ver',
                'abastecimientos.ver',


                'analisis.kilometraje.ver',
                'analisis.consumo_unidad.ver',
                'analisis.rutas.ver',
            ],

            'DIESEL_AUDITOR' => [
                'empresas.consultar',

                'usuarios.ver',
                'unidades.ver',
                'licencias.ver',
                'marchamos.ver',
                'marchamos.ver_historial',
                'gasolineras.ver',
                'gasolineras_externas.ver',
                'puntos_ruta.ver',
                'rutas.ver',
                'motoristas.ver',
                'abastecimientos.ver',
                'auditoria.panel_operativo.ver',
                'auditoria.abastecimientos.ver',
                'auditoria.marchamos.ver',
                'analisis.kilometraje.ver',
                'analisis.consumo_unidad.ver',
                'analisis.rutas.ver',
            ],

            'EMPRESA_ADMIN' => [
                'usuarios.ver',
                'usuarios.crear',
                'usuarios.actualizar',
                'usuarios.cambiar_rol',
                'usuarios.inactivar',
                'usuarios.reactivar',

                'unidades.ver',

                'marchamos.ver',
                'marchamos.reemplazar_abastecimiento',
                'marchamos.reemplazar_dano_desgaste',
                'marchamos.ver_historial',

                'gasolineras.ver',
                'gasolineras.crear',
                'gasolineras.actualizar',
                'gasolineras.inactivar',
                'gasolineras.reactivar',

                'tanques.ver',
                'tanques.crear',
                'tanques.actualizar',
                'tanques.inactivar',
                'tanques.reactivar',

                'motoristas.ver',
                'motoristas.crear',
                'motoristas.actualizar',
                'motoristas.inactivar',
                'motoristas.reactivar',

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
                'usuarios.ver',

                'unidades.ver',

                'marchamos.ver',
                'marchamos.reemplazar_abastecimiento',
                'marchamos.reemplazar_dano_desgaste',
                'marchamos.ver_historial',

                'gasolineras.ver',
                'tanques.ver',
                'motoristas.ver',

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
                'unidades.ver',

                'marchamos.ver',
                'marchamos.reemplazar_abastecimiento',
                'marchamos.reemplazar_dano_desgaste',
                'marchamos.ver_historial',

                'gasolineras.ver',
                'tanques.ver',
                'motoristas.ver',

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
                'usuarios.ver',
                'unidades.ver',
                'licencias.ver',
                'marchamos.ver',
                'marchamos.ver_historial',
                'gasolineras.ver',
                'gasolineras_externas.ver',
                'puntos_ruta.ver',
                'rutas.ver',
                'motoristas.ver',
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