<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const CODIGO = 'reportes.gestion-combustible-motorista.consultar';

    private const ROLES = [
        'DIESEL_ADMIN',
        'DIESEL_AUDITOR',
        'EMPRESA_ADMIN',
        'EMPRESA_SUPERVISOR',
        'EMPRESA_AUDITOR',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('permisos')->updateOrInsert(
                ['codigo' => self::CODIGO],
                [
                    'modulo' => 'reportes',
                    'accion' => 'consultar',
                    'nombre' => 'Consultar gestión de combustible por motorista',
                    'descripcion' => 'Permite consultar el reporte consolidado de gestión de combustible por motorista, sus fichas de detalle y exportaciones PDF según el alcance autorizado.',
                    'alcance' => 'ambos',
                    'estado' => 'activo',
                    'fecha_actualizacion' => now(),
                ]
            );

            $permisoId = DB::table('permisos')->where('codigo', self::CODIGO)->value('id');
            $rolIds = DB::table('roles')->whereIn('codigo', self::ROLES)->pluck('id');

            foreach ($rolIds as $rolId) {
                DB::table('rol_permisos')->updateOrInsert(
                    ['rol_id' => $rolId, 'permiso_id' => $permisoId],
                    ['fecha_creacion' => now()]
                );
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $permisoId = DB::table('permisos')->where('codigo', self::CODIGO)->value('id');

            if ($permisoId) {
                DB::table('rol_permisos')->where('permiso_id', $permisoId)->delete();
                DB::table('permisos')->where('id', $permisoId)->delete();
            }
        });
    }
};
