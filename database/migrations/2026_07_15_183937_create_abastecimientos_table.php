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
        Schema::create('abastecimientos', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | Relaciones principales
            |--------------------------------------------------------------------------
            */

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->restrictOnDelete();

            $table->foreignId('unidad_id')
                ->constrained('unidades')
                ->restrictOnDelete();

            $table->foreignId('motorista_id')
                ->constrained('motoristas')
                ->restrictOnDelete();

            $table->foreignId('abastecimiento_anterior_id')
                ->nullable()
                ->constrained('abastecimientos')
                ->restrictOnDelete();

            $table->foreignId('registrado_por')
                ->constrained('users')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | Fotografías históricas
            |--------------------------------------------------------------------------
            */

            $table->string(
                'empresa_nombre_snapshot',
                200
            );

            $table->string(
                'unidad_placa_snapshot',
                25
            );

            $table->string(
                'unidad_marca_snapshot',
                100
            )->nullable();

            $table->string(
                'unidad_modelo_snapshot',
                100
            )->nullable();

            $table->string(
                'motorista_nombre_snapshot',
                200
            );

            $table->string(
                'motorista_licencia_snapshot',
                100
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Información general de la operación
            |--------------------------------------------------------------------------
            */

            $table->dateTime(
                'fecha_hora_abastecimiento'
            );

            $table->string(
                'estado',
                20
            )->default('registrado');

            $table->string(
                'modelo_medicion',
                30
            );

            /*
            |--------------------------------------------------------------------------
            | Lecturas de la unidad
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'lectura_actual',
                14,
                2
            );

            $table->decimal(
                'lectura_anterior',
                14,
                2
            )->nullable();

            $table->decimal(
                'diferencia_lectura',
                14,
                2
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Combustible de la unidad
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'volumen_inicial',
                14,
                2
            );

            $table->decimal(
                'volumen_cargado',
                14,
                2
            );

            $table->decimal(
                'volumen_final',
                14,
                2
            );

            $table->decimal(
                'capacidad_cubierta_snapshot',
                14,
                2
            );

            /*
            |--------------------------------------------------------------------------
            | Cierre del ciclo anterior
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'volumen_final_anterior',
                14,
                2
            )->nullable();

            $table->decimal(
                'combustible_consumido_ciclo',
                14,
                2
            )->nullable();

            $table->decimal(
                'combustible_adicional_no_explicado',
                14,
                2
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Origen del combustible
            |--------------------------------------------------------------------------
            */

            $table->string(
                'tipo_origen',
                20
            );

            $table->foreignId(
                'gasolinera_interna_id'
            )
                ->nullable()
                ->constrained('gasolineras')
                ->restrictOnDelete();

            $table->foreignId(
                'gasolinera_externa_id'
            )
                ->nullable()
                ->constrained('gasolineras_externas')
                ->restrictOnDelete();

            $table->string(
                'origen_nombre_snapshot',
                200
            );

            /*
            |--------------------------------------------------------------------------
            | Información monetaria del origen externo
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'precio_galon',
                14,
                4
            )->nullable();

            $table->decimal(
                'total_pagado',
                14,
                2
            )->nullable();

            $table->char(
                'moneda',
                3
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Totales de rutas
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger(
                'total_rutas'
            )->default(0);

            $table->decimal(
                'kilometros_teoricos',
                14,
                2
            )->nullable();

            $table->decimal(
                'galones_teoricos',
                14,
                2
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Rendimiento del ciclo
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'galones_por_kilometro',
                18,
                6
            )->nullable();

            $table->decimal(
                'kilometros_por_galon',
                18,
                6
            )->nullable();

            $table->decimal(
                'galones_por_hora',
                18,
                6
            )->nullable();

            $table->decimal(
                'diferencia_kilometros_teoricos',
                14,
                2
            )->nullable();

            $table->decimal(
                'diferencia_galones_teoricos',
                14,
                2
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Resumen de tapones y marchamos
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger(
                'total_tapones_abiertos'
            )->default(0);

            $table->unsignedTinyInteger(
                'total_marchamos_reemplazados'
            )->default(0);

            /*
            |--------------------------------------------------------------------------
            | Anulación
            |--------------------------------------------------------------------------
            */

            $table->dateTime(
                'fecha_anulacion'
            )->nullable();

            $table->foreignId(
                'anulado_por'
            )
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->string(
                'motivo_anulacion',
                255
            )->nullable();

            /*
            |--------------------------------------------------------------------------
            | Auditoría Laravel
            |--------------------------------------------------------------------------
            */

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Índices
            |--------------------------------------------------------------------------
            */

            $table->index(
                [
                    'empresa_id',
                    'fecha_hora_abastecimiento',
                ],
                'abast_empresa_fecha_idx'
            );

            $table->index(
                [
                    'unidad_id',
                    'estado',
                    'fecha_hora_abastecimiento',
                ],
                'abast_unidad_estado_fecha_idx'
            );

            $table->index(
                [
                    'motorista_id',
                    'fecha_hora_abastecimiento',
                ],
                'abast_motorista_fecha_idx'
            );

            $table->index(
                [
                    'tipo_origen',
                    'fecha_hora_abastecimiento',
                ],
                'abast_origen_fecha_idx'
            );
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('abastecimientos');
    }
};