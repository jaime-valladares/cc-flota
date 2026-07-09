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
        Schema::create('recargas_combustible', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnDelete();

            $table->foreignId('gasolinera_id')
                ->constrained('gasolineras')
                ->cascadeOnDelete();

            /*
             * Precio del galón aplicado a toda la operación de recarga.
             */
            $table->decimal('precio_galon', 10, 4);

            /*
             * Totales consolidados de la compra.
             */
            $table->decimal('total_galones', 12, 2);
            $table->decimal('total_compra', 14, 2);

            $table->timestamp('fecha_hora_recarga')->useCurrent();

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
            $table->index('gasolinera_id');
            $table->index('fecha_hora_recarga');
            $table->index('estado');
            $table->index(['empresa_id', 'estado'], 'rc_empresa_estado_idx');
            $table->index(['gasolinera_id', 'estado'], 'rc_gasolinera_estado_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recargas_combustible');
    }
};