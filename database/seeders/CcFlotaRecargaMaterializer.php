<?php

namespace Database\Seeders;

use App\Models\MovimientoInventarioCombustible;
use App\Models\RecargaCombustible;
use App\Models\Tanque;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CcFlotaRecargaMaterializer
{
    /**
     * Materializa una recarga planificada dentro de una transacción
     * operacional mayor.
     *
     * No inicia una transacción propia porque el seeder operacional
     * recorrerá recargas y abastecimientos en una única línea temporal.
     */
    public static function crear(
        array $evento,
        int $usuarioId
    ): RecargaCombustible {
        self::validarEvento($evento);

        $fechaRecarga =
            CarbonImmutable::parse(
                $evento['fecha_hora']
            );

        $estado =
            (string) $evento['estado_plan'];

        $fechaAnulacion =
            $estado === 'anulado'
                ? CarbonImmutable::parse(
                    $evento['fecha_anulacion_plan']
                )
                : null;

        $tanqueIds =
            collect($evento['detalles'])
                ->pluck('tanque_id')
                ->map(
                    fn ($id): int => (int) $id
                )
                ->unique()
                ->sort()
                ->values();

        $tanques = Tanque::query()
            ->whereIn('id', $tanqueIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if (
            $tanques->count()
            !== $tanqueIds->count()
        ) {
            throw new RuntimeException(
                'Uno o más tanques de la recarga '
                . 'planificada no existen.'
            );
        }

        $recarga =
            RecargaCombustible::create([
                'empresa_id' =>
                    (int) $evento['empresa_id'],

                'gasolinera_id' =>
                    (int) $evento['gasolinera_id'],

                'precio_galon' =>
                    round(
                        (float) $evento['precio_galon'],
                        4
                    ),

                'total_galones' =>
                    round(
                        (float) $evento['total_galones'],
                        2
                    ),

                'total_compra' =>
                    round(
                        (float) $evento['total_compra'],
                        2
                    ),

                'fecha_hora_recarga' =>
                    $fechaRecarga,

                'observaciones' =>
                    'Recarga histórica generada por '
                    . 'el seeder integral de CC-Flota.',

                'usuario_registra_id' =>
                    $usuarioId,

                'estado' =>
                    $estado,

                'fecha_creacion' =>
                    $fechaRecarga,

                'fecha_actualizacion' =>
                    $fechaAnulacion,

                'actualizado_por' =>
                    $fechaAnulacion
                        ? $usuarioId
                        : null,

                'fecha_anulacion' =>
                    $fechaAnulacion,

                'anulado_por' =>
                    $fechaAnulacion
                        ? $usuarioId
                        : null,

                'motivo_anulacion' =>
                    $evento[
                        'motivo_anulacion_plan'
                    ] ?? null,
            ]);

        foreach (
            $evento['detalles']
            as $detalle
        ) {
            /** @var Tanque|null $tanque */
            $tanque = $tanques->get(
                (int) $detalle['tanque_id']
            );

            if (! $tanque) {
                throw new RuntimeException(
                    'No fue posible recuperar uno de '
                    . 'los tanques bloqueados.'
                );
            }

            if (
                (int) $tanque->gasolinera_id
                !== (int) $evento['gasolinera_id']
            ) {
                throw new RuntimeException(
                    "El tanque {$tanque->id} no pertenece "
                    . 'a la gasolinera de la recarga.'
                );
            }

            $volumenAnterior =
                round(
                    (float) $tanque->volumen_actual,
                    2
                );

            $volumenPlanAnterior =
                round(
                    (float) $detalle[
                        'volumen_anterior'
                    ],
                    2
                );

            if (
                abs(
                    $volumenAnterior
                    - $volumenPlanAnterior
                ) > 0.01
            ) {
                throw new RuntimeException(
                    "Inventario divergente en tanque "
                    . "{$tanque->id}. Esperado: "
                    . number_format(
                        $volumenPlanAnterior,
                        2,
                        '.',
                        ''
                    )
                    . '; encontrado: '
                    . number_format(
                        $volumenAnterior,
                        2,
                        '.',
                        ''
                    )
                    . '.'
                );
            }

            $volumenMovimiento =
                round(
                    (float) $detalle['galones'],
                    2
                );

            $volumenResultante =
                round(
                    $volumenAnterior
                    + $volumenMovimiento,
                    2
                );

            if (
                $volumenResultante
                > round(
                    (float) $tanque->capacidad_total,
                    2
                )
            ) {
                throw new RuntimeException(
                    "La recarga excede la capacidad "
                    . "del tanque {$tanque->id}."
                );
            }

            $movimientoEntrada =
                MovimientoInventarioCombustible::create([
                    'empresa_id' =>
                        (int) $evento['empresa_id'],

                    'tanque_id' =>
                        $tanque->id,

                    'abastecimiento_id' =>
                        null,

                    'recarga_combustible_id' =>
                        $recarga->id,

                    'tipo_movimiento' =>
                        'entrada_recarga',

                    'volumen_anterior' =>
                        $volumenAnterior,

                    'sentido_movimiento' =>
                        'entrada',

                    'volumen_movimiento' =>
                        $volumenMovimiento,

                    'volumen_resultante' =>
                        $volumenResultante,

                    'subtotal_compra' =>
                        round(
                            $volumenMovimiento
                            * (float) $evento[
                                'precio_galon'
                            ],
                            2
                        ),

                    'fecha_hora_movimiento' =>
                        $fechaRecarga,

                    'observaciones' =>
                        null,

                    'usuario_registra_id' =>
                        $usuarioId,

                    'estado' =>
                        $estado === 'anulado'
                            ? 'anulado'
                            : 'registrado',

                    'fecha_creacion' =>
                        $fechaRecarga,

                    'fecha_actualizacion' =>
                        $fechaAnulacion,

                    'actualizado_por' =>
                        $fechaAnulacion
                            ? $usuarioId
                            : null,

                    'fecha_anulacion' =>
                        $fechaAnulacion,

                    'anulado_por' =>
                        $fechaAnulacion
                            ? $usuarioId
                            : null,

                    'motivo_anulacion' =>
                        $evento[
                            'motivo_anulacion_plan'
                        ] ?? null,
                ]);

            if ($estado === 'anulado') {
                MovimientoInventarioCombustible::create([
                    'empresa_id' =>
                        (int) $evento['empresa_id'],

                    'tanque_id' =>
                        $tanque->id,

                    'abastecimiento_id' =>
                        null,

                    'recarga_combustible_id' =>
                        $recarga->id,

                    'tipo_movimiento' =>
                        'reversion_anulacion_recarga',

                    'volumen_anterior' =>
                        $volumenResultante,

                    'sentido_movimiento' =>
                        'salida',

                    'volumen_movimiento' =>
                        $volumenMovimiento,

                    'volumen_resultante' =>
                        $volumenAnterior,

                    'subtotal_compra' =>
                        null,

                    'fecha_hora_movimiento' =>
                        $fechaAnulacion,

                    'observaciones' =>
                        'Reversión completa de la recarga #'
                        . $recarga->id
                        . '.',

                    'usuario_registra_id' =>
                        $usuarioId,

                    'estado' =>
                        'registrado',

                    'fecha_creacion' =>
                        $fechaAnulacion,
                ]);

                $tanque->update([
                    'volumen_actual' =>
                        $volumenAnterior,

                    'fecha_actualizacion' =>
                        $fechaAnulacion,

                    'actualizado_por' =>
                        $usuarioId,
                ]);
            } else {
                $tanque->update([
                    'volumen_actual' =>
                        $volumenResultante,

                    'fecha_actualizacion' =>
                        $fechaRecarga,

                    'actualizado_por' =>
                        $usuarioId,
                ]);
            }
        }

        return $recarga;
    }

    /**
     * Valida la estructura mínima de un evento de recarga.
     */
    private static function validarEvento(
        array $evento
    ): void {
        $campos = [
            'tipo',
            'numero',
            'fecha_hora',
            'empresa_id',
            'gasolinera_id',
            'precio_galon',
            'detalles',
            'total_galones',
            'total_compra',
            'estado_plan',
        ];

        foreach (
            $campos
            as $campo
        ) {
            if (! array_key_exists($campo, $evento)) {
                throw new RuntimeException(
                    "El evento de recarga no contiene "
                    . "el campo [{$campo}]."
                );
            }
        }

        if ($evento['tipo'] !== 'recarga') {
            throw new RuntimeException(
                'El materializador recibió un evento '
                . 'que no es una recarga.'
            );
        }

        if (
            ! in_array(
                $evento['estado_plan'],
                [
                    'registrado',
                    'anulado',
                ],
                true
            )
        ) {
            throw new RuntimeException(
                'El estado planificado de la recarga '
                . 'no es válido.'
            );
        }

        if ($evento['detalles'] === []) {
            throw new RuntimeException(
                'Una recarga debe contener al menos '
                . 'un tanque afectado.'
            );
        }

        if (
            (float) $evento['total_galones'] <= 0
            || (float) $evento['precio_galon'] <= 0
        ) {
            throw new RuntimeException(
                'La recarga debe poseer galones y '
                . 'precio positivos.'
            );
        }
    }
}