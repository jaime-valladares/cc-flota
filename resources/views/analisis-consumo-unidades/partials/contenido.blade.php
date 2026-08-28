@php
    $esVentana = $esVentana ?? false;

    $rutaConsulta = $esVentana
        ? route('analisis.consumo-unidades.index.ventana')
        : route('analisis.consumo-unidades.index');

    $rutaAlterna = $esVentana
        ? route('analisis.consumo-unidades.index', request()->query())
        : route('analisis.consumo-unidades.index.ventana', request()->query());

    $textoAlterna = $esVentana
        ? 'Volver al sistema'
        : 'Abrir en nueva pestaña';

    $sortActual = $sort ?? 'unidad';
    $directionActual = $direction ?? 'asc';

    $formatoNumero = fn (
        mixed $valor,
        int $decimales = 2
    ): string => number_format(
        (float) ($valor ?? 0),
        $decimales
    );

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

    $etiquetaTendencia = [
        'mejora' => 'Mejora',
        'estable' => 'Estable',
        'deterioro' => 'Deterioro',
        'sin_historial' => 'Sin historial',
    ];
@endphp


<div class="cc-card">
    <div class="cc-card-header cc-card-header-compact cc-analytics-header">
        <div>
            <h3 class="cc-title cc-title-compact">
                Análisis de Consumo por Unidad
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
            <div class="cc-alert-title">
                No fue posible completar el análisis.
            </div>

            <ul class="cc-alert-list">
                @foreach (
                    collect($errors->all())->unique()->values()
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
        <input
            type="hidden"
            name="consultar"
            value="1"
        >

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
                                        $empresaOpcion
                                            ->nombre_comercial
                                        ?: $empresaOpcion
                                            ->nombre_legal
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
                    <label>Unidad</label>

                    <div
                        class="cc-filter-multiselect"
                        data-cc-filter-multiselect
                        data-filter-type="unidad"
                    >
                        <button
                            type="button"
                            class="cc-filter-multiselect-toggle"
                            data-cc-filter-toggle
                        >
                            <span
                                data-cc-filter-label
                                data-default-label="Todas"
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
                                    $unidadesSelector
                                    as $unidadOpcion
                                )
                                    <label
                                        class="cc-filter-multiselect-option"
                                        data-cc-filter-option
                                        data-unidad-option
                                        data-empresa-id="{{
                                            $unidadOpcion->empresa_id
                                        }}"
                                    >
                                        <input
                                            type="checkbox"
                                            name="unidad_ids[]"
                                            value="{{ $unidadOpcion->id }}"
                                            @checked(
                                                in_array(
                                                    (int) $unidadOpcion->id,
                                                    $unidadIds ?? [],
                                                    true
                                                )
                                            )
                                            data-cc-filter-checkbox
                                            data-unidad-checkbox
                                        >

                                        <span data-cc-filter-option-label>
                                            {{ $unidadOpcion->placa }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cc-field">
                    <label>Modelo de operación</label>

                    <div
                        class="cc-filter-multiselect"
                        data-cc-filter-multiselect
                        data-filter-type="modelo"
                    >
                        <button
                            type="button"
                            class="cc-filter-multiselect-toggle"
                            data-cc-filter-toggle
                        >
                            <span
                                data-cc-filter-label
                                data-default-label="Todos"
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
                                    $opcionesModelos
                                    as $modeloValor => $modeloEtiqueta
                                )
                                    <label
                                        class="cc-filter-multiselect-option"
                                        data-cc-filter-option
                                    >
                                        <input
                                            type="checkbox"
                                            name="modelos_medicion[]"
                                            value="{{ $modeloValor }}"
                                            @checked(
                                                in_array(
                                                    $modeloValor,
                                                    $modelosMedicion ?? [],
                                                    true
                                                )
                                            )
                                            data-cc-filter-checkbox
                                        >

                                        <span data-cc-filter-option-label>
                                            {{ $modeloEtiqueta }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
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

                <div class="cc-field">
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
                        placeholder="Empresa, Nombre / Placa, marca o modelo"
                    >
                </div>

                <div
                    class="cc-standard-filter-actions cc-analytics-filter-actions"
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
            $tarjetas = [
                [
                    'etiqueta' => 'Unidades analizadas',
                    'valor' => $resumen['unidades'],
                ],
                [
                    'etiqueta' => 'Ciclos completados',
                    'valor' => $resumen['ciclos'],
                ],
                [
                    'etiqueta' => 'Galones consumidos',
                    'valor' => $formatoNumero(
                        $resumen['galones_consumidos']
                    ) . ' gal',
                ],
                [
                    'etiqueta' => 'Unidades en mejora',
                    'valor' => $resumen['unidades_mejora'],
                ],
                [
                    'etiqueta' => 'Unidades estables',
                    'valor' => $resumen['unidades_estables'],
                ],
                [
                    'etiqueta' => 'Unidades en deterioro',
                    'valor' => $resumen['unidades_deterioro'],
                ],
            ];
        @endphp

        <div
            class="cc-summary-strip cc-analytics-summary cc-analytics-summary-three cc-analytics-summary-spacing"
        >
            @foreach ($tarjetas as $tarjeta)
                <div class="cc-summary-strip-item">
                    <span class="cc-summary-strip-label">
                        {{ $tarjeta['etiqueta'] }}
                    </span>

                    <span class="cc-summary-strip-value">
                        {{ $tarjeta['valor'] }}
                    </span>
                </div>
            @endforeach
        </div>

        <div class="cc-analytics-charts">
            @php
                $configuracionesGraficos = [
                    [
                        'id' => 'grafico-comparacion',
                        'titulo' => 'Rendimiento promedio por unidad',
                        'subtitulo' => 'Cada barra conserva la métrica de su modelo de operación.',
                        'badge' => 'Rendimiento',
                        'puntos' => $graficos['comparacion'],
                        'aria' => 'Rendimiento promedio por unidad',
                        'vacio' => 'No hay resultados suficientes para comparar unidades.',
                        'tipo' => 'comparacion',
                    ],
                    [
                        'id' => 'grafico-consumo',
                        'titulo' => 'Galones consumidos por unidad',
                        'subtitulo' => 'Unidades con mayor consumo dentro de los filtros seleccionados.',
                        'badge' => 'Consumo',
                        'puntos' => $graficos['consumo'],
                        'aria' => 'Galones consumidos por unidad',
                        'vacio' => 'No hay consumo suficiente para construir el gráfico.',
                        'tipo' => 'consumo',
                    ],
                ];
            @endphp

            @foreach ($configuracionesGraficos as $grafico)
                <section
                    class="cc-analytics-chart-card cc-analytics-chart-interactive"
                    data-cc-chart-card
                    data-chart-type="{{ $grafico['tipo'] }}"
                >
                    <div class="cc-analytics-chart-header">
                        <div>
                            <h4 class="cc-analytics-chart-title">
                                {{ $grafico['titulo'] }}
                            </h4>

                            <div class="cc-analytics-chart-subtitle">
                                {{ $grafico['subtitulo'] }}
                            </div>
                        </div>

                        <span class="cc-analytics-chart-badge">
                            {{ $grafico['badge'] }}
                        </span>
                    </div>

                    @if ($grafico['puntos'] !== [])
                        <div class="cc-analytics-chart-toolbar">
                            <button
                                type="button"
                                class="cc-analytics-chart-button"
                                data-chart-action="previous"
                            >
                                ← Anterior
                            </button>

                            <button
                                type="button"
                                class="cc-analytics-chart-button"
                                data-chart-action="next"
                            >
                                Siguiente →
                            </button>

                            <button
                                type="button"
                                class="cc-analytics-chart-button"
                                data-chart-action="zoom-in"
                            >
                                Acercar
                            </button>

                            <button
                                type="button"
                                class="cc-analytics-chart-button"
                                data-chart-action="zoom-out"
                            >
                                Alejar
                            </button>

                            <button
                                type="button"
                                class="cc-analytics-chart-button"
                                data-chart-action="reset"
                            >
                                Restablecer
                            </button>

                            <button
                                type="button"
                                class="cc-analytics-chart-button"
                                data-chart-action="data"
                            >
                                Ver datos
                            </button>

                            <span
                                class="cc-analytics-chart-status"
                                data-chart-status
                            ></span>
                        </div>

                        <div class="cc-analytics-chart-area">
                            <canvas
                                class="cc-analytics-chart-canvas"
                                data-cc-bar-chart
                                data-chart-source="{{ $grafico['id'] }}"
                                aria-label="{{ $grafico['aria'] }}"
                            ></canvas>
                        </div>

                        <div
                            class="cc-analytics-chart-detail"
                            data-chart-detail
                        >
                            Mueva el cursor sobre una barra para ver el detalle.
                        </div>

                        <div
                            class="cc-analytics-chart-legend"
                            data-chart-legend
                        ></div>

                        <div class="cc-analytics-chart-navigator">
                            <span>Inicio</span>

                            <input
                                type="range"
                                min="0"
                                value="0"
                                data-chart-navigator
                                aria-label="Desplazar rango visible"
                            >

                            <span>Final</span>
                        </div>

                        <div
                            class="cc-analytics-chart-data"
                            data-chart-data
                        ></div>

                        <script
                            type="application/json"
                            id="{{ $grafico['id'] }}"
                        >@json($grafico['puntos'])</script>
                    @else
                        <div class="cc-analytics-chart-empty">
                            {{ $grafico['vacio'] }}
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
        </div>
    @endif

    @if (! $hayFiltros)
        <section class="cc-empty-panel cc-empty-panel-compact">
            <h5>Análisis pendiente</h5>

            <p>
                Seleccione los criterios necesarios para consultar
                el consumo consolidado por unidad.
            </p>
        </section>
    @elseif ($unidadesAnalizadas->isEmpty())
        <section class="cc-empty-panel cc-empty-panel-compact">
            <h5>Sin resultados</h5>

            <p>
                No existen unidades con ciclos completados que coincidan
                con los filtros seleccionados.
            </p>
        </section>
    @else
        <div
            class="cc-result-count"
        >
            Mostrando

            <span class="cc-result-count-value">
                {{ $unidadesAnalizadas->firstItem() }}
            </span>

            -

            <span class="cc-result-count-value">
                {{ $unidadesAnalizadas->lastItem() }}
            </span>

            de

            <span class="cc-result-count-value">
                {{ $unidadesAnalizadas->total() }}
            </span>
        </div>

        <div class="cc-table-adaptive-wrapper">
            <table
                class="cc-table-adaptive cc-analytics-table-consumption"
            >
                <thead>
                    <tr>
                        @foreach ([
                            'empresa' => 'Empresa',
                            'unidad' => 'Unidad',
                            'modelo' => 'Modelo',
                            'ciclos' => 'Ciclos',
                            'galones' => 'Galones consumidos',
                            'actividad' => 'Actividad acumulada',
                            'rendimiento' => 'Rendimiento promedio',
                        ] as $campo => $etiqueta)
                            <th>
                                <a
                                    href="{{ $urlOrden($campo) }}"
                                    class="cc-analytics-sort-link"
                                >
                                    {{ $etiqueta }}

                                    <span>
                                        {{ $indicadorOrden($campo) }}
                                    </span>
                                </a>
                            </th>
                        @endforeach

                        <th>Mejor resultado</th>
                        <th>Peor resultado</th>
                        <th>Último resultado</th>

                        <th>
                            <a
                                href="{{ $urlOrden('ultimo_ciclo') }}"
                                class="cc-analytics-sort-link"
                            >
                                Último ciclo

                                <span>
                                    {{ $indicadorOrden('ultimo_ciclo') }}
                                </span>
                            </a>
                        </th>

                        <th>Tendencia</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach (
                        $unidadesAnalizadas
                        as $fila
                    )
                        <tr>
                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{ $fila['empresa'] }}
                                </div>
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{ $fila['unidad'] }}
                                </div>
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{ $fila['modelo_etiqueta'] }}
                                </div>
                            </td>

                            <td class="cc-analytics-number">
                                {{ $fila['ciclos'] }}
                            </td>

                            <td class="cc-analytics-number">
                                {{
                                    $formatoNumero(
                                        $fila['galones_consumidos']
                                    )
                                }} gal
                            </td>

                            <td class="cc-analytics-number">
                                {{
                                    $formatoNumero(
                                        $fila['actividad_total']
                                    )
                                }}
                                {{ $fila['unidad_actividad'] }}
                            </td>

                            @foreach ([
                                'rendimiento_promedio',
                                'mejor_resultado',
                                'peor_resultado',
                                'ultimo_resultado',
                            ] as $campoResultado)
                                <td class="cc-analytics-number">
                                    @if (
                                        is_null(
                                            $fila[$campoResultado]
                                        )
                                    )
                                        —
                                    @else
                                        {{
                                            $formatoNumero(
                                                $fila[$campoResultado]
                                            )
                                        }}
                                        {{ $fila['unidad_resultado'] }}
                                    @endif
                                </td>
                            @endforeach

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        optional(
                                            $fila['fecha_ultimo_ciclo']
                                        )->format('d/m/Y')
                                        ?: '—'
                                    }}
                                </div>

                                <div class="cc-table-adaptive-muted">
                                    {{
                                        optional(
                                            $fila['fecha_ultimo_ciclo']
                                        )->format('H:i')
                                        ?: '—'
                                    }}
                                </div>
                            </td>

                            <td>
                                <span class="cc-analytics-trend">
                                    {{
                                        $etiquetaTendencia[
                                            $fila['tendencia']
                                        ] ?? 'Sin historial'
                                    }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{
                $unidadesAnalizadas
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
            const charts = Array.from(
                document.querySelectorAll(
                    '[data-cc-chart-card]'
                )
            );

            /*
             * Paleta sobria derivada de la identidad visual de CC-Flota:
             * azul petróleo digital (#063B4A) y naranja óxido (#D9822B).
             * Los tonos secundarios mantienen contraste sin competir
             * visualmente con la información.
             */
            const palette = {
                kilometros: '#063B4A',
                horas: '#486B75',
                viajes: '#A86A2A',
                consumo: '#35545E',
            };

            function colorPunto(point, chartType) {
                if (chartType === 'consumo') {
                    return palette.consumo;
                }

                const modelo = String(
                    point.modelo || ''
                ).toLowerCase();

                if (modelo.includes('hora')) {
                    return palette.horas;
                }

                if (modelo.includes('viaje')) {
                    return palette.viajes;
                }

                return palette.kilometros;
            }

            function crearTablaDatos(card, puntos) {
                const container = card.querySelector(
                    '[data-chart-data]'
                );

                if (! container) {
                    return;
                }

                const rows = puntos.map(
                    function (point) {
                        const modelo = point.modelo
                            ? '<td>' + point.modelo + '</td>'
                            : '';

                        return '<tr>'
                            + '<td>' + point.etiqueta + '</td>'
                            + modelo
                            + '<td>'
                            + Number(point.valor).toFixed(2)
                            + ' '
                            + (point.unidad || '')
                            + '</td>'
                            + '</tr>';
                    }
                ).join('');

                const tieneModelo = puntos.some(
                    point => point.modelo
                );

                container.innerHTML = '<table>'
                    + '<thead><tr>'
                    + '<th>Unidad</th>'
                    + (tieneModelo ? '<th>Modelo</th>' : '')
                    + '<th>Valor</th>'
                    + '</tr></thead>'
                    + '<tbody>' + rows + '</tbody>'
                    + '</table>';
            }

            function crearLeyenda(card, puntos, chartType) {
                const legend = card.querySelector(
                    '[data-chart-legend]'
                );

                if (! legend) {
                    return;
                }

                if (chartType === 'consumo') {
                    legend.innerHTML = '<span class="cc-analytics-chart-legend-item">'
                        + '<span class="cc-analytics-chart-legend-swatch" '
                        + 'style="background:' + palette.consumo + '"></span>'
                        + 'Galones consumidos'
                        + '</span>';
                    return;
                }

                const modelos = [
                    ['Kilómetros por galón', palette.kilometros],
                    ['Galones por hora', palette.horas],
                    ['Galones por viaje', palette.viajes],
                ];

                const presentes = modelos.filter(
                    function ([label]) {
                        return puntos.some(
                            point => String(point.modelo || '') === label
                        );
                    }
                );

                legend.innerHTML = presentes.map(
                    function ([label, color]) {
                        return '<span class="cc-analytics-chart-legend-item">'
                            + '<span class="cc-analytics-chart-legend-swatch" '
                            + 'style="background:' + color + '"></span>'
                            + label
                            + '</span>';
                    }
                ).join('');
            }

            function inicializarGrafico(card) {
                const canvas = card.querySelector(
                    '[data-cc-bar-chart]'
                );

                if (! canvas) {
                    return;
                }

                const source = document.getElementById(
                    canvas.dataset.chartSource
                );

                if (! source) {
                    return;
                }

                let allPoints = [];

                try {
                    allPoints = JSON.parse(
                        source.textContent || '[]'
                    );
                } catch (error) {
                    return;
                }

                if (! Array.isArray(allPoints) || allPoints.length === 0) {
                    return;
                }

                const chartType = card.dataset.chartType;
                const navigator = card.querySelector(
                    '[data-chart-navigator]'
                );
                const status = card.querySelector(
                    '[data-chart-status]'
                );
                const detail = card.querySelector(
                    '[data-chart-detail]'
                );

                const state = {
                    allPoints,
                    start: 0,
                    visibleCount: Math.min(8, allPoints.length),
                    minVisible: Math.min(3, allPoints.length),
                };

                function visiblePoints() {
                    return state.allPoints.slice(
                        state.start,
                        state.start + state.visibleCount
                    );
                }

                function limitarInicio() {
                    const maxStart = Math.max(
                        0,
                        state.allPoints.length
                            - state.visibleCount
                    );

                    state.start = Math.min(
                        Math.max(0, state.start),
                        maxStart
                    );

                    if (navigator) {
                        navigator.max = String(maxStart);
                        navigator.value = String(state.start);
                        navigator.disabled = maxStart === 0;
                    }
                }

                function dibujar() {
                    limitarInicio();

                    const puntos = visiblePoints();
                    const context = canvas.getContext('2d');
                    const rect = canvas.getBoundingClientRect();
                    const ratio = window.devicePixelRatio || 1;
                    const width = Math.max(rect.width, 320);
                    const height = Math.max(rect.height, 304);

                    canvas.width = Math.floor(width * ratio);
                    canvas.height = Math.floor(height * ratio);
                    context.setTransform(ratio, 0, 0, ratio, 0, 0);

                    const css = getComputedStyle(
                        document.documentElement
                    );
                    const textColor = css
                        .getPropertyValue('--cc-text-muted')
                        .trim() || '#64748b';
                    const borderColor = css
                        .getPropertyValue('--cc-border')
                        .trim() || '#dbe3ea';

                    const padding = {
                        top: 24,
                        right: 16,
                        bottom: 76,
                        left: 58,
                    };

                    const chartWidth = width
                        - padding.left
                        - padding.right;
                    const chartHeight = height
                        - padding.top
                        - padding.bottom;
                    const maxValue = Math.max(
                        ...puntos.map(
                            point => Number(point.valor)
                        ),
                        1
                    );
                    const slotWidth = chartWidth / puntos.length;
                    const barWidth = Math.min(48, slotWidth * .58);

                    context.clearRect(0, 0, width, height);
                    context.font = '12px sans-serif';

                    for (let index = 0; index <= 4; index++) {
                        const y = padding.top
                            + (index / 4) * chartHeight;
                        const value = maxValue
                            - (index / 4) * maxValue;

                        context.beginPath();
                        context.strokeStyle = borderColor;
                        context.lineWidth = 1;
                        context.moveTo(padding.left, y);
                        context.lineTo(width - padding.right, y);
                        context.stroke();

                        context.fillStyle = textColor;
                        context.textAlign = 'right';
                        context.textBaseline = 'middle';
                        context.fillText(
                            value.toFixed(2),
                            padding.left - 8,
                            y
                        );
                    }

                    const hitAreas = [];

                    puntos.forEach(
                        function (point, index) {
                            const value = Number(point.valor);
                            const x = padding.left
                                + index * slotWidth
                                + (slotWidth - barWidth) / 2;
                            const barHeight = (value / maxValue)
                                * chartHeight;
                            const y = padding.top
                                + chartHeight
                                - barHeight;

                            context.fillStyle = colorPunto(
                                point,
                                chartType
                            );
                            context.fillRect(
                                x,
                                y,
                                barWidth,
                                barHeight
                            );

                            context.fillStyle = textColor;
                            context.textAlign = 'right';
                            context.textBaseline = 'middle';
                            context.save();
                            context.translate(
                                x + barWidth / 2,
                                height - padding.bottom + 14
                            );
                            context.rotate(-Math.PI / 4);
                            context.fillText(
                                String(point.etiqueta || ''),
                                0,
                                0
                            );
                            context.restore();

                            hitAreas.push({
                                x,
                                y,
                                width: barWidth,
                                height: barHeight,
                                point,
                            });
                        }
                    );

                    canvas._ccHitAreas = hitAreas;

                    if (status) {
                        const from = state.start + 1;
                        const to = Math.min(
                            state.start + state.visibleCount,
                            state.allPoints.length
                        );
                        status.textContent = from
                            + '–' + to
                            + ' de '
                            + state.allPoints.length;
                    }

                    card.querySelector(
                        '[data-chart-action="previous"]'
                    )?.toggleAttribute(
                        'disabled',
                        state.start === 0
                    );

                    card.querySelector(
                        '[data-chart-action="next"]'
                    )?.toggleAttribute(
                        'disabled',
                        state.start + state.visibleCount
                            >= state.allPoints.length
                    );
                }

                card.querySelectorAll(
                    '[data-chart-action]'
                ).forEach(
                    function (button) {
                        button.addEventListener(
                            'click',
                            function () {
                                const action = button.dataset.chartAction;

                                if (action === 'previous') {
                                    state.start -= 1;
                                } else if (action === 'next') {
                                    state.start += 1;
                                } else if (action === 'zoom-in') {
                                    state.visibleCount = Math.max(
                                        state.minVisible,
                                        state.visibleCount - 1
                                    );
                                } else if (action === 'zoom-out') {
                                    state.visibleCount = Math.min(
                                        state.allPoints.length,
                                        state.visibleCount + 1
                                    );
                                } else if (action === 'reset') {
                                    state.start = 0;
                                    state.visibleCount = Math.min(
                                        8,
                                        state.allPoints.length
                                    );
                                } else if (action === 'data') {
                                    const dataPanel = card.querySelector(
                                        '[data-chart-data]'
                                    );
                                    dataPanel?.classList.toggle('is-visible');
                                    button.textContent = dataPanel?.classList
                                        .contains('is-visible')
                                        ? 'Ocultar datos'
                                        : 'Ver datos';
                                }

                                dibujar();
                            }
                        );
                    }
                );

                navigator?.addEventListener(
                    'input',
                    function () {
                        state.start = Number(navigator.value);
                        dibujar();
                    }
                );

                canvas.addEventListener(
                    'mousemove',
                    function (event) {
                        const rect = canvas.getBoundingClientRect();
                        const x = event.clientX - rect.left;
                        const y = event.clientY - rect.top;
                        const hit = (canvas._ccHitAreas || []).find(
                            area => x >= area.x
                                && x <= area.x + area.width
                                && y >= area.y
                                && y <= area.y + area.height
                        );

                        if (hit && detail) {
                            const point = hit.point;
                            detail.textContent = point.etiqueta
                                + ': '
                                + Number(point.valor).toFixed(2)
                                + ' '
                                + (point.unidad || '')
                                + (point.modelo
                                    ? ' · ' + point.modelo
                                    : '');
                        }
                    }
                );

                canvas.addEventListener(
                    'mouseleave',
                    function () {
                        if (detail) {
                            detail.textContent = 'Mueva el cursor sobre una barra para ver el detalle.';
                        }
                    }
                );

                crearTablaDatos(card, allPoints);
                crearLeyenda(card, allPoints, chartType);
                card._ccRedraw = dibujar;
                dibujar();
            }

            charts.forEach(inicializarGrafico);

            let resizeTimer = null;

            window.addEventListener(
                'resize',
                function () {
                    window.clearTimeout(resizeTimer);
                    resizeTimer = window.setTimeout(
                        function () {
                            charts.forEach(
                                card => card._ccRedraw?.()
                            );
                        },
                        120
                    );
                }
            );

            const multiselects = Array.from(
                document.querySelectorAll(
                    '[data-cc-filter-multiselect]'
                )
            );

            function cerrarTodos(excepto = null) {
                multiselects.forEach(
                    function (multiselect) {
                        if (
                            excepto
                            && multiselect === excepto
                        ) {
                            return;
                        }

                        multiselect.classList.remove(
                            'is-open'
                        );

                        multiselect
                            .querySelector(
                                '[data-cc-filter-toggle]'
                            )
                            ?.classList.remove('is-open');

                        multiselect
                            .querySelector(
                                '[data-cc-filter-menu]'
                            )
                            ?.classList.remove('is-open');
                    }
                );
            }

            multiselects.forEach(
                function (multiselect) {
                    const toggle =
                        multiselect.querySelector(
                            '[data-cc-filter-toggle]'
                        );

                    const menu =
                        multiselect.querySelector(
                            '[data-cc-filter-menu]'
                        );

                    const label =
                        multiselect.querySelector(
                            '[data-cc-filter-label]'
                        );

                    const master =
                        multiselect.querySelector(
                            '[data-cc-filter-master]'
                        );

                    const checkboxes = Array.from(
                        multiselect.querySelectorAll(
                            '[data-cc-filter-checkbox]'
                        )
                    );

                    function visibles() {
                        return checkboxes.filter(
                            function (checkbox) {
                                return ! checkbox
                                    .closest(
                                        '[data-cc-filter-option]'
                                    )
                                    ?.hidden;
                            }
                        );
                    }

                    function actualizarEtiqueta() {
                        const disponibles = visibles();

                        const seleccionados =
                            disponibles.filter(
                                checkbox =>
                                    checkbox.checked
                            );

                        if (label) {
                            if (
                                seleccionados.length === 0
                            ) {
                                label.textContent =
                                    label.dataset
                                        .defaultLabel
                                    || 'Todos';
                            } else if (
                                seleccionados.length === 1
                            ) {
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
                                    + ' seleccionados';
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

                    toggle?.addEventListener(
                        'click',
                        function () {
                            cerrarTodos(multiselect);

                            const abrira =
                                ! multiselect.classList
                                    .contains('is-open');

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
                        }
                    );

                    master?.addEventListener(
                        'change',
                        function () {
                            visibles().forEach(
                                function (checkbox) {
                                    checkbox.checked =
                                        master.checked;
                                }
                            );

                            actualizarEtiqueta();
                        }
                    );

                    checkboxes.forEach(
                        function (checkbox) {
                            checkbox.addEventListener(
                                'change',
                                actualizarEtiqueta
                            );
                        }
                    );

                    multiselect.actualizarEtiqueta =
                        actualizarEtiqueta;

                    actualizarEtiqueta();
                }
            );

            function empresasSeleccionadas() {
                return Array.from(
                    document.querySelectorAll(
                        '[data-empresa-checkbox]:checked'
                    )
                ).map(
                    checkbox => checkbox.value
                );
            }

            function filtrarUnidades() {
                const empresas =
                    empresasSeleccionadas();

                document
                    .querySelectorAll(
                        '[data-unidad-option]'
                    )
                    .forEach(
                        function (opcion) {
                            const visible =
                                empresas.length === 0
                                || empresas.includes(
                                    opcion.dataset
                                        .empresaId
                                );

                            opcion.hidden = ! visible;

                            if (! visible) {
                                const checkbox =
                                    opcion.querySelector(
                                        '[data-unidad-checkbox]'
                                    );

                                if (checkbox) {
                                    checkbox.checked =
                                        false;
                                }
                            }
                        }
                    );

                multiselects.forEach(
                    multiselect =>
                        multiselect
                            .actualizarEtiqueta
                            ?.()
                );
            }

            document
                .querySelectorAll(
                    '[data-empresa-checkbox]'
                )
                .forEach(
                    function (checkbox) {
                        checkbox.addEventListener(
                            'change',
                            filtrarUnidades
                        );
                    }
                );

            document
                .querySelector(
                    '[data-filter-type="empresa"] '
                    + '[data-cc-filter-master]'
                )
                ?.addEventListener(
                    'change',
                    function () {
                        window.setTimeout(
                            filtrarUnidades,
                            0
                        );
                    }
                );

            filtrarUnidades();

            document.addEventListener(
                'click',
                function (event) {
                    if (
                        event.target.closest(
                            '[data-cc-filter-multiselect]'
                        )
                    ) {
                        return;
                    }

                    cerrarTodos();
                }
            );

            document.addEventListener(
                'keydown',
                function (event) {
                    if (event.key === 'Escape') {
                        cerrarTodos();
                    }
                }
            );
        }
    );
</script>
