@php
    $esVentana = $esVentana ?? false;

    $rutaConsulta = $esVentana
        ? route('analisis.rutas.index.ventana')
        : route('analisis.rutas.index');

    $rutaAlterna = $esVentana
        ? route('analisis.rutas.index', request()->query())
        : route('analisis.rutas.index.ventana', request()->query());

    $textoAlterna = $esVentana
        ? 'Volver al sistema'
        : 'Abrir en nueva pestaña';

    $formatoNumero = fn (
        mixed $valor,
        int $decimales = 2
    ): string => number_format(
        (float) ($valor ?? 0),
        $decimales
    );

    $sortActual = $sort ?? 'fecha';
    $directionActual = $direction ?? 'desc';

    $urlOrden = function (string $campo) use (
        $rutaConsulta,
        $sortActual,
        $directionActual
    ): string {
        $nuevaDireccion = (
            $sortActual === $campo
            && $directionActual === 'asc'
        )
            ? 'desc'
            : 'asc';

        return $rutaConsulta . '?' . http_build_query(
            array_merge(
                request()->query(),
                [
                    'consultar' => 1,
                    'sort' => $campo,
                    'direction' => $nuevaDireccion,
                    'page' => null,
                ]
            )
        );
    };

    $indicadorOrden = function (string $campo) use (
        $sortActual,
        $directionActual
    ): string {
        if ($sortActual !== $campo) {
            return '↕';
        }

        return $directionActual === 'asc'
            ? '↑'
            : '↓';
    };

    $diagnosticoClase = fn (string $clave): string => match ($clave) {
        'consumo_superior' => 'cc-route-status-danger',
        'consumo_inferior' => 'cc-route-status-info',
        'dentro_estimacion' => 'cc-route-status-success',
        default => 'cc-route-status-muted',
    };
@endphp


