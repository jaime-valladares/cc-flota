<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const RENOMBRES = [
        'OTR-34' => [
            'anterior' => 'Base módulo Cummins entrada',
            'nuevo' => 'Base módulo entrada',
        ],
        'OTR-35' => [
            'anterior' => 'Base módulo Cummins salida',
            'nuevo' => 'Base módulo salida',
        ],
    ];

    public function up(): void
    {
        $this->renombrar('anterior', 'nuevo');
    }

    public function down(): void
    {
        $this->renombrar('nuevo', 'anterior');
    }

    private function renombrar(string $origen, string $destino): void
    {
        $this->verificarEstructura();

        DB::transaction(function () use ($origen, $destino): void {
            foreach (self::RENOMBRES as $codigo => $nombres) {
                $nombresEncontrados = DB::table('puntos_seguridad_unidad')
                    ->where('codigo_punto', $codigo)
                    ->lockForUpdate()
                    ->pluck('nombre_punto')
                    ->unique()
                    ->values();

                $inesperados = $nombresEncontrados->diff([
                    $nombres['anterior'],
                    $nombres['nuevo'],
                ]);

                if ($inesperados->isNotEmpty()) {
                    throw new RuntimeException(
                        "El punto {$codigo} está asociado a nombres inesperados: "
                        .$inesperados->implode(', ').'.'
                    );
                }
            }

            foreach (self::RENOMBRES as $codigo => $nombres) {
                DB::table('puntos_seguridad_unidad')
                    ->where('codigo_punto', $codigo)
                    ->where('nombre_punto', $nombres[$origen])
                    ->update([
                        'nombre_punto' => $nombres[$destino],
                    ]);
            }
        });
    }

    private function verificarEstructura(): void
    {
        if (! Schema::hasTable('puntos_seguridad_unidad')) {
            throw new RuntimeException(
                'No existe la tabla puntos_seguridad_unidad.'
            );
        }

        foreach (['codigo_punto', 'nombre_punto'] as $columna) {
            if (! Schema::hasColumn('puntos_seguridad_unidad', $columna)) {
                throw new RuntimeException(
                    "No existe la columna puntos_seguridad_unidad.{$columna}."
                );
            }
        }
    }
};
