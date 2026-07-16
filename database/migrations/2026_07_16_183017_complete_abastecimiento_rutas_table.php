<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completar la estructura de abastecimiento_rutas.
     */
    public function up(): void
    {
        Schema::table(
            'abastecimiento_rutas',
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

                $table->foreignId('ruta_id')
                    ->after('abastecimiento_id')
                    ->constrained('rutas')
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | Orden y tipo de recorrido
                |--------------------------------------------------------------------------
                */

                $table->unsignedSmallInteger('orden')
                    ->after('ruta_id');

                $table->string(
                    'tipo_recorrido',
                    20
                )->after('orden');

                $table->unsignedTinyInteger(
                    'factor_recorrido'
                )->after('tipo_recorrido');

                /*
                |--------------------------------------------------------------------------
                | Fotografías históricas de la ruta
                |--------------------------------------------------------------------------
                */

                $table->string(
                    'ruta_nombre_snapshot',
                    200
                )->after('factor_recorrido');

                $table->foreignId('punto_origen_id')
                    ->after('ruta_nombre_snapshot')
                    ->constrained('puntos_ruta')
                    ->restrictOnDelete();

                $table->foreignId('punto_destino_id')
                    ->after('punto_origen_id')
                    ->constrained('puntos_ruta')
                    ->restrictOnDelete();

                $table->string(
                    'punto_origen_nombre_snapshot',
                    150
                )->after('punto_destino_id');

                $table->string(
                    'punto_destino_nombre_snapshot',
                    150
                )->after('punto_origen_nombre_snapshot');

                /*
                |--------------------------------------------------------------------------
                | Valores base y aplicados
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'kilometros_base_snapshot',
                    14,
                    2
                )->after('punto_destino_nombre_snapshot');

                $table->decimal(
                    'galones_base_snapshot',
                    14,
                    2
                )->after('kilometros_base_snapshot');

                $table->decimal(
                    'kilometros_aplicados',
                    14,
                    2
                )->after('galones_base_snapshot');

                $table->decimal(
                    'galones_aplicados',
                    14,
                    2
                )->after('kilometros_aplicados');

                /*
                |--------------------------------------------------------------------------
                | Índices
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'abastecimiento_id',
                        'orden',
                    ],
                    'abast_rutas_abast_orden_idx'
                );

                $table->index(
                    [
                        'ruta_id',
                        'abastecimiento_id',
                    ],
                    'abast_rutas_ruta_abast_idx'
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
            'abastecimiento_rutas',
            function (Blueprint $table) {
                $table->dropIndex(
                    'abast_rutas_abast_orden_idx'
                );

                $table->dropIndex(
                    'abast_rutas_ruta_abast_idx'
                );

                $table->dropForeign([
                    'abastecimiento_id',
                ]);

                $table->dropForeign([
                    'ruta_id',
                ]);

                $table->dropForeign([
                    'punto_origen_id',
                ]);

                $table->dropForeign([
                    'punto_destino_id',
                ]);

                $table->dropColumn([
                    'abastecimiento_id',
                    'ruta_id',
                    'orden',
                    'tipo_recorrido',
                    'factor_recorrido',
                    'ruta_nombre_snapshot',
                    'punto_origen_id',
                    'punto_destino_id',
                    'punto_origen_nombre_snapshot',
                    'punto_destino_nombre_snapshot',
                    'kilometros_base_snapshot',
                    'galones_base_snapshot',
                    'kilometros_aplicados',
                    'galones_aplicados',
                ]);
            }
        );
    }
};