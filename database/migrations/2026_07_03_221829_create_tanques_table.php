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
        Schema::create('tanques', function (Blueprint $table) {
            $table->id();

            $table->foreignId('gasolinera_id')
                ->constrained('gasolineras')
                ->cascadeOnDelete();

            $table->string('nombre', 100);

            $table->decimal('capacidad_total', 10, 2);
            $table->decimal('volumen_actual', 10, 2)->default(0);
            $table->decimal('volumen_minimo_alerta', 10, 2);

            $table->string('estado', 20)->default('activo');

            /*
             * Permite distinguir tanques inactivados manualmente
             * de tanques inactivados automáticamente por caída de la gasolinera.
             */
            $table->boolean('inactivado_por_gasolinera')->default(false);

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

            $table->unique(['gasolinera_id', 'nombre']);

            $table->index('gasolinera_id');
            $table->index('estado');
            $table->index('inactivado_por_gasolinera');
            $table->index(['gasolinera_id', 'estado']);
            $table->index(['volumen_actual', 'volumen_minimo_alerta']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tanques');
    }
};