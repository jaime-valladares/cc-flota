<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega la relación entre los movimientos de inventario
     * y el abastecimiento interno que los originó.
     */
    public function up(): void
    {
        Schema::table(
            'movimientos_inventario_combustible',
            function (Blueprint $table) {
                $table->foreign('abastecimiento_id')
                    ->references('id')
                    ->on('abastecimientos')
                    ->restrictOnDelete();
            }
        );
    }

    /**
     * Elimina únicamente la llave foránea.
     *
     * La columna y su índice original permanecen porque fueron
     * creados en la migración inicial de movimientos.
     */
    public function down(): void
    {
        Schema::table(
            'movimientos_inventario_combustible',
            function (Blueprint $table) {
                $table->dropForeign([
                    'abastecimiento_id',
                ]);
            }
        );
    }
};