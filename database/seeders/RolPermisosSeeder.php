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
                'empresas.ver',
                'empresas.crear',
                'empresas.actualizar',
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

                'auditoria.ver',
                'analisis.ver',
            ],

            'DIESEL_TECNICO' => [
                'empresas.ver',

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

                'analisis.ver',
            ],

            'DIESEL_AUDITOR' => [
                'empresas.ver',
                'usuarios.ver',

                'unidades.ver',
                'licencias.ver',

                'puntos_seguridad.ver',

                'marchamos.ver',
                'marchamos.ver_historial',

                'gasolineras.ver',
                'tanques.ver',
                'motoristas.ver',
                'abastecimientos.ver',
                'reemplazos.ver',
                'inventario.ver',

                'auditoria.ver',
                'analisis.ver',
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

                'analisis.ver',
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

                'analisis.ver',
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

                'analisis.ver',
            ],

            'EMPRESA_AUDITOR' => [
                'unidades.ver',

                'marchamos.ver',
                'marchamos.ver_historial',

                'gasolineras.ver',
                'tanques.ver',
                'motoristas.ver',

                'abastecimientos.ver',
                'reemplazos.ver',

                'inventario.ver',

                'analisis.ver',
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