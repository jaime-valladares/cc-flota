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

<style>
    .cc-consumo-header {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        align-items: start !important;
        gap: 1rem !important;
    }

    .cc-consumo-summary {
        display: grid !important;
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: .85rem !important;
        width: 100%;
        padding: 0 !important;
        border: 0 !important;
        background: transparent !important;
    }

    .cc-consumo-summary .cc-summary-strip-item {
        display: flex !important;
        min-width: 0;
        min-height: 6.25rem;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: .45rem !important;
        padding: 1rem .85rem !important;
        border: 1px solid var(--cc-border) !important;
        border-radius: 1rem !important;
        background: var(--cc-bg-card) !important;
        text-align: center !important;
        box-shadow: 0 .35rem 1rem rgba(15, 23, 42, .035);
    }

    .cc-consumo-summary .cc-summary-strip-label,
    .cc-consumo-summary .cc-summary-strip-value {
        display: block !important;
        width: 100%;
        margin: 0 !important;
        text-align: center !important;
        white-space: normal !important;
    }

    .cc-consumo-summary .cc-summary-strip-label {
        color: var(--cc-text-muted);
        font-size: .7rem;
        font-weight: 800;
        line-height: 1.3;
        letter-spacing: .08em;
        text-transform: uppercase;
    }

    .cc-consumo-summary .cc-summary-strip-value {
        color: var(--cc-text-main);
        font-size: 1.25rem;
        font-weight: 800;
        line-height: 1.15;
        overflow-wrap: anywhere;
    }

    .cc-consumo-charts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .cc-consumo-chart-card {
        min-width: 0;
        padding: 1rem;
        border: 1px solid var(--cc-border);
        border-radius: 1rem;
        background: var(--cc-bg-card);
        box-shadow: 0 .35rem 1rem rgba(15, 23, 42, .035);
    }

    .cc-consumo-chart-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: .8rem;
    }

    .cc-consumo-chart-title {
        margin: 0 0 .2rem;
        color: var(--cc-text-main);
        font-size: .95rem;
        font-weight: 800;
    }

    .cc-consumo-chart-subtitle {
        color: var(--cc-text-muted);
        font-size: .78rem;
        line-height: 1.4;
    }

    .cc-consumo-chart-badge {
        flex: 0 0 auto;
        padding: .3rem .6rem;
        border: 1px solid var(--cc-border);
        border-radius: 999px;
        color: var(--cc-text-muted);
        font-size: .7rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .cc-consumo-chart-toolbar {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: .45rem;
        margin-bottom: .75rem;
        padding: .65rem;
        border: 1px solid var(--cc-border);
        border-radius: .8rem;
        background: var(--cc-bg-soft);
    }

    .cc-consumo-chart-button {
        display: inline-flex;
        min-height: 2rem;
        align-items: center;
        justify-content: center;
        padding: .35rem .6rem;
        border: 1px solid var(--cc-border);
        border-radius: .55rem;
        background: var(--cc-bg-card);
        color: var(--cc-text-main);
        font-size: .7rem;
        font-weight: 800;
        cursor: pointer;
    }

    .cc-consumo-chart-button:hover {
        border-color: var(--cc-primary);
    }

    .cc-consumo-chart-button:disabled {
        cursor: not-allowed;
        opacity: .45;
    }

    .cc-consumo-chart-status {
        margin-left: auto;
        color: var(--cc-text-muted);
        font-size: .7rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .cc-consumo-chart-stage {
        position: relative;
        min-height: 19rem;
        overflow: hidden;
        border: 1px solid var(--cc-border);
        border-radius: .8rem;
        background: var(--cc-bg-card);
    }

    .cc-consumo-chart-canvas {
        display: block;
        width: 100%;
        height: 19rem;
    }

    .cc-consumo-chart-detail {
        min-height: 1.4rem;
        margin-top: .55rem;
        color: var(--cc-text-muted);
        font-size: .74rem;
        text-align: center;
    }

    .cc-consumo-chart-legend {
        display: flex;
        flex-wrap: wrap;
        gap: .7rem;
        margin-top: .65rem;
        color: var(--cc-text-muted);
        font-size: .7rem;
    }

    .cc-consumo-chart-legend-item {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }

    .cc-consumo-chart-legend-swatch {
        width: .75rem;
        height: .75rem;
        border-radius: .2rem;
    }

    .cc-consumo-chart-navigator {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: center;
        gap: .65rem;
        margin-top: .7rem;
        color: var(--cc-text-muted);
        font-size: .7rem;
    }

    .cc-consumo-chart-navigator input[type="range"] {
        width: 100%;
    }

    .cc-consumo-chart-data {
        display: none;
        margin-top: .75rem;
        overflow-x: auto;
    }

    .cc-consumo-chart-data.is-visible {
        display: block;
    }

    .cc-consumo-chart-data table {
        width: 100%;
        min-width: 30rem;
        border-collapse: collapse;
    }

    .cc-consumo-chart-data th,
    .cc-consumo-chart-data td {
        padding: .55rem .65rem;
        border: 1px solid var(--cc-border);
        font-size: .72rem;
        text-align: center;
    }

    .cc-consumo-chart-data th {
        background: var(--cc-bg-soft);
        font-weight: 800;
        text-transform: uppercase;
    }

    .cc-consumo-chart-empty {
        display: flex;
        min-height: 19rem;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        border: 1px dashed var(--cc-border);
        border-radius: .8rem;
        color: var(--cc-text-muted);
        text-align: center;
    }

    .cc-consumo-sort-link {
        display: inline-flex;
        width: 100%;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        color: inherit;
        text-decoration: none;
        white-space: nowrap;
    }

    .cc-consumo-trend {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 6.5rem;
        padding: .35rem .65rem;
        border: 1px solid var(--cc-border);
        border-radius: 999px;
        background: var(--cc-bg-soft);
        font-size: .75rem;
        font-weight: 800;
        white-space: nowrap;
    }

    .cc-consumo-number {
        white-space: nowrap;
        text-align: right;
    }

    .cc-card .cc-filter-panel,
    .cc-card .cc-filter-panel-inline {
        overflow: visible;
    }

    .cc-card .cc-filter-multiselect {
        position: relative;
        z-index: 1;
    }

    .cc-card .cc-filter-multiselect.is-open {
        z-index: 1000;
    }

    .cc-card .cc-filter-multiselect-menu {
        z-index: 1001;
    }

    @media (max-width: 1100px) {
        .cc-consumo-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 900px) {
        .cc-consumo-charts {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    @media (max-width: 760px) {
        .cc-consumo-header {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .cc-consumo-header > div:last-child,
        .cc-consumo-header > div:last-child > a {
            width: 100%;
        }

        .cc-consumo-chart-toolbar {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .cc-consumo-chart-button {
            width: 100%;
        }

        .cc-consumo-chart-status {
            grid-column: 1 / -1;
            margin-left: 0;
            text-align: center;
        }

        .cc-consumo-chart-navigator {
            grid-template-columns: minmax(0, 1fr);
        }
    }

    @media (max-width: 520px) {
        .cc-consumo-summary {
            grid-template-columns: minmax(0, 1fr) !important;
        }
    }
</style>

<div class="cc-card">
    <div class="cc-card-header cc-card-header-compact cc-consumo-header">
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
            <div class="font-bold">
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
                class="cc-form-section
                       cc-form-section-compact"
                style="margin-top: 0;"
            >
                <div class="cc-form-section-title">
                    Filtros de análisis
                </div>
            </div>

            <div
                style="
                    display: grid;
                    grid-template-columns:
                        repeat(auto-fit, minmax(14rem, 1fr));
                    gap: 1rem;
                    align-items: end;
                "
            >
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
                    class="cc-standard-filter-actions"
                    style="
                        display: flex;
                        flex-wrap: nowrap;
                        gap: .75rem;
                        align-items: center;
                        justify-content: flex-end;
                        grid-column: 1 / -1;
                        width: 100%;
                    "
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
            class="cc-summary-strip cc-consumo-summary"
            style="margin-bottom: 1.25rem;"
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

        <div class="cc-consumo-charts">
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
                    class="cc-consumo-chart-card"
                    data-cc-chart-card
                    data-chart-type="{{ $grafico['tipo'] }}"
                >
                    <div class="cc-consumo-chart-header">
                        <div>
                            <h4 class="cc-consumo-chart-title">
                                {{ $grafico['titulo'] }}
                            </h4>

                            <div class="cc-consumo-chart-subtitle">
                                {{ $grafico['subtitulo'] }}
                            </div>
                        </div>

                        <span class="cc-consumo-chart-badge">
                            {{ $grafico['badge'] }}
                        </span>
                    </div>

                    @if ($grafico['puntos'] !== [])
                        <div class="cc-consumo-chart-toolbar">
                            <button
                                type="button"
                                class="cc-consumo-chart-button"
                                data-chart-action="previous"
                            >
                                ← Anterior
                            </button>

                            <button
                                type="button"
                                class="cc-consumo-chart-button"
                                data-chart-action="next"
                            >
                                Siguiente →
                            </button>

                            <button
                                type="button"
                                class="cc-consumo-chart-button"
                                data-chart-action="zoom-in"
                            >
                                Acercar
                            </button>

                            <button
                                type="button"
                                class="cc-consumo-chart-button"
                                data-chart-action="zoom-out"
                            >
                                Alejar
                            </button>

                            <button
                                type="button"
                                class="cc-consumo-chart-button"
                                data-chart-action="reset"
                            >
                                Restablecer
                            </button>

                            <button
                                type="button"
                                class="cc-consumo-chart-button"
                                data-chart-action="data"
                            >
                                Ver datos
                            </button>

                            <span
                                class="cc-consumo-chart-status"
                                data-chart-status
                            ></span>
                        </div>

                        <div class="cc-consumo-chart-stage">
                            <canvas
                                class="cc-consumo-chart-canvas"
                                data-cc-bar-chart
                                data-chart-source="{{ $grafico['id'] }}"
                                aria-label="{{ $grafico['aria'] }}"
                            ></canvas>
                        </div>

                        <div
                            class="cc-consumo-chart-detail"
                            data-chart-detail
                        >
                            Mueva el cursor sobre una barra para ver el detalle.
                        </div>

                        <div
                            class="cc-consumo-chart-legend"
                            data-chart-legend
                        ></div>

                        <div class="cc-consumo-chart-navigator">
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
                            class="cc-consumo-chart-data"
                            data-chart-data
                        ></div>

                        <script
                            type="application/json"
                            id="{{ $grafico['id'] }}"
                        >@json($grafico['puntos'])</script>
                    @else
                        <div class="cc-consumo-chart-empty">
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
            class="mb-4 flex justify-end
                   text-sm text-[var(--cc-text-muted)]"
        >
            Mostrando

            <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                {{ $unidadesAnalizadas->firstItem() }}
            </span>

            -

            <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                {{ $unidadesAnalizadas->lastItem() }}
            </span>

            de

            <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                {{ $unidadesAnalizadas->total() }}
            </span>
        </div>

        <div class="cc-table-adaptive-wrapper">
            <table
                class="cc-table-adaptive"
                style="min-width: 108rem;"
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
                                    class="cc-consumo-sort-link"
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
                                class="cc-consumo-sort-link"
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

                            <td class="cc-consumo-number">
                                {{ $fila['ciclos'] }}
                            </td>

                            <td class="cc-consumo-number">
                                {{
                                    $formatoNumero(
                                        $fila['galones_consumidos']
                                    )
                                }} gal
                            </td>

                            <td class="cc-consumo-number">
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
                                <td class="cc-consumo-number">
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
                                <span class="cc-consumo-trend">
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
                    legend.innerHTML = '<span class="cc-consumo-chart-legend-item">'
                        + '<span class="cc-consumo-chart-legend-swatch" '
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
                        return '<span class="cc-consumo-chart-legend-item">'
                            + '<span class="cc-consumo-chart-legend-swatch" '
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