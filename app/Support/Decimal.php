<?php

namespace App\Support;

final class Decimal
{
    public static function normalizar(string|int|float $valor, int $escala): string
    {
        return self::redondear((string) $valor, $escala);
    }

    public static function sumar(string $a, string $b, int $escala): string
    {
        return self::redondear(bcadd($a, $b, $escala + 2), $escala);
    }

    public static function restar(string $a, string $b, int $escala): string
    {
        return self::redondear(bcsub($a, $b, $escala + 2), $escala);
    }

    public static function multiplicar(string $a, string $b, int $escala): string
    {
        return self::redondear(bcmul($a, $b, $escala + 2), $escala);
    }

    public static function dividir(string $a, string $b, int $escala): string
    {
        if (bccomp($b, '0', $escala + 2) === 0) {
            throw new \DivisionByZeroError('No se puede dividir entre cero.');
        }

        return self::redondear(bcdiv($a, $b, $escala + 2), $escala);
    }

    public static function comparar(string $a, string $b, int $escala): int
    {
        return bccomp($a, $b, $escala);
    }

    private static function redondear(string $valor, int $escala): string
    {
        $incremento = '0.'.str_repeat('0', $escala).'5';
        $ajustado = str_starts_with($valor, '-')
            ? bcsub($valor, $incremento, $escala + 1)
            : bcadd($valor, $incremento, $escala + 1);

        return bcadd($ajustado, '0', $escala);
    }
}
