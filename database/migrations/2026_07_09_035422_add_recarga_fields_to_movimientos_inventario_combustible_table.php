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
        Schema::table('movimientos_inventario_combustible', function (Blueprint $table) {
            $table->unsignedBigInteger('recarga_combustible_id')
                ->nullable()
                ->after('abastecimiento_id');

            $table->decimal('subtotal_compra', 14, 2)
                ->nullable()
                ->after('volumen_resultante');

            $table->foreign('recarga_combustible_id', 'mic_recarga_fk')
                ->references('id')
                ->on('recargas_combustible')
                ->nullOnDelete();

            $table->index('recarga_combustible_id', 'mic_recarga_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movimientos_inventario_combustible', function (Blueprint $table) {
            $table->dropForeign('mic_recarga_fk');
            $table->dropIndex('mic_recarga_idx');

            $table->dropColumn([
                'recarga_combustible_id',
                'subtotal_compra',
            ]);
        });
    }
};