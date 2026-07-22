<?php

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use RuntimeException;

class CcFlotaSeederContext
{
    private static ?int $superUserId = null;

    private static ?CarbonImmutable $fechaInicio = null;

    private static ?CarbonImmutable $fechaFin = null;

    private static array $referencias = [];

    private static array $escenarios = [];

    private static array $secuencias = [];

    public static function reiniciar(): void
    {
        self::$superUserId = null;
        self::$fechaInicio = null;
        self::$fechaFin = null;
        self::$referencias = [];
        self::$escenarios = [];
        self::$secuencias = [];
    }

    public static function inicializar(
        int $superUserId,
        ?CarbonImmutable $fechaInicio = null,
        ?CarbonImmutable $fechaFin = null
    ): void {
        self::reiniciar();

        self::$superUserId = $superUserId;

        self::$fechaFin = $fechaFin
            ?? CarbonImmutable::now()->startOfDay();

        self::$fechaInicio = $fechaInicio
            ?? self::$fechaFin
                ->subMonths(17)
                ->startOfMonth();
    }

    public static function superUserId(): int
    {
        if (is_null(self::$superUserId)) {
            throw new RuntimeException(
                'El contexto del seeder no ha sido inicializado.'
            );
        }

        return self::$superUserId;
    }

    public static function fechaInicio(): CarbonImmutable
    {
        if (is_null(self::$fechaInicio)) {
            throw new RuntimeException(
                'La fecha inicial del seeder no ha sido definida.'
            );
        }

        return self::$fechaInicio;
    }

    public static function fechaFin(): CarbonImmutable
    {
        if (is_null(self::$fechaFin)) {
            throw new RuntimeException(
                'La fecha final del seeder no ha sido definida.'
            );
        }

        return self::$fechaFin;
    }

    public static function registrarReferencia(
        string $clave,
        mixed $valor
    ): void {
        if (array_key_exists($clave, self::$referencias)) {
            throw new RuntimeException(
                "La referencia [{$clave}] ya fue registrada."
            );
        }

        self::$referencias[$clave] = $valor;
    }

    public static function establecerReferencia(
        string $clave,
        mixed $valor
    ): void {
        self::$referencias[$clave] = $valor;
    }

    public static function referencia(
        string $clave
    ): mixed {
        if (! array_key_exists($clave, self::$referencias)) {
            throw new RuntimeException(
                "La referencia [{$clave}] no existe."
            );
        }

        return self::$referencias[$clave];
    }

    public static function referenciaOpcional(
        string $clave,
        mixed $predeterminado = null
    ): mixed {
        return self::$referencias[$clave]
            ?? $predeterminado;
    }

    public static function tieneReferencia(
        string $clave
    ): bool {
        return array_key_exists(
            $clave,
            self::$referencias
        );
    }

    public static function registrarEscenario(
        string $clave,
        mixed $valor
    ): void {
        if (array_key_exists($clave, self::$escenarios)) {
            throw new RuntimeException(
                "El escenario [{$clave}] ya fue registrado."
            );
        }

        self::$escenarios[$clave] = $valor;
    }

    public static function escenario(
        string $clave
    ): mixed {
        if (! array_key_exists($clave, self::$escenarios)) {
            throw new RuntimeException(
                "El escenario [{$clave}] no existe."
            );
        }

        return self::$escenarios[$clave];
    }

    public static function escenarios(): array
    {
        return self::$escenarios;
    }

    public static function siguiente(
        string $clave,
        int $inicio = 1
    ): int {
        if (! array_key_exists($clave, self::$secuencias)) {
            self::$secuencias[$clave] = $inicio;

            return self::$secuencias[$clave];
        }

        self::$secuencias[$clave]++;

        return self::$secuencias[$clave];
    }

    public static function codigoNumerico(
        string $secuencia,
        int $longitud,
        int $inicio = 1
    ): string {
        $valor = self::siguiente(
            $secuencia,
            $inicio
        );

        $codigo = str_pad(
            (string) $valor,
            $longitud,
            '0',
            STR_PAD_LEFT
        );

        if (strlen($codigo) > $longitud) {
            throw new RuntimeException(
                "La secuencia [{$secuencia}] excedió "
                . "la longitud máxima de {$longitud} caracteres."
            );
        }

        return $codigo;
    }

    public static function resumen(): array
    {
        return [
            'super_user_id' => self::$superUserId,
            'fecha_inicio' =>
                self::$fechaInicio?->toDateString(),

            'fecha_fin' =>
                self::$fechaFin?->toDateString(),

            'referencias' =>
                count(self::$referencias),

            'escenarios' =>
                count(self::$escenarios),

            'secuencias' =>
                self::$secuencias,
        ];
    }
}