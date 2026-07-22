<?php

namespace Database\Seeders;

use App\Models\Abastecimiento;
use App\Models\MovimientoInventarioCombustible;
use App\Models\RecargaCombustible;
use App\Models\ReemplazoMarchamoDetalle;
use App\Models\ReemplazoMarchamoEvento;
use App\Models\Tanque;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class CcFlotaOperacionHistoricaSeeder extends Seeder
{
    /**
     * Materializa cronológicamente todas las recargas
     * y abastecimientos definidos en el plan operacional.
     */
    public function run(): void
    {
        $this->command?->info(
            'Materializando operación histórica de CC-Flota...'
        );

        $usuarioId =
            CcFlotaSeederContext::superUserId();

        $plan =
            CcFlotaSeederContext::referencia(
                'operacion.plan'
            );

        if ($plan === []) {
            $plan =
                CcFlotaOperationalPlan::construir();
        }

        $eventos =
            $plan['eventos'] ?? [];

        if ($eventos === []) {
            throw new RuntimeException(
                'El plan operacional no contiene eventos.'
            );
        }

        $totalEsperado =
            CcFlotaSeederConfig::
                TOTAL_RECARGAS_OBJETIVO
            + CcFlotaSeederConfig::
                TOTAL_ABASTECIMIENTOS_OBJETIVO;

        if (count($eventos) !== $totalEsperado) {
            throw new RuntimeException(
                'La cantidad de eventos del plan no coincide '
                . 'con la configuración operacional.'
            );
        }

        $barra =
            $this->command?->getOutput()
                ->createProgressBar(
                    count($eventos)
                );

        $barra?->setFormat(
            ' %current%/%max% [%bar%] %percent:3s%% '
            . '%elapsed:6s% / %estimated:-6s%'
        );

        $barra?->start();

        $recargaIds = [];
        $abastecimientoIds = [];

        try {
            DB::transaction(
                function () use (
                    $eventos,
                    $usuarioId,
                    &$recargaIds,
                    &$abastecimientoIds,
                    $barra
                ): void {
                    foreach (
                        $eventos
                        as $evento
                    ) {
                        if (
                            $evento['tipo']
                            === 'recarga'
                        ) {
                            $recarga =
                                CcFlotaRecargaMaterializer::
                                    crear(
                                        evento: $evento,
                                        usuarioId: $usuarioId
                                    );

                            $recargaIds[] =
                                $recarga->id;
                        } elseif (
                            $evento['tipo']
                            === 'abastecimiento'
                        ) {
                            $abastecimiento =
                                CcFlotaAbastecimientoMaterializer::
                                    crear(
                                        evento: $evento,
                                        usuarioId: $usuarioId
                                    );

                            $abastecimientoIds[] =
                                $abastecimiento->id;
                        } else {
                            throw new RuntimeException(
                                'El plan contiene un tipo '
                                . 'de evento desconocido.'
                            );
                        }

                        $barra?->advance();
                    }

                    $this->validarMaterializacion(
                        plan: CcFlotaSeederContext::
                            referencia(
                                'operacion.plan'
                            ),
                        recargaIds: $recargaIds,
                        abastecimientoIds:
                            $abastecimientoIds
                    );
                },
                1
            );
        } catch (Throwable $error) {
            $barra?->finish();

            $this->command?->newLine(2);

            throw new RuntimeException(
                'La operación histórica fue revertida por completo. '
                . 'Evento procesado: '
                . (
                    count($recargaIds)
                    + count($abastecimientoIds)
                )
                . ' de '
                . count($eventos)
                . '. Error original: '
                . $error->getMessage(),
                previous: $error
            );
        }

        $barra?->finish();

        $this->command?->newLine(2);

        $resumen =
            $this->crearResumenFinal(
                plan: $plan,
                recargaIds: $recargaIds,
                abastecimientoIds:
                    $abastecimientoIds
            );

        CcFlotaSeederContext::registrarReferencia(
            'operacion.recargas_ids',
            $recargaIds
        );

        CcFlotaSeederContext::registrarReferencia(
            'operacion.abastecimientos_ids',
            $abastecimientoIds
        );

        CcFlotaSeederContext::registrarReferencia(
            'operacion.materializada',
            $resumen
        );

        $this->command?->info(
            'Operación histórica materializada correctamente.'
        );

        foreach (
            [
                'Recargas' =>
                    $resumen['recargas'],

                'Recargas anuladas' =>
                    $resumen['recargas_anuladas'],

                'Abastecimientos' =>
                    $resumen['abastecimientos'],

                'Abastecimientos internos' =>
                    $resumen[
                        'abastecimientos_internos'
                    ],

                'Abastecimientos externos' =>
                    $resumen[
                        'abastecimientos_externos'
                    ],

                'Movimientos de inventario' =>
                    $resumen[
                        'movimientos_inventario'
                    ],

                'Eventos de marchamos' =>
                    $resumen[
                        'eventos_marchamos'
                    ],

                'Detalles de marchamos' =>
                    $resumen[
                        'detalles_marchamos'
                    ],
            ]
            as $etiqueta => $valor
        ) {
            $this->command?->line(
                $etiqueta . ': ' . $valor
            );
        }
    }

    /**
     * Verifica que la materialización coincida con el plan
     * antes de confirmar la transacción.
     */
    private function validarMaterializacion(
        array $plan,
        array $recargaIds,
        array $abastecimientoIds
    ): void {
        if (
            count($recargaIds)
            !== CcFlotaSeederConfig::
                TOTAL_RECARGAS_OBJETIVO
        ) {
            throw new RuntimeException(
                'La cantidad materializada de recargas '
                . 'no coincide con el objetivo.'
            );
        }

        if (
            count($abastecimientoIds)
            !== CcFlotaSeederConfig::
                TOTAL_ABASTECIMIENTOS_OBJETIVO
        ) {
            throw new RuntimeException(
                'La cantidad materializada de abastecimientos '
                . 'no coincide con el objetivo.'
            );
        }

        $recargasEnBase =
            RecargaCombustible::query()
                ->whereIn('id', $recargaIds)
                ->count();

        $abastecimientosEnBase =
            Abastecimiento::query()
                ->whereIn(
                    'id',
                    $abastecimientoIds
                )
                ->count();

        if (
            $recargasEnBase
            !== count($recargaIds)
        ) {
            throw new RuntimeException(
                'No todas las recargas planificadas '
                . 'fueron persistidas.'
            );
        }

        if (
            $abastecimientosEnBase
            !== count($abastecimientoIds)
        ) {
            throw new RuntimeException(
                'No todos los abastecimientos planificados '
                . 'fueron persistidos.'
            );
        }

        $inventarioProyectado =
            $plan[
                'inventario_final_proyectado'
            ] ?? [];

        if ($inventarioProyectado === []) {
            throw new RuntimeException(
                'El plan no contiene inventario final '
                . 'proyectado para validar.'
            );
        }

        $tanques = Tanque::query()
            ->whereIn(
                'id',
                array_keys(
                    $inventarioProyectado
                )
            )
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach (
            $inventarioProyectado
            as $tanqueId => $volumenEsperado
        ) {
            $tanque =
                $tanques->get(
                    (int) $tanqueId
                );

            if (! $tanque) {
                throw new RuntimeException(
                    "No existe el tanque {$tanqueId} "
                    . 'durante la validación final.'
                );
            }

            $volumenReal =
                round(
                    (float) $tanque->volumen_actual,
                    2
                );

            $volumenEsperado =
                round(
                    (float) $volumenEsperado,
                    2
                );

            if (
                abs(
                    $volumenReal
                    - $volumenEsperado
                ) > 0.01
            ) {
                throw new RuntimeException(
                    "Inventario final divergente en tanque "
                    . "{$tanqueId}. Esperado: "
                    . number_format(
                        $volumenEsperado,
                        2,
                        '.',
                        ''
                    )
                    . '; encontrado: '
                    . number_format(
                        $volumenReal,
                        2,
                        '.',
                        ''
                    )
                    . '.'
                );
            }
        }

        $abastecimientosInternos =
            Abastecimiento::query()
                ->whereIn(
                    'id',
                    $abastecimientoIds
                )
                ->where(
                    'tipo_origen',
                    Abastecimiento::ORIGEN_INTERNO
                )
                ->count();

        $abastecimientosExternos =
            Abastecimiento::query()
                ->whereIn(
                    'id',
                    $abastecimientoIds
                )
                ->where(
                    'tipo_origen',
                    Abastecimiento::ORIGEN_EXTERNO
                )
                ->count();

        $resumenPlan =
            $plan['resumen'] ?? [];

        if (
            $abastecimientosInternos
            !== (
                $resumenPlan[
                    'abastecimientos_internos'
                ] ?? -1
            )
        ) {
            throw new RuntimeException(
                'Los abastecimientos internos materializados '
                . 'no coinciden con el plan.'
            );
        }

        if (
            $abastecimientosExternos
            !== (
                $resumenPlan[
                    'abastecimientos_externos'
                ] ?? -1
            )
        ) {
            throw new RuntimeException(
                'Los abastecimientos externos materializados '
                . 'no coinciden con el plan.'
            );
        }
    }

    /**
     * Construye el resumen persistido del proceso.
     */
    private function crearResumenFinal(
        array $plan,
        array $recargaIds,
        array $abastecimientoIds
    ): array {
        return [
            'recargas' =>
                count($recargaIds),

            'recargas_registradas' =>
                RecargaCombustible::query()
                    ->whereIn('id', $recargaIds)
                    ->where(
                        'estado',
                        'registrado'
                    )
                    ->count(),

            'recargas_anuladas' =>
                RecargaCombustible::query()
                    ->whereIn('id', $recargaIds)
                    ->where(
                        'estado',
                        'anulado'
                    )
                    ->count(),

            'abastecimientos' =>
                count($abastecimientoIds),

            'abastecimientos_internos' =>
                Abastecimiento::query()
                    ->whereIn(
                        'id',
                        $abastecimientoIds
                    )
                    ->where(
                        'tipo_origen',
                        Abastecimiento::
                            ORIGEN_INTERNO
                    )
                    ->count(),

            'abastecimientos_externos' =>
                Abastecimiento::query()
                    ->whereIn(
                        'id',
                        $abastecimientoIds
                    )
                    ->where(
                        'tipo_origen',
                        Abastecimiento::
                            ORIGEN_EXTERNO
                    )
                    ->count(),

            'lineas_base' =>
                Abastecimiento::query()
                    ->whereIn(
                        'id',
                        $abastecimientoIds
                    )
                    ->whereNull(
                        'abastecimiento_anterior_id'
                    )
                    ->count(),

            'movimientos_inventario' =>
                MovimientoInventarioCombustible::
                    query()
                    ->where(
                        function ($query) use (
                            $recargaIds,
                            $abastecimientoIds
                        ): void {
                            $query
                                ->whereIn(
                                    'recarga_combustible_id',
                                    $recargaIds
                                )
                                ->orWhereIn(
                                    'abastecimiento_id',
                                    $abastecimientoIds
                                );
                        }
                    )
                    ->count(),

            'eventos_marchamos' =>
                ReemplazoMarchamoEvento::query()
                    ->whereIn(
                        'abastecimiento_id',
                        $abastecimientoIds
                    )
                    ->count(),

            'detalles_marchamos' =>
                ReemplazoMarchamoDetalle::query()
                    ->whereHas(
                        'evento',
                        function ($query) use (
                            $abastecimientoIds
                        ): void {
                            $query->whereIn(
                                'abastecimiento_id',
                                $abastecimientoIds
                            );
                        }
                    )
                    ->count(),

            'inventario_final_total' =>
                round(
                    array_sum(
                        $plan[
                            'inventario_final_proyectado'
                        ]
                    ),
                    2
                ),
        ];
    }
}