<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motoristas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nombres', 100);
            $table->string('apellidos', 100);

            $table->string('licencia', 14);
            $table->string('telefono', 9);

            $table->string('estado', 20)->default('activo');

            $table->timestamp('fecha_creacion')->useCurrent();
            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('fecha_actualizacion')->nullable();
            $table->foreignId('actualizado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('fecha_inactivacion')->nullable();
            $table->foreignId('inactivado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('motivo_inactivacion', 255)->nullable();

            $table->unique(['empresa_id', 'licencia']);

            $table->index('empresa_id');
            $table->index('nombres');
            $table->index('apellidos');
            $table->index('licencia');
            $table->index('estado');
            $table->index(['empresa_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('motoristas');
    }
};