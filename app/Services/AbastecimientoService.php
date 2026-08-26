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
use App\Support\Decimal;
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
     * kilometraje_actual
     * horometro_actual (solo para galones_hora)
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
                            Abastecimiento::MODELO_KILOMETROS_GALON,
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

                $kilometrajeActual = $this->numero(
                    $datos['kilometraje_actual'] ?? null,
                    'kilometraje_actual',
                    'Debe ingresar el kilometraje actual de la unidad.'
                );

                $horometroActual = null;

                if (
                    $modeloMedicion
                    === Abastecimiento::MODELO_GALONES_HORA
                ) {
                    $horometroActual = $this->numero(
                        $datos['horometro_actual'] ?? null,
                        'horometro_actual',
                        'Debe ingresar la lectura actual del horómetro.'
                    );
                }

                if ($kilometrajeActual < 0) {
                    $this->fallar(
                        'kilometraje_actual',
                        'El kilometraje actual no puede ser negativo.'
                    );
                }

                if (
                    ! is_null($horometroActual)
                    && $horometroActual < 0
                ) {
                    $this->fallar(
                        'horometro_actual',
                        'La lectura del horómetro no puede ser negativa.'
                    );
                }

                $kilometrajeAnterior =
                    $abastecimientoAnterior
                        ? (float) (
                            $abastecimientoAnterior
                                ->kilometraje_actual
                            ?? $abastecimientoAnterior
                                ->lectura_actual
                        )
                        : null;

                $diferenciaKilometraje = null;

                if (! is_null($kilometrajeAnterior)) {
                    if (
                        $kilometrajeActual
                        < $kilometrajeAnterior
                    ) {
                        $this->fallar(
                            'kilometraje_actual',
                            'El kilometraje actual no puede ser menor que el kilometraje del abastecimiento anterior.'
                        );
                    }

                    $diferenciaKilometraje = round(
                        $kilometrajeActual
                        - $kilometrajeAnterior,
                        2
                    );
                }

                $horometroAnterior = null;
                $diferenciaHorometro = null;

                if (
                    $modeloMedicion
                    === Abastecimiento::MODELO_GALONES_HORA
                    && $abastecimientoAnterior
                ) {
                    $horometroAnterior = filled(
                        $abastecimientoAnterior
                            ->horometro_actual
                    )
                        ? (float) $abastecimientoAnterior
                            ->horometro_actual
                        : null;

                    if (is_null($horometroAnterior)) {
                        $this->fallar(
                            'horometro_actual',
                            'El abastecimiento anterior no posee una lectura de horómetro válida. Registre una nueva línea base después de reiniciar los datos de prueba.'
                        );
                    }

                    if (
                        $horometroActual
                        < $horometroAnterior
                    ) {
                        $this->fallar(
                            'horometro_actual',
                            'La lectura actual del horómetro no puede ser menor que la lectura anterior.'
                        );
                    }

                    $diferenciaHorometro = round(
                        $horometroActual
                        - $horometroAnterior,
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

                    $origenNombre = trim(
                        $gasolineraExterna->compania
                        . ' — '
                        . $gasolineraExterna->direccion
                    );
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

                if (! $abastecimientoAnterior) {
                    if ($volumenCargado !== $capacidadCubierta) {
                        $this->fallar(
                            'volumen_cargado',
                            'El primer abastecimiento debe cargar exactamente toda la capacidad cubierta de la unidad.'
                        );
                    }
                } elseif (
                    $volumenCargado <= 0
                    || $volumenCargado > $capacidadCubierta
                ) {
                    $this->fallar(
                        'volumen_cargado',
                        'La carga debe ser mayor que cero y no puede exceder la capacidad cubierta de la unidad.'
                    );
                }

                $volumenFinal = $capacidadCubierta;

                /*
                |--------------------------------------------------------------------------
                | Cierre del ciclo anterior
                |--------------------------------------------------------------------------
                */

                $volumenFinalAnterior = null;
                $combustibleConsumido = $abastecimientoAnterior
                    ? $volumenCargado
                    : null;
                $combustibleAdicional = 0.0;

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

                $totalViajes = (int) $rutasProcesadas
                    ->sum('factor_recorrido');

                if (
                    $modeloMedicion
                        === Abastecimiento::MODELO_GALONES_VIAJE
                    && $abastecimientoAnterior
                    && $diferenciaKilometraje === 0.0
                ) {
                    $this->fallar(
                        'kilometraje_actual',
                        'Se registraron viajes, pero el kilometraje no avanzó.'
                    );
                }

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
                $consumoRealCiclo = $abastecimientoAnterior
                    ? Decimal::normalizar((string) $volumenCargado, 2)
                    : null;
                $consumoTeoricoCiclo = null;
                $diferenciaGalonesCiclo = null;

                if ($abastecimientoAnterior) {
                    if ($modeloMedicion === Abastecimiento::MODELO_KILOMETROS_GALON) {
                        $kilometrosPorGalon = $diferenciaKilometraje === 0.0
                            ? 0.0
                            : round($volumenCargado > 0
                                ? $diferenciaKilometraje / $volumenCargado
                                : 0, 6);
                        $galonesPorKilometro = $diferenciaKilometraje > 0
                            ? round($volumenCargado / $diferenciaKilometraje, 6)
                            : null;
                        $consumoTeoricoCiclo = Decimal::dividir(
                            (string) $diferenciaKilometraje,
                            (string) $abastecimientoAnterior->rendimiento_teorico_km_galon_snapshot,
                            8
                        );
                    } elseif ($modeloMedicion === Abastecimiento::MODELO_GALONES_HORA) {
                        if ($diferenciaHorometro === 0.0) {
                            $this->fallar(
                                'horometro_actual',
                                'Se consumió combustible, pero el horómetro no avanzó.'
                            );
                        }
                        $galonesPorHora = round($volumenCargado / $diferenciaHorometro, 6);
                        $consumoTeoricoCiclo = Decimal::multiplicar(
                            (string) $diferenciaHorometro,
                            (string) $abastecimientoAnterior->rendimiento_teorico_gal_hora_snapshot,
                            8
                        );
                    } else {
                        $consumoTeoricoCiclo = Decimal::normalizar(
                            (string) ($galonesTeoricos ?? 0),
                            8
                        );
                    }

                    $diferenciaGalonesCiclo = Decimal::restar(
                        $consumoTeoricoCiclo,
                        $consumoRealCiclo,
                        8
                    );
                }

                $diferenciaKilometrosTeoricos = null;
                $diferenciaGalonesTeoricos = null;

                if (
                    $modeloMedicion
                    === Abastecimiento::MODELO_GALONES_VIAJE
                    && ! is_null($kilometrosTeoricos)
                    && ! is_null($diferenciaKilometraje)
                ) {
                    $diferenciaKilometrosTeoricos =
                        round(
                            (float) $diferenciaKilometraje
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
                | Economía agregada a bordo
                |--------------------------------------------------------------------------
                */

                $valorCarga = $tipoOrigen === Abastecimiento::ORIGEN_INTERNO
                    ? $tanquesProcesados->reduce(
                        fn (string $total, array $detalle): string => Decimal::sumar(
                            $total,
                            (string) $detalle['costo_total_snapshot'],
                            8
                        ),
                        '0.00000000'
                    )
                    : Decimal::multiplicar(
                        (string) $volumenCargado,
                        (string) $precioGalon,
                        8
                    );

                $costoEfectivoCarga = Decimal::dividir(
                    $valorCarga,
                    (string) $volumenCargado,
                    8
                );
                $costoCombustibleConsumido = null;
                $valorRemanente = null;

                if (! $abastecimientoAnterior) {
                    $valorAbordoResultante = $valorCarga;
                } else {
                    if (
                        Decimal::comparar(
                            (string) $abastecimientoAnterior->capacidad_cubierta_snapshot,
                            (string) $capacidadCubierta,
                            2
                        ) !== 0
                    ) {
                        $this->fallar(
                            'unidad_id',
                            'La capacidad cubierta cambió durante el ciclo abierto.'
                        );
                    }

                    if (
                        is_null($abastecimientoAnterior->valor_abordo_resultante)
                        || is_null($abastecimientoAnterior->costo_promedio_abordo_resultante)
                    ) {
                        $this->fallar(
                            'unidad_id',
                            'El ciclo abierto no posee un inventario económico a bordo conocido.'
                        );
                    }

                    $costoCombustibleConsumido = Decimal::multiplicar(
                        (string) $volumenCargado,
                        (string) $abastecimientoAnterior->costo_promedio_abordo_resultante,
                        8
                    );
                    $valorRemanente = Decimal::restar(
                        (string) $abastecimientoAnterior->valor_abordo_resultante,
                        $costoCombustibleConsumido,
                        8
                    );
                    $valorAbordoResultante = Decimal::sumar(
                        $valorRemanente,
                        $valorCarga,
                        8
                    );
                }

                $costoPromedioAbordoResultante = Decimal::dividir(
                    $valorAbordoResultante,
                    (string) $capacidadCubierta,
                    8
                );

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

                        'rendimiento_teorico_km_galon_snapshot' =>
                            $unidad->rendimiento_teorico_km_galon,

                        'rendimiento_teorico_gal_hora_snapshot' =>
                            $unidad->rendimiento_teorico_gal_hora,

                        /*
                         * Compatibilidad temporal:
                         * los campos lectura_* reflejan kilometraje.
                         */
                        'lectura_actual' =>
                            $kilometrajeActual,

                        'lectura_anterior' =>
                            $kilometrajeAnterior,

                        'diferencia_lectura' =>
                            $diferenciaKilometraje,

                        'kilometraje_actual' =>
                            $kilometrajeActual,

                        'kilometraje_anterior' =>
                            $kilometrajeAnterior,

                        'diferencia_kilometraje' =>
                            $diferenciaKilometraje,

                        'horometro_actual' =>
                            $horometroActual,

                        'horometro_anterior' =>
                            $horometroAnterior,

                        'diferencia_horometro' =>
                            $diferenciaHorometro,

                        'volumen_inicial' =>
                            0,

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

                        'consumo_real_ciclo' =>
                            $consumoRealCiclo,

                        'consumo_teorico_ciclo' =>
                            $consumoTeoricoCiclo,

                        'diferencia_galones_ciclo' =>
                            $diferenciaGalonesCiclo,

                        'costo_combustible_consumido_ciclo' =>
                            $costoCombustibleConsumido,

                        'valor_remanente_antes_carga_snapshot' =>
                            $valorRemanente,

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

                        'valor_carga_snapshot' =>
                            $valorCarga,

                        'costo_efectivo_carga_snapshot' =>
                            $costoEfectivoCarga,

                        'valor_abordo_resultante' =>
                            $valorAbordoResultante,

                        'costo_promedio_abordo_resultante' =>
                            $costoPromedioAbordoResultante,

                        'total_rutas' =>
                            $totalRutas,

                        'total_viajes' =>
                            $totalViajes,

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

                $unidad->update([
                    'valor_combustible_abordo_actual' =>
                        $valorAbordoResultante,
                    'costo_promedio_abordo_actual' =>
                        $costoPromedioAbordoResultante,
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
     * Modifica de forma atómica el último abastecimiento registrado
     * de una unidad.
     *
     * Reglas principales:
     *
     * - el abastecimiento debe continuar registrado;
     * - debe seguir siendo el último registro vigente de la unidad;
     * - la versión recibida debe coincidir con la versión almacenada;
     * - únicamente se revisan los tapones abiertos por esta operación;
     * - los marchamos instalados en esos tapones deben continuar siendo
     *   los marchamos actuales;
     * - los puntos de seguridad afectados no pueden cambiar;
     * - los ajustes de inventario se aplican por diferencia sobre el
     *   inventario actual de cada tanque.
     */
    public function modificar(
        Abastecimiento $abastecimiento,
        array $datos,
        int $usuarioId
    ): Abastecimiento {
        $this->fallar(
            'abastecimiento',
            'Los abastecimientos registrados están congelados y no pueden modificarse.'
        );

        return DB::transaction(
            function () use (
                $abastecimiento,
                $datos,
                $usuarioId
            ): Abastecimiento {
                $fechaOperacion = now();

                /*
                |--------------------------------------------------------------------------
                | Bloqueo del abastecimiento y validación de concurrencia
                |--------------------------------------------------------------------------
                */

                $abastecimientoBloqueado =
                    Abastecimiento::query()
                        ->whereKey(
                            $abastecimiento->id
                        )
                        ->lockForUpdate()
                        ->first();

                if (! $abastecimientoBloqueado) {
                    $this->fallar(
                        'abastecimiento',
                        'El abastecimiento seleccionado ya no existe.'
                    );
                }

                if (
                    $abastecimientoBloqueado->estado
                    !== Abastecimiento::ESTADO_REGISTRADO
                ) {
                    $this->fallar(
                        'abastecimiento',
                        'El abastecimiento ya no se encuentra registrado y no puede modificarse.'
                    );
                }

                $versionFormulario = trim(
                    (string) (
                        $datos['abastecimiento_version']
                        ?? ''
                    )
                );

                $versionReal =
                    $this->versionAbastecimiento(
                        $abastecimientoBloqueado
                    );

                if (
                    $versionFormulario === ''
                    || ! hash_equals(
                        $versionReal,
                        $versionFormulario
                    )
                ) {
                    $this->fallar(
                        'abastecimiento',
                        'El abastecimiento fue modificado después de abrir el formulario. Actualice la página antes de continuar.'
                    );
                }

                $ultimoAbastecimiento =
                    Abastecimiento::query()
                        ->where(
                            'unidad_id',
                            $abastecimientoBloqueado
                                ->unidad_id
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

                if (
                    ! $ultimoAbastecimiento
                    || (int) $ultimoAbastecimiento->id
                        !== (int) $abastecimientoBloqueado->id
                ) {
                    $this->fallar(
                        'abastecimiento',
                        'Este abastecimiento dejó de ser el último registro vigente de la unidad y ya no puede modificarse.'
                    );
                }

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
                        $abastecimientoBloqueado
                            ->unidad_id
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $unidad) {
                    $this->fallar(
                        'unidad_id',
                        'La unidad asociada al abastecimiento ya no existe.'
                    );
                }

                $empresaId = (int)
                    $abastecimientoBloqueado
                        ->empresa_id;

                if (
                    (int) ($datos['empresa_id'] ?? 0)
                        !== $empresaId
                    || (int) ($datos['unidad_id'] ?? 0)
                        !== (int) $unidad->id
                ) {
                    $this->fallar(
                        'unidad_id',
                        'La empresa y la unidad del abastecimiento no pueden cambiarse.'
                    );
                }

                if (
                    ! $unidad->empresa
                    || $unidad->empresa->estado
                        !== 'activa'
                ) {
                    $this->fallar(
                        'empresa_id',
                        'La empresa de la unidad está inactiva.'
                    );
                }

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
                | Abastecimiento anterior y lecturas
                |--------------------------------------------------------------------------
                */

                $abastecimientoAnterior = null;

                if (
                    $abastecimientoBloqueado
                        ->abastecimiento_anterior_id
                ) {
                    $abastecimientoAnterior =
                        Abastecimiento::query()
                            ->whereKey(
                                $abastecimientoBloqueado
                                    ->abastecimiento_anterior_id
                            )
                            ->lockForUpdate()
                            ->first();
                }

                $modeloMedicion =
                    (string)
                    $abastecimientoBloqueado
                        ->modelo_medicion;

                if (
                    ! in_array(
                        $modeloMedicion,
                        [
                            Abastecimiento::MODELO_KILOMETROS_GALON,
                            Abastecimiento::MODELO_GALONES_HORA,
                            Abastecimiento::MODELO_GALONES_VIAJE,
                        ],
                        true
                    )
                ) {
                    $this->fallar(
                        'unidad_id',
                        'El abastecimiento no posee un modelo de medición válido.'
                    );
                }

                $kilometrajeActual = $this->numero(
                    $datos['kilometraje_actual']
                        ?? null,
                    'kilometraje_actual',
                    'Debe ingresar el kilometraje actual de la unidad.'
                );

                $horometroActual = null;

                if (
                    $modeloMedicion
                    === Abastecimiento::MODELO_GALONES_HORA
                ) {
                    $horometroActual = $this->numero(
                        $datos['horometro_actual']
                            ?? null,
                        'horometro_actual',
                        'Debe ingresar la lectura actual del horómetro.'
                    );
                }

                $volumenInicial = $this->numero(
                    $datos['volumen_inicial']
                        ?? null,
                    'volumen_inicial',
                    'Debe ingresar el combustible existente antes de la carga.'
                );

                if ($kilometrajeActual < 0) {
                    $this->fallar(
                        'kilometraje_actual',
                        'El kilometraje actual no puede ser negativo.'
                    );
                }

                if (
                    ! is_null($horometroActual)
                    && $horometroActual < 0
                ) {
                    $this->fallar(
                        'horometro_actual',
                        'La lectura del horómetro no puede ser negativa.'
                    );
                }

                if ($volumenInicial < 0) {
                    $this->fallar(
                        'volumen_inicial',
                        'El combustible inicial no puede ser negativo.'
                    );
                }

                $kilometrajeAnterior =
                    $abastecimientoAnterior
                        ? (float) (
                            $abastecimientoAnterior
                                ->kilometraje_actual
                            ?? $abastecimientoAnterior
                                ->lectura_actual
                        )
                        : null;

                $diferenciaKilometraje = null;

                if (! is_null($kilometrajeAnterior)) {
                    if (
                        $kilometrajeActual
                        < $kilometrajeAnterior
                    ) {
                        $this->fallar(
                            'kilometraje_actual',
                            'El kilometraje actual no puede ser menor que el kilometraje del abastecimiento anterior.'
                        );
                    }

                    $diferenciaKilometraje = round(
                        $kilometrajeActual
                        - $kilometrajeAnterior,
                        2
                    );
                }

                $horometroAnterior = null;
                $diferenciaHorometro = null;

                if (
                    $modeloMedicion
                    === Abastecimiento::MODELO_GALONES_HORA
                    && $abastecimientoAnterior
                ) {
                    $horometroAnterior = filled(
                        $abastecimientoAnterior
                            ->horometro_actual
                    )
                        ? (float)
                            $abastecimientoAnterior
                                ->horometro_actual
                        : null;

                    if (is_null($horometroAnterior)) {
                        $this->fallar(
                            'horometro_actual',
                            'El abastecimiento anterior no posee una lectura de horómetro válida.'
                        );
                    }

                    if (
                        $horometroActual
                        < $horometroAnterior
                    ) {
                        $this->fallar(
                            'horometro_actual',
                            'La lectura actual del horómetro no puede ser menor que la lectura anterior.'
                        );
                    }

                    $diferenciaHorometro = round(
                        $horometroActual
                        - $horometroAnterior,
                        2
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Marchamos de los tapones abiertos por esta operación
                |--------------------------------------------------------------------------
                */

                $marchamosProcesados =
                    $this->prepararMarchamosModificacion(
                        $datos,
                        $abastecimientoBloqueado,
                        $unidad,
                        $empresaId
                    );

                /*
                |--------------------------------------------------------------------------
                | Origen y ajuste de inventario
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
                $ajustesInventario = collect();
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
                        $ajustesInventario,
                        $volumenCargado,
                    ] = $this
                        ->prepararOrigenInternoModificacion(
                            $datos,
                            $abastecimientoBloqueado,
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

                    [
                        $tanquesProcesados,
                        $ajustesInventario,
                    ] = $this
                        ->prepararRetiroOrigenInternoAnterior(
                            $abastecimientoBloqueado
                        );

                    $moneda = 'USD';

                    $origenNombre = trim(
                        $gasolineraExterna->compania
                        . ' — '
                        . $gasolineraExterna->direccion
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Capacidad y cierre de ciclo
                |--------------------------------------------------------------------------
                */

                $capacidadCubierta = round(
                    (float)
                    $abastecimientoBloqueado
                        ->capacidad_cubierta_snapshot,
                    2
                );

                if ($capacidadCubierta <= 0) {
                    $capacidadCubierta = round(
                        (float)
                        $unidad->capacidad_cubierta,
                        2
                    );
                }

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

                $volumenFinalAnterior = null;
                $combustibleConsumido = null;
                $combustibleAdicional = 0.0;

                if ($abastecimientoAnterior) {
                    $volumenFinalAnterior = round(
                        (float)
                        $abastecimientoAnterior
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
                | Rutas y rendimiento
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

                $galonesPorKilometro = null;
                $kilometrosPorGalon = null;
                $galonesPorHora = null;

                if (
                    ! is_null($combustibleConsumido)
                    && $combustibleConsumido > 0
                    && $combustibleAdicional <= 0
                ) {
                    if (
                        in_array(
                            $modeloMedicion,
                            [
                                Abastecimiento::MODELO_KILOMETROS_GALON,
                                Abastecimiento::MODELO_GALONES_VIAJE,
                            ],
                            true
                        )
                        && ! is_null($diferenciaKilometraje)
                        && $diferenciaKilometraje > 0
                    ) {
                        $galonesPorKilometro = round(
                            $combustibleConsumido
                            / $diferenciaKilometraje,
                            6
                        );

                        $kilometrosPorGalon = round(
                            $diferenciaKilometraje
                            / $combustibleConsumido,
                            6
                        );
                    }

                    if (
                        $modeloMedicion
                        === Abastecimiento::MODELO_GALONES_HORA
                        && ! is_null($diferenciaHorometro)
                        && $diferenciaHorometro > 0
                    ) {
                        $galonesPorHora = round(
                            $combustibleConsumido
                            / $diferenciaHorometro,
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
                    && ! is_null($diferenciaKilometraje)
                ) {
                    $diferenciaKilometrosTeoricos =
                        round(
                            $diferenciaKilometraje
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
                | Aplicar ajustes de inventario
                |--------------------------------------------------------------------------
                */

                $this->aplicarAjustesInventarioModificacion(
                    $abastecimientoBloqueado,
                    $ajustesInventario,
                    $fechaOperacion,
                    $usuarioId
                );

                /*
                |--------------------------------------------------------------------------
                | Reemplazar detalles de tanques y rutas
                |--------------------------------------------------------------------------
                */

                AbastecimientoTanque::query()
                    ->where(
                        'abastecimiento_id',
                        $abastecimientoBloqueado->id
                    )
                    ->delete();

                if (
                    $tipoOrigen
                    === Abastecimiento::ORIGEN_INTERNO
                ) {
                    foreach (
                        $tanquesProcesados
                        as $procesado
                    ) {
                        /** @var Tanque $tanque */
                        $tanque = $procesado['tanque'];

                        AbastecimientoTanque::create([
                            'abastecimiento_id' =>
                                $abastecimientoBloqueado->id,

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
                                    'inventario_anterior_snapshot'
                                ],

                            'galones_retirados' =>
                                $procesado[
                                    'galones_retirados'
                                ],

                            'costo_promedio_galon_snapshot' =>
                                $procesado[
                                    'costo_promedio_galon_snapshot'
                                ],

                            'costo_total_snapshot' =>
                                $procesado[
                                    'costo_total_snapshot'
                                ],

                            'inventario_resultante' =>
                                $procesado[
                                    'inventario_resultante_snapshot'
                                ],

                            'quedo_bajo_minimo' =>
                                $procesado[
                                    'quedo_bajo_minimo'
                                ],
                        ]);
                    }
                }

                AbastecimientoRuta::query()
                    ->where(
                        'abastecimiento_id',
                        $abastecimientoBloqueado->id
                    )
                    ->delete();

                foreach (
                    $rutasProcesadas
                    as $rutaProcesada
                ) {
                    AbastecimientoRuta::create(
                        array_merge(
                            [
                                'abastecimiento_id' =>
                                    $abastecimientoBloqueado->id,
                            ],
                            $rutaProcesada
                        )
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Actualizar códigos de marchamo
                |--------------------------------------------------------------------------
                */

                foreach (
                    $marchamosProcesados
                    as $procesado
                ) {
                    /** @var Marchamo $marchamoActual */
                    $marchamoActual =
                        $procesado['marchamo_actual'];

                    $marchamoActual->update([
                        'codigo_marchamo' =>
                            $procesado[
                                'nuevo_codigo_marchamo'
                            ],

                        'actualizado_por' =>
                            $usuarioId,
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Actualizar cabecera
                |--------------------------------------------------------------------------
                */

                $empresaNombre =
                    $unidad->empresa?->nombre_comercial
                    ?: $unidad->empresa?->nombre_legal
                    ?: $abastecimientoBloqueado
                        ->empresa_nombre_snapshot;

                $motoristaNombre = trim(
                    $motorista->nombres
                    . ' '
                    . $motorista->apellidos
                );

                $abastecimientoBloqueado->update([
                    'motorista_id' =>
                        $motorista->id,

                    'empresa_nombre_snapshot' =>
                        $empresaNombre,

                    'unidad_placa_snapshot' =>
                        $unidad->placa,

                    'unidad_marca_snapshot' =>
                        $unidad->marca,

                    'motorista_nombre_snapshot' =>
                        $motoristaNombre,

                    'motorista_licencia_snapshot' =>
                        $motorista->licencia,

                    /*
                     * La fecha_hora_abastecimiento no cambia.
                     */

                    'lectura_actual' =>
                        $kilometrajeActual,

                    'lectura_anterior' =>
                        $kilometrajeAnterior,

                    'diferencia_lectura' =>
                        $diferenciaKilometraje,

                    'kilometraje_actual' =>
                        $kilometrajeActual,

                    'kilometraje_anterior' =>
                        $kilometrajeAnterior,

                    'diferencia_kilometraje' =>
                        $diferenciaKilometraje,

                    'horometro_actual' =>
                        $horometroActual,

                    'horometro_anterior' =>
                        $horometroAnterior,

                    'diferencia_horometro' =>
                        $diferenciaHorometro,

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

                return $abastecimientoBloqueado->fresh([
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
     * Genera la versión utilizada para controlar concurrencia.
     */
    public function versionAbastecimiento(
        Abastecimiento $abastecimiento
    ): string {
        return hash(
            'sha256',
            implode(
                '|',
                [
                    (string) $abastecimiento->id,
                    (string) $abastecimiento->estado,
                    optional(
                        $abastecimiento->updated_at
                    )?->format('Y-m-d H:i:s.u')
                        ?? 'sin-version',
                ]
            )
        );
    }

    /**
     * Verifica y prepara únicamente los marchamos de los tapones
     * abiertos por el abastecimiento original.
     */
    private function prepararMarchamosModificacion(
        array $datos,
        Abastecimiento $abastecimiento,
        Unidad $unidad,
        int $empresaId
    ): Collection {
        $evento = ReemplazoMarchamoEvento::query()
            ->where(
                'abastecimiento_id',
                $abastecimiento->id
            )
            ->where(
                'origen_evento',
                ReemplazoMarchamoEvento::
                    ORIGEN_ABASTECIMIENTO
            )
            ->where(
                'estado',
                'registrado'
            )
            ->lockForUpdate()
            ->first();

        if (! $evento) {
            $this->fallar(
                'marchamos',
                'El abastecimiento no posee un evento vigente de reemplazo de marchamos.'
            );
        }

        $detalles = ReemplazoMarchamoDetalle::query()
            ->where(
                'reemplazo_evento_id',
                $evento->id
            )
            ->lockForUpdate()
            ->get()
            ->keyBy('punto_seguridad_id');

        if ($detalles->isEmpty()) {
            $this->fallar(
                'marchamos',
                'El abastecimiento no posee tapones registrados para validar.'
            );
        }

        $lineas = collect(
            $datos['marchamos'] ?? []
        )
            ->map(
                function ($linea): array {
                    return [
                        'punto_seguridad_id' =>
                            (int) (
                                $linea[
                                    'punto_seguridad_id'
                                ]
                                ?? 0
                            ),

                        'marchamo_actual_id' =>
                            (int) (
                                $linea[
                                    'marchamo_actual_id'
                                ]
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
                }
            )
            ->values();

        $puntosOriginales = $detalles
            ->keys()
            ->map(
                fn ($id): int => (int) $id
            )
            ->sort()
            ->values();

        $puntosFormulario = $lineas
            ->pluck('punto_seguridad_id')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if (
            $puntosFormulario->all()
            !== $puntosOriginales->all()
        ) {
            $this->fallar(
                'marchamos',
                'Los tapones abiertos por el abastecimiento original no pueden agregarse, eliminarse ni sustituirse durante la edición.'
            );
        }

        $puntos = PuntoSeguridadUnidad::query()
            ->where(
                'unidad_id',
                $unidad->id
            )
            ->whereIn(
                'id',
                $puntosOriginales
            )
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if (
            $puntos->count()
            !== $puntosOriginales->count()
        ) {
            $this->fallar(
                'marchamos',
                'Uno de los tapones afectados por el abastecimiento ya no está disponible.'
            );
        }

        $marchamoIdsOriginales = $detalles
            ->pluck('marchamo_nuevo_id')
            ->map(
                fn ($id): int => (int) $id
            )
            ->values();

        $marchamosActuales = Marchamo::query()
            ->whereIn(
                'id',
                $marchamoIdsOriginales
            )
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if (
            $marchamosActuales->count()
            !== $marchamoIdsOriginales
                ->unique()
                ->count()
        ) {
            $this->fallar(
                'marchamos',
                'Uno de los marchamos instalados por el abastecimiento ya no existe.'
            );
        }

        $procesados = $lineas->map(
            function (
                array $linea
            ) use (
                $detalles,
                $puntos,
                $marchamosActuales,
                $empresaId,
                $unidad
            ): array {
                $detalle = $detalles->get(
                    $linea['punto_seguridad_id']
                );

                $punto = $puntos->get(
                    $linea['punto_seguridad_id']
                );

                $marchamoActual =
                    $marchamosActuales->get(
                        (int) $detalle
                            ->marchamo_nuevo_id
                    );

                if (
                    ! $detalle
                    || ! $punto
                    || ! $marchamoActual
                ) {
                    $this->fallar(
                        'marchamos',
                        'No fue posible validar uno de los tapones afectados.'
                    );
                }

                if (
                    (int) $punto->marchamo_actual_id
                        !== (int) $marchamoActual->id
                    || (int) $linea['marchamo_actual_id']
                        !== (int) $marchamoActual->id
                    || $marchamoActual->estado
                        !== 'activo'
                    || ! (bool) $marchamoActual
                        ->activo_actual
                ) {
                    $this->fallar(
                        'marchamos',
                        "El punto {$punto->nombre_punto} fue intervenido después del abastecimiento. La operación ya no puede modificarse."
                    );
                }

                if (
                    (int) $marchamoActual->empresa_id
                        !== $empresaId
                    || (int) $marchamoActual->unidad_id
                        !== (int) $unidad->id
                    || (int) $marchamoActual
                        ->punto_seguridad_id
                        !== (int) $punto->id
                ) {
                    $this->fallar(
                        'marchamos',
                        'Uno de los marchamos actuales no corresponde a la unidad o al punto esperado.'
                    );
                }

                if (
                    ! preg_match(
                        '/^\d{7}$/',
                        $linea[
                            'nuevo_codigo_marchamo'
                        ]
                    )
                ) {
                    $this->fallar(
                        'marchamos',
                        'Cada código de marchamo debe contener exactamente 7 dígitos.'
                    );
                }

                return [
                    'punto' =>
                        $punto,

                    'detalle' =>
                        $detalle,

                    'marchamo_actual' =>
                        $marchamoActual,

                    'nuevo_codigo_marchamo' =>
                        $linea[
                            'nuevo_codigo_marchamo'
                        ],
                ];
            }
        );

        $codigos = $procesados->pluck(
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

        $idsEditados = $procesados
            ->pluck('marchamo_actual.id')
            ->map(
                fn ($id): int => (int) $id
            )
            ->all();

        $codigosExistentes = Marchamo::query()
            ->whereIn(
                'codigo_marchamo',
                $codigos
            )
            ->whereNotIn(
                'id',
                $idsEditados
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

        return $procesados;
    }

    /**
     * Prepara un origen interno modificado y calcula el ajuste neto
     * que debe aplicarse sobre el inventario actual.
     */
    private function prepararOrigenInternoModificacion(
        array $datos,
        Abastecimiento $abastecimiento,
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
            || $gasolinera->empresa->estado
                !== 'activa'
        ) {
            $this->fallar(
                'gasolinera_interna_id',
                'La gasolinera interna no está disponible.'
            );
        }

        $lineas = collect(
            $datos['tanques'] ?? []
        )
            ->map(
                function ($linea): array {
                    return [
                        'tanque_id' =>
                            (int) (
                                $linea['tanque_id']
                                ?? 0
                            ),

                        'galones' =>
                            round(
                                (float) (
                                    $linea['galones']
                                    ?? 0
                                ),
                                2
                            ),
                    ];
                }
            )
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

        $detallesAnteriores =
            AbastecimientoTanque::query()
                ->where(
                    'abastecimiento_id',
                    $abastecimiento->id
                )
                ->lockForUpdate()
                ->get()
                ->keyBy('tanque_id');

        $idsTanques = $detallesAnteriores
            ->keys()
            ->merge(
                $lineas->pluck('tanque_id')
            )
            ->map(
                fn ($id): int => (int) $id
            )
            ->unique()
            ->sort()
            ->values();

        $tanques = Tanque::query()
            ->whereIn(
                'id',
                $idsTanques
            )
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if (
            $tanques->count()
            !== $idsTanques->count()
        ) {
            $this->fallar(
                'tanques',
                'Uno o más tanques relacionados con la modificación ya no existen.'
            );
        }

        foreach (
            $lineas
            as $indice => $linea
        ) {
            $tanque = $tanques->get(
                $linea['tanque_id']
            );

            if (
                (int) $tanque->gasolinera_id
                    !== (int) $gasolinera->id
                || $tanque->estado !== 'activo'
            ) {
                $this->fallar(
                    "tanques.{$indice}.tanque_id",
                    'Uno de los tanques seleccionados no está activo o no pertenece a la gasolinera indicada.'
                );
            }
        }

        $nuevosPorTanque = $lineas
            ->keyBy('tanque_id');

        $procesados = collect();
        $ajustes = collect();

        foreach (
            $idsTanques
            as $tanqueId
        ) {
            /** @var Tanque $tanque */
            $tanque = $tanques->get(
                $tanqueId
            );

            $detalleAnterior =
                $detallesAnteriores->get(
                    $tanqueId
                );

            $galonesAnteriores =
                $detalleAnterior
                    ? round(
                        (float)
                        $detalleAnterior
                            ->galones_retirados,
                        2
                    )
                    : 0.0;

            $lineaNueva =
                $nuevosPorTanque->get(
                    $tanqueId
                );

            $galonesNuevos =
                $lineaNueva
                    ? round(
                        (float)
                        $lineaNueva['galones'],
                        2
                    )
                    : 0.0;

            $inventarioActual = round(
                (float) $tanque->volumen_actual,
                2
            );

            if (
                $inventarioActual > 0
                && (
                    is_null($tanque->valor_inventario_actual)
                    || is_null($tanque->costo_promedio_galon_actual)
                )
            ) {
                $this->fallar(
                    'tanques',
                    "El tanque {$tanque->nombre} tiene inventario sin costo conocido."
                );
            }

            if (
                $detalleAnterior
                && (
                    is_null($detalleAnterior->costo_promedio_galon_snapshot)
                    || is_null($detalleAnterior->costo_total_snapshot)
                )
            ) {
                $this->fallar(
                    'tanques',
                    'El abastecimiento histórico no posee snapshots económicos y no puede modificarse.'
                );
            }

            $diferenciaInventario = round(
                $galonesAnteriores
                - $galonesNuevos,
                2
            );

            $inventarioResultanteActual = round(
                $inventarioActual
                + $diferenciaInventario,
                2
            );

            if ($inventarioResultanteActual < 0) {
                $this->fallar(
                    'tanques',
                    "El tanque {$tanque->nombre} no posee inventario suficiente para aplicar la corrección."
                );
            }

            if (
                $inventarioResultanteActual
                > round(
                    (float) $tanque
                        ->capacidad_total,
                    2
                )
            ) {
                $this->fallar(
                    'tanques',
                    "La corrección excedería la capacidad total del tanque {$tanque->nombre}. Existen movimientos posteriores que impiden modificar esta distribución."
                );
            }

            if (
                abs($diferenciaInventario)
                >= 0.005
            ) {
                $valorInventarioActual = $inventarioActual > 0
                    ? (string) $tanque->valor_inventario_actual
                    : '0';
                $costoAjuste = $diferenciaInventario > 0
                    ? (string) $detalleAnterior->costo_promedio_galon_snapshot
                    : (string) $tanque->costo_promedio_galon_actual;
                $valorAjuste = Decimal::multiplicar(
                    (string) abs($diferenciaInventario),
                    $costoAjuste,
                    8
                );
                $valorInventarioResultante = $diferenciaInventario > 0
                    ? Decimal::sumar($valorInventarioActual, $valorAjuste, 8)
                    : Decimal::restar($valorInventarioActual, $valorAjuste, 8);
                $costoPromedioResultante = $inventarioResultanteActual > 0
                    ? Decimal::dividir(
                        $valorInventarioResultante,
                        (string) $inventarioResultanteActual,
                        8
                    )
                    : null;

                $ajustes->push([
                    'tanque' =>
                        $tanque,

                    'inventario_anterior' =>
                        $inventarioActual,

                    'diferencia_inventario' =>
                        $diferenciaInventario,

                    'inventario_resultante' =>
                        $inventarioResultanteActual,

                    'valor_inventario_anterior' =>
                        $valorInventarioActual,

                    'valor_ajuste' =>
                        $valorAjuste,

                    'valor_inventario_resultante' =>
                        $inventarioResultanteActual > 0
                            ? $valorInventarioResultante
                            : '0.00000000',

                    'costo_unitario_aplicado' =>
                        $costoAjuste,

                    'costo_promedio_resultante' =>
                        $costoPromedioResultante,
                ]);
            }

            if ($lineaNueva) {
                if ($detalleAnterior) {
                    if ($galonesNuevos > $galonesAnteriores) {
                        $costoAdicional = Decimal::multiplicar(
                            (string) ($galonesNuevos - $galonesAnteriores),
                            (string) $tanque->costo_promedio_galon_actual,
                            8
                        );
                        $costoTotalSnapshot = Decimal::sumar(
                            (string) $detalleAnterior->costo_total_snapshot,
                            $costoAdicional,
                            8
                        );
                        $costoSnapshot = Decimal::dividir(
                            $costoTotalSnapshot,
                            (string) $galonesNuevos,
                            8
                        );
                    } else {
                        $costoSnapshot = (string)
                            $detalleAnterior->costo_promedio_galon_snapshot;
                        $costoTotalSnapshot = Decimal::multiplicar(
                            (string) $galonesNuevos,
                            $costoSnapshot,
                            8
                        );
                    }
                } else {
                    $costoSnapshot = (string)
                        $tanque->costo_promedio_galon_actual;
                    $costoTotalSnapshot = Decimal::multiplicar(
                        (string) $galonesNuevos,
                        $costoSnapshot,
                        8
                    );
                }

                $inventarioAnteriorSnapshot =
                    $detalleAnterior
                        ? round(
                            (float)
                            $detalleAnterior
                                ->inventario_anterior,
                            2
                        )
                        : round(
                            $inventarioActual
                            + $galonesNuevos,
                            2
                        );

                $inventarioResultanteSnapshot =
                    round(
                        $inventarioAnteriorSnapshot
                        - $galonesNuevos,
                        2
                    );

                $procesados->push([
                    'tanque' =>
                        $tanque,

                    'orden' =>
                        $lineas->search(
                            fn (array $linea): bool =>
                                (int) $linea['tanque_id']
                                === (int) $tanqueId
                        ) + 1,

                    'inventario_anterior_snapshot' =>
                        $inventarioAnteriorSnapshot,

                    'galones_retirados' =>
                        $galonesNuevos,

                    'costo_promedio_galon_snapshot' =>
                        $costoSnapshot,

                    'costo_total_snapshot' =>
                        $costoTotalSnapshot,

                    'inventario_resultante_snapshot' =>
                        $inventarioResultanteSnapshot,

                    'quedo_bajo_minimo' =>
                        $inventarioResultanteSnapshot
                        <= (float) $tanque
                            ->volumen_minimo_alerta,
                ]);
            }
        }

        return [
            $gasolinera,
            $procesados->sortBy('orden')->values(),
            $ajustes,
            round(
                $lineas->sum('galones'),
                2
            ),
        ];
    }

    /**
     * Prepara la devolución completa del retiro interno anterior cuando
     * el abastecimiento cambia a un origen externo.
     */
    private function prepararRetiroOrigenInternoAnterior(
        Abastecimiento $abastecimiento
    ): array {
        $detallesAnteriores =
            AbastecimientoTanque::query()
                ->where(
                    'abastecimiento_id',
                    $abastecimiento->id
                )
                ->lockForUpdate()
                ->get();

        if ($detallesAnteriores->isEmpty()) {
            return [
                collect(),
                collect(),
            ];
        }

        $tanqueIds = $detallesAnteriores
            ->pluck('tanque_id')
            ->map(
                fn ($id): int => (int) $id
            )
            ->unique()
            ->sort()
            ->values();

        $tanques = Tanque::query()
            ->whereIn(
                'id',
                $tanqueIds
            )
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        if (
            $tanques->count()
            !== $tanqueIds->count()
        ) {
            $this->fallar(
                'tanques',
                'Uno de los tanques utilizados originalmente ya no existe.'
            );
        }

        $ajustes = collect();

        foreach (
            $detallesAnteriores
            as $detalle
        ) {
            /** @var Tanque $tanque */
            $tanque = $tanques->get(
                (int) $detalle->tanque_id
            );

            $inventarioActual = round(
                (float) $tanque->volumen_actual,
                2
            );

            if (
                is_null($detalle->costo_promedio_galon_snapshot)
                || is_null($detalle->costo_total_snapshot)
                || (
                    $inventarioActual > 0
                    && is_null($tanque->valor_inventario_actual)
                )
            ) {
                $this->fallar(
                    'tanques',
                    'El abastecimiento histórico no posee valoración suficiente para devolver su inventario.'
                );
            }

            $diferenciaInventario = round(
                (float) $detalle
                    ->galones_retirados,
                2
            );

            $inventarioResultante = round(
                $inventarioActual
                + $diferenciaInventario,
                2
            );

            if (
                $inventarioResultante
                > round(
                    (float) $tanque
                        ->capacidad_total,
                    2
                )
            ) {
                $this->fallar(
                    'tanques',
                    "La corrección excedería la capacidad total del tanque {$tanque->nombre}. Existen movimientos posteriores que impiden cambiar el origen a externo."
                );
            }

            $ajustes->push([
                'tanque' =>
                    $tanque,

                'inventario_anterior' =>
                    $inventarioActual,

                'diferencia_inventario' =>
                    $diferenciaInventario,

                'inventario_resultante' =>
                    $inventarioResultante,

                'valor_inventario_anterior' =>
                    $inventarioActual > 0
                        ? (string) $tanque->valor_inventario_actual
                        : '0',

                'valor_ajuste' =>
                    (string) $detalle->costo_total_snapshot,

                'valor_inventario_resultante' =>
                    Decimal::sumar(
                        $inventarioActual > 0
                            ? (string) $tanque->valor_inventario_actual
                            : '0',
                        (string) $detalle->costo_total_snapshot,
                        8
                    ),

                'costo_unitario_aplicado' =>
                    (string) $detalle->costo_promedio_galon_snapshot,

                'costo_promedio_resultante' =>
                    Decimal::dividir(
                        Decimal::sumar(
                            $inventarioActual > 0
                                ? (string) $tanque->valor_inventario_actual
                                : '0',
                            (string) $detalle->costo_total_snapshot,
                            8
                        ),
                        (string) $inventarioResultante,
                        8
                    ),
            ]);
        }

        return [
            collect(),
            $ajustes,
        ];
    }

    /**
     * Aplica los ajustes netos de inventario y conserva una trazabilidad
     * independiente por cada tanque afectado.
     */
    private function aplicarAjustesInventarioModificacion(
        Abastecimiento $abastecimiento,
        Collection $ajustes,
        $fechaOperacion,
        int $usuarioId
    ): void {
        foreach (
            $ajustes
            as $ajuste
        ) {
            /** @var Tanque $tanque */
            $tanque = $ajuste['tanque'];

            $diferencia = round(
                (float)
                $ajuste[
                    'diferencia_inventario'
                ],
                2
            );

            if (abs($diferencia) < 0.005) {
                continue;
            }

            $sentido =
                $diferencia > 0
                    ? 'entrada'
                    : 'salida';

            $tanque->update([
                'volumen_actual' =>
                    $ajuste[
                        'inventario_resultante'
                    ],

                'valor_inventario_actual' =>
                    $ajuste[
                        'valor_inventario_resultante'
                    ],

                'costo_promedio_galon_actual' =>
                    $ajuste[
                        'costo_promedio_resultante'
                    ],

                'fecha_actualizacion' =>
                    $fechaOperacion,

                'actualizado_por' =>
                    $usuarioId,
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
                    'ajuste_modificacion_abastecimiento',

                'volumen_anterior' =>
                    $ajuste[
                        'inventario_anterior'
                    ],

                'sentido_movimiento' =>
                    $sentido,

                'volumen_movimiento' =>
                    abs($diferencia),

                'volumen_resultante' =>
                    $ajuste[
                        'inventario_resultante'
                    ],

                'valor_inventario_anterior' =>
                    $ajuste[
                        'valor_inventario_anterior'
                    ],

                'valor_movimiento' =>
                    $ajuste[
                        'valor_ajuste'
                    ],

                'valor_inventario_resultante' =>
                    $ajuste[
                        'valor_inventario_resultante'
                    ],

                'costo_unitario_aplicado' =>
                    $ajuste[
                        'costo_unitario_aplicado'
                    ],

                'subtotal_compra' =>
                    null,

                'fecha_hora_movimiento' =>
                    $fechaOperacion,

                'observaciones' =>
                    'Ajuste generado por modificación del abastecimiento #'
                    . $abastecimiento->id,

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

            if (
                $inventarioAnterior > 0
                && (
                    is_null($tanque->valor_inventario_actual)
                    || is_null($tanque->costo_promedio_galon_actual)
                )
            ) {
                $this->fallar(
                    "tanques.{$indice}.galones",
                    "El tanque {$tanque->nombre} tiene inventario sin costo conocido."
                );
            }

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

            $valorInventarioAnterior = (string)
                $tanque->valor_inventario_actual;
            $costoPromedio = (string)
                $tanque->costo_promedio_galon_actual;
            $costoRetiro = Decimal::multiplicar(
                (string) $galones,
                $costoPromedio,
                8
            );
            $valorInventarioResultante = $inventarioResultante > 0
                ? Decimal::restar(
                    $valorInventarioAnterior,
                    $costoRetiro,
                    8
                )
                : '0.00000000';

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

                'valor_inventario_anterior' =>
                    $valorInventarioAnterior,

                'costo_promedio_galon_snapshot' =>
                    $costoPromedio,

                'costo_total_snapshot' =>
                    $costoRetiro,

                'valor_inventario_resultante' =>
                    $valorInventarioResultante,

                'costo_promedio_resultante' =>
                    $inventarioResultante > 0
                        ? $costoPromedio
                        : null,

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

                'valor_inventario_actual' =>
                    $procesado[
                        'valor_inventario_resultante'
                    ],

                'costo_promedio_galon_actual' =>
                    $procesado[
                        'costo_promedio_resultante'
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

                'costo_promedio_galon_snapshot' =>
                    $procesado[
                        'costo_promedio_galon_snapshot'
                    ],

                'costo_total_snapshot' =>
                    $procesado[
                        'costo_total_snapshot'
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

                'valor_inventario_anterior' =>
                    $procesado[
                        'valor_inventario_anterior'
                    ],

                'valor_movimiento' =>
                    $procesado[
                        'costo_total_snapshot'
                    ],

                'valor_inventario_resultante' =>
                    $procesado[
                        'valor_inventario_resultante'
                    ],

                'costo_unitario_aplicado' =>
                    $procesado[
                        'costo_promedio_galon_snapshot'
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
