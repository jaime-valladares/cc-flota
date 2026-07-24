<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class UsuariosPruebaRolesSeeder extends Seeder
{
    /**
     * Crea los siete usuarios de prueba restantes para validar
     * visibilidad, permisos, alcance y protección multiempresa.
     *
     * Este seeder:
     * - No modifica admin@cc-flota.local.
     * - Conserva la contraseña de una cuenta si ya existe.
     * - Asigna los cuatro usuarios empresariales a una sola empresa activa.
     * - Puede ejecutarse repetidamente sin duplicar usuarios.
     */
    public function run(): void
    {
        $empresaPrueba = Empresa::query()
            ->where('estado', 'activa')
            ->where(function ($query) {
                $query
                    ->where(
                        'nombre_legal',
                        'Carga Centroamericana, S.A. de C.V.'
                    )
                    ->orWhere(
                        'nombre_comercial',
                        'Carga Centroamericana'
                    );
            })
            ->first();

        if (! $empresaPrueba) {
            throw new RuntimeException(
                'No se encontró activa la empresa de prueba "Carga Centroamericana".'
            );
        }

        $usuariosPrueba = [
            [
                'rol_codigo' => 'DIESEL_ADMIN',
                'empresa_id' => null,
                'tipo_usuario' => User::TIPO_DIESEL_COP,
                'name' => 'Prueba',
                'apellido' => 'Administrador Diesel',
                'email' => 'prueba.diesel.admin@cc-flota.local',
                'cargo' => 'Administrador Diesel Cop de prueba',
            ],
            [
                'rol_codigo' => 'DIESEL_TECNICO',
                'empresa_id' => null,
                'tipo_usuario' => User::TIPO_DIESEL_COP,
                'name' => 'Prueba',
                'apellido' => 'Técnico Diesel',
                'email' => 'prueba.diesel.tecnico@cc-flota.local',
                'cargo' => 'Técnico Diesel Cop de prueba',
            ],
            [
                'rol_codigo' => 'DIESEL_AUDITOR',
                'empresa_id' => null,
                'tipo_usuario' => User::TIPO_DIESEL_COP,
                'name' => 'Prueba',
                'apellido' => 'Auditor Diesel',
                'email' => 'prueba.diesel.auditor@cc-flota.local',
                'cargo' => 'Auditor Diesel Cop de prueba',
            ],
            [
                'rol_codigo' => 'EMPRESA_ADMIN',
                'empresa_id' => $empresaPrueba->id,
                'tipo_usuario' => User::TIPO_EMPRESA,
                'name' => 'Prueba',
                'apellido' => 'Administrador Empresa',
                'email' => 'prueba.empresa.admin@cc-flota.local',
                'cargo' => 'Administrador de empresa de prueba',
            ],
            [
                'rol_codigo' => 'EMPRESA_SUPERVISOR',
                'empresa_id' => $empresaPrueba->id,
                'tipo_usuario' => User::TIPO_EMPRESA,
                'name' => 'Prueba',
                'apellido' => 'Supervisor Empresa',
                'email' => 'prueba.empresa.supervisor@cc-flota.local',
                'cargo' => 'Supervisor de empresa de prueba',
            ],
            [
                'rol_codigo' => 'EMPRESA_OPERADOR',
                'empresa_id' => $empresaPrueba->id,
                'tipo_usuario' => User::TIPO_EMPRESA,
                'name' => 'Prueba',
                'apellido' => 'Operador Empresa',
                'email' => 'prueba.empresa.operador@cc-flota.local',
                'cargo' => 'Operador de empresa de prueba',
            ],
            [
                'rol_codigo' => 'EMPRESA_AUDITOR',
                'empresa_id' => $empresaPrueba->id,
                'tipo_usuario' => User::TIPO_EMPRESA,
                'name' => 'Prueba',
                'apellido' => 'Auditor Empresa',
                'email' => 'prueba.empresa.auditor@cc-flota.local',
                'cargo' => 'Auditor de empresa de prueba',
            ],
        ];

        foreach ($usuariosPrueba as $datosUsuario) {
            $role = Role::query()
                ->where('codigo', $datosUsuario['rol_codigo'])
                ->where('estado', 'activo')
                ->firstOrFail();

            $usuario = User::query()->firstOrNew([
                'email' => $datosUsuario['email'],
            ]);

            $esNuevo = ! $usuario->exists;

            $usuario->fill([
                'empresa_id' => $datosUsuario['empresa_id'],
                'rol_id' => $role->id,
                'tipo_usuario' => $datosUsuario['tipo_usuario'],
                'name' => $datosUsuario['name'],
                'apellido' => $datosUsuario['apellido'],
                'telefono' => null,
                'cargo' => $datosUsuario['cargo'],
                'estado' => 'activo',
                'fecha_inactivacion' => null,
                'inactivado_por' => null,
                'motivo_inactivacion' => null,
            ]);

            if ($esNuevo) {
                $usuario->password = Hash::make('PruebaCCFlota2026!');
            }

            $usuario->save();
        }
    }
}