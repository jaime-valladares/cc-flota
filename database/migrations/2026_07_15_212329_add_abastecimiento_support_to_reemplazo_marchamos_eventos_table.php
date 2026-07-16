<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Habilita los reemplazos de marchamos originados
     * durante un abastecimiento.
     */
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Ampliar valores permitidos
        |--------------------------------------------------------------------------
        |
        | motivo_reemplazo sigue siendo obligatorio en la tabla existente.
        | Para abastecimientos se utilizará apertura_abastecimiento.
        |
        | origen_evento permite distinguir claramente una operación normal
        | de abastecimiento de un reemplazo general administrativo.
        |
        */

        DB::statement("
            ALTER TABLE reemplazo_marchamos_eventos
            MODIFY motivo_reemplazo ENUM(
                'dano',
                'desgaste',
                'perdida',
                'manipulacion_detectada',
                'correccion_instalacion',
                'apertura_abastecimiento'
            ) NOT NULL
        ");

        DB::statement("
            ALTER TABLE reemplazo_marchamos_eventos
            MODIFY origen_evento ENUM(
                'reemplazo_general',
                'abastecimiento'
            ) NOT NULL DEFAULT 'reemplazo_general'
        ");

        /*
        |--------------------------------------------------------------------------
        | Relación uno a uno con abastecimientos
        |--------------------------------------------------------------------------
        */

        Schema::table(
            'reemplazo_marchamos_eventos',
            function (Blueprint $table) {
                $table->foreignId('abastecimiento_id')
                    ->nullable()
                    ->after('unidad_id')
                    ->constrained('abastecimientos')
                    ->restrictOnDelete();

                $table->unique(
                    'abastecimiento_id',
                    'reemplazo_evento_abastecimiento_unique'
                );
            }
        );
    }

    /**
     * Revierte la integración.
     *
     * Esta reversión solo debe ejecutarse mientras no existan eventos
     * originados por abastecimientos.
     */
    public function down(): void
    {
        $existenEventosAbastecimiento = DB::table(
            'reemplazo_marchamos_eventos'
        )
            ->where('origen_evento', 'abastecimiento')
            ->orWhere(
                'motivo_reemplazo',
                'apertura_abastecimiento'
            )
            ->exists();

        if ($existenEventosAbastecimiento) {
            throw new RuntimeException(
                'No se puede revertir esta migración porque existen eventos de marchamos vinculados a abastecimientos.'
            );
        }

        Schema::table(
            'reemplazo_marchamos_eventos',
            function (Blueprint $table) {
                $table->dropUnique(
                    'reemplazo_evento_abastecimiento_unique'
                );

                $table->dropForeign([
                    'abastecimiento_id',
                ]);

                $table->dropColumn(
                    'abastecimiento_id'
                );
            }
        );

        DB::statement("
            ALTER TABLE reemplazo_marchamos_eventos
            MODIFY origen_evento ENUM(
                'reemplazo_general'
            ) NOT NULL DEFAULT 'reemplazo_general'
        ");

        DB::statement("
            ALTER TABLE reemplazo_marchamos_eventos
            MODIFY motivo_reemplazo ENUM(
                'dano',
                'desgaste',
                'perdida',
                'manipulacion_detectada',
                'correccion_instalacion'
            ) NOT NULL
        ");
    }
};