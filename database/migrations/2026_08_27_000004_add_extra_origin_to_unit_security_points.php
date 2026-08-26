<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE puntos_seguridad_unidad MODIFY plantilla_origen ENUM('plantilla_1_tanque', 'plantilla_2_tanques', 'plantilla_3_tanques', 'extra') NOT NULL"
            );
        }

        Schema::table('puntos_seguridad_unidad', function (Blueprint $table) {
            $table->unique(
                ['unidad_id', 'codigo_punto'],
                'puntos_seguridad_unidad_codigo_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('puntos_seguridad_unidad', function (Blueprint $table) {
            $table->dropUnique('puntos_seguridad_unidad_codigo_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE puntos_seguridad_unidad MODIFY plantilla_origen ENUM('plantilla_1_tanque', 'plantilla_2_tanques', 'plantilla_3_tanques') NOT NULL"
            );
        }
    }
};
