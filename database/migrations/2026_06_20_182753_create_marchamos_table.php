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
        Schema::create('marchamos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('unidad_id')
                ->constrained('unidades')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('punto_seguridad_id')
                ->constrained('puntos_seguridad_unidad')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            /*
             * Código físico del marchamo.
             * Se guarda como CHAR(7) para conservar ceros a la izquierda.
             * Ejemplo válido: 0006387.
             */
            $table->char('codigo_marchamo', 7);

            $table->timestamp('fecha_activacion');

            $table->enum('estado', [
                'activo',
                'reemplazado',
                'anulado',
            ])->default('activo');

            /*
             * Protección técnica:
             * activo_actual = 1 solo para el marchamo activo.
             * activo_actual = NULL para históricos reemplazados/anulados.
             *
             * MySQL permite múltiples NULL en índices UNIQUE,
             * por eso esta estructura permite muchos históricos,
             * pero solo un activo por punto.
             */
            $table->unsignedTinyInteger('activo_actual')->nullable()->default(1);

            $table->timestamp('fecha_desactivacion')->nullable();

            $table->string('motivo_desactivacion', 80)->nullable();

            $table->enum('origen_creacion', [
                'asignacion_inicial',
                'abastecimiento',
                'reemplazo_dano_desgaste',
                'correccion',
            ])->default('asignacion_inicial');

            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('actualizado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['empresa_id', 'codigo_marchamo']);
            $table->unique(['punto_seguridad_id', 'activo_actual']);

            $table->index('empresa_id');
            $table->index('unidad_id');
            $table->index('punto_seguridad_id');
            $table->index('codigo_marchamo');
            $table->index('estado');
            $table->index('activo_actual');
            $table->index('fecha_activacion');
            $table->index('origen_creacion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marchamos');
    }
};