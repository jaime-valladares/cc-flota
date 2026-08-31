<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class InitialAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim(
            (string) config('cc-flota.initial_admin.email')
        ));

        if ($email === '') {
            throw new RuntimeException(
                'CC_FLOTA_INITIAL_ADMIN_EMAIL no puede estar vacío.'
            );
        }

        /*
         * Una cuenta existente se conserva exactamente como está.
         * El seeder nunca cambia contraseña, rol, estado ni datos personales.
         */
        if (User::query()->where('email', $email)->exists()) {
            return;
        }

        $password = (string) config(
            'cc-flota.initial_admin.password'
        );

        if ($password === '') {
            throw new RuntimeException(
                'Defina CC_FLOTA_INITIAL_ADMIN_PASSWORD para crear '
                .'el administrador inicial en una instalación nueva.'
            );
        }

        if (mb_strlen($password) < 12) {
            throw new RuntimeException(
                'CC_FLOTA_INITIAL_ADMIN_PASSWORD debe tener al menos 12 caracteres.'
            );
        }

        $superAdminRole = Role::query()
            ->where('codigo', User::ROL_DIESEL_SUPER_ADMIN)
            ->where('estado', 'activo')
            ->firstOrFail();

        User::query()->create([
            'empresa_id' => null,
            'rol_id' => $superAdminRole->id,
            'tipo_usuario' => User::TIPO_DIESEL_COP,
            'name' => 'Administrador',
            'apellido' => 'Global',
            'email' => $email,
            'telefono' => null,
            'cargo' => 'Administrador Global',
            'estado' => 'activo',
            'es_cuenta_recuperacion' => false,
            'password' => Hash::make($password),
        ]);
    }
}
