<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unidades', function (Blueprint $table) {
            /*
             * Se conservan nullable para no inventar rendimientos en las
             * unidades demo existentes. Las nuevas altas y ediciones los
             * exigen desde la capa de aplicación.
             */
            $table->decimal('rendimiento_teorico_km_galon', 12, 4)
                ->nullable()
                ->after('modelo_medicion');

            $table->decimal('rendimiento_teorico_gal_hora', 12, 4)
                ->nullable()
                ->after('rendimiento_teorico_km_galon');
        });

        Schema::create('unidad_tanques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unidad_id')
                ->constrained('unidades')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('numero');
            $table->decimal('capacidad', 10, 2);
            $table->boolean('cubierto_por_licencia')->default(false);
            $table->timestamps();

            $table->unique(['unidad_id', 'numero']);
            $table->index(['unidad_id', 'cubierto_por_licencia']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidad_tanques');

        Schema::table('unidades', function (Blueprint $table) {
            $table->dropColumn([
                'rendimiento_teorico_km_galon',
                'rendimiento_teorico_gal_hora',
            ]);
        });
    }
};
