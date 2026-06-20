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
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('placa', 30);
            $table->string('marca', 100)->nullable();

            $table->unsignedTinyInteger('total_tanques');
            $table->unsignedTinyInteger('cantidad_tanques_con_licencia');

            $table->decimal('capacidad_total', 10, 2);
            $table->decimal('capacidad_cubierta', 10, 2);

            $table->enum('modelo_medicion', [
                'galones_hora',
                'galones_kilometro',
                'galones_viaje',
            ]);

            $table->enum('estado', [
                'registrada',
                'activa',
                'inactiva',
            ])->default('registrada');

            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('actualizado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('fecha_inactivacion')->nullable();

            $table->foreignId('inactivado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('motivo_inactivacion', 150)->nullable();

            $table->timestamps();

            $table->unique('placa');

            $table->index('empresa_id');
            $table->index('estado');
            $table->index('modelo_medicion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};