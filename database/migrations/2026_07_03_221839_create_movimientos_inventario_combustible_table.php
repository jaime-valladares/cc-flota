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
        Schema::create('movimientos_inventario_combustible', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnDelete();

            $table->foreignId('tanque_id')
                ->constrained('tanques')
                ->cascadeOnDelete();

            /*
             * Se usará más adelante cuando el movimiento provenga
             * de un abastecimiento interno de unidad.
             */
            $table->unsignedBigInteger('abastecimiento_id')->nullable();

            /*
             * Valores V1:
             * - carga_inicial
             * - entrada_recarga
             * - salida_abastecimiento
             * - ajuste_manual
             */
            $table->string('tipo_movimiento', 40);

            $table->decimal('volumen_anterior', 10, 2);

            /*
             * Valores:
             * - entrada
             * - salida
             */
            $table->string('sentido_movimiento', 20);

            $table->decimal('volumen_movimiento', 10, 2);
            $table->decimal('volumen_resultante', 10, 2);

            $table->timestamp('fecha_hora_movimiento')->useCurrent();

            $table->text('observaciones')->nullable();

            $table->foreignId('usuario_registra_id')
                ->constrained('users')
                ->cascadeOnDelete();

            /*
             * Valores V1:
             * - registrado
             * - anulado
             */
            $table->string('estado', 20)->default('registrado');

            $table->timestamp('fecha_creacion')->useCurrent();

            $table->timestamp('fecha_actualizacion')->nullable();
            $table->foreignId('actualizado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('fecha_anulacion')->nullable();
            $table->foreignId('anulado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('motivo_anulacion', 255)->nullable();

            $table->index('empresa_id');
            $table->index('tanque_id');
            $table->index('abastecimiento_id');
            $table->index('tipo_movimiento');
            $table->index('sentido_movimiento');
            $table->index('fecha_hora_movimiento');
            $table->index('estado');
            $table->index(['empresa_id', 'estado'], 'mic_empresa_estado_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario_combustible');
    }
};