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
        Schema::create('reemplazo_marchamos_detalle', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reemplazo_evento_id')
                ->constrained('reemplazo_marchamos_eventos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('punto_seguridad_id')
                ->constrained('puntos_seguridad_unidad')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('marchamo_anterior_id')
                ->constrained('marchamos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('marchamo_nuevo_id')
                ->constrained('marchamos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->timestamp('fecha_registro')->useCurrent();

            $table->timestamps();

            $table->unique(['reemplazo_evento_id', 'punto_seguridad_id'], 'reemplazo_detalle_evento_punto_unique');
            $table->unique('marchamo_anterior_id', 'reemplazo_detalle_marchamo_anterior_unique');
            $table->unique('marchamo_nuevo_id', 'reemplazo_detalle_marchamo_nuevo_unique');

            $table->index('reemplazo_evento_id');
            $table->index('punto_seguridad_id');
            $table->index('marchamo_anterior_id');
            $table->index('marchamo_nuevo_id');
            $table->index('fecha_registro');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reemplazo_marchamos_detalle');
    }
};