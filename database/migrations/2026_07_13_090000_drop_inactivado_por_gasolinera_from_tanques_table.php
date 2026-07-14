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
        Schema::table('tanques', function (Blueprint $table) {
            $table->dropIndex(['inactivado_por_gasolinera']);
            $table->dropColumn('inactivado_por_gasolinera');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tanques', function (Blueprint $table) {
            $table->boolean('inactivado_por_gasolinera')
                ->default(false)
                ->after('estado');

            $table->index('inactivado_por_gasolinera');
        });
    }
};