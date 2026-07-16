<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración.
     */
    public function up(): void
    {
        Schema::create(
            'abastecimiento_tanques',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | Relaciones
                |--------------------------------------------------------------------------
                */

                $table->foreignId('abastecimiento_id')
                    ->constrained('abastecimientos')
                    ->cascadeOnDelete();

                $table->foreignId('tanque_id')
                    ->constrained('tanques')
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Orden dentro de la operación
                |--------------------------------------------------------------------------
                */

                $table->unsignedSmallInteger('orden');

                /*
                |--------------------------------------------------------------------------
                | Fotografías históricas
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'tanque_nombre_snapshot',
                    100
                );

                $table->decimal(
                    'capacidad_total_snapshot',
                    10,
                    2
                );

                $table->decimal(
                    'volumen_minimo_alerta_snapshot',
                    10,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | Movimiento de inventario
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'inventario_anterior',
                    10,
                    2
                );

                $table->decimal(
                    'galones_retirados',
                    10,
                    2
                );

                $table->decimal(
                    'inventario_resultante',
                    10,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | Indicadores históricos
                |--------------------------------------------------------------------------
                */

                $table->boolean(
                    'quedo_bajo_minimo'
                )->default(false);

                /*
                |--------------------------------------------------------------------------
                | Auditoría Laravel
                |--------------------------------------------------------------------------
                */

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | Restricciones e índices
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'abastecimiento_id',
                        'tanque_id',
                    ],
                    'abast_tanque_operacion_unique'
                );

                $table->unique(
                    [
                        'abastecimiento_id',
                        'orden',
                    ],
                    'abast_tanque_orden_unique'
                );

                $table->index(
                    [
                        'tanque_id',
                        'created_at',
                    ],
                    'abast_tanque_fecha_idx'
                );
            }
        );
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'abastecimiento_tanques'
        );
    }
};