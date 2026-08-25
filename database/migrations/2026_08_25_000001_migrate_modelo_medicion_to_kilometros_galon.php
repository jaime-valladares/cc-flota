<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MODELO_ANTIGUO = 'galones_kilometro';

    private const MODELO_NUEVO = 'kilometros_galon';

    /**
     * @var array<int, string>
     */
    private const MODELOS_VALIDOS_ANTIGUOS = [
        'galones_hora',
        self::MODELO_ANTIGUO,
        'galones_viaje',
    ];

    /**
     * @var array<int, string>
     */
    private const MODELOS_VALIDOS_NUEVOS = [
        'galones_hora',
        self::MODELO_NUEVO,
        'galones_viaje',
    ];

    public function up(): void
    {
        $this->verificarModelosConocidos(
            self::MODELOS_VALIDOS_ANTIGUOS
        );

        if (DB::getDriverName() === 'mysql') {
            $this->ampliarEnum();
        }

        $this->migrarCodigo(
            self::MODELO_ANTIGUO,
            self::MODELO_NUEVO
        );

        $this->verificarAusencia(self::MODELO_ANTIGUO);
        $this->verificarModelosConocidos(
            self::MODELOS_VALIDOS_NUEVOS
        );

        if (DB::getDriverName() === 'mysql') {
            $this->restringirEnumNuevo();
        }
    }

    public function down(): void
    {
        $this->verificarModelosConocidos(
            self::MODELOS_VALIDOS_NUEVOS
        );

        if (DB::getDriverName() === 'mysql') {
            $this->ampliarEnum();
        }

        $this->migrarCodigo(
            self::MODELO_NUEVO,
            self::MODELO_ANTIGUO
        );

        $this->verificarAusencia(self::MODELO_NUEVO);
        $this->verificarModelosConocidos(
            self::MODELOS_VALIDOS_ANTIGUOS
        );

        if (DB::getDriverName() === 'mysql') {
            $this->restringirEnumAntiguo();
        }
    }

    private function migrarCodigo(
        string $origen,
        string $destino
    ): void {
        DB::transaction(function () use ($origen, $destino): void {
            foreach (['unidades', 'abastecimientos'] as $tabla) {
                $esperados = DB::table($tabla)
                    ->where('modelo_medicion', $origen)
                    ->count();

                $actualizados = DB::table($tabla)
                    ->where('modelo_medicion', $origen)
                    ->update([
                        'modelo_medicion' => $destino,
                    ]);

                if ($actualizados !== $esperados) {
                    throw new RuntimeException(
                        "La migración de {$tabla}.modelo_medicion "
                        ."actualizó {$actualizados} de {$esperados} registros."
                    );
                }
            }
        });
    }

    /**
     * @param  array<int, string>  $permitidos
     */
    private function verificarModelosConocidos(
        array $permitidos
    ): void {
        foreach (['unidades', 'abastecimientos'] as $tabla) {
            $desconocidos = DB::table($tabla)
                ->whereNotIn('modelo_medicion', $permitidos)
                ->count();

            if ($desconocidos > 0) {
                throw new RuntimeException(
                    "La tabla {$tabla} contiene {$desconocidos} "
                    .'modelos de medición desconocidos.'
                );
            }
        }
    }

    private function verificarAusencia(string $modelo): void
    {
        foreach (['unidades', 'abastecimientos'] as $tabla) {
            $restantes = DB::table($tabla)
                ->where('modelo_medicion', $modelo)
                ->count();

            if ($restantes > 0) {
                throw new RuntimeException(
                    "La tabla {$tabla} todavía contiene {$restantes} "
                    ."registros con el modelo {$modelo}."
                );
            }
        }
    }

    private function ampliarEnum(): void
    {
        DB::statement(
            'ALTER TABLE unidades MODIFY modelo_medicion '
            ."ENUM('galones_hora', 'galones_kilometro', "
            ."'kilometros_galon', 'galones_viaje') NOT NULL"
        );
    }

    private function restringirEnumNuevo(): void
    {
        DB::statement(
            'ALTER TABLE unidades MODIFY modelo_medicion '
            ."ENUM('galones_hora', 'kilometros_galon', "
            ."'galones_viaje') NOT NULL"
        );
    }

    private function restringirEnumAntiguo(): void
    {
        DB::statement(
            'ALTER TABLE unidades MODIFY modelo_medicion '
            ."ENUM('galones_hora', 'galones_kilometro', "
            ."'galones_viaje') NOT NULL"
        );
    }
};
