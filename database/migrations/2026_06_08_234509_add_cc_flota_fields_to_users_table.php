<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('empresa_id')
                ->nullable()
                ->after('id')
                ->constrained('empresas')
                ->nullOnDelete();

            $table->foreignId('rol_id')
                ->nullable()
                ->after('empresa_id')
                ->constrained('roles')
                ->nullOnDelete();

            $table->string('tipo_usuario', 30)
                ->after('rol_id');

            $table->string('apellido', 100)
                ->nullable()
                ->after('name');

            $table->string('telefono', 30)
                ->nullable()
                ->after('email');

            $table->string('cargo', 100)
                ->nullable()
                ->after('telefono');

            $table->string('estado', 20)
                ->default('activo')
                ->after('cargo');

            $table->timestamp('fecha_inactivacion')
                ->nullable()
                ->after('estado');

            $table->foreignId('inactivado_por')
                ->nullable()
                ->after('fecha_inactivacion')
                ->constrained('users')
                ->nullOnDelete();

            $table->string('motivo_inactivacion', 255)
                ->nullable()
                ->after('inactivado_por');

            $table->index('empresa_id');
            $table->index('rol_id');
            $table->index('tipo_usuario');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
            $table->dropForeign(['rol_id']);
            $table->dropForeign(['inactivado_por']);

            $table->dropIndex(['empresa_id']);
            $table->dropIndex(['rol_id']);
            $table->dropIndex(['tipo_usuario']);
            $table->dropIndex(['estado']);

            $table->dropColumn([
                'empresa_id',
                'rol_id',
                'tipo_usuario',
                'apellido',
                'telefono',
                'cargo',
                'estado',
                'fecha_inactivacion',
                'inactivado_por',
                'motivo_inactivacion',
            ]);
        });
    }
};