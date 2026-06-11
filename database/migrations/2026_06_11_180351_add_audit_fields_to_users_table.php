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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('ultimo_acceso')
                ->nullable()
                ->after('estado');

            $table->foreignId('creado_por')
                ->nullable()
                ->after('remember_token')
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('actualizado_por')
                ->nullable()
                ->after('creado_por')
                ->constrained('users')
                ->nullOnDelete();

            $table->index('creado_por');
            $table->index('actualizado_por');
            $table->index('ultimo_acceso');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['creado_por']);
            $table->dropForeign(['actualizado_por']);

            $table->dropIndex(['creado_por']);
            $table->dropIndex(['actualizado_por']);
            $table->dropIndex(['ultimo_acceso']);

            $table->dropColumn([
                'ultimo_acceso',
                'creado_por',
                'actualizado_por',
            ]);
        });
    }
};