<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencia_renovaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licencia_id')
                ->constrained('licencias')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->date('fecha_vencimiento_anterior');
            $table->unsignedTinyInteger('periodo_agregado_meses');
            $table->date('fecha_vencimiento_nueva');
            $table->foreignId('renovado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->index(['licencia_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('licencia_renovaciones');
    }
};
