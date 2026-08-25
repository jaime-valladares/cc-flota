@php
    $queryParams = collect(request()->query())
        ->except([
            'abastecimiento',
            'return_to',
            'return_query',
        ])
        ->all();

    $queryParamsFicha = array_merge(
        $queryParams,
        [
            'abastecimiento' => $abastecimiento,
            'origen_retorno' => $origenRetorno,
        ]
    );

    $rutaRetorno = $origenRetorno === 'consulta'
        ? route(
            'abastecimientos.consulta.ventana',
            $parametrosRetorno
        )
        : route(
            'abastecimientos.administrar.ventana',
            $parametrosRetorno
        );

    $textoRetorno = $origenRetorno === 'consulta'
        ? 'Volver a consulta'
        : 'Volver a administrar';

    $rutaSistema = route(
        'abastecimientos.show',
        $queryParamsFicha
    );

    $empresaNombre = $abastecimiento->empresa_nombre_snapshot
        ?: (
            $abastecimiento->empresa
                ? (
                    $abastecimiento->empresa->nombre_comercial
                    ?: $abastecimiento->empresa->nombre_legal
                )
                : 'Empresa no disponible'
        );

    $unidadPlaca = $abastecimiento->unidad_placa_snapshot
        ?: ($abastecimiento->unidad?->placa ?: 'Unidad no disponible');

    $unidadDescripcion = collect([
        $abastecimiento->unidad_marca_snapshot,
        $abastecimiento->unidad_modelo_snapshot,
    ])->filter()->implode(' · ');

    $motoristaNombre = $abastecimiento->motorista_nombre_snapshot
        ?: ($abastecimiento->motorista?->nombre_completo ?: 'Motorista no disponible');

    $modeloTexto = match ($abastecimiento->modelo_medicion) {
        'kilometros_galon' => 'Kilómetros por galón',
        'galones_hora' => 'Horas por galón',
        'galones_viaje' => 'Galones por viaje',
        default => 'No definido',
    };

    $origenTipoTexto = match ($abastecimiento->tipo_origen) {
        'interno' => 'Gasolinera interna',
        'externo' => 'Gasolinera externa',
        default => 'No definido',
    };

    $origenNombre = $abastecimiento->origen_nombre_snapshot
        ?: (
            $abastecimiento->esOrigenInterno()
                ? ($abastecimiento->gasolineraInterna?->nombre ?: 'Gasolinera interna no disponible')
                : ($abastecimiento->gasolineraExterna?->compania ?: 'Gasolinera externa no disponible')
        );

    $eventoMarchamos = $abastecimiento->reemplazoMarchamoEvento;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        Ficha de abastecimiento · CC-Flota
    </title>

    @include('layouts.partials.favicon')

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
        rel="stylesheet"
    >

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])

    <style>
        html,
        body {
            min-width: 0;
            min-height: 100%;
        }

        body {
            margin: 0;
            background: var(--cc-bg-main);
        }

        .cc-abastecimiento-ficha-ventana {
            width: 100%;
            min-width: 0;
        }

        .cc-abastecimiento-ficha-ventana .cc-page-wrapper {
            width: 100%;
            min-width: 0;
        }

        @media (max-width: 760px) {
            .cc-abastecimiento-ficha-ventana
            .cc-card-header {
                align-items: stretch;
                flex-direction: column;
            }

            .cc-abastecimiento-ficha-ventana
            .cc-card-header > div:last-child {
                width: 100%;
            }

            .cc-abastecimiento-ficha-ventana
            .cc-card-header > div:last-child > a {
                width: 100%;
            }
        }
    </style>
</head>

