<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('gasolineras_externas')) {
            return;
        }

        $indices = DB::select("
            SHOW INDEX FROM gasolineras_externas
            WHERE Key_name = 'gasolineras_externas_empresa_id_nombre_unique'
        ");

        if (! empty($indices)) {
            DB::statement("
                ALTER TABLE gasolineras_externas
                DROP INDEX gasolineras_externas_empresa_id_nombre_unique
            ");
        }

        $indiceNuevo = DB::select("
            SHOW INDEX FROM gasolineras_externas
            WHERE Key_name = 'gas_ext_empresa_compania_direccion_unique'
        ");

        if (empty($indiceNuevo)) {
            DB::statement("
                ALTER TABLE gasolineras_externas
                ADD UNIQUE gas_ext_empresa_compania_direccion_unique
                (empresa_id, compania, direccion)
            ");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('gasolineras_externas')) {
            return;
        }

        $indiceNuevo = DB::select("
            SHOW INDEX FROM gasolineras_externas
            WHERE Key_name = 'gas_ext_empresa_compania_direccion_unique'
        ");

        if (! empty($indiceNuevo)) {
            DB::statement("
                ALTER TABLE gasolineras_externas
                DROP INDEX gas_ext_empresa_compania_direccion_unique
            ");
        }
    }
};