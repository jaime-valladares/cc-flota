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
            'abastecimiento_rutas',
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

                $table->foreignId('ruta_id')
                    ->constrained('rutas')
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Orden dentro del abastecimiento
                |--------------------------------------------------------------------------
                */

                $table->unsignedSmallInteger('orden');

                /*
                |--------------------------------------------------------------------------
                | Tipo de recorrido
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'tipo_recorrido',
                    20
                );

                $table->unsignedTinyInteger(
                    'factor_recorrido'
                );

                /*
                |--------------------------------------------------------------------------
                | Fotografías históricas de la ruta
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'ruta_nombre_snapshot',
                    255
                );

                $table->foreignId(
                    'punto_origen_id'
                )
                    ->nullable()
                    ->constrained('puntos_ruta')
                    ->restrictOnDelete();

                $table->foreignId(
                    'punto_destino_id'
                )
                    ->nullable()
                    ->constrained('puntos_ruta')
                    ->restrictOnDelete();

                $table->string(
                    'punto_origen_nombre_snapshot',
                    200
                );

                $table->string(
                    'punto_destino_nombre_snapshot',
                    200
                );

                /*
                |--------------------------------------------------------------------------
                | Valores base de la ruta
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'kilometros_base_snapshot',
                    10,
                    2
                );

                $table->decimal(
                    'galones_base_snapshot',
                    10,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | Valores aplicados según el recorrido
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'kilometros_aplicados',
                    14,
                    2
                );

                $table->decimal(
                    'galones_aplicados',
                    14,
                    2
                );

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
                        'orden',
                    ],
                    'abast_ruta_orden_unique'
                );

                $table->index(
                    [
                        'ruta_id',
                        'created_at',
                    ],
                    'abast_ruta_fecha_idx'
                );

                $table->index(
                    [
                        'abastecimiento_id',
                        'tipo_recorrido',
                    ],
                    'abast_ruta_tipo_idx'
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
            'abastecimiento_rutas'
        );
    }
};