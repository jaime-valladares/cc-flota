<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completa la tabla abastecimientos que previamente
     * fue creada únicamente con id y timestamps.
     */
    public function up(): void
    {
        Schema::table('abastecimientos', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Relaciones principales
            |--------------------------------------------------------------------------
            */

            $table->foreignId('empresa_id')
                ->after('id')
                ->constrained('empresas')
                ->restrictOnDelete();

            $table->foreignId('unidad_id')
                ->after('empresa_id')
                ->constrained('unidades')
                ->restrictOnDelete();

            $table->foreignId('motorista_id')
                ->after('unidad_id')
                ->constrained('motoristas')
                ->restrictOnDelete();

            $table->foreignId('abastecimiento_anterior_id')
                ->nullable()
                ->after('motorista_id');

            $table->foreignId('registrado_por')
                ->after('abastecimiento_anterior_id')
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
            )->after('registrado_por');

            $table->string(
                'unidad_placa_snapshot',
                25
            )->after('empresa_nombre_snapshot');

            $table->string(
                'unidad_marca_snapshot',
                100
            )
                ->nullable()
                ->after('unidad_placa_snapshot');

            $table->string(
                'unidad_modelo_snapshot',
                100
            )
                ->nullable()
                ->after('unidad_marca_snapshot');

            $table->string(
                'motorista_nombre_snapshot',
                200
            )->after('unidad_modelo_snapshot');

            $table->string(
                'motorista_licencia_snapshot',
                100
            )
                ->nullable()
                ->after('motorista_nombre_snapshot');

            /*
            |--------------------------------------------------------------------------
            | Información general
            |--------------------------------------------------------------------------
            */

            $table->dateTime(
                'fecha_hora_abastecimiento'
            )->after('motorista_licencia_snapshot');

            $table->string(
                'estado',
                20
            )
                ->default('registrado')
                ->after('fecha_hora_abastecimiento');

            $table->string(
                'modelo_medicion',
                30
            )->after('estado');

            /*
            |--------------------------------------------------------------------------
            | Lecturas
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'lectura_actual',
                14,
                2
            )->after('modelo_medicion');

            $table->decimal(
                'lectura_anterior',
                14,
                2
            )
                ->nullable()
                ->after('lectura_actual');

            $table->decimal(
                'diferencia_lectura',
                14,
                2
            )
                ->nullable()
                ->after('lectura_anterior');

            /*
            |--------------------------------------------------------------------------
            | Combustible de la unidad
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'volumen_inicial',
                14,
                2
            )->after('diferencia_lectura');

            $table->decimal(
                'volumen_cargado',
                14,
                2
            )->after('volumen_inicial');

            $table->decimal(
                'volumen_final',
                14,
                2
            )->after('volumen_cargado');

            $table->decimal(
                'capacidad_cubierta_snapshot',
                14,
                2
            )->after('volumen_final');

            /*
            |--------------------------------------------------------------------------
            | Cierre del ciclo anterior
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'volumen_final_anterior',
                14,
                2
            )
                ->nullable()
                ->after('capacidad_cubierta_snapshot');

            $table->decimal(
                'combustible_consumido_ciclo',
                14,
                2
            )
                ->nullable()
                ->after('volumen_final_anterior');

            $table->decimal(
                'combustible_adicional_no_explicado',
                14,
                2
            )
                ->default(0)
                ->after('combustible_consumido_ciclo');

            /*
            |--------------------------------------------------------------------------
            | Origen del combustible
            |--------------------------------------------------------------------------
            */

            $table->string(
                'tipo_origen',
                20
            )->after('combustible_adicional_no_explicado');

            $table->foreignId(
                'gasolinera_interna_id'
            )
                ->nullable()
                ->after('tipo_origen')
                ->constrained('gasolineras')
                ->restrictOnDelete();

            $table->foreignId(
                'gasolinera_externa_id'
            )
                ->nullable()
                ->after('gasolinera_interna_id')
                ->constrained('gasolineras_externas')
                ->restrictOnDelete();

            $table->string(
                'origen_nombre_snapshot',
                200
            )->after('gasolinera_externa_id');

            /*
            |--------------------------------------------------------------------------
            | Información monetaria externa
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'precio_galon',
                14,
                4
            )
                ->nullable()
                ->after('origen_nombre_snapshot');

            $table->decimal(
                'total_pagado',
                14,
                2
            )
                ->nullable()
                ->after('precio_galon');

            $table->char(
                'moneda',
                3
            )
                ->nullable()
                ->after('total_pagado');

            /*
            |--------------------------------------------------------------------------
            | Totales de rutas
            |--------------------------------------------------------------------------
            */

            $table->unsignedInteger(
                'total_rutas'
            )
                ->default(0)
                ->after('moneda');

            $table->decimal(
                'kilometros_teoricos',
                14,
                2
            )
                ->nullable()
                ->after('total_rutas');

            $table->decimal(
                'galones_teoricos',
                14,
                2
            )
                ->nullable()
                ->after('kilometros_teoricos');

            /*
            |--------------------------------------------------------------------------
            | Rendimiento
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'galones_por_kilometro',
                18,
                6
            )
                ->nullable()
                ->after('galones_teoricos');

            $table->decimal(
                'kilometros_por_galon',
                18,
                6
            )
                ->nullable()
                ->after('galones_por_kilometro');

            $table->decimal(
                'galones_por_hora',
                18,
                6
            )
                ->nullable()
                ->after('kilometros_por_galon');

            $table->decimal(
                'diferencia_kilometros_teoricos',
                14,
                2
            )
                ->nullable()
                ->after('galones_por_hora');

            $table->decimal(
                'diferencia_galones_teoricos',
                14,
                2
            )
                ->nullable()
                ->after('diferencia_kilometros_teoricos');

            /*
            |--------------------------------------------------------------------------
            | Marchamos
            |--------------------------------------------------------------------------
            */

            $table->unsignedTinyInteger(
                'total_tapones_abiertos'
            )
                ->default(0)
                ->after('diferencia_galones_teoricos');

            $table->unsignedTinyInteger(
                'total_marchamos_reemplazados'
            )
                ->default(0)
                ->after('total_tapones_abiertos');

            /*
            |--------------------------------------------------------------------------
            | Anulación
            |--------------------------------------------------------------------------
            */

            $table->dateTime(
                'fecha_anulacion'
            )
                ->nullable()
                ->after('total_marchamos_reemplazados');

            $table->foreignId(
                'anulado_por'
            )
                ->nullable()
                ->after('fecha_anulacion')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string(
                'motivo_anulacion',
                255
            )
                ->nullable()
                ->after('anulado_por');
        });

        /*
        |--------------------------------------------------------------------------
        | Relación autorreferencial
        |--------------------------------------------------------------------------
        |
        | Se agrega después de crear abastecimiento_anterior_id para evitar
        | problemas al construir toda la estructura en una sola operación.
        |
        */

        Schema::table('abastecimientos', function (Blueprint $table) {
            $table->foreign(
                'abastecimiento_anterior_id',
                'abastecimientos_anterior_fk'
            )
                ->references('id')
                ->on('abastecimientos')
                ->restrictOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | Índices operativos
        |--------------------------------------------------------------------------
        */

        Schema::table('abastecimientos', function (Blueprint $table) {
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
     * Revierte únicamente la estructura agregada.
     */
    public function down(): void
    {
        Schema::table('abastecimientos', function (Blueprint $table) {
            $table->dropIndex(
                'abast_empresa_fecha_idx'
            );

            $table->dropIndex(
                'abast_unidad_estado_fecha_idx'
            );

            $table->dropIndex(
                'abast_motorista_fecha_idx'
            );

            $table->dropIndex(
                'abast_origen_fecha_idx'
            );

            $table->dropForeign(
                'abastecimientos_anterior_fk'
            );

            $table->dropForeign([
                'empresa_id',
            ]);

            $table->dropForeign([
                'unidad_id',
            ]);

            $table->dropForeign([
                'motorista_id',
            ]);

            $table->dropForeign([
                'registrado_por',
            ]);

            $table->dropForeign([
                'gasolinera_interna_id',
            ]);

            $table->dropForeign([
                'gasolinera_externa_id',
            ]);

            $table->dropForeign([
                'anulado_por',
            ]);
        });

        Schema::table('abastecimientos', function (Blueprint $table) {
            $table->dropColumn([
                'empresa_id',
                'unidad_id',
                'motorista_id',
                'abastecimiento_anterior_id',
                'registrado_por',
                'empresa_nombre_snapshot',
                'unidad_placa_snapshot',
                'unidad_marca_snapshot',
                'unidad_modelo_snapshot',
                'motorista_nombre_snapshot',
                'motorista_licencia_snapshot',
                'fecha_hora_abastecimiento',
                'estado',
                'modelo_medicion',
                'lectura_actual',
                'lectura_anterior',
                'diferencia_lectura',
                'volumen_inicial',
                'volumen_cargado',
                'volumen_final',
                'capacidad_cubierta_snapshot',
                'volumen_final_anterior',
                'combustible_consumido_ciclo',
                'combustible_adicional_no_explicado',
                'tipo_origen',
                'gasolinera_interna_id',
                'gasolinera_externa_id',
                'origen_nombre_snapshot',
                'precio_galon',
                'total_pagado',
                'moneda',
                'total_rutas',
                'kilometros_teoricos',
                'galones_teoricos',
                'galones_por_kilometro',
                'kilometros_por_galon',
                'galones_por_hora',
                'diferencia_kilometros_teoricos',
                'diferencia_galones_teoricos',
                'total_tapones_abiertos',
                'total_marchamos_reemplazados',
                'fecha_anulacion',
                'anulado_por',
                'motivo_anulacion',
            ]);
        });
    }
};