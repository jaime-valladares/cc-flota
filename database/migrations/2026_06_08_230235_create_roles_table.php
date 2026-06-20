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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 50)->unique();
            $table->string('nombre', 100);
            $table->string('alcance', 30);
            $table->text('descripcion')->nullable();

            $table->string('estado', 20)->default('activo');

            $table->timestamp('fecha_creacion')->useCurrent();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('fecha_actualizacion')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('fecha_inactivacion')->nullable();
            $table->foreignId('inactivado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo_inactivacion', 255)->nullable();

            $table->index('codigo');
            $table->index('alcance');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};