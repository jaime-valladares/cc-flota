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
        Schema::create('puntos_seguridad_unidad', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unidad_id')
                ->constrained('unidades')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedSmallInteger('orden');

            $table->string('codigo_punto', 50)->nullable();
            $table->string('grupo', 100)->nullable();
            $table->string('subgrupo', 100)->nullable();

            $table->string('nombre_punto', 150);
            $table->string('descripcion', 255)->nullable();

            $table->string('posicion_tanque', 50)->nullable();
            $table->string('tipo_punto', 80)->nullable();

            $table->boolean('requiere_marchamo')->default(true);

            $table->enum('plantilla_origen', [
                'plantilla_1_tanque',
                'plantilla_2_tanques',
                'plantilla_3_tanques',
            ]);

            $table->string('criterio_origen', 100)->nullable();

            $table->enum('estado_asignacion', [
                'pendiente',
                'asignado',
                'corregido',
            ])->default('pendiente');

            $table->unsignedBigInteger('marchamo_actual_id')->nullable();

            $table->enum('estado', [
                'activo',
                'inactivo',
            ])->default('activo');

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

            $table->unique(['unidad_id', 'orden']);

            $table->index('unidad_id');
            $table->index('estado');
            $table->index('estado_asignacion');
            $table->index('plantilla_origen');
            $table->index('marchamo_actual_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puntos_seguridad_unidad');
    }
};