<div class="cc-card">
    <div
        class="cc-card-header
               cc-card-header-compact
               cc-route-header"
    >
        <div>
            <h3 class="cc-title cc-title-compact">
                Análisis Operativo de Rutas
            </h3>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a
                href="{{ $rutaAlterna }}"
                @unless($esVentana)
                    target="_blank"
                    rel="noopener noreferrer"
                @endunless
                class="cc-btn-secondary cc-btn-wide"
            >
                {{ $textoAlterna }}
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="cc-alert cc-alert-danger">
            <div class="font-bold">
                No fue posible completar el análisis.
            </div>

            <ul class="cc-alert-list">
                @foreach (
                    collect($errors->all())
                        ->unique()
                        ->values()
                    as $error
                )
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="GET"
        action="{{ $rutaConsulta }}"
        class="mb-5"
    >
        <input type="hidden" name="consultar" value="1">

        <div
            class="cc-filter-panel
                   cc-filter-panel-compact
                   cc-filter-panel-inline"
        >
            <div
                class="cc-form-section cc-form-section-compact cc-analytics-filter-heading"
            >
                <div class="cc-form-section-title">
                    Filtros de análisis
                </div>
            </div>

            <div class="cc-standard-filter-grid cc-analytics-filter-grid">
                <div class="cc-field">
                    <label>Empresa</label>

                    @if ($esUsuarioDieselCop)
                        <div
                            class="cc-filter-multiselect"
                            data-cc-filter-multiselect
                            data-filter-type="empresa"
                        >
                            <button
                                type="button"
                                class="cc-filter-multiselect-toggle"
                                data-cc-filter-toggle
                            >
                                <span
                                    data-cc-filter-label
                                    data-default-label="Todas"
                                    data-plural-suffix="seleccionadas"
                                >
                                    Todas
                                </span>

                                <span class="cc-filter-multiselect-arrow">
                                    ⌄
                                </span>
                            </button>

                            <div
                                class="cc-filter-multiselect-menu"
                                data-cc-filter-menu
                            >
                                <div class="cc-filter-multiselect-list">
                                    <label
                                        class="cc-filter-multiselect-option
                                               cc-filter-multiselect-option-master"
                                    >
                                        <input
                                            type="checkbox"
                                            data-cc-filter-master
                                        >

                                        <span>Seleccionar todo</span>
                                    </label>

                                    @foreach (
                                        $empresasSelector
                                        as $empresaOpcion
                                    )
                                        <label
                                            class="cc-filter-multiselect-option"
                                            data-cc-filter-option
                                        >
                                            <input
                                                type="checkbox"
                                                name="empresa_ids[]"
                                                value="{{ $empresaOpcion->id }}"
                                                @checked(
                                                    in_array(
                                                        (int) $empresaOpcion->id,
                                                        $empresaIds ?? [],
                                                        true
                                                    )
                                                )
                                                data-cc-filter-checkbox
                                                data-empresa-checkbox
                                            >

                                            <span data-cc-filter-option-label>
                                                {{
                                                    $empresaOpcion
                                                        ->nombre_comercial
                                                    ?: $empresaOpcion
                                                        ->nombre_legal
                                                }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <select class="cc-input" disabled>
                            @foreach (
                                $empresasSelector
                                as $empresaOpcion
                            )
                                <option selected>
                                    {{
                                        $empresaOpcion->nombre_comercial
                                        ?: $empresaOpcion->nombre_legal
                                    }}
                                </option>
                            @endforeach
                        </select>

                        @foreach (
                            $empresaIds ?? []
                            as $empresaSeleccionadaId
                        )
                            <input
                                type="hidden"
                                name="empresa_ids[]"
                                value="{{ $empresaSeleccionadaId }}"
                            >
                        @endforeach
                    @endif
                </div>

                <div class="cc-field">
                    <label for="fecha_desde">
                        Fecha de inicio
                    </label>

                    <input
                        id="fecha_desde"
                        type="date"
                        name="fecha_desde"
                        value="{{ $fechaDesde }}"
                        class="cc-input"
                    >
                </div>

                <div class="cc-field">
                    <label for="fecha_hasta">
                        Fecha final
                    </label>

                    <input
                        id="fecha_hasta"
                        type="date"
                        name="fecha_hasta"
                        value="{{ $fechaHasta }}"
                        class="cc-input"
                    >
                </div>

                @php
                    $multiselects = [
                        [
                            'tipo' => 'unidad',
                            'etiqueta' => 'Unidad',
                            'default' => 'Todas',
                            'nombre' => 'unidad_ids[]',
                            'seleccionados' => $unidadIds ?? [],
                            'opciones' => $unidadesSelector,
                            'valor' => fn ($item) => $item->id,
                            'texto' => fn ($item) => $item->placa,
                            'empresa' => fn ($item) => $item->empresa_id,
                        ],
                        [
                            'tipo' => 'motorista',
                            'etiqueta' => 'Motorista',
                            'default' => 'Todos',
                            'nombre' => 'motorista_ids[]',
                            'seleccionados' => $motoristaIds ?? [],
                            'opciones' => $motoristasSelector,
                            'valor' => fn ($item) => $item->id,
                            'texto' => fn ($item) => trim(
                                $item->nombres . ' ' . $item->apellidos
                            ),
                            'empresa' => fn ($item) => $item->empresa_id,
                        ],
                        [
                            'tipo' => 'ruta',
                            'etiqueta' => 'Ruta',
                            'default' => 'Todas',
                            'nombre' => 'ruta_ids[]',
                            'seleccionados' => $rutaIds ?? [],
                            'opciones' => $rutasSelector,
                            'valor' => fn ($item) => $item->id,
                            'texto' => fn ($item) => $item->ruta,
                            'empresa' => fn ($item) => $item->empresa_id,
                        ],
                    ];
                @endphp

                @foreach ($multiselects as $config)
                    <div class="cc-field">
                        <label>{{ $config['etiqueta'] }}</label>

                        <div
                            class="cc-filter-multiselect"
                            data-cc-filter-multiselect
                            data-filter-type="{{ $config['tipo'] }}"
                        >
                            <button
                                type="button"
                                class="cc-filter-multiselect-toggle"
                                data-cc-filter-toggle
                            >
                                <span
                                    data-cc-filter-label
                                    data-default-label="{{ $config['default'] }}"
                                    data-plural-suffix="seleccionados"
                                >
                                    {{ $config['default'] }}
                                </span>

                                <span class="cc-filter-multiselect-arrow">
                                    ⌄
                                </span>
                            </button>

                            <div
                                class="cc-filter-multiselect-menu"
                                data-cc-filter-menu
                            >
                                <div class="cc-filter-multiselect-list">
                                    <label
                                        class="cc-filter-multiselect-option
                                               cc-filter-multiselect-option-master"
                                    >
                                        <input
                                            type="checkbox"
                                            data-cc-filter-master
                                        >

                                        <span>Seleccionar todo</span>
                                    </label>

                                    @foreach (
                                        $config['opciones']
                                        as $opcion
                                    )
                                        <label
                                            class="cc-filter-multiselect-option"
                                            data-cc-filter-option
                                            data-dependent-option
                                            data-empresa-id="{{
                                                $config['empresa']($opcion)
                                            }}"
                                        >
                                            <input
                                                type="checkbox"
                                                name="{{ $config['nombre'] }}"
                                                value="{{
                                                    $config['valor']($opcion)
                                                }}"
                                                @checked(
                                                    in_array(
                                                        (int) $config['valor']($opcion),
                                                        $config['seleccionados'],
                                                        true
                                                    )
                                                )
                                                data-cc-filter-checkbox
                                            >

                                            <span data-cc-filter-option-label>
                                                {{
                                                    $config['texto']($opcion)
                                                }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="cc-field">
                    <label>Tipo de recorrido</label>

                    <div
                        class="cc-filter-multiselect"
                        data-cc-filter-multiselect
                    >
                        <button
                            type="button"
                            class="cc-filter-multiselect-toggle"
                            data-cc-filter-toggle
                        >
                            <span
                                data-cc-filter-label
                                data-default-label="Todos"
                                data-plural-suffix="seleccionados"
                            >
                                Todos
                            </span>

                            <span class="cc-filter-multiselect-arrow">
                                ⌄
                            </span>
                        </button>

                        <div
                            class="cc-filter-multiselect-menu"
                            data-cc-filter-menu
                        >
                            <div class="cc-filter-multiselect-list">
                                <label
                                    class="cc-filter-multiselect-option
                                           cc-filter-multiselect-option-master"
                                >
                                    <input
                                        type="checkbox"
                                        data-cc-filter-master
                                    >
                                    <span>Seleccionar todo</span>
                                </label>

                                @foreach (
                                    $opcionesTiposRecorrido
                                    as $valor => $etiqueta
                                )
                                    <label
                                        class="cc-filter-multiselect-option"
                                        data-cc-filter-option
                                    >
                                        <input
                                            type="checkbox"
                                            name="tipos_recorrido[]"
                                            value="{{ $valor }}"
                                            @checked(
                                                in_array(
                                                    $valor,
                                                    $tiposRecorrido ?? [],
                                                    true
                                                )
                                            )
                                            data-cc-filter-checkbox
                                        >

                                        <span data-cc-filter-option-label>
                                            {{ $etiqueta }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cc-field">
                    <label>Estado de comparación</label>

                    <div
                        class="cc-filter-multiselect"
                        data-cc-filter-multiselect
                    >
                        <button
                            type="button"
                            class="cc-filter-multiselect-toggle"
                            data-cc-filter-toggle
                        >
                            <span
                                data-cc-filter-label
                                data-default-label="Todos"
                                data-plural-suffix="seleccionados"
                            >
                                Todos
                            </span>

                            <span class="cc-filter-multiselect-arrow">
                                ⌄
                            </span>
                        </button>

                        <div
                            class="cc-filter-multiselect-menu"
                            data-cc-filter-menu
                        >
                            <div class="cc-filter-multiselect-list">
                                <label
                                    class="cc-filter-multiselect-option
                                           cc-filter-multiselect-option-master"
                                >
                                    <input
                                        type="checkbox"
                                        data-cc-filter-master
                                    >
                                    <span>Seleccionar todo</span>
                                </label>

                                @foreach (
                                    $opcionesEstadosComparacion
                                    as $valor => $etiqueta
                                )
                                    <label
                                        class="cc-filter-multiselect-option"
                                        data-cc-filter-option
                                    >
                                        <input
                                            type="checkbox"
                                            name="estados_comparacion[]"
                                            value="{{ $valor }}"
                                            @checked(
                                                in_array(
                                                    $valor,
                                                    $estadosComparacion ?? [],
                                                    true
                                                )
                                            )
                                            data-cc-filter-checkbox
                                        >

                                        <span data-cc-filter-option-label>
                                            {{ $etiqueta }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="cc-field cc-analytics-filter-search"
                >
                    <label for="busqueda">
                        Búsqueda general
                    </label>

                    <input
                        id="busqueda"
                        type="text"
                        name="busqueda"
                        value="{{ $busqueda }}"
                        maxlength="150"
                        class="cc-input"
                        placeholder="Empresa, Nombre / Placa, motorista, ruta, punto o abastecimiento"
                    >
                </div>

                <div
                    class="cc-standard-filter-actions
                           cc-route-actions cc-analytics-filter-actions"
                >
                    <button
                        type="submit"
                        class="cc-btn-primary"
                    >
                        Consultar
                    </button>

                    <a
                        href="{{ $rutaConsulta }}"
                        class="cc-btn-secondary"
                    >
                        Limpiar
                    </a>
                </div>
            </div>
        </div>
    </form>

    @if ($hayFiltros)
        @php
            $variacion = $resumen['variacion_porcentaje'];

            $tarjetas = [
                [
                    'etiqueta' => 'Ciclos analizados',
                    'valor' => $resumen['abastecimientos_con_ruta'],
                ],
                [
                    'etiqueta' => 'Kilómetros teóricos',
                    'valor' => $formatoNumero(
                        $resumen['kilometros_teoricos'],
                        1
                    ) . ' km',
                ],
                [
                    'etiqueta' => 'Kilómetros medidos',
                    'valor' => $formatoNumero(
                        $resumen['kilometros_medidos'],
                        1
                    ) . ' km',
                ],
                [
                    'etiqueta' => 'Galones teóricos',
                    'valor' => $formatoNumero(
                        $resumen['galones_teoricos']
                    ) . ' gal',
                ],
                [
                    'etiqueta' => 'Galones consumidos',
                    'valor' => $formatoNumero(
                        $resumen['galones_consumidos']
                    ) . ' gal',
                ],
                [
                    'etiqueta' => 'Eficiencia real',
                    'valor' => is_null(
                        $resumen['eficiencia_real']
                    )
                        ? '—'
                        : $formatoNumero(
                            $resumen['eficiencia_real']
                        ) . ' km/gal',
                ],
                [
                    'etiqueta' => 'Variación vs. teórico',
                    'valor' => is_null($variacion)
                        ? '—'
                        : (
                            ($variacion > 0 ? '+' : '')
                            . $formatoNumero($variacion)
                            . ' %'
                        ),
                    'detalle' => is_null($variacion)
                        ? 'Sin comparación'
                        : (
                            $variacion > 0
                                ? 'Sobre estimación'
                                : (
                                    $variacion < 0
                                        ? 'Bajo estimación'
                                        : 'Sin variación'
                                )
                        ),
                    'clase_valor' => is_null($variacion)
                        ? 'cc-route-summary-value-neutral'
                        : (
                            $variacion > 0
                                ? 'cc-route-summary-value-positive'
                                : (
                                    $variacion < 0
                                        ? 'cc-route-summary-value-negative'
                                        : 'cc-route-summary-value-neutral'
                                )
                        ),
                ],
            ];
        @endphp

        <div
            class="cc-summary-strip cc-route-summary cc-analytics-summary-spacing"
        >
            @foreach ($tarjetas as $tarjeta)
                <div class="cc-summary-strip-item">
                    <span class="cc-summary-strip-label">
                        {{ $tarjeta['etiqueta'] }}
                    </span>

                    <span
                        class="cc-summary-strip-value {{
                            $tarjeta['clase_valor'] ?? ''
                        }}"
                    >
                        {{ $tarjeta['valor'] }}
                    </span>

                    @if (! empty($tarjeta['detalle']))
                        <span class="cc-route-summary-detail">
                            {{ $tarjeta['detalle'] }}
                        </span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="cc-route-diagnostic-strip">
            <div class="cc-route-diagnostic-item">
                <span class="cc-route-diagnostic-label">
                    Dentro de estimación
                </span>

                <span
                    class="cc-route-diagnostic-value
                           cc-route-status-success"
                >
                    {{
                        $resumen['diagnosticos'][
                            'dentro_estimacion'
                        ]
                    }}
                </span>
            </div>

            <div class="cc-route-diagnostic-item">
                <span class="cc-route-diagnostic-label">
                    Consumo superior
                </span>

                <span
                    class="cc-route-diagnostic-value
                           cc-route-status-danger"
                >
                    {{
                        $resumen['diagnosticos'][
                            'consumo_superior'
                        ]
                    }}
                </span>
            </div>

            <div class="cc-route-diagnostic-item">
                <span class="cc-route-diagnostic-label">
                    Consumo inferior
                </span>

                <span
                    class="cc-route-diagnostic-value
                           cc-route-status-info"
                >
                    {{
                        $resumen['diagnosticos'][
                            'consumo_inferior'
                        ]
                    }}
                </span>
            </div>

            <div class="cc-route-diagnostic-item">
                <span class="cc-route-diagnostic-label">
                    Sin comparación
                </span>

                <span
                    class="cc-route-diagnostic-value
                           cc-route-status-muted"
                >
                    {{
                        $resumen['diagnosticos'][
                            'sin_comparacion'
                        ]
                    }}
                </span>
            </div>
        </div>

        <div class="cc-route-charts">
            <section class="cc-route-chart-card">
                <h4 class="cc-route-chart-title">
                    Consumo teórico frente a real por ciclo
                </h4>

                <div class="cc-route-chart-subtitle">
                    El eje X representa los ciclos de abastecimiento
                    completados y el eje Y representa galones.
                </div>

                @if ($graficos['ciclos']['puntos'] !== [])
                    <div class="cc-route-chart-area">
                        <canvas
                            class="cc-route-chart-canvas"
                            data-route-line-chart
                            data-chart-source="grafico-rutas-ciclos"
                            aria-label="Consumo teórico y real por ciclo"
                        ></canvas>
                    </div>

                    <div
                        class="cc-analytics-chart-detail"
                        data-route-chart-detail
                    >
                        Mueva el cursor sobre el gráfico para ver
                        el detalle del ciclo.
                    </div>

                    <div class="cc-route-chart-toolbar">
                        <div class="cc-route-chart-toolbar-actions">
                            <button
                                type="button"
                                class="cc-btn-secondary"
                                data-chart-previous
                            >
                                Anterior
                            </button>

                            <button
                                type="button"
                                class="cc-btn-secondary"
                                data-chart-next
                            >
                                Siguiente
                            </button>

                            <button
                                type="button"
                                class="cc-btn-secondary"
                                data-chart-zoom-in
                            >
                                Acercar
                            </button>

                            <button
                                type="button"
                                class="cc-btn-secondary"
                                data-chart-zoom-out
                            >
                                Alejar
                            </button>

                            <button
                                type="button"
                                class="cc-btn-secondary"
                                data-chart-reset
                            >
                                Restablecer
                            </button>

                            <button
                                type="button"
                                class="cc-btn-secondary"
                                data-chart-table-toggle
                                aria-expanded="false"
                            >
                                Ver datos
                            </button>
                        </div>

                        <div
                            class="cc-route-chart-toolbar-status"
                            data-chart-window-status
                        >
                            —
                        </div>
                    </div>

                    <div class="cc-route-chart-navigator">
                        <span class="cc-route-chart-navigator-label">
                            Inicio
                        </span>

                        <input
                            type="range"
                            min="0"
                            max="0"
                            value="0"
                            step="1"
                            class="cc-route-chart-range"
                            data-chart-range
                        >

                        <span class="cc-route-chart-navigator-label">
                            Fin
                        </span>
                    </div>

                    <div
                        class="cc-route-chart-table-panel"
                        data-chart-table-panel
                        hidden
                    >
                        <table class="cc-route-chart-table">
                            <thead>
                                <tr>
                                    <th>Ciclo</th>
                                    <th>Detalle</th>
                                    <th>Teórico</th>
                                    <th>Real</th>
                                    <th>Variación</th>
                                </tr>
                            </thead>

                            <tbody data-chart-table-body></tbody>
                        </table>
                    </div>

                    <div class="cc-route-chart-legend">
                        <span class="cc-route-chart-legend-item">
                            <span
                                class="cc-route-chart-legend-line
                                       cc-route-chart-legend-line-theoretical"
                            ></span>

                            Consumo teórico
                        </span>

                        <span class="cc-route-chart-legend-item">
                            <span
                                class="cc-route-chart-legend-line
                                       cc-route-chart-legend-line-real"
                            ></span>

                            Consumo real
                        </span>
                    </div>

                    <script
                        type="application/json"
                        id="grafico-rutas-ciclos"
                    >@json($graficos['ciclos']['puntos'])</script>
                @else
                    <div class="cc-route-chart-empty">
                        No hay ciclos suficientes para construir
                        la comparación.
                    </div>
                @endif
            </section>
        </div>
    @endif

    @if (! $hayFiltros)
        <section class="cc-empty-panel cc-empty-panel-compact">
            <h5>Análisis pendiente</h5>

            <p>
                Seleccione los criterios necesarios y presione
                Consultar para analizar las rutas.
            </p>
        </section>
    @elseif ($abastecimientos->isEmpty())
        <section class="cc-empty-panel cc-empty-panel-compact">
            <h5>Sin resultados</h5>

            <p>
                No existen ciclos con rutas que coincidan
                con los filtros seleccionados.
            </p>
        </section>
    @else
        <div class="cc-table-adaptive-wrapper">
            <table
                class="cc-table-adaptive cc-analytics-table-routes"
            >
                <thead>
                    <tr>
                        @foreach ([
                            'fecha' => 'Ciclo y fecha',
                            'empresa' => 'Empresa',
                            'unidad' => 'Unidad',
                            'motorista' => 'Motorista',
                            'kilometros_teoricos' => 'Km reales',
                            'galones_teoricos' => 'Galones teóricos',
                            'galones_consumidos' => 'Galones consumidos',
                            'variacion_porcentaje' => 'Variación %',
                            'diagnostico' => 'Diagnóstico',
                        ] as $campo => $etiqueta)
                            <th>
                                <a
                                    href="{{ $urlOrden($campo) }}"
                                    class="cc-route-sort-link"
                                >
                                    {{ $etiqueta }}

                                    <span class="cc-route-sort-icon">
                                        {{ $indicadorOrden($campo) }}
                                    </span>
                                </a>
                            </th>
                        @endforeach

                        <th>Detalle de ruta</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach (
                        $abastecimientos
                        as $abastecimiento
                    )
                        @php
                            $detalleId =
                                'detalle-ruta-ciclo-'
                                . $abastecimiento->id;
                        @endphp

                        <tr>
                            <td class="cc-route-nowrap">
                                <div class="cc-table-adaptive-strong">
                                    Ciclo {{
                                        $abastecimiento
                                            ->numero_ciclo_analitico
                                    }}
                                </div>

                                <div class="cc-table-adaptive-muted">
                                    {{
                                        optional(
                                            $abastecimiento
                                                ->fecha_hora_abastecimiento
                                        )->format('d/m/Y H:i')
                                        ?: '—'
                                    }}
                                </div>
                            </td>

                            <td class="cc-route-nowrap">
                                {{
                                    $abastecimiento
                                        ->empresa_texto_analitico
                                }}
                            </td>

                            <td class="cc-route-nowrap">
                                {{
                                    $abastecimiento
                                        ->unidad_texto_analitico
                                }}
                            </td>

                            <td class="cc-route-nowrap">
                                {{
                                    $abastecimiento
                                        ->motorista_texto_analitico
                                }}
                            </td>

                            <td class="cc-route-number">
                                {{
                                    is_null(
                                        $abastecimiento
                                            ->kilometros_reales_analitico
                                    )
                                        ? '—'
                                        : $formatoNumero(
                                            $abastecimiento
                                                ->kilometros_reales_analitico,
                                            1
                                        ) . ' km'
                                }}
                            </td>

                            <td class="cc-route-number">
                                {{
                                    is_null(
                                        $abastecimiento
                                            ->galones_teoricos_analitico
                                    )
                                        ? '—'
                                        : $formatoNumero(
                                            $abastecimiento
                                                ->galones_teoricos_analitico
                                        ) . ' gal'
                                }}
                            </td>

                            <td class="cc-route-number">
                                {{
                                    is_null(
                                        $abastecimiento
                                            ->galones_consumidos_analitico
                                    )
                                        ? '—'
                                        : $formatoNumero(
                                            $abastecimiento
                                                ->galones_consumidos_analitico
                                        ) . ' gal'
                                }}
                            </td>

                            <td class="cc-route-number">
                                @if (
                                    is_null(
                                        $abastecimiento
                                            ->variacion_porcentaje_analitico
                                    )
                                )
                                    —
                                @else
                                    {{
                                        (
                                            $abastecimiento
                                                ->variacion_porcentaje_analitico
                                            > 0
                                                ? '+'
                                                : ''
                                        )
                                        . $formatoNumero(
                                            $abastecimiento
                                                ->variacion_porcentaje_analitico
                                        )
                                        . ' %'
                                    }}
                                @endif
                            </td>

                            <td>
                                <span
                                    class="cc-route-badge {{
                                        $diagnosticoClase(
                                            $abastecimiento
                                                ->diagnostico_clave_analitico
                                        )
                                    }}"
                                >
                                    {{
                                        $abastecimiento
                                            ->diagnostico_analitico
                                    }}
                                </span>
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="cc-btn-secondary
                                           cc-route-detail-button"
                                    data-route-detail-toggle
                                    aria-expanded="false"
                                    aria-controls="{{ $detalleId }}"
                                >
                                    Ver ruta
                                </button>
                            </td>
                        </tr>

                        <tr
                            id="{{ $detalleId }}"
                            class="cc-route-detail-row"
                            data-route-detail-row
                            hidden
                        >
                            <td
                                colspan="10"
                                class="cc-route-detail-cell"
                            >
                                <div class="cc-route-detail-panel">
                                    <div class="cc-route-detail-heading">
                                        <div>
                                            <div class="cc-route-detail-title">
                                                Rutas del ciclo {{
                                                    $abastecimiento
                                                        ->numero_ciclo_analitico
                                                }}
                                            </div>
                                        </div>

                                        <div class="cc-route-detail-note">
                                            Los kilómetros y galones reales
                                            por ruta son valores distribuidos
                                            proporcionalmente desde el total
                                            real del ciclo.
                                        </div>
                                    </div>

                                    <div
                                        class="cc-route-detail-table-wrapper"
                                    >
                                        <table
                                            class="cc-route-detail-table"
                                        >
                                            <thead>
                                                <tr>
                                                    <th>Ruta</th>
                                                    <th>Recorrido</th>
                                                    <th>Tipo</th>
                                                    <th>Km teóricos</th>
                                                    <th>Km reales</th>
                                                    <th>Consumo teórico</th>
                                                    <th>Consumo real</th>
                                                    <th>Eficiencia</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @foreach (
                                                    $abastecimiento
                                                        ->detalle_rutas_analitico
                                                    as $detalleRuta
                                                )
                                                    <tr>
                                                        <td>
                                                            <div
                                                                class="cc-table-adaptive-strong"
                                                            >
                                                                {{
                                                                    $detalleRuta[
                                                                        'ruta'
                                                                    ]
                                                                }}
                                                            </div>
                                                        </td>

                                                        <td>
                                                            {{
                                                                $detalleRuta[
                                                                    'recorrido'
                                                                ]
                                                                ?: '—'
                                                            }}
                                                        </td>

                                                        <td
                                                            class="cc-route-nowrap"
                                                        >
                                                            {{
                                                                $detalleRuta[
                                                                    'tipo_recorrido'
                                                                ]
                                                            }}
                                                        </td>

                                                        <td
                                                            class="cc-route-number"
                                                        >
                                                            {{
                                                                $formatoNumero(
                                                                    $detalleRuta[
                                                                        'kilometros_teoricos'
                                                                    ],
                                                                    1
                                                                )
                                                            }} km
                                                        </td>

                                                        <td
                                                            class="cc-route-number"
                                                        >
                                                            {{
                                                                is_null(
                                                                    $detalleRuta[
                                                                        'kilometros_reales'
                                                                    ]
                                                                )
                                                                    ? '—'
                                                                    : $formatoNumero(
                                                                        $detalleRuta[
                                                                            'kilometros_reales'
                                                                        ],
                                                                        1
                                                                    ) . ' km'
                                                            }}
                                                        </td>

                                                        <td
                                                            class="cc-route-number"
                                                        >
                                                            {{
                                                                $formatoNumero(
                                                                    $detalleRuta[
                                                                        'galones_teoricos'
                                                                    ]
                                                                )
                                                            }} gal
                                                        </td>

                                                        <td
                                                            class="cc-route-number"
                                                        >
                                                            {{
                                                                is_null(
                                                                    $detalleRuta[
                                                                        'galones_reales'
                                                                    ]
                                                                )
                                                                    ? '—'
                                                                    : $formatoNumero(
                                                                        $detalleRuta[
                                                                            'galones_reales'
                                                                        ]
                                                                    ) . ' gal'
                                                            }}
                                                        </td>

                                                        <td
                                                            class="cc-route-number"
                                                        >
                                                            {{
                                                                is_null(
                                                                    $detalleRuta[
                                                                        'eficiencia'
                                                                    ]
                                                                )
                                                                    ? '—'
                                                                    : $formatoNumero(
                                                                        $detalleRuta[
                                                                            'eficiencia'
                                                                        ]
                                                                    ) . ' km/gal'
                                                            }}
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{
                $abastecimientos
                    ->appends(
                        array_merge(
                            request()->query(),
                            ['consultar' => 1]
                        )
                    )
                    ->links()
            }}
        </div>
    @endif
