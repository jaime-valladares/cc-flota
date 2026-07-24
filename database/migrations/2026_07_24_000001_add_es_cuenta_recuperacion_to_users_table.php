<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table
                ->boolean('es_cuenta_recuperacion')
                ->default(false)
                ->after('estado')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['es_cuenta_recuperacion']);
            $table->dropColumn('es_cuenta_recuperacion');
        });
    }
};