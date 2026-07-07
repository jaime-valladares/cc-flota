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
        Schema::create('gasolineras_externas', function (Blueprint $table) {
            $table->id();

            /*
             * Empresa que registra o autoriza esta gasolinera externa
             * dentro de su catálogo operativo.
             *
             * No implica propiedad física ni exclusividad comercial.
             */
            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->string('nombre', 150);
            $table->string('direccion', 255);

            /*
             * Campos preparados para uso futuro.
             * Se crean desde V1, pero pueden no mostrarse todavía
             * en los formularios principales.
             */
            $table->string('compania', 150)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 150)->nullable();

            $table->string('estado', 20)->default('activa');

            $table->timestamp('fecha_creacion')->useCurrent();
            $table->foreignId('creado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('fecha_actualizacion')->nullable();
            $table->foreignId('actualizado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('fecha_inactivacion')->nullable();
            $table->foreignId('inactivado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('motivo_inactivacion', 255)->nullable();

            $table->unique(['empresa_id', 'nombre']);

            $table->index('empresa_id');
            $table->index('nombre');
            $table->index('estado');
            $table->index('compania');
            $table->index('ciudad');
            $table->index('departamento');
            $table->index(['empresa_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gasolineras_externas');
    }
};