<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('puntos_ruta', function (Blueprint $table) {
            if (! Schema::hasColumn('puntos_ruta', 'direccion')) {
                $table->string('direccion', 255)
                    ->nullable()
                    ->after('nombre');
            }
        });
    }

    public function down(): void
    {
        Schema::table('puntos_ruta', function (Blueprint $table) {
            if (Schema::hasColumn('puntos_ruta', 'direccion')) {
                $table->dropColumn('direccion');
            }
        });
    }
};