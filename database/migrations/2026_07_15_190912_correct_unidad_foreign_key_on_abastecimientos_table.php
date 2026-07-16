<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Corrige la llave foránea de unidad_id para que apunte
     * a la tabla real unidades.
     */
    public function up(): void
    {
        Schema::table('abastecimientos', function (Blueprint $table) {
            $table->dropForeign([
                'unidad_id',
            ]);
        });

        Schema::table('abastecimientos', function (Blueprint $table) {
            $table->foreign('unidad_id')
                ->references('id')
                ->on('unidades')
                ->restrictOnDelete();
        });
    }

    /**
     * Elimina la relación corregida al revertir.
     *
     * No se restaura la referencia incorrecta a unidads.
     */
    public function down(): void
    {
        Schema::table('abastecimientos', function (Blueprint $table) {
            $table->dropForeign([
                'unidad_id',
            ]);
        });
    }
};