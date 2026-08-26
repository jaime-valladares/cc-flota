<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            $table->decimal('valor_combustible_abordo_actual', 24, 8)
                ->nullable()->after('capacidad_cubierta');
            $table->decimal('costo_promedio_abordo_actual', 18, 8)
                ->nullable()->after('valor_combustible_abordo_actual');
        });

        Schema::table('abastecimientos', function (Blueprint $table) {
            $table->decimal('rendimiento_teorico_km_galon_snapshot', 12, 4)
                ->nullable()->after('modelo_medicion');
            $table->decimal('rendimiento_teorico_gal_hora_snapshot', 12, 4)
                ->nullable()->after('rendimiento_teorico_km_galon_snapshot');
            $table->decimal('valor_carga_snapshot', 24, 8)
                ->nullable()->after('moneda');
            $table->decimal('costo_efectivo_carga_snapshot', 18, 8)
                ->nullable()->after('valor_carga_snapshot');
            $table->decimal('consumo_real_ciclo', 14, 2)
                ->nullable()->after('combustible_adicional_no_explicado');
            $table->decimal('consumo_teorico_ciclo', 24, 8)
                ->nullable()->after('consumo_real_ciclo');
            $table->decimal('diferencia_galones_ciclo', 24, 8)
                ->nullable()->after('consumo_teorico_ciclo');
            $table->decimal('costo_combustible_consumido_ciclo', 24, 8)
                ->nullable()->after('diferencia_galones_ciclo');
            $table->decimal('valor_remanente_antes_carga_snapshot', 24, 8)
                ->nullable()->after('costo_combustible_consumido_ciclo');
            $table->decimal('valor_abordo_resultante', 24, 8)
                ->nullable()->after('costo_efectivo_carga_snapshot');
            $table->decimal('costo_promedio_abordo_resultante', 18, 8)
                ->nullable()->after('valor_abordo_resultante');
            $table->unsignedInteger('total_viajes')
                ->default(0)->after('total_rutas');
            $table->unique(
                'abastecimiento_anterior_id',
                'abast_anterior_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('abastecimientos', function (Blueprint $table) {
            $table->dropUnique('abast_anterior_unique');
            $table->dropColumn([
                'rendimiento_teorico_km_galon_snapshot',
                'rendimiento_teorico_gal_hora_snapshot',
                'valor_carga_snapshot',
                'costo_efectivo_carga_snapshot',
                'consumo_real_ciclo',
                'consumo_teorico_ciclo',
                'diferencia_galones_ciclo',
                'costo_combustible_consumido_ciclo',
                'valor_remanente_antes_carga_snapshot',
                'valor_abordo_resultante',
                'costo_promedio_abordo_resultante',
                'total_viajes',
            ]);
        });

        Schema::table('unidades', function (Blueprint $table) {
            $table->dropColumn([
                'valor_combustible_abordo_actual',
                'costo_promedio_abordo_actual',
            ]);
        });
    }
};
