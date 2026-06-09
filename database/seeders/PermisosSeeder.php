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
            [
                'codigo' => 'empresas.ver',
                'modulo' => 'empresas',
                'accion' => 'ver',
                'nombre' => 'Ver empresas',
                'descripcion' => 'Permite visualizar el listado y detalle de empresas registradas en CC-Flota.',
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
                'descripcion' => 'Permite modificar información general de empresas cliente registradas.',
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
                'descripcion' => 'Permite reactivar empresas previamente inactivadas, previa revisión administrativa.',
                'alcance' => 'diesel_cop',
                'estado' => 'activo',
            ],
        ];

        foreach ($permisos as $permiso) {
            Permiso::updateOrCreate(
                ['codigo' => $permiso['codigo']],
                $permiso
            );
        }
    }
}