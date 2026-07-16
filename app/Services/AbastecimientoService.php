<?php

namespace App\Services;

use App\Models\Abastecimiento;
use App\Models\AbastecimientoRuta;
use App\Models\AbastecimientoTanque;
use App\Models\Gasolinera;
use App\Models\GasolineraExterna;
use App\Models\Marchamo;
use App\Models\Motorista;
use App\Models\MovimientoInventarioCombustible;
use App\Models\PuntoSeguridadUnidad;
use App\Models\ReemplazoMarchamoDetalle;
use App\Models\ReemplazoMarchamoEvento;
use App\Models\Ruta;
use App\Models\Tanque;
use App\Models\Unidad;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AbastecimientoService
{
    /**
     * Registra un abastecimiento completo.
     *
     * Estructura esperada en $datos:
     *
     * empresa_id
     * unidad_id
     * motorista_id
     * ultimo_abastecimiento_id
     * lectura_actual
     * volumen_inicial
     * tipo_origen
     *
     * Para origen interno:
     * gasolinera_interna_id
     * tanques => [
     *     [
     *         tanque_id,
     *         galones,
     *     ],
     * ]
     *
     * Para origen externo:
     * gasolinera_externa_id
     * galones_externos
     * precio_galon
     *
     * Para galones_viaje desde el segundo abastecimiento:
     * rutas => [
     *     [
     *         ruta_id,
     *         tipo_recorrido,
     *     ],
     * ]
     *
     * Marchamos:
     * marchamos => [
     *     [
     *         punto_seguridad_id,
     *         nuevo_codigo_marchamo,
     *     ],
     * ]
     */
    public function registrar(
        array $datos,
        int $usuarioId
    ): Abastecimiento {
        return DB::transaction(
            function () use (
                $datos,
                $usuarioId
            ): Abastecimiento {
                $fechaOperacion = now();

                /*
                |--------------------------------------------------------------------------
                | Unidad y empresa
                |--------------------------------------------------------------------------
                */

                $unidad = Unidad::query()
                    ->with([
                        'empresa',
                        'licencia',
                    ])
                    ->whereKey(
                        (int) ($datos['unidad_id'] ?? 0)
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $unidad) {
                    $this->fallar(
                        'unidad_id',
                        'La unidad seleccionada no existe.'
                    );
                }

                $empresaId = (int) (
                    $datos['empresa_id']
                    ?? $unidad->empresa_id
                );

                if (
                    $empresaId !== (int) $unidad->empresa_id
                ) {
                    $this->fallar(
                        'empresa_id',
                        'La unidad no pertenece a la empresa seleccionada.'
                    );
                }

                $this->validarUnidadOperable(
                    $unidad
                );

                /*
                |--------------------------------------------------------------------------
                | Motorista
                |--------------------------------------------------------------------------
                */

                $motorista = Motorista::query()
                    ->whereKey(
                        (int) ($datos['motorista_id'] ?? 0)
                    )
                    ->where(
                        'empresa_id',
                        $empresaId
                    )
                    ->where(
                        'estado',
                        'activo'
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $motorista) {
                    $this->fallar(
                        'motorista_id',
                        'El motorista no está activo o no pertenece a la empresa de la unidad.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Cadena y concurrencia
                |--------------------------------------------------------------------------
                */

                $abastecimientoAnterior =
                    Abastecimiento::query()
                        ->where(
                            'unidad_id',
                            $unidad->id
                        )
                        ->where(
                            'estado',
                            Abastecimiento::ESTADO_REGISTRADO
                        )
                        ->orderByDesc(
                            'fecha_hora_abastecimiento'
                        )
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->first();

                $ultimoIdFormulario = $datos[
                    'ultimo_abastecimiento_id'
                ] ?? null;

                $ultimoIdFormulario = filled(
                    $ultimoIdFormulario
                )
                    ? (int) $ultimoIdFormulario
                    : null;

                $ultimoIdReal =
                    $abastecimientoAnterior?->id;

                if (
                    $ultimoIdFormulario
                    !== $ultimoIdReal
                ) {
                    $this->fallar(
                        'unidad_id',
                        'La unidad recibió otro abastecimiento después de abrir el formulario. Actualice la página antes de continuar.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Lectura y combustible inicial
                |--------------------------------------------------------------------------
                */

                $modeloMedicion =
                    (string) $unidad->modelo_medicion;

                if (
                    ! in_array(
                        $modeloMedicion,
                        [
                            Abastecimiento::MODELO_GALONES_KILOMETRO,
                            Abastecimiento::MODELO_GALONES_HORA,
                            Abastecimiento::MODELO_GALONES_VIAJE,
                        ],
                        true
                    )
                ) {
                    $this->fallar(
                        'unidad_id',
                        'La unidad no posee un modelo de medición válido.'
                    );
                }

                $lecturaActual = $this->numero(
                    $datos['lectura_actual'] ?? null,
                    'lectura_actual',
                    'Debe ingresar la lectura actual de la unidad.'
                );

                $volumenInicial = $this->numero(
                    $datos['volumen_inicial'] ?? null,
                    'volumen_inicial',
                    'Debe ingresar el combustible existente antes de la carga.'
                );

                if ($lecturaActual < 0) {
                    $this->fallar(
                        'lectura_actual',
                        'La lectura actual no puede ser negativa.'
                    );
                }

                if ($volumenInicial < 0) {
                    $this->fallar(
                        'volumen_inicial',
                        'El combustible inicial no puede ser negativo.'
                    );
                }

                $lecturaAnterior =
                    $abastecimientoAnterior
                        ? (float) $abastecimientoAnterior
                            ->lectura_actual
                        : null;

                $diferenciaLectura = null;

                if (! is_null($lecturaAnterior)) {
                    if (
                        $lecturaActual
                        < $lecturaAnterior
                    ) {
                        $this->fallar(
                            'lectura_actual',
                            'La lectura actual no puede ser menor que la lectura del abastecimiento anterior.'
                        );
                    }

                    $diferenciaLectura = round(
                        $lecturaActual
                        - $lecturaAnterior,
                        2
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Origen
                |--------------------------------------------------------------------------
                */

                $tipoOrigen = (string) (
                    $datos['tipo_origen']
                    ?? ''
                );

                if (
                    ! in_array(
                        $tipoOrigen,
                        [
                            Abastecimiento::ORIGEN_INTERNO,
                            Abastecimiento::ORIGEN_EXTERNO,
                        ],
                        true
                    )
                ) {
                    $this->fallar(
                        'tipo_origen',
                        'Debe seleccionar un origen de combustible válido.'
                    );
                }

                $gasolineraInterna = null;
                $gasolineraExterna = null;
                $tanquesProcesados = collect();
                $precioGalon = null;
                $totalPagado = null;
                $moneda = null;

                if (
                    $tipoOrigen
                    === Abastecimiento::ORIGEN_INTERNO
                ) {
                    [
                        $gasolineraInterna,
                        $tanquesProcesados,
                        $volumenCargado,
                    ] = $this->prepararOrigenInterno(
                        $datos,
                        $empresaId
                    );

                    $origenNombre =
                        $gasolineraInterna->nombre;
                } else {
                    [
                        $gasolineraExterna,
                        $volumenCargado,
                        $precioGalon,
                        $totalPagado,
                    ] = $this->prepararOrigenExterno(
                        $datos,
                        $empresaId
                    );

                    $moneda = 'USD';

                    $origenNombre =
                        $gasolineraExterna->compania;
                }

                /*
                |--------------------------------------------------------------------------
                | Capacidad de la unidad
                |--------------------------------------------------------------------------
                */

                $capacidadCubierta = round(
                    (float) $unidad->capacidad_cubierta,
                    2
                );

                if ($capacidadCubierta <= 0) {
                    $this->fallar(
                        'unidad_id',
                        'La unidad no posee una capacidad cubierta válida.'
                    );
                }

                $volumenFinal = round(
                    $volumenInicial
                    + $volumenCargado,
                    2
                );

                if (
                    $volumenFinal
                    > $capacidadCubierta
                ) {
                    $this->fallar(
                        'volumen_inicial',
                        'El combustible inicial más la carga excede la capacidad cubierta de la unidad.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Cierre del ciclo anterior
                |--------------------------------------------------------------------------
                */

                $volumenFinalAnterior = null;
                $combustibleConsumido = null;
                $combustibleAdicional = 0.0;

                if ($abastecimientoAnterior) {
                    $volumenFinalAnterior = round(
                        (float) $abastecimientoAnterior
                            ->volumen_final,
                        2
                    );

                    if (
                        $volumenInicial
                        <= $volumenFinalAnterior
                    ) {
                        $combustibleConsumido = round(
                            $volumenFinalAnterior
                            - $volumenInicial,
                            2
                        );
                    } else {
                        $combustibleAdicional = round(
                            $volumenInicial
                            - $volumenFinalAnterior,
                            2
                        );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Rutas
                |--------------------------------------------------------------------------
                */

                $rutasProcesadas =
                    $this->prepararRutas(
                        $datos,
                        $unidad,
                        $empresaId,
                        $abastecimientoAnterior
                    );

                $totalRutas =
                    $rutasProcesadas->count();

                $kilometrosTeoricos =
                    $totalRutas > 0
                        ? round(
                            $rutasProcesadas->sum(
                                'kilometros_aplicados'
                            ),
                            2
                        )
                        : null;

                $galonesTeoricos =
                    $totalRutas > 0
                        ? round(
                            $rutasProcesadas->sum(
                                'galones_aplicados'
                            ),
                            2
                        )
                        : null;

                /*
                |--------------------------------------------------------------------------
                | Rendimientos
                |--------------------------------------------------------------------------
                */

                $galonesPorKilometro = null;
                $kilometrosPorGalon = null;
                $galonesPorHora = null;

                if (
                    ! is_null($diferenciaLectura)
                    && $diferenciaLectura > 0
                    && ! is_null($combustibleConsumido)
                    && $combustibleConsumido > 0
                    && $combustibleAdicional <= 0
                ) {
                    if (
                        in_array(
                            $modeloMedicion,
                            [
                                Abastecimiento::MODELO_GALONES_KILOMETRO,
                                Abastecimiento::MODELO_GALONES_VIAJE,
                            ],
                            true
                        )
                    ) {
                        $galonesPorKilometro = round(
                            $combustibleConsumido
                            / $diferenciaLectura,
                            6
                        );

                        $kilometrosPorGalon = round(
                            $diferenciaLectura
                            / $combustibleConsumido,
                            6
                        );
                    }

                    if (
                        $modeloMedicion
                        === Abastecimiento::MODELO_GALONES_HORA
                    ) {
                        $galonesPorHora = round(
                            $combustibleConsumido
                            / $diferenciaLectura,
                            6
                        );
                    }
                }

                $diferenciaKilometrosTeoricos = null;
                $diferenciaGalonesTeoricos = null;

                if (
                    $modeloMedicion
                    === Abastecimiento::MODELO_GALONES_VIAJE
                    && ! is_null($kilometrosTeoricos)
                ) {
                    $diferenciaKilometrosTeoricos =
                        round(
                            (float) $diferenciaLectura
                            - $kilometrosTeoricos,
                            2
                        );

                    if (
                        ! is_null($combustibleConsumido)
                        && ! is_null($galonesTeoricos)
                    ) {
                        $diferenciaGalonesTeoricos =
                            round(
                                $combustibleConsumido
                                - $galonesTeoricos,
                                2
                            );
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Marchamos
                |--------------------------------------------------------------------------
                */

                $marchamosProcesados =
                    $this->prepararMarchamos(
                        $datos,
                        $unidad,
                        $empresaId
                    );

                /*
                |--------------------------------------------------------------------------
                | Crear cabecera
                |--------------------------------------------------------------------------
                */

                $empresaNombre =
                    $unidad->empresa?->nombre_comercial
                    ?: $unidad->empresa?->nombre_legal
                    ?: 'Sin empresa';

                $motoristaNombre = trim(
                    $motorista->nombres
                    . ' '
                    . $motorista->apellidos
                );

                $abastecimiento =
                    Abastecimiento::create([
                        'empresa_id' =>
                            $empresaId,

                        'unidad_id' =>
                            $unidad->id,

                        'motorista_id' =>
                            $motorista->id,

                        'abastecimiento_anterior_id' =>
                            $abastecimientoAnterior?->id,

                        'registrado_por' =>
                            $usuarioId,

                        'empresa_nombre_snapshot' =>
                            $empresaNombre,

                        'unidad_placa_snapshot' =>
                            $unidad->placa,

                        'unidad_marca_snapshot' =>
                            $unidad->marca,

                        'unidad_modelo_snapshot' =>
                            null,

                        'motorista_nombre_snapshot' =>
                            $motoristaNombre,

                        'motorista_licencia_snapshot' =>
                            $motorista->licencia,

                        'fecha_hora_abastecimiento' =>
                            $fechaOperacion,

                        'estado' =>
                            Abastecimiento::ESTADO_REGISTRADO,

                        'modelo_medicion' =>
                            $modeloMedicion,

                        'lectura_actual' =>
                            $lecturaActual,

                        'lectura_anterior' =>
                            $lecturaAnterior,

                        'diferencia_lectura' =>
                            $diferenciaLectura,

                        'volumen_inicial' =>
                            $volumenInicial,

                        'volumen_cargado' =>
                            $volumenCargado,

                        'volumen_final' =>
                            $volumenFinal,

                        'capacidad_cubierta_snapshot' =>
                            $capacidadCubierta,

                        'volumen_final_anterior' =>
                            $volumenFinalAnterior,

                        'combustible_consumido_ciclo' =>
                            $combustibleConsumido,

                        'combustible_adicional_no_explicado' =>
                            $combustibleAdicional,

                        'tipo_origen' =>
                            $tipoOrigen,

                        'gasolinera_interna_id' =>
                            $gasolineraInterna?->id,

                        'gasolinera_externa_id' =>
                            $gasolineraExterna?->id,

                        'origen_nombre_snapshot' =>
                            $origenNombre,

                        'precio_galon' =>
                            $precioGalon,

                        'total_pagado' =>
                            $totalPagado,

                        'moneda' =>
                            $moneda,

                        'total_rutas' =>
                            $totalRutas,

                        'kilometros_teoricos' =>
                            $kilometrosTeoricos,

                        'galones_teoricos' =>
                            $galonesTeoricos,

                        'galones_por_kilometro' =>
                            $galonesPorKilometro,

                        'kilometros_por_galon' =>
                            $kilometrosPorGalon,

                        'galones_por_hora' =>
                            $galonesPorHora,

                        'diferencia_kilometros_teoricos' =>
                            $diferenciaKilometrosTeoricos,

                        'diferencia_galones_teoricos' =>
                            $diferenciaGalonesTeoricos,

                        'total_tapones_abiertos' =>
                            $marchamosProcesados->count(),

                        'total_marchamos_reemplazados' =>
                            $marchamosProcesados->count(),
                    ]);

                /*
                |--------------------------------------------------------------------------
                | Detalles internos y movimientos
                |--------------------------------------------------------------------------
                */

                if (
                    $tipoOrigen
                    === Abastecimiento::ORIGEN_INTERNO
                ) {
                    $this->guardarOrigenInterno(
                        $abastecimiento,
                        $tanquesProcesados,
                        $fechaOperacion,
                        $usuarioId
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Detalles de rutas
                |--------------------------------------------------------------------------
                */

                foreach (
                    $rutasProcesadas
                    as $rutaProcesada
                ) {
                    AbastecimientoRuta::create(
                        array_merge(
                            [
                                'abastecimiento_id' =>
                                    $abastecimiento->id,
                            ],
                            $rutaProcesada
                        )
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Reemplazo de marchamos
                |--------------------------------------------------------------------------
                */

                $this->guardarMarchamos(
                    $abastecimiento,
                    $marchamosProcesados,
                    $fechaOperacion,
                    $usuarioId
                );

                return $abastecimiento->fresh([
                    'empresa',
                    'unidad',
                    'motorista',
                    'tanques',
                    'rutas',
                    'movimientosInventario',
                    'reemplazoMarchamoEvento.detalles',
                ]);
            },
            3
        );
    }

    /**
     * Validar disponibilidad total de la unidad.
     */
    private function validarUnidadOperable(
        Unidad $unidad
    ): void {
        if (
            ! $unidad->empresa
            || $unidad->empresa->estado !== 'activa'
        ) {
            $this->fallar(
                'unidad_id',
                'La empresa de la unidad está inactiva.'
            );
        }

        if ($unidad->estado !== 'activa') {
            $this->fallar(
                'unidad_id',
                'La unidad debe estar activa para recibir combustible.'
            );
        }

        if (! $unidad->licencia) {
            $this->fallar(
                'unidad_id',
                'La unidad no posee una licencia registrada.'
            );
        }

        if ($unidad->licencia->estado !== 'activa') {
            $this->fallar(
                'unidad_id',
                'La licencia de la unidad está inactiva.'
            );
        }

        if (
            ! $unidad->licencia->fecha_activacion
            || ! $unidad->licencia->fecha_vencimiento
        ) {
            $this->fallar(
                'unidad_id',
                'La licencia no posee un período de vigencia válido.'
            );
        }

        $hoy = now()->startOfDay();

        if (
            $unidad->licencia
                ->fecha_activacion
                ->copy()
                ->startOfDay()
                ->gt($hoy)
        ) {
            $this->fallar(
                'unidad_id',
                'La licencia todavía no ha iniciado su vigencia.'
            );
        }

        if (
            $unidad->licencia
                ->fecha_vencimiento
                ->copy()
                ->startOfDay()
                ->lt($hoy)
        ) {
            $this->fallar(
                'unidad_id',
                'La licencia de la unidad está vencida.'
            );
        }

        $puntosRequeridos =
            PuntoSeguridadUnidad::query()
                ->where(
                    'unidad_id',
                    $unidad->id
                )
                ->where(
                    'estado',
                    'activo'
                )
                ->where(
                    'requiere_marchamo',
                    true
                )
                ->count();

        if ($puntosRequeridos === 0) {
            $this->fallar(
                'unidad_id',
                'La unidad no posee puntos de seguridad activos.'
            );
        }

        $puntosCubiertos =
            PuntoSeguridadUnidad::query()
                ->where(
                    'unidad_id',
                    $unidad->id
                )
                ->where(
                    'estado',
                    'activo'
                )
                ->where(
                    'requiere_marchamo',
                    true
                )
                ->whereNotNull(
                    'marchamo_actual_id'
                )
                ->whereHas(
                    'marchamoActual',
                    function ($query) {
                        $query
                            ->where(
                                'estado',
                                'activo'
                            )
                            ->where(
                                'activo_actual',
                                true
                            );
                    }
                )
                ->count();

        if ($puntosCubiertos !== $puntosRequeridos) {
            $this->fallar(
                'unidad_id',
                'La unidad no posee cobertura completa de marchamos.'
            );
        }
    }

    /**
     * Preparar origen interno bajo bloqueo.
     */
    private function prepararOrigenInterno(
        array $datos,
        int $empresaId
    ): array {
        $gasolinera = Gasolinera::query()
            ->with('empresa')
            ->whereKey(
                (int) (
                    $datos['gasolinera_interna_id']
                    ?? 0
                )
            )
            ->where(
                'empresa_id',
                $empresaId
            )
            ->where(
                'estado',
                'activa'
            )
            ->lockForUpdate()
            ->first();

        if (
            ! $gasolinera
            || ! $gasolinera->empresa
            || $gasolinera->empresa->estado !== 'activa'
        ) {
            $this->fallar(
                'gasolinera_interna_id',
                'La gasolinera interna no está disponible.'
            );
        }

        $lineas = collect(
            $datos['tanques'] ?? []
        )
            ->map(function ($linea) {
                return [
                    'tanque_id' =>
                        (int) ($linea['tanque_id'] ?? 0),

                    'galones' =>
                        round(
                            (float) ($linea['galones'] ?? 0),
                            2
                        ),
                ];
            })
            ->filter(
                fn (array $linea): bool =>
                    $linea['tanque_id'] > 0
                    && $linea['galones'] > 0
            )
            ->values();

        if ($lineas->isEmpty()) {
            $this->fallar(
                'tanques',
                'Debe indicar al menos un tanque con una cantidad mayor que cero.'
            );
        }

        if (
            $lineas->pluck('tanque_id')->count()
            !== $lineas
                ->pluck('tanque_id')
                ->unique()
                ->count()
        ) {
            $this->fallar(
                'tanques',
                'No puede repetir el mismo tanque dentro del abastecimiento.'
            );
        }

        $tanques = Tanque::query()
            ->whereIn(
                'id',
                $lineas->pluck('tanque_id')
            )
            ->where(
                'gasolinera_id',
                $gasolinera->id
            )
            ->where(
                'estado',
                'activo'
            )
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if (
            $tanques->count()
            !== $lineas->count()
        ) {
            $this->fallar(
                'tanques',
                'Uno o más tanques dejaron de estar disponibles.'
            );
        }

        $procesados = collect();

        foreach (
            $lineas
            as $indice => $linea
        ) {
            $tanque = $tanques->get(
                $linea['tanque_id']
            );

            $inventarioAnterior = round(
                (float) $tanque->volumen_actual,
                2
            );

            $galones = $linea['galones'];

            if ($galones > $inventarioAnterior) {
                $this->fallar(
                    "tanques.{$indice}.galones",
                    "El tanque {$tanque->nombre} no posee inventario suficiente."
                );
            }

            $inventarioResultante = round(
                $inventarioAnterior
                - $galones,
                2
            );

            $procesados->push([
                'tanque' =>
                    $tanque,

                'orden' =>
                    $indice + 1,

                'inventario_anterior' =>
                    $inventarioAnterior,

                'galones_retirados' =>
                    $galones,

                'inventario_resultante' =>
                    $inventarioResultante,

                'quedo_bajo_minimo' =>
                    $inventarioResultante
                    <= (float) $tanque
                        ->volumen_minimo_alerta,
            ]);
        }

        return [
            $gasolinera,
            $procesados,
            round(
                $procesados->sum(
                    'galones_retirados'
                ),
                2
            ),
        ];
    }

    /**
     * Preparar origen externo.
     */
    private function prepararOrigenExterno(
        array $datos,
        int $empresaId
    ): array {
        $gasolinera = GasolineraExterna::query()
            ->with('empresa')
            ->whereKey(
                (int) (
                    $datos['gasolinera_externa_id']
                    ?? 0
                )
            )
            ->where(
                'empresa_id',
                $empresaId
            )
            ->where(
                'estado',
                'activa'
            )
            ->lockForUpdate()
            ->first();

        if (
            ! $gasolinera
            || ! $gasolinera->empresa
            || $gasolinera->empresa->estado !== 'activa'
        ) {
            $this->fallar(
                'gasolinera_externa_id',
                'La gasolinera externa no está disponible.'
            );
        }

        $galones = $this->numero(
            $datos['galones_externos'] ?? null,
            'galones_externos',
            'Debe ingresar la cantidad de galones cargados.'
        );

        $precioGalon = $this->numero(
            $datos['precio_galon'] ?? null,
            'precio_galon',
            'Debe ingresar el precio por galón.'
        );

        if ($galones <= 0) {
            $this->fallar(
                'galones_externos',
                'Los galones cargados deben ser mayores que cero.'
            );
        }

        if ($precioGalon <= 0) {
            $this->fallar(
                'precio_galon',
                'El precio por galón debe ser mayor que cero.'
            );
        }

        $galones = round(
            $galones,
            2
        );

        $precioGalon = round(
            $precioGalon,
            4
        );

        return [
            $gasolinera,
            $galones,
            $precioGalon,
            round(
                $galones * $precioGalon,
                2
            ),
        ];
    }

    /**
     * Preparar rutas y fotografías históricas.
     */
    private function prepararRutas(
        array $datos,
        Unidad $unidad,
        int $empresaId,
        ?Abastecimiento $abastecimientoAnterior
    ): Collection {
        $lineas = collect(
            $datos['rutas'] ?? []
        )->values();

        $requiereRutas =
            $unidad->modelo_medicion
            === Abastecimiento::MODELO_GALONES_VIAJE
            && ! is_null($abastecimientoAnterior);

        if (
            $requiereRutas
            && $lineas->isEmpty()
        ) {
            $this->fallar(
                'rutas',
                'Debe registrar al menos una ruta realizada durante el período.'
            );
        }

        if (
            ! $requiereRutas
            && $lineas->isNotEmpty()
        ) {
            $this->fallar(
                'rutas',
                'Las rutas no aplican para este abastecimiento.'
            );
        }

        if ($lineas->isEmpty()) {
            return collect();
        }

        $rutaIds = $lineas
            ->pluck('ruta_id')
            ->map(
                fn ($id): int => (int) $id
            )
            ->filter()
            ->unique()
            ->values();

        $rutas = Ruta::query()
            ->with([
                'puntoOrigen',
                'puntoDestino',
            ])
            ->whereIn(
                'id',
                $rutaIds
            )
            ->where(
                'empresa_id',
                $empresaId
            )
            ->where(
                'estado',
                'activo'
            )
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if (
            $rutas->count()
            !== $rutaIds->count()
        ) {
            $this->fallar(
                'rutas',
                'Una o más rutas no están activas o no pertenecen a la empresa.'
            );
        }

        return $lineas->map(
            function (
                array $linea,
                int $indice
            ) use ($rutas): array {
                $rutaId = (int) (
                    $linea['ruta_id'] ?? 0
                );

                $tipo = (string) (
                    $linea['tipo_recorrido']
                    ?? ''
                );

                if (
                    ! in_array(
                        $tipo,
                        [
                            AbastecimientoRuta::TIPO_IDA,
                            AbastecimientoRuta::TIPO_IDA_VUELTA,
                        ],
                        true
                    )
                ) {
                    $this->fallar(
                        "rutas.{$indice}.tipo_recorrido",
                        'El tipo de recorrido seleccionado no es válido.'
                    );
                }

                $ruta = $rutas->get(
                    $rutaId
                );

                if (
                    ! $ruta
                    || ! $ruta->puntoOrigen
                    || ! $ruta->puntoDestino
                    || $ruta->puntoOrigen->estado
                        !== 'activo'
                    || $ruta->puntoDestino->estado
                        !== 'activo'
                ) {
                    $this->fallar(
                        "rutas.{$indice}.ruta_id",
                        'La ruta seleccionada posee puntos inactivos o no disponibles.'
                    );
                }

                $factor =
                    $tipo
                    === AbastecimientoRuta::TIPO_IDA_VUELTA
                        ? 2
                        : 1;

                $kilometrosBase = round(
                    (float) $ruta
                        ->kilometros_estimados,
                    2
                );

                $galonesBase = round(
                    (float) $ruta
                        ->galones_estimados,
                    2
                );

                return [
                    'ruta_id' =>
                        $ruta->id,

                    'orden' =>
                        $indice + 1,

                    'tipo_recorrido' =>
                        $tipo,

                    'factor_recorrido' =>
                        $factor,

                    'ruta_nombre_snapshot' =>
                        $ruta->ruta,

                    'punto_origen_id' =>
                        $ruta->punto_origen_id,

                    'punto_destino_id' =>
                        $ruta->punto_destino_id,

                    'punto_origen_nombre_snapshot' =>
                        $ruta->puntoOrigen->nombre,

                    'punto_destino_nombre_snapshot' =>
                        $ruta->puntoDestino->nombre,

                    'kilometros_base_snapshot' =>
                        $kilometrosBase,

                    'galones_base_snapshot' =>
                        $galonesBase,

                    'kilometros_aplicados' =>
                        round(
                            $kilometrosBase * $factor,
                            2
                        ),

                    'galones_aplicados' =>
                        round(
                            $galonesBase * $factor,
                            2
                        ),
                ];
            }
        );
    }

    /**
     * Preparar los tapones y marchamos seleccionados.
     */
    private function prepararMarchamos(
        array $datos,
        Unidad $unidad,
        int $empresaId
    ): Collection {
        $lineas = collect(
            $datos['marchamos'] ?? []
        )
            ->map(function ($linea) {
                return [
                    'punto_seguridad_id' =>
                        (int) (
                            $linea['punto_seguridad_id']
                            ?? 0
                        ),

                    'nuevo_codigo_marchamo' =>
                        trim(
                            (string) (
                                $linea[
                                    'nuevo_codigo_marchamo'
                                ]
                                ?? ''
                            )
                        ),
                ];
            })
            ->filter(
                fn (array $linea): bool =>
                    $linea['punto_seguridad_id'] > 0
            )
            ->values();

        if ($lineas->isEmpty()) {
            $this->fallar(
                'marchamos',
                'Debe seleccionar al menos un tapón abierto.'
            );
        }

        if (
            $lineas->pluck(
                'punto_seguridad_id'
            )->count()
            !== $lineas->pluck(
                'punto_seguridad_id'
            )->unique()->count()
        ) {
            $this->fallar(
                'marchamos',
                'No puede repetir el mismo punto de seguridad.'
            );
        }

        foreach (
            $lineas
            as $indice => $linea
        ) {
            if (
                ! preg_match(
                    '/^\d{7}$/',
                    $linea[
                        'nuevo_codigo_marchamo'
                    ]
                )
            ) {
                $this->fallar(
                    "marchamos.{$indice}.nuevo_codigo_marchamo",
                    'Cada nuevo marchamo debe contener exactamente 7 dígitos.'
                );
            }
        }

        $codigos = $lineas->pluck(
            'nuevo_codigo_marchamo'
        );

        if (
            $codigos->count()
            !== $codigos->unique()->count()
        ) {
            $this->fallar(
                'marchamos',
                'No puede repetir un código de marchamo dentro de la operación.'
            );
        }

        $codigosExistentes = Marchamo::query()
            ->whereIn(
                'codigo_marchamo',
                $codigos
            )
            ->lockForUpdate()
            ->pluck('codigo_marchamo');

        if ($codigosExistentes->isNotEmpty()) {
            $this->fallar(
                'marchamos',
                'Los siguientes códigos ya existen en el sistema: '
                . $codigosExistentes
                    ->unique()
                    ->implode(', ')
            );
        }

        $puntos = PuntoSeguridadUnidad::query()
            ->where(
                'unidad_id',
                $unidad->id
            )
            ->whereIn(
                'id',
                $lineas->pluck(
                    'punto_seguridad_id'
                )
            )
            ->where(
                'estado',
                'activo'
            )
            ->where(
                'requiere_marchamo',
                true
            )
            ->where(
                'tipo_punto',
                'tapón'
            )
            ->where(
                'subgrupo',
                'Depósito'
            )
            ->whereNotNull(
                'marchamo_actual_id'
            )
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if (
            $puntos->count()
            !== $lineas->count()
        ) {
            $this->fallar(
                'marchamos',
                'Uno o más tapones seleccionados no están disponibles para abastecimiento.'
            );
        }

        return $lineas->map(
            function (
                array $linea
            ) use (
                $puntos,
                $empresaId,
                $unidad
            ): array {
                $punto = $puntos->get(
                    $linea[
                        'punto_seguridad_id'
                    ]
                );

                $marchamoAnterior =
                    Marchamo::query()
                        ->whereKey(
                            $punto->marchamo_actual_id
                        )
                        ->where(
                            'empresa_id',
                            $empresaId
                        )
                        ->where(
                            'unidad_id',
                            $unidad->id
                        )
                        ->where(
                            'punto_seguridad_id',
                            $punto->id
                        )
                        ->where(
                            'estado',
                            'activo'
                        )
                        ->where(
                            'activo_actual',
                            true
                        )
                        ->lockForUpdate()
                        ->first();

                if (! $marchamoAnterior) {
                    $this->fallar(
                        'marchamos',
                        "El punto {$punto->nombre_punto} no posee un marchamo activo válido."
                    );
                }

                return [
                    'punto' =>
                        $punto,

                    'marchamo_anterior' =>
                        $marchamoAnterior,

                    'nuevo_codigo_marchamo' =>
                        $linea[
                            'nuevo_codigo_marchamo'
                        ],
                ];
            }
        );
    }

    /**
     * Guardar salidas internas e inventario.
     */
    private function guardarOrigenInterno(
        Abastecimiento $abastecimiento,
        Collection $tanquesProcesados,
        $fechaOperacion,
        int $usuarioId
    ): void {
        foreach (
            $tanquesProcesados
            as $procesado
        ) {
            /** @var Tanque $tanque */
            $tanque = $procesado['tanque'];

            $tanque->update([
                'volumen_actual' =>
                    $procesado[
                        'inventario_resultante'
                    ],

                'fecha_actualizacion' =>
                    $fechaOperacion,

                'actualizado_por' =>
                    $usuarioId,
            ]);

            AbastecimientoTanque::create([
                'abastecimiento_id' =>
                    $abastecimiento->id,

                'tanque_id' =>
                    $tanque->id,

                'orden' =>
                    $procesado['orden'],

                'tanque_nombre_snapshot' =>
                    $tanque->nombre,

                'capacidad_total_snapshot' =>
                    $tanque->capacidad_total,

                'volumen_minimo_alerta_snapshot' =>
                    $tanque->volumen_minimo_alerta,

                'inventario_anterior' =>
                    $procesado[
                        'inventario_anterior'
                    ],

                'galones_retirados' =>
                    $procesado[
                        'galones_retirados'
                    ],

                'inventario_resultante' =>
                    $procesado[
                        'inventario_resultante'
                    ],

                'quedo_bajo_minimo' =>
                    $procesado[
                        'quedo_bajo_minimo'
                    ],
            ]);

            MovimientoInventarioCombustible::create([
                'empresa_id' =>
                    $abastecimiento->empresa_id,

                'tanque_id' =>
                    $tanque->id,

                'abastecimiento_id' =>
                    $abastecimiento->id,

                'recarga_combustible_id' =>
                    null,

                'tipo_movimiento' =>
                    'salida_abastecimiento',

                'volumen_anterior' =>
                    $procesado[
                        'inventario_anterior'
                    ],

                'sentido_movimiento' =>
                    'salida',

                'volumen_movimiento' =>
                    $procesado[
                        'galones_retirados'
                    ],

                'volumen_resultante' =>
                    $procesado[
                        'inventario_resultante'
                    ],

                'subtotal_compra' =>
                    null,

                'fecha_hora_movimiento' =>
                    $fechaOperacion,

                'observaciones' =>
                    null,

                'usuario_registra_id' =>
                    $usuarioId,

                'estado' =>
                    'registrado',

                'fecha_creacion' =>
                    $fechaOperacion,
            ]);
        }
    }

    /**
     * Guardar evento y reemplazos de marchamos.
     */
    private function guardarMarchamos(
        Abastecimiento $abastecimiento,
        Collection $marchamosProcesados,
        $fechaOperacion,
        int $usuarioId
    ): void {
        $evento = ReemplazoMarchamoEvento::create([
            'empresa_id' =>
                $abastecimiento->empresa_id,

            'unidad_id' =>
                $abastecimiento->unidad_id,

            'abastecimiento_id' =>
                $abastecimiento->id,

            'motivo_reemplazo' =>
                ReemplazoMarchamoEvento::
                    MOTIVO_APERTURA_ABASTECIMIENTO,

            'cantidad_reemplazos' =>
                $marchamosProcesados->count(),

            'origen_evento' =>
                ReemplazoMarchamoEvento::
                    ORIGEN_ABASTECIMIENTO,

            'estado' =>
                'registrado',

            'fecha_registro' =>
                $fechaOperacion,

            'registrado_por' =>
                $usuarioId,
        ]);

        foreach (
            $marchamosProcesados
            as $procesado
        ) {
            /** @var PuntoSeguridadUnidad $punto */
            $punto = $procesado['punto'];

            /** @var Marchamo $marchamoAnterior */
            $marchamoAnterior =
                $procesado['marchamo_anterior'];

            /*
             * Debe quedar NULL, no false.
             *
             * La tabla permite múltiples históricos con activo_actual NULL,
             * pero únicamente un marchamo activo con activo_actual = 1.
             */
            $marchamoAnterior->update([
                'estado' =>
                    'reemplazado',

                'activo_actual' =>
                    null,

                'fecha_desactivacion' =>
                    $fechaOperacion,

                'motivo_desactivacion' =>
                    'Apertura por abastecimiento',

                'actualizado_por' =>
                    $usuarioId,
            ]);

            $marchamoNuevo = Marchamo::create([
                'empresa_id' =>
                    $abastecimiento->empresa_id,

                'unidad_id' =>
                    $abastecimiento->unidad_id,

                'punto_seguridad_id' =>
                    $punto->id,

                'codigo_marchamo' =>
                    $procesado[
                        'nuevo_codigo_marchamo'
                    ],

                'fecha_activacion' =>
                    $fechaOperacion,

                'estado' =>
                    'activo',

                'activo_actual' =>
                    true,

                'fecha_desactivacion' =>
                    null,

                'motivo_desactivacion' =>
                    null,

                'origen_creacion' =>
                    'abastecimiento',

                'creado_por' =>
                    $usuarioId,

                'actualizado_por' =>
                    $usuarioId,
            ]);

            $punto->update([
                'marchamo_actual_id' =>
                    $marchamoNuevo->id,

                'estado_asignacion' =>
                    'asignado',

                'actualizado_por' =>
                    $usuarioId,
            ]);

            ReemplazoMarchamoDetalle::create([
                'reemplazo_evento_id' =>
                    $evento->id,

                'punto_seguridad_id' =>
                    $punto->id,

                'marchamo_anterior_id' =>
                    $marchamoAnterior->id,

                'marchamo_nuevo_id' =>
                    $marchamoNuevo->id,

                'fecha_registro' =>
                    $fechaOperacion,
            ]);
        }
    }

    /**
     * Convertir un valor requerido a número.
     */
    private function numero(
        mixed $valor,
        string $campo,
        string $mensaje
    ): float {
        if (
            is_null($valor)
            || $valor === ''
            || ! is_numeric($valor)
        ) {
            $this->fallar(
                $campo,
                $mensaje
            );
        }

        return round(
            (float) $valor,
            4
        );
    }

    /**
     * Lanzar un error de validación.
     */
    private function fallar(
        string $campo,
        string $mensaje
    ): never {
        throw ValidationException::withMessages([
            $campo => $mensaje,
        ]);
    }
}