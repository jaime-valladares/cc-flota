<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licencia_tanques', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licencia_id')
                ->constrained('licencias')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('unidad_tanque_id')
                ->constrained('unidad_tanques')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->unsignedTinyInteger('numero_tanque_snapshot');
            $table->decimal('capacidad_snapshot', 10, 2);
            $table->timestamps();

            $table->unique(['licencia_id', 'unidad_tanque_id']);
            $table->index('unidad_tanque_id');
        });

        /*
         * Sólo se migra cobertura legacy inequívoca: debe existir una licencia
         * real y deben coincidir cantidad y capacidad con sus tanques marcados.
         * Las unidades sin licencia nunca producen detalles contractuales.
         */
        DB::table('licencias')
            ->join('unidades', 'unidades.id', '=', 'licencias.unidad_id')
            ->select([
                'licencias.id as licencia_id',
                'licencias.unidad_id',
                'unidades.cantidad_tanques_con_licencia',
                'unidades.capacidad_cubierta',
            ])
            ->orderBy('licencias.id')
            ->chunkById(100, function ($licencias): void {
                foreach ($licencias as $licencia) {
                    $tanques = DB::table('unidad_tanques')
                        ->where('unidad_id', $licencia->unidad_id)
                        ->where('cubierto_por_licencia', true)
                        ->orderBy('numero')
                        ->get(['id', 'numero', 'capacidad']);

                    $cantidadCoincide = $tanques->isNotEmpty()
                        && $tanques->count()
                            === (int) $licencia->cantidad_tanques_con_licencia;
                    $capacidadCalculada = round(
                        (float) $tanques->sum('capacidad'),
                        2
                    );
                    $capacidadCoincide = abs(
                        $capacidadCalculada
                        - round((float) $licencia->capacidad_cubierta, 2)
                    ) < 0.005;

                    if (! $cantidadCoincide || ! $capacidadCoincide) {
                        continue;
                    }

                    $ahora = now();
                    DB::table('licencia_tanques')->insert(
                        $tanques->map(fn ($tanque): array => [
                            'licencia_id' => $licencia->licencia_id,
                            'unidad_tanque_id' => $tanque->id,
                            'numero_tanque_snapshot' => $tanque->numero,
                            'capacidad_snapshot' => $tanque->capacidad,
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ])->all()
                    );
                }
            }, 'licencias.id', 'licencia_id');
    }

    public function down(): void
    {
        Schema::dropIfExists('licencia_tanques');
    }
};
