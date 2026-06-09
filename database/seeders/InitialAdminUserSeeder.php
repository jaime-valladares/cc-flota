<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InitialAdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdminRole = Role::where('codigo', 'DIESEL_SUPER_ADMIN')->firstOrFail();

        User::updateOrCreate(
            ['email' => 'admin@cc-flota.local'],
            [
                'empresa_id' => null,
                'rol_id' => $superAdminRole->id,
                'tipo_usuario' => 'diesel_cop',
                'name' => 'Administrador',
                'apellido' => 'Global',
                'email' => 'admin@cc-flota.local',
                'telefono' => null,
                'cargo' => 'Administrador Global',
                'estado' => 'activo',
                'password' => Hash::make('Admin12345!'),
            ]
        );
    }
}