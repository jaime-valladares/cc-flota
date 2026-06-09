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
        $rolesConPermisosEmpresas = [
            'DIESEL_SUPER_ADMIN',
            'DIESEL_ADMIN',
        ];

        $permisosEmpresas = [
            'empresas.ver',
            'empresas.crear',
            'empresas.editar',
            'empresas.inactivar',
            'empresas.reactivar',
        ];

        foreach ($rolesConPermisosEmpresas as $codigoRol) {
            $role = Role::where('codigo', $codigoRol)->firstOrFail();

            foreach ($permisosEmpresas as $codigoPermiso) {
                $permiso = Permiso::where('codigo', $codigoPermiso)->firstOrFail();

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