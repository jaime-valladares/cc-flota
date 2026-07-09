<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gasolineras_externas', function (Blueprint $table) {
            $table->dropUnique('gasolineras_externas_empresa_id_nombre_unique');
        });

        Schema::table('gasolineras_externas', function (Blueprint $table) {
            $table->unique(
                ['empresa_id', 'compania', 'direccion'],
                'gas_ext_empresa_compania_direccion_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('gasolineras_externas', function (Blueprint $table) {
            $table->dropUnique('gas_ext_empresa_compania_direccion_unique');
        });

        Schema::table('gasolineras_externas', function (Blueprint $table) {
            $table->unique(
                ['empresa_id'],
                'gasolineras_externas_empresa_id_nombre_unique'
            );
        });
    }
};