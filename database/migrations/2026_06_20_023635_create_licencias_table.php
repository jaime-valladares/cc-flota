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
        Schema::create('licencias', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('unidad_id')
                ->unique()
                ->constrained('unidades')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedTinyInteger('periodo_vigencia_meses');

            $table->date('fecha_activacion');
            $table->date('fecha_vencimiento');

            $table->enum('estado', [
                'activa',
                'inactiva',
            ])->default('activa');

            $table->enum('plantilla_puntos_seguridad', [
                'plantilla_1_tanque',
                'plantilla_2_tanques',
                'plantilla_3_tanques',
            ]);

            $table->text('observaciones')->nullable();

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

            $table->index('empresa_id');
            $table->index('estado');
            $table->index('periodo_vigencia_meses');
            $table->index('fecha_activacion');
            $table->index('fecha_vencimiento');
            $table->index('plantilla_puntos_seguridad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licencias');
    }
};