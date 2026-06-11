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
                'nombre' => 'Super Administrador Diesel Cop',
                'alcance' => 'diesel_cop',
                'descripcion' => 'Rol interno de Diesel Cop con acceso total global sobre CC-Flota, usuarios Diesel Cop y todas las empresas cliente.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'DIESEL_ADMIN',
                'nombre' => 'Administrador Diesel Cop',
                'alcance' => 'diesel_cop',
                'descripcion' => 'Rol administrativo de Diesel Cop orientado a la gestión operativa de empresas cliente, sin administración de usuarios internos Diesel Cop.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'DIESEL_TECNICO',
                'nombre' => 'Técnico Diesel Cop',
                'alcance' => 'diesel_cop',
                'descripcion' => 'Rol técnico de Diesel Cop para configuración de unidades, puntos de seguridad, marchamos iniciales y reemplazos técnicos.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'DIESEL_AUDITOR',
                'nombre' => 'Auditor Diesel Cop',
                'alcance' => 'diesel_cop',
                'descripcion' => 'Rol interno de Diesel Cop con acceso global de consulta, revisión, auditoría y análisis, sin modificación de registros.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'EMPRESA_ADMIN',
                'nombre' => 'Administrador Empresa',
                'alcance' => 'empresa',
                'descripcion' => 'Rol máximo operativo dentro de una empresa cliente. Administra usuarios y operación de su propia empresa.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'EMPRESA_SUPERVISOR',
                'nombre' => 'Supervisor Empresa',
                'alcance' => 'empresa',
                'descripcion' => 'Rol de supervisión operativa dentro de una empresa cliente. Consulta operación, registra eventos y corrige abastecimientos.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'EMPRESA_OPERADOR',
                'nombre' => 'Operador Empresa',
                'alcance' => 'empresa',
                'descripcion' => 'Rol operativo de empresa para registrar abastecimientos y todo tipo de reemplazos permitidos dentro de su propia empresa.',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'EMPRESA_AUDITOR',
                'nombre' => 'Auditor Empresa',
                'alcance' => 'empresa',
                'descripcion' => 'Rol de empresa con acceso de consulta, revisión y análisis sobre la información de su propia empresa, sin modificación de registros.',
                'estado' => 'activo',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(
                ['codigo' => $role['codigo']],
                $role
            );
        }

        $rolConsultaAnterior = Role::where('codigo', 'EMPRESA_CONSULTA')->first();

        if ($rolConsultaAnterior) {
            $rolConsultaAnterior->update([
                'estado' => 'inactivo',
                'fecha_actualizacion' => now(),
                'fecha_inactivacion' => now(),
                'motivo_inactivacion' => 'Rol reemplazado por EMPRESA_AUDITOR en definición funcional V1.',
            ]);
        }
    }
}