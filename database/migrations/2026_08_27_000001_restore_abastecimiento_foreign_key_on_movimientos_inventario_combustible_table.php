<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLA_MOVIMIENTOS =
        'movimientos_inventario_combustible';

    private const TABLA_ABASTECIMIENTOS = 'abastecimientos';

    private const COLUMNA = 'abastecimiento_id';

    private const FOREIGN_KEY =
        'movimientos_inventario_combustible_abastecimiento_id_foreign';

    public function up(): void
    {
        $this->validarEstructura();
        $this->validarCompatibilidad();
        $this->validarIntegridadReferencial();
        $this->validarIndice();

        $foreignKeys = $this->foreignKeysSobreColumna();

        if ($foreignKeys->isEmpty()) {
            Schema::table(
                self::TABLA_MOVIMIENTOS,
                function (Blueprint $table): void {
                    $table->foreign(
                        self::COLUMNA,
                        self::FOREIGN_KEY
                    )
                        ->references('id')
                        ->on(self::TABLA_ABASTECIMIENTOS)
                        ->restrictOnDelete();
                }
            );

            return;
        }

        if ($foreignKeys->count() !== 1) {
            throw new RuntimeException(
                'Existe más de una foreign key sobre abastecimiento_id.'
            );
        }

        $foreignKey = $foreignKeys->first();

        if (! $this->esForeignKeyEsperada($foreignKey)) {
            throw new RuntimeException(
                'Existe una foreign key distinta sobre abastecimiento_id; '
                .'no se reemplazará automáticamente.'
            );
        }
    }

    public function down(): void
    {
        $foreignKey = DB::table(
            'information_schema.KEY_COLUMN_USAGE as kcu'
        )
            ->join(
                'information_schema.TABLE_CONSTRAINTS as tc',
                function ($join): void {
                    $join->on(
                        'tc.CONSTRAINT_SCHEMA',
                        '=',
                        'kcu.CONSTRAINT_SCHEMA'
                    )
                        ->on(
                            'tc.TABLE_NAME',
                            '=',
                            'kcu.TABLE_NAME'
                        )
                        ->on(
                            'tc.CONSTRAINT_NAME',
                            '=',
                            'kcu.CONSTRAINT_NAME'
                        );
                }
            )
            ->whereRaw('kcu.CONSTRAINT_SCHEMA = DATABASE()')
            ->where('kcu.TABLE_NAME', self::TABLA_MOVIMIENTOS)
            ->where('kcu.CONSTRAINT_NAME', self::FOREIGN_KEY)
            ->where('tc.CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();

        if (! $foreignKey) {
            return;
        }

        Schema::table(
            self::TABLA_MOVIMIENTOS,
            function (Blueprint $table): void {
                $table->dropForeign(self::FOREIGN_KEY);
            }
        );
    }

    private function validarEstructura(): void
    {
        if (
            ! Schema::hasTable(self::TABLA_MOVIMIENTOS)
            || ! Schema::hasTable(self::TABLA_ABASTECIMIENTOS)
            || ! Schema::hasColumn(
                self::TABLA_MOVIMIENTOS,
                self::COLUMNA
            )
        ) {
            throw new RuntimeException(
                'No existe la estructura requerida para restaurar la '
                .'foreign key de abastecimiento.'
            );
        }

        if (! Schema::hasColumn(self::TABLA_ABASTECIMIENTOS, 'id')) {
            throw new RuntimeException(
                'No existe abastecimientos.id.'
            );
        }
    }

    private function validarCompatibilidad(): void
    {
        $columnas = DB::table('information_schema.COLUMNS')
            ->select(
                'TABLE_NAME',
                'COLUMN_NAME',
                'DATA_TYPE',
                'COLUMN_TYPE',
                'IS_NULLABLE'
            )
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where(function ($query): void {
                $query->where(function ($query): void {
                    $query->where(
                        'TABLE_NAME',
                        self::TABLA_MOVIMIENTOS
                    )->where('COLUMN_NAME', self::COLUMNA);
                })->orWhere(function ($query): void {
                    $query->where(
                        'TABLE_NAME',
                        self::TABLA_ABASTECIMIENTOS
                    )->where('COLUMN_NAME', 'id');
                });
            })
            ->get()
            ->keyBy('TABLE_NAME');

        $movimiento = $columnas->get(self::TABLA_MOVIMIENTOS);
        $abastecimiento = $columnas->get(
            self::TABLA_ABASTECIMIENTOS
        );

        if (! $movimiento || ! $abastecimiento) {
            throw new RuntimeException(
                'No se pudo determinar la definición de las columnas.'
            );
        }

        if ($movimiento->IS_NULLABLE !== 'YES') {
            throw new RuntimeException(
                'abastecimiento_id debe permanecer nullable.'
            );
        }

        if (
            $movimiento->DATA_TYPE !== $abastecimiento->DATA_TYPE
            || $movimiento->COLUMN_TYPE !== $abastecimiento->COLUMN_TYPE
        ) {
            throw new RuntimeException(
                'abastecimiento_id no es compatible con abastecimientos.id.'
            );
        }
    }

    private function validarIntegridadReferencial(): void
    {
        $huerfanos = DB::table(self::TABLA_MOVIMIENTOS.' as m')
            ->leftJoin(
                self::TABLA_ABASTECIMIENTOS.' as a',
                'a.id',
                '=',
                'm.'.self::COLUMNA
            )
            ->whereNotNull('m.'.self::COLUMNA)
            ->whereNull('a.id')
            ->exists();

        if ($huerfanos) {
            throw new RuntimeException(
                'Existen movimientos con abastecimiento_id huérfano.'
            );
        }
    }

    private function validarIndice(): void
    {
        $indice = DB::table('information_schema.STATISTICS')
            ->whereRaw('TABLE_SCHEMA = DATABASE()')
            ->where('TABLE_NAME', self::TABLA_MOVIMIENTOS)
            ->where('COLUMN_NAME', self::COLUMNA)
            ->where('SEQ_IN_INDEX', 1)
            ->exists();

        if (! $indice) {
            throw new RuntimeException(
                'No existe un índice que comience con abastecimiento_id.'
            );
        }
    }

    private function foreignKeysSobreColumna()
    {
        return DB::table(
            'information_schema.KEY_COLUMN_USAGE as kcu'
        )
            ->join(
                'information_schema.REFERENTIAL_CONSTRAINTS as rc',
                function ($join): void {
                    $join->on(
                        'rc.CONSTRAINT_SCHEMA',
                        '=',
                        'kcu.CONSTRAINT_SCHEMA'
                    )->on(
                        'rc.CONSTRAINT_NAME',
                        '=',
                        'kcu.CONSTRAINT_NAME'
                    );
                }
            )
            ->select(
                'kcu.CONSTRAINT_NAME',
                'kcu.REFERENCED_TABLE_NAME',
                'kcu.REFERENCED_COLUMN_NAME',
                'rc.DELETE_RULE'
            )
            ->whereRaw('kcu.CONSTRAINT_SCHEMA = DATABASE()')
            ->where('kcu.TABLE_NAME', self::TABLA_MOVIMIENTOS)
            ->where('kcu.COLUMN_NAME', self::COLUMNA)
            ->whereNotNull('kcu.REFERENCED_TABLE_NAME')
            ->get();
    }

    private function esForeignKeyEsperada(object $foreignKey): bool
    {
        return $foreignKey->CONSTRAINT_NAME === self::FOREIGN_KEY
            && $foreignKey->REFERENCED_TABLE_NAME
                === self::TABLA_ABASTECIMIENTOS
            && $foreignKey->REFERENCED_COLUMN_NAME === 'id'
            && $foreignKey->DELETE_RULE === 'RESTRICT';
    }
};
