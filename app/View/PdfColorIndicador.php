<?php

namespace App\View;

final class PdfColorIndicador
{
    public static function resultado(?string $resultado): string
    {
        return match ($resultado) {
            'Ahorro' => 'pdf-indicador-favorable',
            'Sobreconsumo' => 'pdf-indicador-desfavorable',
            default => 'pdf-indicador-neutro',
        };
    }

    public static function valor(int|float|string|null $valor, ?string $resultado = null, int $decimales = 2): string
    {
        if ($valor === null || (float) number_format(abs((float) $valor), $decimales, '.', '') === 0.0) {
            return self::resultado(null);
        }

        return self::resultado($resultado ?? ((float) $valor > 0 ? 'Ahorro' : 'Sobreconsumo'));
    }
}
