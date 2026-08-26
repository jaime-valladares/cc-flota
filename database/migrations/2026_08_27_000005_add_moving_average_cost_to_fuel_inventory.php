<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tanques', function (Blueprint $table) {
            $table->decimal('valor_inventario_actual', 24, 8)
                ->nullable()->after('volumen_actual');
            $table->decimal('costo_promedio_galon_actual', 18, 8)
                ->nullable()->after('valor_inventario_actual');
        });

        Schema::table('abastecimiento_tanques', function (Blueprint $table) {
            $table->decimal('costo_promedio_galon_snapshot', 18, 8)
                ->nullable()->after('galones_retirados');
            $table->decimal('costo_total_snapshot', 24, 8)
                ->nullable()->after('costo_promedio_galon_snapshot');
        });

        Schema::table('movimientos_inventario_combustible', function (Blueprint $table) {
            $table->decimal('valor_inventario_anterior', 24, 8)
                ->nullable()->after('volumen_resultante');
            $table->decimal('valor_movimiento', 24, 8)
                ->nullable()->after('valor_inventario_anterior');
            $table->decimal('valor_inventario_resultante', 24, 8)
                ->nullable()->after('valor_movimiento');
            $table->decimal('costo_unitario_aplicado', 18, 8)
                ->nullable()->after('valor_inventario_resultante');
        });

        DB::table('tanques')
            ->where('volumen_actual', 0)
            ->update(['valor_inventario_actual' => '0.00000000']);
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario_combustible', function (Blueprint $table) {
            $table->dropColumn([
                'valor_inventario_anterior',
                'valor_movimiento',
                'valor_inventario_resultante',
                'costo_unitario_aplicado',
            ]);
        });

        Schema::table('abastecimiento_tanques', function (Blueprint $table) {
            $table->dropColumn([
                'costo_promedio_galon_snapshot',
                'costo_total_snapshot',
            ]);
        });

        Schema::table('tanques', function (Blueprint $table) {
            $table->dropColumn([
                'valor_inventario_actual',
                'costo_promedio_galon_actual',
            ]);
        });
    }
};
