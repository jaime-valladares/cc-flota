<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'codigo' => 'DIESEL_SUPER_ADMIN',
                'nombre' => 'Diesel Cop Super Administrador',
                'alcance' => 'diesel_cop',
                'descripcion' => 'Rol con acceso máximo a la administración global de CC-Flota.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'DIESEL_ADMIN',
                'nombre' => 'Diesel Cop Administrador',
                'alcance' => 'diesel_cop',
                'descripcion' => 'Rol administrativo de Diesel Cop con permisos globales controlados.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'DIESEL_TECNICO',
                'nombre' => 'Diesel Cop Técnico',
                'alcance' => 'diesel_cop',
                'descripcion' => 'Rol técnico para configuración de unidades, puntos de seguridad y marchamos.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'DIESEL_AUDITOR',
                'nombre' => 'Diesel Cop Auditor',
                'alcance' => 'diesel_cop',
                'descripcion' => 'Rol de consulta y revisión de auditoría del sistema.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'EMPRESA_ADMIN',
                'nombre' => 'Empresa Administrador',
                'alcance' => 'empresa',
                'descripcion' => 'Rol administrador de una empresa cliente dentro de CC-Flota.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'EMPRESA_SUPERVISOR',
                'nombre' => 'Empresa Supervisor',
                'alcance' => 'empresa',
                'descripcion' => 'Rol supervisor para revisión y control operativo de la empresa.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'EMPRESA_OPERADOR',
                'nombre' => 'Empresa Operador',
                'alcance' => 'empresa',
                'descripcion' => 'Rol operativo para registrar abastecimientos y operaciones permitidas.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'EMPRESA_CONSULTA',
                'nombre' => 'Empresa Consulta',
                'alcance' => 'empresa',
                'descripcion' => 'Rol de consulta para visualizar información sin modificar registros.',
                'estado' => 'activo',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['codigo' => $role['codigo']],
                $role
            );
        }
    }
}