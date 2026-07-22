<?php

namespace Database\Seeders;

use RuntimeException;

final class CcFlotaDeterministicGenerator
{
    /**
     * Estado interno del generador.
     */
    private static int $estado =
        CcFlotaSeederConfig::RANDOM_SEED;

    /**
     * Reinicia el generador con una semilla específica.
     */
    public static function reiniciar(
        ?int $semilla = null
    ): void {
        self::$estado =
            $semilla
            ?? CcFlotaSeederConfig::RANDOM_SEED;
    }

    /**
     * Genera el siguiente entero pseudoaleatorio.
     *
     * Implementación determinística basada en LCG.
     */
    private static function siguienteEntero(): int
    {
        self::$estado = (
            1103515245 * self::$estado
            + 12345
        ) % 2147483648;

        return self::$estado;
    }

    /**
     * Genera un entero dentro de un rango inclusivo.
     */
    public static function entero(
        int $minimo,
        int $maximo
    ): int {
        if ($minimo > $maximo) {
            throw new RuntimeException(
                'El mínimo no puede ser mayor que el máximo.'
            );
        }

        if ($minimo === $maximo) {
            return $minimo;
        }

        $rango = $maximo - $minimo + 1;

        return $minimo
            + (
                self::siguienteEntero()
                % $rango
            );
    }

    /**
     * Genera un decimal dentro de un rango.
     */
    public static function decimal(
        float $minimo,
        float $maximo,
        int $decimales = 2
    ): float {
        if ($minimo > $maximo) {
            throw new RuntimeException(
                'El mínimo no puede ser mayor que el máximo.'
            );
        }

        if ($decimales < 0 || $decimales > 8) {
            throw new RuntimeException(
                'La cantidad de decimales no es válida.'
            );
        }

        if ($minimo === $maximo) {
            return round(
                $minimo,
                $decimales
            );
        }

        $fraccion =
            self::siguienteEntero()
            / 2147483647;

        return round(
            $minimo
            + (($maximo - $minimo) * $fraccion),
            $decimales
        );
    }

    /**
     * Devuelve true según un porcentaje de probabilidad.
     */
    public static function probabilidad(
        int $porcentaje
    ): bool {
        if ($porcentaje < 0 || $porcentaje > 100) {
            throw new RuntimeException(
                'El porcentaje debe estar entre 0 y 100.'
            );
        }

        if ($porcentaje === 0) {
            return false;
        }

        if ($porcentaje === 100) {
            return true;
        }

        return self::entero(1, 100)
            <= $porcentaje;
    }

    /**
     * Selecciona un elemento de una lista indexada.
     */
    public static function elegir(
        array $elementos
    ): mixed {
        if ($elementos === []) {
            throw new RuntimeException(
                'No se puede seleccionar de una lista vacía.'
            );
        }

        $elementos = array_values(
            $elementos
        );

        return $elementos[
            self::entero(
                0,
                count($elementos) - 1
            )
        ];
    }

    /**
     * Selecciona una clave a partir de una distribución porcentual.
     *
     * Ejemplo:
     *
     * [
     *     'activo' => 80,
     *     'inactivo' => 20,
     * ]
     */
    public static function distribuir(
        array $distribucion
    ): string|int {
        if ($distribucion === []) {
            throw new RuntimeException(
                'La distribución no puede estar vacía.'
            );
        }

        $total = array_sum(
            $distribucion
        );

        if ($total <= 0) {
            throw new RuntimeException(
                'La distribución debe tener un total mayor que cero.'
            );
        }

        $seleccion = self::entero(
            1,
            $total
        );

        $acumulado = 0;

        foreach (
            $distribucion
            as $clave => $peso
        ) {
            if ($peso < 0) {
                throw new RuntimeException(
                    'La distribución no admite pesos negativos.'
                );
            }

            $acumulado += $peso;

            if ($seleccion <= $acumulado) {
                return $clave;
            }
        }

        return array_key_last(
            $distribucion
        );
    }

    /**
     * Reordena una lista de forma determinística.
     */
    public static function mezclar(
        array $elementos
    ): array {
        $elementos = array_values(
            $elementos
        );

        for (
            $indice = count($elementos) - 1;
            $indice > 0;
            $indice--
        ) {
            $intercambio = self::entero(
                0,
                $indice
            );

            [
                $elementos[$indice],
                $elementos[$intercambio],
            ] = [
                $elementos[$intercambio],
                $elementos[$indice],
            ];
        }

        return $elementos;
    }

    /**
     * Selecciona varios elementos sin repetir.
     */
    public static function elegirVarios(
        array $elementos,
        int $cantidad
    ): array {
        if ($cantidad < 0) {
            throw new RuntimeException(
                'La cantidad no puede ser negativa.'
            );
        }

        $elementos = array_values(
            $elementos
        );

        if ($cantidad > count($elementos)) {
            throw new RuntimeException(
                'La cantidad solicitada supera '
                . 'los elementos disponibles.'
            );
        }

        return array_slice(
            self::mezclar($elementos),
            0,
            $cantidad
        );
    }

    /**
     * Genera un valor incremental con variación porcentual.
     *
     * Útil para precios, lecturas y volúmenes históricos.
     */
    public static function variar(
        float $valorBase,
        float $porcentajeMaximo,
        int $decimales = 2
    ): float {
        if ($porcentajeMaximo < 0) {
            throw new RuntimeException(
                'El porcentaje máximo no puede ser negativo.'
            );
        }

        $variacion = self::decimal(
            -$porcentajeMaximo,
            $porcentajeMaximo,
            4
        );

        return round(
            $valorBase
            * (1 + ($variacion / 100)),
            $decimales
        );
    }

    /**
     * Devuelve el estado actual para depuración.
     */
    public static function estado(): int
    {
        return self::$estado;
    }
}