<body class="antialiased">
    <main class="cc-abastecimiento-ficha-ventana">
        <div class="cc-page-wrapper">
            <div
                class="cc-window-container"
                style="width: 100%; max-width: 80rem;"
            >
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Ficha de abastecimiento
                        </h3>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a
                            href="{{ $rutaSistema }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver al Sistema
                        </a>

                        <a
                            href="{{ $rutaRetorno }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            {{ $textoRetorno }}
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="cc-alert cc-alert-danger">
                        <div class="font-bold">
                            No fue posible completar la operación.
                        </div>

                        <ul class="mt-2 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="cc-profile-summary">
                    <div>
                        <div class="cc-profile-eyebrow">
                            Abastecimiento
                        </div>

                        <h4 class="cc-profile-title">
                            {{ $unidadPlaca }}
                        </h4>

                        <div class="cc-profile-meta flex flex-wrap gap-x-5 gap-y-2">
                            <span>
                                <strong>Empresa:</strong>
                                {{ $empresaNombre }}
                            </span>

                            <span>
                                <strong>Unidad:</strong>
                                {{ $unidadDescripcion ?: 'Sin marca ni modelo registrados' }}
                            </span>

                            <span>
                                <strong>Motorista:</strong>
                                {{ $motoristaNombre }}
                            </span>

                            <span>
                                <strong>Fecha:</strong>
                                {{
                                    optional(
                                        $abastecimiento->fecha_hora_abastecimiento
                                    )->format('d/m/Y H:i')
                                    ?: '—'
                                }}
                            </span>
                        </div>
                    </div>

                    <div class="cc-profile-status">
                        @if ($abastecimiento->estaRegistrado())
                            <span class="cc-badge cc-badge-active">
                                Registrado
                            </span>
                        @else
                            <span class="cc-badge cc-badge-inactive">
                                Anulado
                            </span>
                        @endif

                        @if ($abastecimiento->esPrimerAbastecimiento())
                            <span class="cc-badge cc-badge-warning">
                                Línea base
                            </span>
                        @else
                            <span class="cc-badge cc-badge-active">
                                Ciclo cerrado
                            </span>
                        @endif

                        @if ($esUltimoRegistrado)
                            <span class="cc-badge cc-badge-active">
                                Último vigente
                            </span>
                        @endif
                    </div>
                </div>

                <div class="cc-detail-layout">

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Identificación de la operación</h5>
                            <p>Datos históricos conservados al momento del abastecimiento.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Empresa</div>
                                <div class="cc-detail-value">{{ $empresaNombre }}</div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Placa</div>
                                <div class="cc-detail-value">{{ $unidadPlaca }}</div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Marca y modelo</div>
                                <div class="cc-detail-value">
                                    {{ $unidadDescripcion ?: 'No disponible' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Motorista</div>
                                <div class="cc-detail-value">{{ $motoristaNombre }}</div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Licencia del motorista</div>
                                <div class="cc-detail-value">
                                    {{ $abastecimiento->motorista_licencia_snapshot ?: 'No disponible' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Fecha y hora</div>
                                <div class="cc-detail-value">
                                    {{
                                        optional(
                                            $abastecimiento->fecha_hora_abastecimiento
                                        )->format('d/m/Y H:i:s')
                                        ?: '—'
                                    }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Estado</div>
                                <div class="cc-detail-value">
                                    {{ $abastecimiento->estaRegistrado() ? 'Registrado' : 'Anulado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Modelo de medición</div>
                                <div class="cc-detail-value">{{ $modeloTexto }}</div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Lecturas y ciclo operativo</h5>
                            <p>Valores utilizados para cerrar el ciclo anterior y establecer la nueva referencia.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Kilometraje anterior</div>
                                <div class="cc-detail-value">
                                    @if (is_null($abastecimiento->kilometraje_anterior))
                                        —
                                    @else
                                        {{ number_format((float) $abastecimiento->kilometraje_anterior, 2) }} km
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Kilometraje actual</div>
                                <div class="cc-detail-value">
                                    {{ number_format((float) $abastecimiento->kilometraje_actual, 2) }} km
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Diferencia de kilometraje</div>
                                <div class="cc-detail-value">
                                    @if (is_null($abastecimiento->diferencia_kilometraje))
                                        —
                                    @else
                                        {{ number_format((float) $abastecimiento->diferencia_kilometraje, 2) }} km
                                    @endif
                                </div>
                            </div>

                            @if ($abastecimiento->usaHorometro())
                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Horómetro anterior</div>
                                    <div class="cc-detail-value">
                                        @if (is_null($abastecimiento->horometro_anterior))
                                            —
                                        @else
                                            {{ number_format((float) $abastecimiento->horometro_anterior, 2) }} h
                                        @endif
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Horómetro actual</div>
                                    <div class="cc-detail-value">
                                        {{ number_format((float) $abastecimiento->horometro_actual, 2) }} h
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Diferencia de horómetro</div>
                                    <div class="cc-detail-value">
                                        @if (is_null($abastecimiento->diferencia_horometro))
                                            —
                                        @else
                                            {{ number_format((float) $abastecimiento->diferencia_horometro, 2) }} h
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Abastecimiento anterior</div>
                                <div class="cc-detail-value">
                                    @if ($abastecimiento->abastecimientoAnterior)
                                        #{{ $abastecimiento->abastecimientoAnterior->id }}
                                        ·
                                        {{
                                            optional(
                                                $abastecimiento
                                                    ->abastecimientoAnterior
                                                    ->fecha_hora_abastecimiento
                                            )->format('d/m/Y H:i')
                                            ?: 'Fecha no disponible'
                                        }}
                                    @else
                                        No aplica · Línea base
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Condición histórica</div>
                                <div class="cc-detail-value">
                                    @if ($abastecimiento->esPrimerAbastecimiento())
                                        Primer abastecimiento de la unidad.
                                    @elseif ($esUltimoRegistrado)
                                        Último abastecimiento registrado y vigente.
                                    @else
                                        Abastecimiento histórico de la unidad.
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Combustible y capacidad</h5>
                            <p>Volúmenes registrados y resultados calculados para la operación.</p>
                        </div>

                        <div class="cc-summary-strip">
                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">Volumen inicial</span>
                                <span class="cc-summary-strip-value">
                                    {{ number_format((float) $abastecimiento->volumen_inicial, 2) }} gal
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">Volumen cargado</span>
                                <span class="cc-summary-strip-value">
                                    {{ number_format((float) $abastecimiento->volumen_cargado, 2) }} gal
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">Volumen final</span>
                                <span class="cc-summary-strip-value">
                                    {{ number_format((float) $abastecimiento->volumen_final, 2) }} gal
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">Capacidad cubierta</span>
                                <span class="cc-summary-strip-value">
                                    {{ number_format((float) $abastecimiento->capacidad_cubierta_snapshot, 2) }} gal
                                </span>
                            </div>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Volumen final anterior</div>
                                <div class="cc-detail-value">
                                    @if (is_null($abastecimiento->volumen_final_anterior))
                                        —
                                    @else
                                        {{ number_format((float) $abastecimiento->volumen_final_anterior, 2) }} gal
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Combustible consumido en el ciclo</div>
                                <div class="cc-detail-value">
                                    @if (is_null($abastecimiento->combustible_consumido_ciclo))
                                        —
                                    @else
                                        {{ number_format((float) $abastecimiento->combustible_consumido_ciclo, 2) }} gal
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Combustible adicional no explicado</div>
                                <div class="cc-detail-value">
                                    @if (is_null($abastecimiento->combustible_adicional_no_explicado))
                                        —
                                    @else
                                        {{ number_format((float) $abastecimiento->combustible_adicional_no_explicado, 2) }} gal
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Origen del combustible</h5>
                            <p>Procedencia registrada para esta operación.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Tipo de origen</div>
                                <div class="cc-detail-value">{{ $origenTipoTexto }}</div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Origen</div>
                                <div class="cc-detail-value">{{ $origenNombre }}</div>
                            </div>

                            @if ($abastecimiento->esOrigenExterno())
                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Precio por galón</div>
                                    <div class="cc-detail-value">
                                        ${{ number_format((float) $abastecimiento->precio_galon, 4) }}
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Total pagado</div>
                                    <div class="cc-detail-value">
                                        ${{ number_format((float) $abastecimiento->total_pagado, 2) }}
                                        {{ $abastecimiento->moneda ?: 'USD' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                    @if ($abastecimiento->esOrigenInterno())
                        <section class="cc-detail-section">
                            <div class="cc-detail-section-header">
                                <h5>Tanques utilizados</h5>
                                <p>Detalle de las salidas aplicadas al inventario interno.</p>
                            </div>

                            @if ($abastecimiento->tanques->isEmpty())
                                <div class="cc-empty-panel cc-empty-panel-compact">
                                    <h5>Sin detalle de tanques</h5>
                                    <p>No hay líneas de tanque asociadas a este abastecimiento.</p>
                                </div>
                            @else
                                <div class="cc-table-adaptive-wrapper">
                                    <table class="cc-table-adaptive" style="min-width: 72rem;">
                                        <thead>
                                            <tr>
                                                <th>Orden</th>
                                                <th>Tanque</th>
                                                <th>Inventario anterior</th>
                                                <th>Galones retirados</th>
                                                <th>Inventario resultante</th>
                                                <th>Mínimo</th>
                                                <th>Condición</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($abastecimiento->tanques as $detalleTanque)
                                                <tr>
                                                    <td>{{ $detalleTanque->orden }}</td>

                                                    <td>
                                                        <div class="cc-table-adaptive-strong">
                                                            {{
                                                                $detalleTanque->tanque_nombre_snapshot
                                                                ?: ($detalleTanque->tanque?->nombre ?: 'Tanque no disponible')
                                                            }}
                                                        </div>
                                                    </td>

                                                    <td>
                                                        {{ number_format((float) $detalleTanque->inventario_anterior, 2) }} gal
                                                    </td>

                                                    <td>
                                                        {{ number_format((float) $detalleTanque->galones_retirados, 2) }} gal
                                                    </td>

                                                    <td>
                                                        {{ number_format((float) $detalleTanque->inventario_resultante, 2) }} gal
                                                    </td>

                                                    <td>
                                                        {{ number_format((float) $detalleTanque->volumen_minimo_alerta_snapshot, 2) }} gal
                                                    </td>

                                                    <td>
                                                        @if ($detalleTanque->quedoBajoMinimo())
                                                            <span class="cc-badge cc-badge-warning">
                                                                Bajo mínimo
                                                            </span>
                                                        @else
                                                            <span class="cc-badge cc-badge-active">
                                                                Disponible
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </section>

                        <section class="cc-detail-section">
                            <div class="cc-detail-section-header">
                                <h5>Movimientos de inventario</h5>
                                <p>Trazabilidad contable y operativa generada por la salida de combustible.</p>
                            </div>

                            @if ($abastecimiento->movimientosInventario->isEmpty())
                                <div class="cc-empty-panel cc-empty-panel-compact">
                                    <h5>Sin movimientos</h5>
                                    <p>No hay movimientos de inventario asociados a este abastecimiento.</p>
                                </div>
                            @else
                                <div class="cc-table-adaptive-wrapper">
                                    <table class="cc-table-adaptive" style="min-width: 78rem;">
                                        <thead>
                                            <tr>
                                                <th>Fecha y hora</th>
                                                <th>Tanque</th>
                                                <th>Volumen anterior</th>
                                                <th>Movimiento</th>
                                                <th>Volumen resultante</th>
                                                <th>Sentido</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($abastecimiento->movimientosInventario as $movimiento)
                                                <tr>
                                                    <td>
                                                        {{
                                                            optional(
                                                                $movimiento->fecha_hora_movimiento
                                                            )->format('d/m/Y H:i:s')
                                                            ?: '—'
                                                        }}
                                                    </td>

                                                    <td>
                                                        {{ $movimiento->tanque?->nombre ?: 'Tanque no disponible' }}
                                                    </td>

                                                    <td>
                                                        {{ number_format((float) $movimiento->volumen_anterior, 2) }} gal
                                                    </td>

                                                    <td>
                                                        {{ number_format((float) $movimiento->volumen_movimiento, 2) }} gal
                                                    </td>

                                                    <td>
                                                        {{ number_format((float) $movimiento->volumen_resultante, 2) }} gal
                                                    </td>

                                                    <td>
                                                        {{ ucfirst($movimiento->sentido_movimiento ?: 'No definido') }}
                                                    </td>

                                                    <td>
                                                        @if ($movimiento->estaRegistrado())
                                                            <span class="cc-badge cc-badge-active">
                                                                Registrado
                                                            </span>
                                                        @else
                                                            <span class="cc-badge cc-badge-inactive">
                                                                Anulado
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </section>
                    @endif

                    @if ($abastecimiento->modelo_medicion === 'galones_viaje')
                        <section class="cc-detail-section">
                            <div class="cc-detail-section-header">
                                <h5>Rutas del ciclo</h5>
                                <p>Recorridos declarados para el período cerrado por este abastecimiento.</p>
                            </div>

                            @if ($abastecimiento->rutas->isEmpty())
                                <div class="cc-empty-panel cc-empty-panel-compact">
                                    <h5>Sin rutas registradas</h5>
                                    <p>Este abastecimiento no posee rutas asociadas.</p>
                                </div>
                            @else
                                <div class="cc-table-adaptive-wrapper">
                                    <table class="cc-table-adaptive" style="min-width: 78rem;">
                                        <thead>
                                            <tr>
                                                <th>Orden</th>
                                                <th>Ruta</th>
                                                <th>Recorrido</th>
                                                <th>Tipo</th>
                                                <th>Factor</th>
                                                <th>Kilómetros aplicados</th>
                                                <th>Galones aplicados</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($abastecimiento->rutas as $detalleRuta)
                                                <tr>
                                                    <td>{{ $detalleRuta->orden }}</td>
                                                    <td>
                                                        {{
                                                            $detalleRuta->ruta_nombre_snapshot
                                                            ?: ($detalleRuta->ruta?->ruta ?: 'Ruta no disponible')
                                                        }}
                                                    </td>
                                                    <td>
                                                        {{ $detalleRuta->recorrido_texto ?: 'No disponible' }}
                                                    </td>
                                                    <td>{{ $detalleRuta->tipo_recorrido_texto }}</td>
                                                    <td>{{ $detalleRuta->factor_recorrido }}</td>
                                                    <td>
                                                        {{ number_format((float) $detalleRuta->kilometros_aplicados, 2) }} km
                                                    </td>
                                                    <td>
                                                        {{ number_format((float) $detalleRuta->galones_aplicados, 2) }} gal
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <div class="cc-summary-strip mt-5">
                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">Total de rutas</span>
                                    <span class="cc-summary-strip-value">
                                        {{ $abastecimiento->total_rutas ?? 0 }}
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">Kilómetros teóricos</span>
                                    <span class="cc-summary-strip-value">
                                        {{ number_format((float) $abastecimiento->kilometros_teoricos, 2) }} km
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">Galones teóricos</span>
                                    <span class="cc-summary-strip-value">
                                        {{ number_format((float) $abastecimiento->galones_teoricos, 2) }} gal
                                    </span>
                                </div>
                            </div>
                        </section>
                    @endif

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Rendimiento calculado</h5>
                            <p>Indicadores derivados según el modelo de medición de la unidad.</p>
                        </div>

                        <div class="cc-detail-grid">
                            @if ($abastecimiento->modelo_medicion === 'kilometros_galon')
                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Kilómetros por galón</div>
                                    <div class="cc-detail-value">
                                        @if (is_null($abastecimiento->kilometros_por_galon))
                                            —
                                        @else
                                            {{ number_format((float) $abastecimiento->kilometros_por_galon, 2) }}
                                        @endif
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Galones por kilómetro</div>
                                    <div class="cc-detail-value">
                                        @if (is_null($abastecimiento->galones_por_kilometro))
                                            —
                                        @else
                                            {{ number_format((float) $abastecimiento->galones_por_kilometro, 2) }}
                                        @endif
                                    </div>
                                </div>
                            @elseif ($abastecimiento->modelo_medicion === 'galones_hora')
                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Galones por hora</div>
                                    <div class="cc-detail-value">
                                        @if (is_null($abastecimiento->galones_por_hora))
                                            —
                                        @else
                                            {{ number_format((float) $abastecimiento->galones_por_hora, 2) }}
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Diferencia de kilómetros teóricos</div>
                                    <div class="cc-detail-value">
                                        @if (is_null($abastecimiento->diferencia_kilometros_teoricos))
                                            —
                                        @else
                                            {{ number_format((float) $abastecimiento->diferencia_kilometros_teoricos, 2) }} km
                                        @endif
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Diferencia de galones teóricos</div>
                                    <div class="cc-detail-value">
                                        @if (is_null($abastecimiento->diferencia_galones_teoricos))
                                            —
                                        @else
                                            {{ number_format((float) $abastecimiento->diferencia_galones_teoricos, 2) }} gal
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Marchamos sustituidos</h5>
                            <p>Tapones abiertos y códigos reemplazados durante el abastecimiento.</p>
                        </div>

                        @if (! $eventoMarchamos || $eventoMarchamos->detalles->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>Sin detalle de marchamos</h5>
                                <p>No hay sustituciones de marchamos asociadas a este abastecimiento.</p>
                            </div>
                        @else
                            <div class="cc-summary-strip">
                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">Tapones abiertos</span>
                                    <span class="cc-summary-strip-value">
                                        {{ $abastecimiento->total_tapones_abiertos ?? 0 }}
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">Marchamos reemplazados</span>
                                    <span class="cc-summary-strip-value">
                                        {{ $abastecimiento->total_marchamos_reemplazados ?? 0 }}
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">Motivo</span>
                                    <span class="cc-summary-strip-value">
                                        {{ $eventoMarchamos->motivo_reemplazo_texto }}
                                    </span>
                                </div>
                            </div>

                            <div class="cc-table-adaptive-wrapper mt-5">
                                <table class="cc-table-adaptive" style="min-width: 72rem;">
                                    <thead>
                                        <tr>
                                            <th>Punto de seguridad</th>
                                            <th>Marchamo retirado</th>
                                            <th>Marchamo nuevo</th>
                                            <th>Fecha</th>
                                            <th>Condición</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($eventoMarchamos->detalles as $detalleMarchamo)
                                            <tr>
                                                <td>{{ $detalleMarchamo->punto_seguridad_texto }}</td>
                                                <td>{{ $detalleMarchamo->codigo_anterior ?: 'No disponible' }}</td>
                                                <td>{{ $detalleMarchamo->codigo_nuevo ?: 'No disponible' }}</td>
                                                <td>
                                                    {{
                                                        optional(
                                                            $detalleMarchamo->fecha_registro
                                                        )->format('d/m/Y H:i:s')
                                                        ?: '—'
                                                    }}
                                                </td>
                                                <td>
                                                    @if ($detalleMarchamo->esta_completo)
                                                        <span class="cc-badge cc-badge-active">
                                                            Completo
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-warning">
                                                            Incompleto
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Control y auditoría</h5>
                            <p>Usuarios, fechas y condición administrativa del registro.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Registrado por</div>
                                <div class="cc-detail-value">
                                    {{ $abastecimiento->registradoPor?->name ?: '—' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Fecha de creación</div>
                                <div class="cc-detail-value">
                                    {{ optional($abastecimiento->created_at)->format('d/m/Y H:i:s') ?: '—' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Última actualización</div>
                                <div class="cc-detail-value">
                                    {{ optional($abastecimiento->updated_at)->format('d/m/Y H:i:s') ?: '—' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Puede modificarse</div>
                                <div class="cc-detail-value">
                                    @if ($puedeModificarse)
                                        <span class="cc-badge cc-badge-active">Sí</span>
                                    @else
                                        <span class="cc-badge cc-badge-inactive">No</span>
                                    @endif
                                </div>
                            </div>

                            @if ($abastecimiento->estaAnulado())
                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Fecha de anulación</div>
                                    <div class="cc-detail-value">
                                        {{
                                            optional(
                                                $abastecimiento->fecha_anulacion
                                            )->format('d/m/Y H:i:s')
                                            ?: '—'
                                        }}
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Anulado por</div>
                                    <div class="cc-detail-value">
                                        {{ $abastecimiento->anuladoPor?->name ?: '—' }}
                                    </div>
                                </div>

                                <div class="cc-detail-item cc-detail-item-wide">
                                    <div class="cc-detail-label">Motivo de anulación</div>
                                    <div class="cc-detail-value">
                                        {{ $abastecimiento->motivo_anulacion ?: '—' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                </div>

                <div class="cc-actions cc-actions-split">
                    <div class="cc-actions-normal">
                        <a
                            href="{{ $rutaRetorno }}"
                            class="cc-btn-secondary cc-btn-form-action"
                        >
                            {{ $textoRetorno }}
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </main>
</body>
</html>