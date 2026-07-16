<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Garantiza que codigo_marchamo sea único globalmente.
     *
     * La migración es segura tanto para bases que ya poseen la
     * restricción global como para instalaciones nuevas que todavía
     * tengan una restricción compuesta por empresa y código.
     */
    public function up(): void
    {
        $databaseName = DB::connection()
            ->getDatabaseName();

        /*
        |--------------------------------------------------------------------------
        | Verificar si ya existe unicidad global
        |--------------------------------------------------------------------------
        |
        | Buscamos un índice UNIQUE compuesto únicamente por
        | codigo_marchamo. Si ya existe, no hacemos ningún cambio.
        |
        */

        $indiceGlobal = DB::table(
            'information_schema.statistics'
        )
            ->select('index_name')
            ->where('table_schema', $databaseName)
            ->where('table_name', 'marchamos')
            ->where('non_unique', 0)
            ->groupBy('index_name')
            ->havingRaw(
                'COUNT(*) = 1
                AND MAX(column_name) = ?
                AND MIN(column_name) = ?',
                [
                    'codigo_marchamo',
                    'codigo_marchamo',
                ]
            )
            ->first();

        if ($indiceGlobal) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Verificar códigos duplicados
        |--------------------------------------------------------------------------
        */

        $codigoDuplicado = DB::table('marchamos')
            ->select(
                'codigo_marchamo',
                DB::raw('COUNT(*) AS cantidad')
            )
            ->groupBy('codigo_marchamo')
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('codigo_marchamo')
            ->first();

        if ($codigoDuplicado) {
            throw new \RuntimeException(
                'No se puede crear la unicidad global de marchamos. '
                . 'El código '
                . $codigoDuplicado->codigo_marchamo
                . ' aparece '
                . $codigoDuplicado->cantidad
                . ' veces.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Detectar índice compuesto anterior
        |--------------------------------------------------------------------------
        */

        $indices = DB::table(
            'information_schema.statistics'
        )
            ->select(
                'index_name',
                'column_name',
                'seq_in_index'
            )
            ->where('table_schema', $databaseName)
            ->where('table_name', 'marchamos')
            ->where('non_unique', 0)
            ->where('index_name', '!=', 'PRIMARY')
            ->orderBy('index_name')
            ->orderBy('seq_in_index')
            ->get()
            ->groupBy('index_name');

        $indiceCompuesto = $indices->first(
            function ($columnas) {
                $nombres = $columnas
                    ->pluck('column_name')
                    ->values()
                    ->all();

                return $nombres === [
                    'empresa_id',
                    'codigo_marchamo',
                ];
            }
        );

        if ($indiceCompuesto) {
            $nombreIndice = $indiceCompuesto
                ->first()
                ->index_name;

            DB::statement(
                'ALTER TABLE `marchamos` '
                . 'DROP INDEX `'
                . str_replace(
                    '`',
                    '``',
                    $nombreIndice
                )
                . '`'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Crear unicidad global
        |--------------------------------------------------------------------------
        */

        DB::statement(
            'ALTER TABLE `marchamos` '
            . 'ADD UNIQUE INDEX '
            . '`marchamos_codigo_marchamo_global_unique` '
            . '(`codigo_marchamo`)'
        );
    }

    /**
     * Revierte únicamente el índice creado por esta migración.
     *
     * Si la unicidad global ya existía antes de ejecutar esta migración,
     * no se modifica durante el rollback.
     */
    public function down(): void
    {
        $databaseName = DB::connection()
            ->getDatabaseName();

        $indiceCreado = DB::table(
            'information_schema.statistics'
        )
            ->where('table_schema', $databaseName)
            ->where('table_name', 'marchamos')
            ->where(
                'index_name',
                'marchamos_codigo_marchamo_global_unique'
            )
            ->exists();

        if (! $indiceCreado) {
            return;
        }

        DB::statement(
            'ALTER TABLE `marchamos` '
            . 'DROP INDEX '
            . '`marchamos_codigo_marchamo_global_unique`'
        );

        DB::statement(
            'ALTER TABLE `marchamos` '
            . 'ADD UNIQUE INDEX '
            . '`marchamos_empresa_codigo_unique` '
            . '(`empresa_id`, `codigo_marchamo`)'
        );
    }
};