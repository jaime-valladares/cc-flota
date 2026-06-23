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
        Schema::create('reemplazo_marchamos_eventos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('unidad_id')
                ->constrained('unidades')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('motivo_reemplazo', [
                'dano',
                'desgaste',
                'perdida',
                'manipulacion_detectada',
                'correccion_instalacion',
            ]);

            $table->unsignedSmallInteger('cantidad_reemplazos')->default(0);

            $table->enum('origen_evento', [
                'reemplazo_general',
            ])->default('reemplazo_general');

            $table->enum('estado', [
                'registrado',
                'anulado',
            ])->default('registrado');

            $table->timestamp('fecha_registro')->useCurrent();

            $table->foreignId('registrado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('fecha_anulacion')->nullable();

            $table->foreignId('anulado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('motivo_anulacion', 150)->nullable();

            $table->timestamps();

            $table->index('empresa_id');
            $table->index('unidad_id');
            $table->index('motivo_reemplazo');
            $table->index('origen_evento');
            $table->index('estado');
            $table->index('fecha_registro');
            $table->index('registrado_por');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reemplazo_marchamos_eventos');
    }
};