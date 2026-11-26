<?php

namespace App\View;

use Illuminate\Support\Carbon;

final class PdfPeriodoConsulta
{
    /** @return array{desde: ?string, hasta: ?string} */
    public static function limites(
        ?string $desde,
        ?string $hasta,
        Carbon|string|null $primeraFecha,
        bool $hayRegistros,
        Carbon $generadoEn,
    ): array {
        $inicio = $desde ?? $primeraFecha;
        $fin = $hasta ?? ($hayRegistros ? $generadoEn : null);

        return [
            'desde' => $inicio === null ? null : Carbon::parse($inicio)->format('d/m/Y'),
            'hasta' => $fin === null ? null : Carbon::parse($fin)->format('d/m/Y'),
        ];
    }
}
