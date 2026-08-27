<?php

use App\Models\Abastecimiento;
use App\Support\Decimal;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('abastecimientos', 'costo_unitario_ciclo')) {
            Schema::table('abastecimientos', function (Blueprint $table) {
                $table->decimal('costo_unitario_ciclo', 24, 8)
                    ->nullable()
                    ->after('costo_combustible_consumido_ciclo');
            });
        }

        DB::table('abastecimientos')
            ->whereNotNull('costo_combustible_consumido_ciclo')
            ->orderBy('id')
            ->eachById(function (object $abastecimiento): void {
                $divisor = match ($abastecimiento->modelo_medicion) {
                    Abastecimiento::MODELO_KILOMETROS_GALON => $abastecimiento->diferencia_kilometraje,
                    Abastecimiento::MODELO_GALONES_HORA => $abastecimiento->diferencia_horometro,
                    Abastecimiento::MODELO_GALONES_VIAJE => $abastecimiento->total_viajes,
                    default => null,
                };

                if (is_null($divisor) || (float) $divisor <= 0) {
                    return;
                }

                DB::table('abastecimientos')
                    ->where('id', $abastecimiento->id)
                    ->update([
                        'costo_unitario_ciclo' => Decimal::dividir(
                            (string) $abastecimiento->costo_combustible_consumido_ciclo,
                            (string) $divisor,
                            8
                        ),
                    ]);
            });
    }

    public function down(): void
    {
        if (Schema::hasColumn('abastecimientos', 'costo_unitario_ciclo')) {
            Schema::table('abastecimientos', function (Blueprint $table) {
                $table->dropColumn('costo_unitario_ciclo');
            });
        }
    }
};
