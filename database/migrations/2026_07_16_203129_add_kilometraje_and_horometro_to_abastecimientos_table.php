<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agregar lecturas separadas de kilometraje y horómetro.
     */
    public function up(): void
    {
        Schema::table(
            'abastecimientos',
            function (Blueprint $table) {
                /*
                |--------------------------------------------------------------------------
                | Kilometraje
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'kilometraje_actual',
                    14,
                    2
                )
                    ->nullable()
                    ->after('diferencia_lectura');

                $table->decimal(
                    'kilometraje_anterior',
                    14,
                    2
                )
                    ->nullable()
                    ->after('kilometraje_actual');

                $table->decimal(
                    'diferencia_kilometraje',
                    14,
                    2
                )
                    ->nullable()
                    ->after('kilometraje_anterior');

                /*
                |--------------------------------------------------------------------------
                | Horómetro
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'horometro_actual',
                    14,
                    2
                )
                    ->nullable()
                    ->after('diferencia_kilometraje');

                $table->decimal(
                    'horometro_anterior',
                    14,
                    2
                )
                    ->nullable()
                    ->after('horometro_actual');

                $table->decimal(
                    'diferencia_horometro',
                    14,
                    2
                )
                    ->nullable()
                    ->after('horometro_anterior');

                /*
                |--------------------------------------------------------------------------
                | Índices de consulta
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'unidad_id',
                        'kilometraje_actual',
                    ],
                    'abast_unidad_kilometraje_idx'
                );

                $table->index(
                    [
                        'unidad_id',
                        'horometro_actual',
                    ],
                    'abast_unidad_horometro_idx'
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
            'abastecimientos',
            function (Blueprint $table) {
                $table->dropIndex(
                    'abast_unidad_kilometraje_idx'
                );

                $table->dropIndex(
                    'abast_unidad_horometro_idx'
                );

                $table->dropColumn([
                    'kilometraje_actual',
                    'kilometraje_anterior',
                    'diferencia_kilometraje',
                    'horometro_actual',
                    'horometro_anterior',
                    'diferencia_horometro',
                ]);
            }
        );
    }
};