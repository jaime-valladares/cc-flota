<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDICE_GLOBAL = 'unidades_placa_unique';

    private const INDICE_EMPRESA =
        'unidades_empresa_id_placa_unique';

    public function up(): void
    {
        $duplicados = DB::table('unidades')
            ->select('empresa_id', 'placa')
            ->groupBy('empresa_id', 'placa')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicados) {
            throw new RuntimeException(
                'Existen Nombres / Placas duplicados dentro de una empresa.'
            );
        }

        Schema::table('unidades', function (Blueprint $table): void {
            $table->dropUnique(self::INDICE_GLOBAL);
            $table->unique(
                ['empresa_id', 'placa'],
                self::INDICE_EMPRESA
            );
        });

        Schema::table(
            'abastecimientos',
            function (Blueprint $table): void {
                $table->string(
                    'unidad_placa_snapshot', 30
                )->change();
            }
        );
    }

    public function down(): void
    {
        $duplicadosGlobales = DB::table('unidades')
            ->select('placa')
            ->groupBy('placa')
            ->havingRaw('COUNT(DISTINCT empresa_id) > 1')
            ->exists();

        if ($duplicadosGlobales) {
            throw new RuntimeException(
                'No se puede restaurar la unicidad global: existen '
                .'Nombres / Placas iguales en empresas distintas.'
            );
        }

        $snapshotsLargos = DB::table('abastecimientos')
            ->whereRaw('CHAR_LENGTH(unidad_placa_snapshot) > 25')
            ->exists();

        if ($snapshotsLargos) {
            throw new RuntimeException(
                'No se puede reducir el snapshot a 25 caracteres sin '
                .'truncar datos históricos.'
            );
        }

        Schema::table('unidades', function (Blueprint $table): void {
            $table->dropUnique(self::INDICE_EMPRESA);
            $table->unique('placa', self::INDICE_GLOBAL);
        });

        Schema::table(
            'abastecimientos',
            function (Blueprint $table): void {
                $table->string(
                    'unidad_placa_snapshot', 25
                )->change();
            }
        );
    }
};
