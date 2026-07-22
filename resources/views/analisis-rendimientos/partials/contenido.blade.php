@php
    $esVentana = $esVentana ?? false;

    $rutaConsulta = $esVentana
        ? route('analisis.rendimientos.index.ventana')
        : route('analisis.rendimientos.index');

    $rutaAlterna = $esVentana
        ? route('analisis.rendimientos.index', request()->query())
        : route('analisis.rendimientos.index.ventana', request()->query());

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

    $sortActual = $sort ?? 'fecha_ciclo';
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
@endphp


<style>
    .cc-analytics-header {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        align-items: start !important;
        gap: 1rem !important;
    }

    .cc-analytics-header > div:last-child {
        display: flex;
        justify-content: flex-end;
        min-width: 0;
    }

    .cc-analytics-summary {
        display: grid !important;
        grid-template-columns:
            repeat(6, minmax(0, 1fr)) !important;
        width: 100%;
    }

    .cc-analytics-summary .cc-summary-strip-item {
        display: flex !important;
        min-width: 0;
        min-height: 5.6rem;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: .45rem !important;
        padding: 1rem .8rem !important;
        text-align: center !important;
    }

    .cc-analytics-summary .cc-summary-strip-label,
    .cc-analytics-summary .cc-summary-strip-value {
        display: block !important;
        width: 100%;
        margin: 0 !important;
        text-align: center !important;
    }

    .cc-analytics-summary .cc-summary-strip-label {
        line-height: 1.25 !important;
        white-space: normal !important;
    }

    .cc-analytics-summary .cc-summary-strip-value {
        line-height: 1.15 !important;
        overflow-wrap: anywhere;
        white-space: normal !important;
    }

    .cc-analytics-actions {
        justify-content: flex-end !important;
    }

    @media (max-width: 1100px) {
        .cc-analytics-summary {
            grid-template-columns:
                repeat(3, minmax(0, 1fr)) !important;
        }
    }

    @media (max-width: 760px) {
        .cc-analytics-header {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .cc-analytics-header > div:last-child {
            width: 100%;
            justify-content: stretch;
        }

        .cc-analytics-header > div:last-child > a {
            width: 100%;
        }

        .cc-analytics-summary {
            grid-template-columns:
                repeat(2, minmax(0, 1fr)) !important;
        }

        .cc-analytics-actions {
            display: grid !important;
            grid-template-columns:
                repeat(2, minmax(0, 1fr)) !important;
            gap: .75rem !important;
        }

        .cc-analytics-actions > * {
            width: 100% !important;
            min-width: 0 !important;
        }
    }

    @media (max-width: 430px) {
        .cc-analytics-summary {
            grid-template-columns:
                minmax(0, 1fr) !important;
        }

        .cc-analytics-actions {
            grid-template-columns:
                minmax(0, 1fr) !important;
        }
    }

    /*
     * Mantiene el multiselect abierto por encima de los demás
     * controles del grid. Sin este nivel en el contenedor padre,
     * los campos de la siguiente fila pueden dibujarse sobre el menú.
     */
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

    .cc-analytics-sort-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        width: 100%;
        color: inherit;
        text-decoration: none;
        white-space: nowrap;
    }

    .cc-analytics-sort-icon {
        font-size: .78rem;
        opacity: .72;
    }

    .cc-cycle-number {
        display: inline-flex;
        min-width: 3rem;
        align-items: center;
        justify-content: center;
        padding: .35rem .65rem;
        border: 1px solid var(--cc-border);
        border-radius: .55rem;
        font-weight: 700;
        background: var(--cc-bg-soft);
    }

    .cc-kilometraje-flow {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        white-space: nowrap;
        font-weight: 700;
    }

    .cc-kilometraje-arrow {
        color: var(--cc-text-muted);
    }

    .cc-motorista-nowrap {
        white-space: nowrap;
    }

    .cc-analytics-number {
        white-space: nowrap;
        text-align: right;
    }

    .cc-analytics-charts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.25rem;
    }

    .cc-analytics-chart-card {
        min-width: 0;
        padding: 1rem 1rem .85rem;
        border: 1px solid var(--cc-border);
        border-radius: 1rem;
        background: var(--cc-bg-card);
    }

    .cc-analytics-chart-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: .8rem;
    }

    .cc-analytics-chart-title {
        margin: 0;
        color: var(--cc-text-main);
        font-size: .95rem;
        font-weight: 800;
    }

    .cc-analytics-chart-subtitle {
        margin-top: .2rem;
        color: var(--cc-text-muted);
        font-size: .78rem;
        line-height: 1.35;
    }

    .cc-analytics-chart-badge {
        flex: 0 0 auto;
        padding: .3rem .55rem;
        border: 1px solid var(--cc-border);
        border-radius: 999px;
        color: var(--cc-text-muted);
        font-size: .72rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .cc-analytics-chart-area {
        position: relative;
        min-height: 18rem;
    }

    .cc-analytics-chart-canvas {
        display: block;
        width: 100%;
        height: 18rem;
    }

    .cc-analytics-chart-detail {
        min-height: 1.3rem;
        margin-top: .4rem;
        color: var(--cc-text-muted);
        font-size: .76rem;
        text-align: center;
    }

    .cc-analytics-chart-empty {
        display: flex;
        min-height: 18rem;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        border: 1px dashed var(--cc-border);
        border-radius: .8rem;
        color: var(--cc-text-muted);
        font-size: .85rem;
        line-height: 1.5;
        text-align: center;
    }

    @media (max-width: 900px) {
        .cc-analytics-charts {
            grid-template-columns: minmax(0, 1fr);
        }
    }
</style>

<div class="cc-card">
    <div class="cc-card-header cc-card-header-compact cc-analytics-header">
        <div>
            <h3 class="cc-title cc-title-compact">
                Kilometraje por Galón
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
                    <li>
                        {{ $error }}
                    </li>
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
                    <label>
                        Empresa
                    </label>

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
                                    data-singular-suffix="seleccionada"
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

                                        <span>
                                            Seleccionar todo
                                        </span>
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
                        <select
                            class="cc-input"
                            disabled
                        >
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

                <div class="cc-field">
                    <label>
                        Motorista
                    </label>

                    <div
                        class="cc-filter-multiselect"
                        data-cc-filter-multiselect
                        data-filter-type="motorista"
                    >
                        <button
                            type="button"
                            class="cc-filter-multiselect-toggle"
                            data-cc-filter-toggle
                        >
                            <span
                                data-cc-filter-label
                                data-default-label="Todos"
                                data-singular-suffix="seleccionado"
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

                                    <span>
                                        Seleccionar todo
                                    </span>
                                </label>

                                @foreach (
                                    $motoristasSelector
                                    as $motoristaOpcion
                                )
                                    <label
                                        class="cc-filter-multiselect-option"
                                        data-cc-filter-option
                                        data-motorista-option
                                        data-empresa-id="{{
                                            $motoristaOpcion->empresa_id
                                        }}"
                                    >
                                        <input
                                            type="checkbox"
                                            name="motorista_ids[]"
                                            value="{{ $motoristaOpcion->id }}"
                                            @checked(
                                                in_array(
                                                    (int) $motoristaOpcion->id,
                                                    $motoristaIds ?? [],
                                                    true
                                                )
                                            )
                                            data-cc-filter-checkbox
                                            data-motorista-checkbox
                                        >

                                        <span data-cc-filter-option-label>
                                            {{
                                                $motoristaOpcion
                                                    ->nombre_completo
                                            }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cc-field">
                    <label>
                        Modelo de operación
                    </label>

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
                                data-singular-suffix="seleccionado"
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

                                    <span>
                                        Seleccionar todo
                                    </span>
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
                    <label>
                        Unidad
                    </label>

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
                                data-singular-suffix="seleccionada"
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

                                    <span>
                                        Seleccionar todo
                                    </span>
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

                <div
                    class="cc-field"
                    style="grid-column: span 2;"
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
                        placeholder="Empresa, placa, motorista, origen, ruta o número de abastecimiento"
                    >
                </div>

                <div
                    class="cc-standard-filter-actions cc-analytics-actions"
                    style="
                        display: flex;
                        flex-wrap: nowrap;
                        gap: .75rem;
                        align-items: center;
                        justify-content: flex-end;
                        white-space: nowrap;
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
                    'etiqueta' => 'Abastecimientos',
                    'valor' => $resumen['abastecimientos'],
                ],
                [
                    'etiqueta' => 'Ciclos completados',
                    'valor' => $resumen['ciclos_completados'],
                ],
                [
                    'etiqueta' => 'Galones abastecidos',
                    'valor' => $formatoNumero(
                        $resumen['galones_abastecidos']
                    ) . ' gal',
                ],
                [
                    'etiqueta' => 'Kilómetros recorridos',
                    'valor' => $formatoNumero(
                        $resumen['kilometros_recorridos']
                    ) . ' km',
                ],
                [
                    'etiqueta' => 'Galones consumidos',
                    'valor' => $formatoNumero(
                        $resumen['galones_consumidos']
                    ) . ' gal',
                ],
                [
                    'etiqueta' => 'Kilómetros por galón',
                    'valor' => is_null(
                        $resumen['kilometros_por_galon']
                    )
                        ? '—'
                        : $formatoNumero(
                            $resumen['kilometros_por_galon']
                        ) . ' km/gal',
                ],
            ];
        @endphp

        <div
            class="cc-summary-strip cc-analytics-summary"
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
    @endif


    @if ($hayFiltros)
        <div class="cc-analytics-charts">
            <section class="cc-analytics-chart-card">
                <div class="cc-analytics-chart-header">
                    <div>
                        <h4 class="cc-analytics-chart-title">
                            Rendimiento a través del tiempo
                        </h4>

                        <div class="cc-analytics-chart-subtitle">
                            Kilómetros por galón consolidados según
                            la fecha de cierre del ciclo.
                        </div>
                    </div>

                    <span class="cc-analytics-chart-badge">
                        {{ $graficos['tiempo']['agrupacion'] }}
                    </span>
                </div>

                @if ($graficos['tiempo']['puntos'] !== [])
                    <div class="cc-analytics-chart-area">
                        <canvas
                            class="cc-analytics-chart-canvas"
                            data-cc-line-chart
                            data-chart-source="grafico-tiempo"
                            aria-label="Rendimiento a través del tiempo"
                        ></canvas>

                        <div
                            class="cc-analytics-chart-detail"
                            data-chart-detail="grafico-tiempo"
                        >
                            Mueva el cursor sobre la línea para ver el detalle.
                        </div>
                    </div>

                    <script
                        type="application/json"
                        id="grafico-tiempo"
                    >@json($graficos['tiempo']['puntos'])</script>
                @else
                    <div class="cc-analytics-chart-empty">
                        No hay ciclos con información suficiente
                        para construir la evolución por tiempo.
                    </div>
                @endif
            </section>

            <section class="cc-analytics-chart-card">
                <div class="cc-analytics-chart-header">
                    <div>
                        <h4 class="cc-analytics-chart-title">
                            Rendimiento por ciclo
                        </h4>

                        <div class="cc-analytics-chart-subtitle">
                            @if ($graficos['ciclos']['unidad'])
                                {{ $graficos['ciclos']['unidad'] }}
                            @else
                                Progreso histórico de una unidad.
                            @endif
                        </div>
                    </div>

                    <span class="cc-analytics-chart-badge">
                        Ciclos
                    </span>
                </div>

                @if (
                    $graficos['ciclos']['disponible']
                    && $graficos['ciclos']['puntos'] !== []
                )
                    <div class="cc-analytics-chart-area">
                        <canvas
                            class="cc-analytics-chart-canvas"
                            data-cc-line-chart
                            data-chart-source="grafico-ciclos"
                            aria-label="Rendimiento por ciclo"
                        ></canvas>

                        <div
                            class="cc-analytics-chart-detail"
                            data-chart-detail="grafico-ciclos"
                        >
                            Mueva el cursor sobre la línea para ver el detalle.
                        </div>
                    </div>

                    <script
                        type="application/json"
                        id="grafico-ciclos"
                    >@json($graficos['ciclos']['puntos'])</script>
                @else
                    <div class="cc-analytics-chart-empty">
                        {{
                            $graficos['ciclos']['mensaje']
                            ?: 'No hay datos disponibles.'
                        }}
                    </div>
                @endif
            </section>
        </div>
    @endif

    @if (
        $hayFiltros
        && $abastecimientos->total() > 0
    )
        <div
            class="mb-4 flex justify-end
                   text-sm text-[var(--cc-text-muted)]"
        >
            Mostrando

            <span
                class="mx-1 font-bold
                       text-[var(--cc-text-main)]"
            >
                {{ $abastecimientos->firstItem() }}
            </span>

            -

            <span
                class="mx-1 font-bold
                       text-[var(--cc-text-main)]"
            >
                {{ $abastecimientos->lastItem() }}
            </span>

            de

            <span
                class="ml-1 font-bold
                       text-[var(--cc-text-main)]"
            >
                {{ $abastecimientos->total() }}
            </span>
        </div>
    @endif

    @if (! $hayFiltros)
        <section
            class="cc-empty-panel
                   cc-empty-panel-compact"
        >
            <h5>
                Análisis pendiente
            </h5>

            <p>
                Seleccione los criterios necesarios para consultar
                los ciclos de rendimiento.
            </p>
        </section>
    @elseif ($abastecimientos->isEmpty())
        <section
            class="cc-empty-panel
                   cc-empty-panel-compact"
        >
            <h5>
                Sin resultados
            </h5>

            <p>
                No existen ciclos de rendimiento que coincidan
                con los filtros seleccionados.
            </p>
        </section>
    @else
        <div class="cc-table-adaptive-wrapper">
            <table
                class="cc-table-adaptive"
                style="min-width: 96rem;"
            >
                <thead>
                    <tr>
                        <th>
                            <a
                                href="{{ $urlOrden('numero_ciclo') }}"
                                class="cc-analytics-sort-link"
                            >
                                Número de ciclo
                                <span class="cc-analytics-sort-icon">
                                    {{ $indicadorOrden('numero_ciclo') }}
                                </span>
                            </a>
                        </th>

                        <th>
                            <a
                                href="{{ $urlOrden('fecha_ciclo') }}"
                                class="cc-analytics-sort-link"
                            >
                                Fecha ciclo completado
                                <span class="cc-analytics-sort-icon">
                                    {{ $indicadorOrden('fecha_ciclo') }}
                                </span>
                            </a>
                        </th>

                        <th>
                            <a
                                href="{{ $urlOrden('unidad') }}"
                                class="cc-analytics-sort-link"
                            >
                                Unidad
                                <span class="cc-analytics-sort-icon">
                                    {{ $indicadorOrden('unidad') }}
                                </span>
                            </a>
                        </th>

                        <th>
                            <a
                                href="{{ $urlOrden('motorista') }}"
                                class="cc-analytics-sort-link"
                            >
                                Motorista
                                <span class="cc-analytics-sort-icon">
                                    {{ $indicadorOrden('motorista') }}
                                </span>
                            </a>
                        </th>

                        <th>
                            Kilometraje
                        </th>

                        <th>
                            <a
                                href="{{ $urlOrden('kilometros_recorridos') }}"
                                class="cc-analytics-sort-link"
                            >
                                Kilómetros recorridos
                                <span class="cc-analytics-sort-icon">
                                    {{ $indicadorOrden('kilometros_recorridos') }}
                                </span>
                            </a>
                        </th>

                        <th>
                            <a
                                href="{{ $urlOrden('galones_abastecidos') }}"
                                class="cc-analytics-sort-link"
                            >
                                Galones abastecidos
                                <span class="cc-analytics-sort-icon">
                                    {{ $indicadorOrden('galones_abastecidos') }}
                                </span>
                            </a>
                        </th>

                        <th>
                            <a
                                href="{{ $urlOrden('galones_utilizados') }}"
                                class="cc-analytics-sort-link"
                            >
                                Galones utilizados
                                <span class="cc-analytics-sort-icon">
                                    {{ $indicadorOrden('galones_utilizados') }}
                                </span>
                            </a>
                        </th>

                        <th>
                            <a
                                href="{{ $urlOrden('kilometros_por_galon') }}"
                                class="cc-analytics-sort-link"
                            >
                                Kilómetros por galón
                                <span class="cc-analytics-sort-icon">
                                    {{ $indicadorOrden('kilometros_por_galon') }}
                                </span>
                            </a>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @foreach (
                        $abastecimientos
                        as $abastecimiento
                    )
                        <tr>
                            <td>
                                <span class="cc-cycle-number">
                                    {{
                                        $abastecimiento
                                            ->numero_ciclo_analitico
                                    }}
                                </span>
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        optional(
                                            $abastecimiento
                                                ->fecha_hora_abastecimiento
                                        )->format('d/m/Y')
                                        ?: '—'
                                    }}
                                </div>

                                <div class="cc-table-adaptive-muted">
                                    {{
                                        optional(
                                            $abastecimiento
                                                ->fecha_hora_abastecimiento
                                        )->format('H:i')
                                        ?: '—'
                                    }}
                                </div>
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        $abastecimiento
                                            ->unidad_texto_analitico
                                    }}
                                </div>
                            </td>

                            <td class="cc-motorista-nowrap">
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        $abastecimiento
                                            ->motorista_texto_analitico
                                    }}
                                </div>
                            </td>

                            <td>
                                <div class="cc-kilometraje-flow">
                                    <span>
                                        {{
                                            is_null(
                                                $abastecimiento
                                                    ->kilometraje_anterior_analitico
                                            )
                                                ? '—'
                                                : $formatoNumero(
                                                    $abastecimiento
                                                        ->kilometraje_anterior_analitico,
                                                    1
                                                )
                                        }}
                                    </span>

                                    <span class="cc-kilometraje-arrow">
                                        →
                                    </span>

                                    <span>
                                        {{
                                            is_null(
                                                $abastecimiento
                                                    ->kilometraje_actual_analitico
                                            )
                                                ? '—'
                                                : $formatoNumero(
                                                    $abastecimiento
                                                        ->kilometraje_actual_analitico,
                                                    1
                                                )
                                        }}
                                    </span>
                                </div>
                            </td>

                            <td class="cc-analytics-number">
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        is_null(
                                            $abastecimiento
                                                ->kilometros_recorridos_analitico
                                        )
                                            ? '—'
                                            : $formatoNumero(
                                                $abastecimiento
                                                    ->kilometros_recorridos_analitico,
                                                1
                                            ) . ' km'
                                    }}
                                </div>
                            </td>

                            <td class="cc-analytics-number">
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        is_null(
                                            $abastecimiento
                                                ->galones_abastecidos_analitico
                                        )
                                            ? '—'
                                            : $formatoNumero(
                                                $abastecimiento
                                                    ->galones_abastecidos_analitico,
                                                2
                                            ) . ' gal'
                                    }}
                                </div>
                            </td>

                            <td class="cc-analytics-number">
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        is_null(
                                            $abastecimiento
                                                ->galones_utilizados_analitico
                                        )
                                            ? '—'
                                            : $formatoNumero(
                                                $abastecimiento
                                                    ->galones_utilizados_analitico,
                                                2
                                            ) . ' gal'
                                    }}
                                </div>
                            </td>

                            <td class="cc-analytics-number">
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        is_null(
                                            $abastecimiento
                                                ->kilometros_por_galon_analitico
                                        )
                                            ? '—'
                                            : $formatoNumero(
                                                $abastecimiento
                                                    ->kilometros_por_galon_analitico,
                                                2
                                            ) . ' km/gal'
                                    }}
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
            const lineCharts = Array.from(
                document.querySelectorAll(
                    '[data-cc-line-chart]'
                )
            );

            function dibujarGrafico(canvas) {
                const sourceId = canvas.dataset.chartSource;
                const source = document.getElementById(sourceId);

                if (! source) {
                    return;
                }

                let points = [];

                try {
                    points = JSON.parse(source.textContent || '[]');
                } catch (error) {
                    return;
                }

                if (! Array.isArray(points) || points.length === 0) {
                    return;
                }

                const context = canvas.getContext('2d');
                const rect = canvas.getBoundingClientRect();
                const ratio = window.devicePixelRatio || 1;
                const width = Math.max(rect.width, 320);
                const height = Math.max(rect.height, 288);

                canvas.width = Math.floor(width * ratio);
                canvas.height = Math.floor(height * ratio);

                context.setTransform(ratio, 0, 0, ratio, 0, 0);

                const css = getComputedStyle(document.documentElement);
                const textColor = css
                    .getPropertyValue('--cc-text-muted')
                    .trim() || '#64748b';
                const mainColor = css
                    .getPropertyValue('--cc-primary')
                    .trim() || '#0f766e';
                const borderColor = css
                    .getPropertyValue('--cc-border')
                    .trim() || '#dbe3ea';
                const cardColor = css
                    .getPropertyValue('--cc-bg-card')
                    .trim() || '#ffffff';

                const padding = {
                    top: 20,
                    right: 18,
                    bottom: 52,
                    left: 54,
                };

                const chartWidth =
                    width - padding.left - padding.right;
                const chartHeight =
                    height - padding.top - padding.bottom;

                const values = points.map(
                    point => Number(point.valor)
                );

                let minValue = Math.min(...values);
                let maxValue = Math.max(...values);

                if (minValue === maxValue) {
                    minValue = Math.max(0, minValue - 1);
                    maxValue += 1;
                } else {
                    const margin = (maxValue - minValue) * .12;
                    minValue = Math.max(0, minValue - margin);
                    maxValue += margin;
                }

                function xFor(index) {
                    if (points.length === 1) {
                        return padding.left + chartWidth / 2;
                    }

                    return padding.left
                        + (index / (points.length - 1))
                        * chartWidth;
                }

                function yFor(value) {
                    return padding.top
                        + (
                            1
                            - (
                                (value - minValue)
                                / (maxValue - minValue)
                            )
                        )
                        * chartHeight;
                }

                context.clearRect(0, 0, width, height);
                context.font = '12px sans-serif';
                context.textBaseline = 'middle';

                const gridLines = 4;

                for (let index = 0; index <= gridLines; index++) {
                    const proportion = index / gridLines;
                    const y = padding.top
                        + proportion * chartHeight;
                    const value = maxValue
                        - proportion * (maxValue - minValue);

                    context.beginPath();
                    context.strokeStyle = borderColor;
                    context.lineWidth = 1;
                    context.moveTo(padding.left, y);
                    context.lineTo(width - padding.right, y);
                    context.stroke();

                    context.fillStyle = textColor;
                    context.textAlign = 'right';
                    context.fillText(
                        value.toFixed(2),
                        padding.left - 8,
                        y
                    );
                }

                context.beginPath();
                context.strokeStyle = mainColor;
                context.lineWidth = 2.5;
                context.lineJoin = 'round';
                context.lineCap = 'round';

                points.forEach(
                    function (point, index) {
                        const x = xFor(index);
                        const y = yFor(Number(point.valor));

                        if (index === 0) {
                            context.moveTo(x, y);
                        } else {
                            context.lineTo(x, y);
                        }
                    }
                );

                context.stroke();

                points.forEach(
                    function (point, index) {
                        const x = xFor(index);
                        const y = yFor(Number(point.valor));

                        context.beginPath();
                        context.fillStyle = cardColor;
                        context.strokeStyle = mainColor;
                        context.lineWidth = 2;
                        context.arc(x, y, 4, 0, Math.PI * 2);
                        context.fill();
                        context.stroke();
                    }
                );

                const maxLabels = Math.min(6, points.length);
                const labelIndexes = new Set();

                for (let index = 0; index < maxLabels; index++) {
                    labelIndexes.add(
                        Math.round(
                            index
                            * (points.length - 1)
                            / Math.max(maxLabels - 1, 1)
                        )
                    );
                }

                context.fillStyle = textColor;
                context.textAlign = 'center';
                context.textBaseline = 'top';

                labelIndexes.forEach(
                    function (index) {
                        const label = String(
                            points[index].etiqueta || ''
                        );

                        context.fillText(
                            label.length > 15
                                ? label.slice(0, 15) + '…'
                                : label,
                            xFor(index),
                            height - padding.bottom + 14
                        );
                    }
                );

                canvas._ccChart = {
                    points,
                    xFor,
                    yFor,
                    width,
                    height,
                };
            }

            lineCharts.forEach(dibujarGrafico);

            let resizeTimer = null;

            window.addEventListener(
                'resize',
                function () {
                    window.clearTimeout(resizeTimer);

                    resizeTimer = window.setTimeout(
                        function () {
                            lineCharts.forEach(dibujarGrafico);
                        },
                        120
                    );
                }
            );

            lineCharts.forEach(
                function (canvas) {
                    canvas.addEventListener(
                        'mousemove',
                        function (event) {
                            const chart = canvas._ccChart;

                            if (! chart) {
                                return;
                            }

                            const rect =
                                canvas.getBoundingClientRect();
                            const mouseX =
                                event.clientX - rect.left;

                            let closestIndex = 0;
                            let closestDistance = Infinity;

                            chart.points.forEach(
                                function (point, index) {
                                    const distance = Math.abs(
                                        chart.xFor(index) - mouseX
                                    );

                                    if (distance < closestDistance) {
                                        closestDistance = distance;
                                        closestIndex = index;
                                    }
                                }
                            );

                            const point =
                                chart.points[closestIndex];

                            const detail =
                                document.querySelector(
                                    '[data-chart-detail="'
                                    + canvas.dataset.chartSource
                                    + '"]'
                                );

                            if (detail) {
                                detail.textContent =
                                    point.etiqueta
                                    + ': '
                                    + Number(point.valor).toFixed(2)
                                    + ' km/gal'
                                    + (
                                        point.detalle
                                            ? ' · ' + point.detalle
                                            : ''
                                    );
                            }
                        }
                    );
                }
            );

            const multiselects = Array.from(
                document.querySelectorAll(
                    '[data-cc-filter-multiselect]'
                )
            );

            function cerrarTodos(
                excepto = null
            ) {
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

                    const checkboxes =
                        Array.from(
                            multiselect.querySelectorAll(
                                '[data-cc-filter-checkbox]'
                            )
                        );

                    function checkboxesVisibles() {
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
                        const visibles =
                            checkboxesVisibles();

                        const seleccionados =
                            visibles.filter(
                                function (checkbox) {
                                    return checkbox.checked;
                                }
                            );

                        const defaultLabel =
                            label?.dataset
                                .defaultLabel
                            || 'Todos';

                        if (label) {
                            if (
                                seleccionados.length === 0
                            ) {
                                label.textContent =
                                    defaultLabel;
                            } else if (
                                seleccionados.length === 1
                            ) {
                                const opcion =
                                    seleccionados[0]
                                        .closest(
                                            '[data-cc-filter-option]'
                                        );

                                label.textContent =
                                    opcion
                                        ?.querySelector(
                                            '[data-cc-filter-option-label]'
                                        )
                                        ?.textContent
                                        ?.trim()
                                    || '1 seleccionado';
                            } else {
                                const sufijo =
                                    label.dataset
                                        .pluralSuffix
                                    || 'seleccionados';

                                label.textContent =
                                    seleccionados.length
                                    + ' '
                                    + sufijo;
                            }
                        }

                        if (master) {
                            master.checked =
                                visibles.length > 0
                                && seleccionados.length
                                    === visibles.length;

                            master.indeterminate =
                                seleccionados.length > 0
                                && seleccionados.length
                                    < visibles.length;
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
                            checkboxesVisibles()
                                .forEach(
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

                    multiselect
                        .actualizarEtiqueta =
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
                    function (checkbox) {
                        return checkbox.value;
                    }
                );
            }

            function filtrarDependientes() {
                const empresas =
                    empresasSeleccionadas();

                [
                    [
                        '[data-unidad-option]',
                        '[data-unidad-checkbox]',
                    ],
                    [
                        '[data-motorista-option]',
                        '[data-motorista-checkbox]',
                    ],
                ].forEach(
                    function (configuracion) {
                        document
                            .querySelectorAll(
                                configuracion[0]
                            )
                            .forEach(
                                function (opcion) {
                                    const visible =
                                        empresas.length === 0
                                        || empresas.includes(
                                            opcion.dataset
                                                .empresaId
                                        );

                                    opcion.hidden =
                                        ! visible;

                                    if (! visible) {
                                        const checkbox =
                                            opcion.querySelector(
                                                configuracion[1]
                                            );

                                        if (checkbox) {
                                            checkbox.checked =
                                                false;
                                        }
                                    }
                                }
                            );
                    }
                );

                multiselects.forEach(
                    function (multiselect) {
                        multiselect
                            .actualizarEtiqueta
                            ?.();
                    }
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
                            filtrarDependientes
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
                            filtrarDependientes,
                            0
                        );
                    }
                );

            filtrarDependientes();

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