<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class RecoveryAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = strtolower(trim(
            (string) config('cc-flota.recovery_admin.email')
        ));

        if ($email === '') {
            throw new RuntimeException(
                'CC_FLOTA_RECOVERY_ADMIN_EMAIL no puede estar vacío.'
            );
        }

        $usuarioExistente = User::query()
            ->where('email', $email)
            ->first();

        /*
         * No se convierte silenciosamente una cuenta ordinaria en cuenta
         * protegida. Esto evita apropiación de cuentas por una variable de
         * entorno mal configurada.
         */
        if ($usuarioExistente) {
            if (! $usuarioExistente->esCuentaRecuperacion()) {
                throw new RuntimeException(
                    'Ya existe un usuario con el correo de recuperación, '
                    .'pero no está marcado como cuenta de recuperación.'
                );
            }

            /*
             * Una cuenta de recuperación existente nunca es modificada por
             * este seeder: contraseña, rol, estado y datos quedan intactos.
             */
            return;
        }

        $password = (string) config(
            'cc-flota.recovery_admin.password'
        );

        if ($password === '') {
            throw new RuntimeException(
                'Defina CC_FLOTA_RECOVERY_ADMIN_PASSWORD para crear '
                .'la cuenta de recuperación.'
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
            'name' => (string) config(
                'cc-flota.recovery_admin.name'
            ),
            'apellido' => (string) config(
                'cc-flota.recovery_admin.apellido'
            ),
            'email' => $email,
            'telefono' => null,
            'cargo' => 'Cuenta de recuperación',
            'estado' => 'activo',
            'es_cuenta_recuperacion' => true,
            'password' => Hash::make($password),
        ]);
    }
}