<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('punto_origen_id')
                ->constrained('puntos_ruta')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('punto_destino_id')
                ->constrained('puntos_ruta')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('ruta', 255);

            $table->decimal('kilometros_estimados', 10, 2);
            $table->decimal('galones_estimados', 10, 2);

            $table->enum('estado', ['activo', 'inactivo'])
                ->default('activo')
                ->index();

            $table->dateTime('fecha_creacion')->useCurrent();
            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('fecha_actualizacion')->nullable();
            $table->foreignId('actualizado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('fecha_inactivacion')->nullable();
            $table->foreignId('inactivado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('motivo_inactivacion', 255)->nullable();

            $table->unique(
                ['empresa_id', 'punto_origen_id', 'punto_destino_id'],
                'rutas_empresa_origen_destino_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutas');
    }
};