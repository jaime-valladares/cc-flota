<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('marchamos')) {
            return;
        }

        $this->dropIndexIfExists('marchamos', 'marchamos_empresa_id_codigo_marchamo_unique');
        $this->dropIndexIfExists('marchamos', 'marchamos_punto_seguridad_id_activo_actual_unique');

        Schema::table('marchamos', function (Blueprint $table) {
            $table->unique('codigo_marchamo', 'marchamos_codigo_marchamo_unique');
            $table->index(['punto_seguridad_id', 'activo_actual'], 'marchamos_punto_activo_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('marchamos')) {
            return;
        }

        $this->dropIndexIfExists('marchamos', 'marchamos_codigo_marchamo_unique');
        $this->dropIndexIfExists('marchamos', 'marchamos_punto_activo_index');

        Schema::table('marchamos', function (Blueprint $table) {
            $table->unique(['empresa_id', 'codigo_marchamo'], 'marchamos_empresa_id_codigo_marchamo_unique');
            $table->unique(['punto_seguridad_id', 'activo_actual'], 'marchamos_punto_seguridad_id_activo_actual_unique');
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $databaseName = DB::getDatabaseName();

        $indexExists = DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();

        if ($indexExists) {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }
    }
};