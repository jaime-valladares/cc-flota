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
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();

            $table->string('nombre_legal', 150);
            $table->string('nombre_comercial', 150)->nullable();
            $table->string('nit', 50)->unique();

            $table->string('direccion', 255)->nullable();
            $table->string('telefono_empresa', 30)->nullable();
            $table->string('correo_empresa', 150)->nullable();

            $table->string('poc_nombre', 150)->nullable();
            $table->string('poc_email', 150)->nullable();
            $table->string('poc_telefono', 30)->nullable();

            $table->string('estado', 20)->default('activa');

            $table->timestamp('fecha_creacion')->useCurrent();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('fecha_actualizacion')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('fecha_inactivacion')->nullable();
            $table->foreignId('inactivado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo_inactivacion', 255)->nullable();

            $table->index('nombre_legal');
            $table->index('nombre_comercial');
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};