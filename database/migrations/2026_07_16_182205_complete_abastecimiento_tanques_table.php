<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completar la estructura de abastecimiento_tanques.
     */
    public function up(): void
    {
        Schema::table(
            'abastecimiento_tanques',
            function (Blueprint $table) {
                /*
                |--------------------------------------------------------------------------
                | Relaciones principales
                |--------------------------------------------------------------------------
                */

                $table->foreignId('abastecimiento_id')
                    ->after('id')
                    ->constrained('abastecimientos')
                    ->restrictOnDelete();

                $table->foreignId('tanque_id')
                    ->after('abastecimiento_id')
                    ->constrained('tanques')
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Orden dentro de la operación
                |--------------------------------------------------------------------------
                */

                $table->unsignedSmallInteger('orden')
                    ->after('tanque_id');

                /*
                |--------------------------------------------------------------------------
                | Fotografías históricas del tanque
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'tanque_nombre_snapshot',
                    150
                )->after('orden');

                $table->decimal(
                    'capacidad_total_snapshot',
                    14,
                    2
                )->after('tanque_nombre_snapshot');

                $table->decimal(
                    'volumen_minimo_alerta_snapshot',
                    14,
                    2
                )->after('capacidad_total_snapshot');

                /*
                |--------------------------------------------------------------------------
                | Movimiento de inventario
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'inventario_anterior',
                    14,
                    2
                )->after('volumen_minimo_alerta_snapshot');

                $table->decimal(
                    'galones_retirados',
                    14,
                    2
                )->after('inventario_anterior');

                $table->decimal(
                    'inventario_resultante',
                    14,
                    2
                )->after('galones_retirados');

                $table->boolean('quedo_bajo_minimo')
                    ->default(false)
                    ->after('inventario_resultante');

                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->unique(
                    [
                        'abastecimiento_id',
                        'tanque_id',
                    ],
                    'abast_tanques_abast_tanque_unique'
                );

                $table->index(
                    [
                        'tanque_id',
                        'abastecimiento_id',
                    ],
                    'abast_tanques_tanque_abast_idx'
                );

                $table->index(
                    [
                        'abastecimiento_id',
                        'orden',
                    ],
                    'abast_tanques_abast_orden_idx'
                );
            }
        );
    }

    /**
     * Revertir las columnas agregadas.
     */
    public function down(): void
    {
        Schema::table(
            'abastecimiento_tanques',
            function (Blueprint $table) {
                $table->dropUnique(
                    'abast_tanques_abast_tanque_unique'
                );

                $table->dropIndex(
                    'abast_tanques_tanque_abast_idx'
                );

                $table->dropIndex(
                    'abast_tanques_abast_orden_idx'
                );

                $table->dropForeign([
                    'abastecimiento_id',
                ]);

                $table->dropForeign([
                    'tanque_id',
                ]);

                $table->dropColumn([
                    'abastecimiento_id',
                    'tanque_id',
                    'orden',
                    'tanque_nombre_snapshot',
                    'capacidad_total_snapshot',
                    'volumen_minimo_alerta_snapshot',
                    'inventario_anterior',
                    'galones_retirados',
                    'inventario_resultante',
                    'quedo_bajo_minimo',
                ]);
            }
        );
    }
};