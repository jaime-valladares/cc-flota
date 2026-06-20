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
        Schema::table('puntos_seguridad_unidad', function (Blueprint $table) {
            $table->foreign('marchamo_actual_id')
                ->references('id')
                ->on('marchamos')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('puntos_seguridad_unidad', function (Blueprint $table) {
            $table->dropForeign(['marchamo_actual_id']);
        });
    }
};