</div>

<script>
    document.addEventListener(
        'DOMContentLoaded',
        function () {
            const multiselects = Array.from(
                document.querySelectorAll(
                    '[data-cc-filter-multiselect]'
                )
            );

            function cerrarTodos(excepto = null) {
                multiselects.forEach(function (item) {
                    if (excepto && item === excepto) {
                        return;
                    }

                    item.classList.remove('is-open');
                    item.querySelector(
                        '[data-cc-filter-toggle]'
                    )?.classList.remove('is-open');

                    item.querySelector(
                        '[data-cc-filter-menu]'
                    )?.classList.remove('is-open');
                });
            }

            multiselects.forEach(function (multiselect) {
                const toggle = multiselect.querySelector(
                    '[data-cc-filter-toggle]'
                );

                const menu = multiselect.querySelector(
                    '[data-cc-filter-menu]'
                );

                const label = multiselect.querySelector(
                    '[data-cc-filter-label]'
                );

                const master = multiselect.querySelector(
                    '[data-cc-filter-master]'
                );

                const checkboxes = Array.from(
                    multiselect.querySelectorAll(
                        '[data-cc-filter-checkbox]'
                    )
                );

                function visibles() {
                    return checkboxes.filter(function (checkbox) {
                        return ! checkbox.closest(
                            '[data-cc-filter-option]'
                        )?.hidden;
                    });
                }

                function actualizarEtiqueta() {
                    const disponibles = visibles();
                    const seleccionados = disponibles.filter(
                        checkbox => checkbox.checked
                    );

                    if (label) {
                        if (seleccionados.length === 0) {
                            label.textContent =
                                label.dataset.defaultLabel || 'Todos';
                        } else if (seleccionados.length === 1) {
                            label.textContent =
                                seleccionados[0]
                                    .closest(
                                        '[data-cc-filter-option]'
                                    )
                                    ?.querySelector(
                                        '[data-cc-filter-option-label]'
                                    )
                                    ?.textContent
                                    ?.trim()
                                || '1 seleccionado';
                        } else {
                            label.textContent =
                                seleccionados.length
                                + ' '
                                + (
                                    label.dataset.pluralSuffix
                                    || 'seleccionados'
                                );
                        }
                    }

                    if (master) {
                        master.checked =
                            disponibles.length > 0
                            && seleccionados.length
                                === disponibles.length;

                        master.indeterminate =
                            seleccionados.length > 0
                            && seleccionados.length
                                < disponibles.length;
                    }
                }

                toggle?.addEventListener('click', function () {
                    cerrarTodos(multiselect);

                    const abrira =
                        ! multiselect.classList.contains('is-open');

                    multiselect.classList.toggle(
                        'is-open',
                        abrira
                    );

                    toggle.classList.toggle(
                        'is-open',
                        abrira
                    );

                    menu?.classList.toggle(
                        'is-open',
                        abrira
                    );
                });

                master?.addEventListener('change', function () {
                    visibles().forEach(function (checkbox) {
                        checkbox.checked = master.checked;
                    });

                    actualizarEtiqueta();
                });

                checkboxes.forEach(function (checkbox) {
                    checkbox.addEventListener(
                        'change',
                        actualizarEtiqueta
                    );
                });

                multiselect.actualizarEtiqueta =
                    actualizarEtiqueta;

                actualizarEtiqueta();
            });

            function empresasSeleccionadas() {
                return Array.from(
                    document.querySelectorAll(
                        '[data-empresa-checkbox]:checked'
                    )
                ).map(checkbox => checkbox.value);
            }

            function filtrarDependientes() {
                const empresas = empresasSeleccionadas();

                document.querySelectorAll(
                    '[data-dependent-option]'
                ).forEach(function (opcion) {
                    const visible =
                        empresas.length === 0
                        || empresas.includes(
                            opcion.dataset.empresaId
                        );

                    opcion.hidden = ! visible;

                    if (! visible) {
                        const checkbox = opcion.querySelector(
                            '[data-cc-filter-checkbox]'
                        );

                        if (checkbox) {
                            checkbox.checked = false;
                        }
                    }
                });

                multiselects.forEach(function (item) {
                    item.actualizarEtiqueta?.();
                });
            }

            document.querySelectorAll(
                '[data-empresa-checkbox]'
            ).forEach(function (checkbox) {
                checkbox.addEventListener(
                    'change',
                    filtrarDependientes
                );
            });

            document.querySelector(
                '[data-filter-type="empresa"] '
                + '[data-cc-filter-master]'
            )?.addEventListener('change', function () {
                window.setTimeout(filtrarDependientes, 0);
            });

            filtrarDependientes();

            document.addEventListener('click', function (event) {
                if (
                    event.target.closest(
                        '[data-cc-filter-multiselect]'
                    )
                ) {
                    return;
                }

                cerrarTodos();
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    cerrarTodos();
                }
            });

            function prepararCanvas(canvas) {
                const rect = canvas.getBoundingClientRect();
                const ratio = window.devicePixelRatio || 1;
                const width = Math.max(rect.width, 320);
                const height = Math.max(rect.height, 288);

                canvas.width = Math.floor(width * ratio);
                canvas.height = Math.floor(height * ratio);

                const context = canvas.getContext('2d');
                context.setTransform(
                    ratio,
                    0,
                    0,
                    ratio,
                    0,
                    0
                );

                return {
                    context,
                    width,
                    height,
                };
            }

            document.querySelectorAll(
                '[data-route-detail-toggle]'
            ).forEach(function (button) {
                button.addEventListener('click', function () {
                    const detailId =
                        button.getAttribute('aria-controls');

                    const detailRow =
                        document.getElementById(detailId);

                    if (! detailRow) {
                        return;
                    }

                    const willOpen = detailRow.hidden;

                    detailRow.hidden = ! willOpen;
                    button.setAttribute(
                        'aria-expanded',
                        willOpen ? 'true' : 'false'
                    );

                    button.textContent = willOpen
                        ? 'Ocultar ruta'
                        : 'Ver ruta';
                });
            });

            document.querySelectorAll(
                '[data-route-line-chart]'
            ).forEach(function (canvas) {
                const source = document.getElementById(
                    canvas.dataset.chartSource
                );

                if (! source) {
                    return;
                }

                const allPoints = JSON.parse(
                    source.textContent || '[]'
                );

                if (! Array.isArray(allPoints) || ! allPoints.length) {
                    return;
                }

                const status = document.querySelector(
                    '[data-chart-window-status]'
                );

                const range = document.querySelector(
                    '[data-chart-range]'
                );

                const previousButton = document.querySelector(
                    '[data-chart-previous]'
                );

                const nextButton = document.querySelector(
                    '[data-chart-next]'
                );

                const zoomInButton = document.querySelector(
                    '[data-chart-zoom-in]'
                );

                const zoomOutButton = document.querySelector(
                    '[data-chart-zoom-out]'
                );

                const resetButton = document.querySelector(
                    '[data-chart-reset]'
                );

                const tableToggle = document.querySelector(
                    '[data-chart-table-toggle]'
                );

                const tablePanel = document.querySelector(
                    '[data-chart-table-panel]'
                );

                const tableBody = document.querySelector(
                    '[data-chart-table-body]'
                );

                let windowSize = Math.min(
                    60,
                    allPoints.length
                );

                let startIndex = 0;

                function clampWindow() {
                    windowSize = Math.max(
                        10,
                        Math.min(
                            windowSize,
                            allPoints.length
                        )
                    );

                    startIndex = Math.max(
                        0,
                        Math.min(
                            startIndex,
                            Math.max(
                                allPoints.length - windowSize,
                                0
                            )
                        )
                    );
                }

                function visiblePoints() {
                    clampWindow();

                    return allPoints.slice(
                        startIndex,
                        startIndex + windowSize
                    );
                }

                function updateControls() {
                    const endIndex = Math.min(
                        startIndex + windowSize,
                        allPoints.length
                    );

                    if (status) {
                        status.textContent =
                            'Ciclos '
                            + (startIndex + 1)
                            + '–'
                            + endIndex
                            + ' de '
                            + allPoints.length;
                    }

                    if (range) {
                        range.max = Math.max(
                            allPoints.length - windowSize,
                            0
                        );

                        range.value = startIndex;
                        range.disabled =
                            allPoints.length <= windowSize;
                    }

                    if (previousButton) {
                        previousButton.disabled =
                            startIndex <= 0;
                    }

                    if (nextButton) {
                        nextButton.disabled =
                            endIndex >= allPoints.length;
                    }

                    if (zoomInButton) {
                        zoomInButton.disabled =
                            windowSize <= 10;
                    }

                    if (zoomOutButton) {
                        zoomOutButton.disabled =
                            windowSize >= allPoints.length;
                    }
                }

                function updateTable(points) {
                    if (! tableBody) {
                        return;
                    }

                    tableBody.innerHTML = '';

                    points.forEach(function (point) {
                        const theoretical =
                            Number(point.teorico);

                        const real =
                            Number(point.real);

                        const variation =
                            real - theoretical;

                        const row =
                            document.createElement('tr');

                        [
                            point.etiqueta || '—',
                            point.detalle || '—',
                            theoretical.toFixed(2) + ' gal',
                            real.toFixed(2) + ' gal',
                            (
                                variation > 0
                                    ? '+'
                                    : ''
                            )
                            + variation.toFixed(2)
                            + ' gal',
                        ].forEach(function (value) {
                            const cell =
                                document.createElement('td');

                            cell.textContent = value;
                            row.appendChild(cell);
                        });

                        tableBody.appendChild(row);
                    });
                }

                function drawChart() {
                    const points = visiblePoints();

                    const { context, width, height } =
                        prepararCanvas(canvas);

                    const padding = {
                        top: 20,
                        right: 18,
                        bottom: 52,
                        left: 56,
                    };

                    const chartWidth =
                        width
                        - padding.left
                        - padding.right;

                    const chartHeight =
                        height
                        - padding.top
                        - padding.bottom;

                    const maxValue = Math.max(
                        ...points.flatMap(point => [
                            Number(point.teorico),
                            Number(point.real),
                        ]),
                        1
                    );

                    const css = getComputedStyle(
                        document.documentElement
                    );

                    const borderColor = css
                        .getPropertyValue('--cc-border')
                        .trim() || '#dbe3ea';

                    const textColor = css
                        .getPropertyValue('--cc-text-muted')
                        .trim() || '#64748b';

                    const theoreticalColor = css
                        .getPropertyValue('--cc-primary')
                        .trim() || '#0f766e';

                    const realColor = '#d97706';

                    function xFor(index) {
                        return points.length === 1
                            ? padding.left
                                + chartWidth / 2
                            : padding.left
                                + index
                                / (points.length - 1)
                                * chartWidth;
                    }

                    function yFor(value) {
                        return padding.top
                            + (
                                1
                                - Number(value)
                                / maxValue
                            )
                            * chartHeight;
                    }

                    context.clearRect(
                        0,
                        0,
                        width,
                        height
                    );

                    context.font = '11px sans-serif';
                    context.textBaseline = 'middle';

                    for (
                        let index = 0;
                        index <= 4;
                        index++
                    ) {
                        const proportion =
                            index / 4;

                        const y =
                            padding.top
                            + proportion
                            * chartHeight;

                        const value =
                            maxValue
                            - proportion
                            * maxValue;

                        context.beginPath();
                        context.strokeStyle =
                            borderColor;

                        context.lineWidth = 1;
                        context.moveTo(
                            padding.left,
                            y
                        );

                        context.lineTo(
                            width - padding.right,
                            y
                        );

                        context.stroke();

                        context.fillStyle =
                            textColor;

                        context.textAlign =
                            'right';

                        context.fillText(
                            value.toFixed(2),
                            padding.left - 8,
                            y
                        );
                    }

                    [
                        ['teorico', theoreticalColor],
                        ['real', realColor],
                    ].forEach(
                        function ([campo, color]) {
                            context.beginPath();
                            context.strokeStyle =
                                color;

                            context.lineWidth = 2.5;
                            context.lineJoin =
                                'round';

                            context.lineCap =
                                'round';

                            points.forEach(
                                function (
                                    point,
                                    index
                                ) {
                                    const x =
                                        xFor(index);

                                    const y =
                                        yFor(
                                            point[campo]
                                        );

                                    if (index === 0) {
                                        context.moveTo(
                                            x,
                                            y
                                        );
                                    } else {
                                        context.lineTo(
                                            x,
                                            y
                                        );
                                    }
                                }
                            );

                            context.stroke();

                            points.forEach(
                                function (
                                    point,
                                    index
                                ) {
                                    context.beginPath();
                                    context.fillStyle =
                                        color;

                                    context.arc(
                                        xFor(index),
                                        yFor(
                                            point[campo]
                                        ),
                                        3.5,
                                        0,
                                        Math.PI * 2
                                    );

                                    context.fill();
                                }
                            );
                        }
                    );

                    const maxLabels = Math.min(
                        7,
                        points.length
                    );

                    const labelIndexes =
                        new Set();

                    for (
                        let index = 0;
                        index < maxLabels;
                        index++
                    ) {
                        labelIndexes.add(
                            Math.round(
                                index
                                * (
                                    points.length
                                    - 1
                                )
                                / Math.max(
                                    maxLabels - 1,
                                    1
                                )
                            )
                        );
                    }

                    context.fillStyle =
                        textColor;

                    context.textAlign =
                        'center';

                    context.textBaseline =
                        'top';

                    labelIndexes.forEach(
                        function (index) {
                            context.fillText(
                                String(
                                    points[index]
                                        .etiqueta
                                    || ''
                                ),
                                xFor(index),
                                height
                                - padding.bottom
                                + 14
                            );
                        }
                    );

                    canvas._ccRouteChart = {
                        points,
                        xFor,
                    };

                    updateTable(points);
                    updateControls();
                }

                previousButton?.addEventListener(
                    'click',
                    function () {
                        startIndex -= windowSize;
                        clampWindow();
                        drawChart();
                    }
                );

                nextButton?.addEventListener(
                    'click',
                    function () {
                        startIndex += windowSize;
                        clampWindow();
                        drawChart();
                    }
                );

                zoomInButton?.addEventListener(
                    'click',
                    function () {
                        const center =
                            startIndex
                            + windowSize / 2;

                        windowSize = Math.max(
                            10,
                            Math.floor(
                                windowSize * .65
                            )
                        );

                        startIndex = Math.floor(
                            center
                            - windowSize / 2
                        );

                        clampWindow();
                        drawChart();
                    }
                );

                zoomOutButton?.addEventListener(
                    'click',
                    function () {
                        const center =
                            startIndex
                            + windowSize / 2;

                        windowSize = Math.min(
                            allPoints.length,
                            Math.ceil(
                                windowSize * 1.5
                            )
                        );

                        startIndex = Math.floor(
                            center
                            - windowSize / 2
                        );

                        clampWindow();
                        drawChart();
                    }
                );

                resetButton?.addEventListener(
                    'click',
                    function () {
                        windowSize = Math.min(
                            60,
                            allPoints.length
                        );

                        startIndex = 0;
                        drawChart();
                    }
                );

                range?.addEventListener(
                    'input',
                    function () {
                        startIndex = Number(
                            range.value
                        );

                        drawChart();
                    }
                );

                tableToggle?.addEventListener(
                    'click',
                    function () {
                        if (! tablePanel) {
                            return;
                        }

                        const willOpen =
                            tablePanel.hidden;

                        tablePanel.hidden =
                            ! willOpen;

                        tableToggle.setAttribute(
                            'aria-expanded',
                            willOpen
                                ? 'true'
                                : 'false'
                        );

                        tableToggle.textContent =
                            willOpen
                                ? 'Ocultar datos'
                                : 'Ver datos';
                    }
                );

                canvas.addEventListener(
                    'mousemove',
                    function (event) {
                        const chart =
                            canvas._ccRouteChart;

                        if (! chart) {
                            return;
                        }

                        const rect =
                            canvas.getBoundingClientRect();

                        const mouseX =
                            event.clientX
                            - rect.left;

                        let closestIndex = 0;
                        let closestDistance =
                            Infinity;

                        chart.points.forEach(
                            function (
                                point,
                                index
                            ) {
                                const distance =
                                    Math.abs(
                                        chart.xFor(
                                            index
                                        )
                                        - mouseX
                                    );

                                if (
                                    distance
                                    < closestDistance
                                ) {
                                    closestDistance =
                                        distance;

                                    closestIndex =
                                        index;
                                }
                            }
                        );

                        const point =
                            chart.points[
                                closestIndex
                            ];

                        const detail =
                            document.querySelector(
                                '[data-route-chart-detail]'
                            );

                        if (detail) {
                            detail.textContent =
                                point.etiqueta
                                + ': '
                                + Number(
                                    point.teorico
                                ).toFixed(2)
                                + ' gal teóricos · '
                                + Number(
                                    point.real
                                ).toFixed(2)
                                + ' gal reales'
                                + (
                                    point.detalle
                                        ? ' · '
                                            + point.detalle
                                        : ''
                                );
                        }
                    }
                );

                let resizeTimer = null;

                window.addEventListener(
                    'resize',
                    function () {
                        window.clearTimeout(
                            resizeTimer
                        );

                        resizeTimer =
                            window.setTimeout(
                                drawChart,
                                120
                            );
                    }
                );

                drawChart();
            });
        }
    );
</script>
