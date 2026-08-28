@php
    $esVentana = $esVentana ?? false;

    $rutaConsulta = $esVentana
        ? route('auditoria.abastecimientos.index.ventana')
        : route('auditoria.abastecimientos.index');

    $rutaAlterna = $esVentana
        ? route('auditoria.abastecimientos.index', request()->query())
        : route('auditoria.abastecimientos.index.ventana', request()->query());

    $textoAlterna = $esVentana
        ? 'Volver al sistema'
        : 'Abrir en nueva pestaña';

    $sortActual = $sort ?? 'fecha';
    $directionActual = $direction ?? 'desc';

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
            array_filter(
                array_merge(
                    request()->query(),
                    [
                        'consultar' => 1,
                        'sort' => $campo,
                        'direction' => $nuevaDireccion,
                        'page' => null,
                    ]
                ),
                fn ($valor) => ! is_null($valor)
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


<div class="cc-card">
    <div class="cc-card-header cc-card-header-compact cc-aud-abast-header cc-analytics-header">
        <div>
            <h3 class="cc-title cc-title-compact">
                Auditoría de Abastecimientos
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
                No fue posible completar la auditoría.
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
                class="cc-form-section
                       cc-form-section-compact"
                style="margin-top: 0;"
            >
                <div class="cc-form-section-title">
                    Filtros de auditoría
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
                                <span class="cc-filter-multiselect-arrow">⌄</span>
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

                                    @foreach ($empresasSelector as $empresaOpcion)
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
                                                    $empresaOpcion->nombre_comercial
                                                    ?: $empresaOpcion->nombre_legal
                                                }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <select class="cc-input" disabled>
                            @foreach ($empresasSelector as $empresaOpcion)
                                <option selected>
                                    {{
                                        $empresaOpcion->nombre_comercial
                                        ?: $empresaOpcion->nombre_legal
                                    }}
                                </option>
                            @endforeach
                        </select>

                        @foreach ($empresaIds ?? [] as $empresaSeleccionadaId)
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
                            <span class="cc-filter-multiselect-arrow">⌄</span>
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
                                    <input type="checkbox" data-cc-filter-master>
                                    <span>Seleccionar todo</span>
                                </label>

                                @foreach ($unidadesSelector as $unidadOpcion)
                                    <label
                                        class="cc-filter-multiselect-option"
                                        data-cc-filter-option
                                        data-unidad-option
                                        data-empresa-id="{{ $unidadOpcion->empresa_id }}"
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
                    <label>Motorista</label>

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
                            >
                                Todos
                            </span>
                            <span class="cc-filter-multiselect-arrow">⌄</span>
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
                                    <input type="checkbox" data-cc-filter-master>
                                    <span>Seleccionar todo</span>
                                </label>

                                @foreach ($motoristasSelector as $motoristaOpcion)
                                    <label
                                        class="cc-filter-multiselect-option"
                                        data-cc-filter-option
                                        data-motorista-option
                                        data-empresa-id="{{ $motoristaOpcion->empresa_id }}"
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
                                            {{ $motoristaOpcion->nombre_completo }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cc-field">
                    <label for="tipo_origen">Origen</label>

                    <select
                        id="tipo_origen"
                        name="tipo_origen"
                        class="cc-input"
                    >
                        <option value="">Todos</option>
                        <option value="interno" @selected($tipoOrigen === 'interno')>
                            Interno
                        </option>
                        <option value="externo" @selected($tipoOrigen === 'externo')>
                            Externo
                        </option>
                    </select>
                </div>

                <div class="cc-field">
                    <label for="estado">Estado</label>

                    <select
                        id="estado"
                        name="estado"
                        class="cc-input"
                    >
                        <option value="">Todos</option>
                        <option value="registrado" @selected($estado === 'registrado')>
                            Registrado
                        </option>
                        <option value="anulado" @selected($estado === 'anulado')>
                            Anulado
                        </option>
                    </select>
                </div>

                <div class="cc-field">
                    <label for="fecha_desde">Fecha de inicio</label>

                    <input
                        id="fecha_desde"
                        type="date"
                        name="fecha_desde"
                        value="{{ $fechaDesde }}"
                        class="cc-input"
                    >
                </div>

                <div class="cc-field">
                    <label for="fecha_hasta">Fecha final</label>

                    <input
                        id="fecha_hasta"
                        type="date"
                        name="fecha_hasta"
                        value="{{ $fechaHasta }}"
                        class="cc-input"
                    >
                </div>

                <div class="cc-field" style="grid-column: span 2;">
                    <label for="busqueda">Búsqueda general</label>

                    <input
                        id="busqueda"
                        type="text"
                        name="busqueda"
                        value="{{ $busqueda }}"
                        maxlength="150"
                        class="cc-input"
                        placeholder="Empresa, unidad, motorista, origen o motivo"
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
                    <button type="submit" class="cc-btn-primary">
                        Consultar
                    </button>

                    <a href="{{ $rutaConsulta }}" class="cc-btn-secondary">
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
                    'etiqueta' => 'Abastecimientos auditados',
                    'valor' => $resumen['abastecimientos'],
                    'meta' => 'Operaciones dentro de los filtros seleccionados.',
                ],
                [
                    'etiqueta' => 'Galones cargados',
                    'valor' => $formatoNumero(
                        $resumen['galones']
                    ) . ' gal',
                    'meta' => 'Volumen total abastecido.',
                ],
                [
                    'etiqueta' => 'Gasolinera interna',
                    'valor' => $formatoNumero(
                        $resumen['galones_internos']
                    ) . ' gal',
                    'meta' => 'Galones cargados desde inventario interno.',
                ],
                [
                    'etiqueta' => 'Gasolinera externa',
                    'valor' => $formatoNumero(
                        $resumen['galones_externos']
                    ) . ' gal',
                    'meta' => 'Galones cargados en proveedores externos.',
                ],
            ];
        @endphp

        <div class="cc-aud-abast-summary cc-analytics-kpi-grid">
            @foreach ($tarjetas as $tarjeta)
                <div class="cc-aud-abast-kpi cc-analytics-kpi">
                    <div class="cc-aud-abast-kpi-label cc-analytics-kpi-label">
                        {{ $tarjeta['etiqueta'] }}
                    </div>

                    <div class="cc-aud-abast-kpi-value cc-analytics-kpi-value">
                        {{ $tarjeta['valor'] }}
                    </div>

                    <div class="cc-aud-abast-kpi-meta cc-analytics-kpi-meta">
                        {{ $tarjeta['meta'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="cc-aud-abast-charts cc-analytics-grid">
            <section class="cc-aud-abast-chart-card cc-analytics-chart">
                <div class="cc-aud-abast-chart-head cc-analytics-chart-header">
                    <div>
                        <h4 class="cc-aud-abast-chart-title cc-analytics-chart-title">
                            Galones abastecidos por fecha
                        </h4>

                        <div class="cc-aud-abast-chart-subtitle cc-analytics-chart-subtitle">
                            Comparación diaria entre origen interno y externo.
                        </div>
                    </div>

                </div>

                <div class="cc-aud-abast-chart-stage cc-analytics-chart-stage">
                    @if ($graficos['tendencia'] !== [])
                        <canvas
                            id="chart-tendencia"
                            class="cc-aud-abast-chart-canvas"
                            data-chart-type="grouped"
                            data-chart-source="data-chart-tendencia"
                        ></canvas>

                        <script
                            type="application/json"
                            id="data-chart-tendencia"
                        >@json($graficos['tendencia'])</script>
                    @else
                        <div class="cc-aud-abast-chart-empty cc-analytics-chart-empty">
                            No hay información suficiente para construir el gráfico.
                        </div>
                    @endif
                </div>

                    <div class="cc-aud-abast-chart-controls cc-analytics-chart-toolbar">
                        <button type="button" data-chart-prev="chart-tendencia">
                            Anterior
                        </button>
                        <button type="button" data-chart-next="chart-tendencia">
                            Siguiente
                        </button>
                        <button type="button" data-chart-zoom-in="chart-tendencia">
                            Acercar
                        </button>
                        <button type="button" data-chart-zoom-out="chart-tendencia">
                            Alejar
                        </button>
                        <button type="button" data-chart-reset="chart-tendencia">
                            Restablecer
                        </button>
                    </div>

                <div class="cc-aud-abast-chart-legend cc-analytics-chart-legend">
                    <span class="cc-aud-abast-legend-item">
                        <span
                            class="cc-aud-abast-legend-dot"
                            style="background: #063B4A;"
                        ></span>
                        Origen interno
                    </span>

                    <span class="cc-aud-abast-legend-item">
                        <span
                            class="cc-aud-abast-legend-dot"
                            style="background: #7A5D3C;"
                        ></span>
                        Origen externo
                    </span>
                </div>

                <div
                    class="cc-aud-abast-chart-range cc-analytics-chart-navigator"
                    data-chart-range-label="chart-tendencia"
                ></div>
            </section>

            <section class="cc-aud-abast-chart-card cc-analytics-chart">
                <div class="cc-aud-abast-chart-head cc-analytics-chart-header">
                    <div>
                        <h4 class="cc-aud-abast-chart-title cc-analytics-chart-title">
                            Unidades con mayor volumen abastecido
                        </h4>

                        <div class="cc-aud-abast-chart-subtitle cc-analytics-chart-subtitle">
                            Ranking de unidades por galones acumulados.
                        </div>
                    </div>

                </div>

                <div class="cc-aud-abast-chart-stage cc-analytics-chart-stage">
                    @if ($graficos['unidades'] !== [])
                        <canvas
                            id="chart-unidades"
                            class="cc-aud-abast-chart-canvas"
                            data-chart-type="single"
                            data-chart-source="data-chart-unidades"
                        ></canvas>

                        <script
                            type="application/json"
                            id="data-chart-unidades"
                        >@json($graficos['unidades'])</script>
                    @else
                        <div class="cc-aud-abast-chart-empty cc-analytics-chart-empty">
                            No hay información suficiente para construir el gráfico.
                        </div>
                    @endif
                </div>

                    <div class="cc-aud-abast-chart-controls cc-analytics-chart-toolbar">
                        <button type="button" data-chart-prev="chart-unidades">
                            Anterior
                        </button>
                        <button type="button" data-chart-next="chart-unidades">
                            Siguiente
                        </button>
                        <button type="button" data-chart-zoom-in="chart-unidades">
                            Acercar
                        </button>
                        <button type="button" data-chart-zoom-out="chart-unidades">
                            Alejar
                        </button>
                        <button type="button" data-chart-reset="chart-unidades">
                            Restablecer
                        </button>
                    </div>

                <div class="cc-aud-abast-chart-legend cc-analytics-chart-legend">
                    <span class="cc-aud-abast-legend-item">
                        <span
                            class="cc-aud-abast-legend-dot"
                            style="background: #35545E;"
                        ></span>
                        Galones abastecidos
                    </span>
                </div>

                <div
                    class="cc-aud-abast-chart-range cc-analytics-chart-navigator"
                    data-chart-range-label="chart-unidades"
                ></div>
            </section>
        </div>
    @endif

    @if (! $hayFiltros)
        <section class="cc-empty-panel cc-empty-panel-compact">
            <h5>Auditoría pendiente</h5>
            <p>
                Seleccione los criterios necesarios para revisar
                los abastecimientos.
            </p>
        </section>
    @elseif ($abastecimientosAuditados->isEmpty())
        <section class="cc-empty-panel cc-empty-panel-compact">
            <h5>Sin resultados</h5>
            <p>
                No existen abastecimientos que coincidan con
                los filtros seleccionados.
            </p>
        </section>
    @else
        <div
            class="cc-result-count"
        >
            Mostrando
            <span class="cc-result-count-value">
                {{ $abastecimientosAuditados->firstItem() }}
            </span>
            -
            <span class="cc-result-count-value">
                {{ $abastecimientosAuditados->lastItem() }}
            </span>
            de
            <span class="cc-result-count-value">
                {{ $abastecimientosAuditados->total() }}
            </span>
        </div>

        <div class="cc-table-adaptive-wrapper">
            <table
                class="cc-table-adaptive"
                style="min-width: 112rem;"
            >
                <thead>
                    <tr>
                        @foreach ([
                            'fecha' => 'Fecha',
                            'empresa' => 'Empresa',
                            'unidad' => 'Unidad',
                            'motorista' => 'Motorista',
                            'origen' => 'Origen',
                            'galones' => 'Galones cargados',
                            'estado' => 'Estado',
                            'usuario' => 'Usuario',
                        ] as $campo => $etiqueta)
                            <th>
                                <a
                                    href="{{ $urlOrden($campo) }}"
                                    class="cc-aud-abast-sort-link"
                                >
                                    {{ $etiqueta }}
                                    <span>{{ $indicadorOrden($campo) }}</span>
                                </a>
                            </th>
                        @endforeach

                        <th>Volumen inicial</th>
                        <th>Volumen final</th>
                        <th>Consumido en ciclo</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($abastecimientosAuditados as $fila)
                        <tr>
                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        optional($fila['fecha'])->format('d/m/Y')
                                        ?: '—'
                                    }}
                                </div>

                                <div class="cc-table-adaptive-muted">
                                    {{
                                        optional($fila['fecha'])->format('H:i')
                                        ?: '—'
                                    }}
                                </div>
                            </td>

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
                                    {{ $fila['motorista'] }}
                                </div>
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        $fila['tipo_origen'] === 'interno'
                                            ? 'Interno'
                                            : 'Externo'
                                    }}
                                </div>

                                <div class="cc-table-adaptive-muted">
                                    {{ $fila['origen'] }}
                                </div>
                            </td>

                            <td style="text-align: right;">
                                <div class="cc-table-adaptive-strong">
                                    {{ $formatoNumero($fila['galones']) }} gal
                                </div>
                            </td>

                            <td>
                                <span
                                    class="cc-badge {{
                                        $fila['estado'] === 'registrado'
                                            ? 'cc-badge-active'
                                            : 'cc-badge-inactive'
                                    }}"
                                >
                                    {{
                                        $fila['estado'] === 'registrado'
                                            ? 'Registrado'
                                            : 'Anulado'
                                    }}
                                </span>
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{ $fila['usuario'] }}
                                </div>
                            </td>

                            <td style="text-align: right;">
                                {{ $formatoNumero($fila['volumen_inicial']) }} gal
                            </td>

                            <td style="text-align: right;">
                                {{ $formatoNumero($fila['volumen_final']) }} gal
                            </td>

                            <td style="text-align: right;">
                                @if (is_null($fila['combustible_consumido']))
                                    —
                                @else
                                    {{
                                        $formatoNumero(
                                            $fila['combustible_consumido']
                                        )
                                    }} gal
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{
                $abastecimientosAuditados
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
    document.addEventListener('DOMContentLoaded', function () {
        const chartConfigs = new Map();

        function parseData(sourceId) {
            const source = document.getElementById(sourceId);

            if (! source) {
                return [];
            }

            try {
                const parsed = JSON.parse(source.textContent || '[]');
                return Array.isArray(parsed) ? parsed : [];
            } catch (error) {
                return [];
            }
        }

        function createChart(canvas) {
            const data = parseData(canvas.dataset.chartSource);

            if (data.length === 0) {
                return;
            }

            const type = canvas.dataset.chartType || 'single';

            chartConfigs.set(canvas.id, {
                canvas,
                data,
                type,
                start: 0,
                visible: Math.min(8, data.length),
                defaultVisible: Math.min(8, data.length),
            });
        }

        function drawChart(config) {
            const canvas = config.canvas;
            const context = canvas.getContext('2d');
            const rect = canvas.getBoundingClientRect();
            const ratio = window.devicePixelRatio || 1;
            const width = Math.max(rect.width, 320);
            const height = Math.max(rect.height, 304);

            canvas.width = Math.floor(width * ratio);
            canvas.height = Math.floor(height * ratio);

            context.setTransform(ratio, 0, 0, ratio, 0, 0);

            const css = getComputedStyle(document.documentElement);
            const textColor = css
                .getPropertyValue('--cc-text-muted')
                .trim()
                || '#64748b';
            const borderColor = css
                .getPropertyValue('--cc-border')
                .trim()
                || '#dbe3ea';

            const slice = config.data.slice(
                config.start,
                config.start + config.visible
            );

            const padding = {
                top: 22,
                right: 18,
                bottom: 72,
                left: 62,
            };

            const chartWidth = width - padding.left - padding.right;
            const chartHeight = height - padding.top - padding.bottom;

            const values = slice.flatMap(function (point) {
                if (config.type === 'grouped') {
                    return [
                        Number(point.interno || 0),
                        Number(point.externo || 0),
                    ];
                }

                return [Number(point.galones || 0)];
            });

            const maxValue = Math.max(...values, 1);
            const roundedMax = Math.ceil(maxValue / 100) * 100 || 1;

            context.clearRect(0, 0, width, height);

            for (let index = 0; index <= 4; index++) {
                const y = padding.top + (index / 4) * chartHeight;
                const value = roundedMax - (index / 4) * roundedMax;

                context.beginPath();
                context.strokeStyle = borderColor;
                context.lineWidth = 1;
                context.moveTo(padding.left, y);
                context.lineTo(width - padding.right, y);
                context.stroke();

                context.fillStyle = textColor;
                context.font = '12px Inter, sans-serif';
                context.textAlign = 'right';
                context.textBaseline = 'middle';
                context.fillText(
                    value.toLocaleString(
                        'es-SV',
                        {
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0,
                        }
                    ),
                    padding.left - 8,
                    y
                );
            }

            const slotWidth = chartWidth / Math.max(slice.length, 1);

            slice.forEach(function (point, index) {
                const centerX = padding.left
                    + index * slotWidth
                    + slotWidth / 2;

                if (config.type === 'grouped') {
                    const groupWidth = Math.min(56, slotWidth * .72);
                    const barWidth = Math.max(7, groupWidth / 2 - 3);

                    [
                        {
                            value: Number(point.interno || 0),
                            color: '#063B4A',
                            offset: -barWidth - 2,
                        },
                        {
                            value: Number(point.externo || 0),
                            color: '#7A5D3C',
                            offset: 2,
                        },
                    ].forEach(function (serie) {
                        const barHeight = (
                            serie.value / roundedMax
                        ) * chartHeight;

                        context.fillStyle = serie.color;
                        context.fillRect(
                            centerX + serie.offset,
                            padding.top + chartHeight - barHeight,
                            barWidth,
                            barHeight
                        );
                    });
                } else {
                    const barWidth = Math.min(42, slotWidth * .62);
                    const value = Number(point.galones || 0);
                    const barHeight = (value / roundedMax) * chartHeight;

                    context.fillStyle = '#35545E';
                    context.fillRect(
                        centerX - barWidth / 2,
                        padding.top + chartHeight - barHeight,
                        barWidth,
                        barHeight
                    );
                }

                const label = String(point.etiqueta || '');

                context.save();
                context.translate(
                    centerX,
                    height - padding.bottom + 15
                );
                context.rotate(-Math.PI / 5);
                context.fillStyle = textColor;
                context.font = '12px Inter, sans-serif';
                context.textAlign = 'right';
                context.textBaseline = 'middle';
                context.fillText(
                    label.length > 16
                        ? label.slice(0, 16) + '…'
                        : label,
                    0,
                    0
                );
                context.restore();
            });

            const rangeLabel = document.querySelector(
                '[data-chart-range-label="' + canvas.id + '"]'
            );

            if (rangeLabel) {
                const start = config.data.length === 0
                    ? 0
                    : config.start + 1;
                const end = Math.min(
                    config.start + config.visible,
                    config.data.length
                );

                rangeLabel.textContent = start
                    + '–'
                    + end
                    + ' de '
                    + config.data.length;
            }

            document
                .querySelectorAll('[data-chart-prev="' + canvas.id + '"]')
                .forEach(button => {
                    button.disabled = config.start === 0;
                });

            document
                .querySelectorAll('[data-chart-next="' + canvas.id + '"]')
                .forEach(button => {
                    button.disabled = (
                        config.start + config.visible
                        >= config.data.length
                    );
                });
        }

        document
            .querySelectorAll('[data-chart-source]')
            .forEach(createChart);

        chartConfigs.forEach(drawChart);

        function updateChart(id, callback) {
            const config = chartConfigs.get(id);

            if (! config) {
                return;
            }

            callback(config);

            config.visible = Math.max(
                3,
                Math.min(config.visible, config.data.length)
            );

            config.start = Math.max(
                0,
                Math.min(
                    config.start,
                    Math.max(0, config.data.length - config.visible)
                )
            );

            drawChart(config);
        }

        document
            .querySelectorAll('[data-chart-prev]')
            .forEach(function (button) {
                button.addEventListener('click', function () {
                    updateChart(
                        button.dataset.chartPrev,
                        config => {
                            config.start = Math.max(
                                0,
                                config.start - config.visible
                            );
                        }
                    );
                });
            });

        document
            .querySelectorAll('[data-chart-next]')
            .forEach(function (button) {
                button.addEventListener('click', function () {
                    updateChart(
                        button.dataset.chartNext,
                        config => {
                            config.start = Math.min(
                                Math.max(
                                    0,
                                    config.data.length - config.visible
                                ),
                                config.start + config.visible
                            );
                        }
                    );
                });
            });

        document
            .querySelectorAll('[data-chart-zoom-in]')
            .forEach(function (button) {
                button.addEventListener('click', function () {
                    updateChart(
                        button.dataset.chartZoomIn,
                        config => {
                            config.visible = Math.max(
                                3,
                                config.visible - 1
                            );
                        }
                    );
                });
            });

        document
            .querySelectorAll('[data-chart-zoom-out]')
            .forEach(function (button) {
                button.addEventListener('click', function () {
                    updateChart(
                        button.dataset.chartZoomOut,
                        config => {
                            config.visible = Math.min(
                                config.data.length,
                                config.visible + 1
                            );
                        }
                    );
                });
            });

        document
            .querySelectorAll('[data-chart-reset]')
            .forEach(function (button) {
                button.addEventListener('click', function () {
                    updateChart(
                        button.dataset.chartReset,
                        config => {
                            config.start = 0;
                            config.visible = config.defaultVisible;
                        }
                    );
                });
            });

        window.addEventListener('resize', function () {
            chartConfigs.forEach(drawChart);
        });

        const multiselects = Array.from(
            document.querySelectorAll(
                '[data-cc-filter-multiselect]'
            )
        );

        function closeAll(except = null) {
            multiselects.forEach(function (multiselect) {
                if (except && multiselect === except) {
                    return;
                }

                multiselect.classList.remove('is-open');
                multiselect
                    .querySelector('[data-cc-filter-toggle]')
                    ?.classList.remove('is-open');
                multiselect
                    .querySelector('[data-cc-filter-menu]')
                    ?.classList.remove('is-open');
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

            function visibleCheckboxes() {
                return checkboxes.filter(function (checkbox) {
                    return ! checkbox
                        .closest('[data-cc-filter-option]')
                        ?.hidden;
                });
            }

            function updateLabel() {
                const available = visibleCheckboxes();
                const selected = available.filter(
                    checkbox => checkbox.checked
                );

                if (label) {
                    if (selected.length === 0) {
                        label.textContent =
                            label.dataset.defaultLabel || 'Todos';
                    } else if (selected.length === 1) {
                        label.textContent =
                            selected[0]
                                .closest('[data-cc-filter-option]')
                                ?.querySelector(
                                    '[data-cc-filter-option-label]'
                                )
                                ?.textContent
                                ?.trim()
                            || '1 seleccionado';
                    } else {
                        label.textContent =
                            selected.length + ' seleccionados';
                    }
                }

                if (master) {
                    master.checked = (
                        available.length > 0
                        && selected.length === available.length
                    );

                    master.indeterminate = (
                        selected.length > 0
                        && selected.length < available.length
                    );
                }
            }

            toggle?.addEventListener('click', function () {
                closeAll(multiselect);

                const willOpen = ! multiselect.classList.contains(
                    'is-open'
                );

                multiselect.classList.toggle('is-open', willOpen);
                toggle.classList.toggle('is-open', willOpen);
                menu?.classList.toggle('is-open', willOpen);
            });

            master?.addEventListener('change', function () {
                visibleCheckboxes().forEach(function (checkbox) {
                    checkbox.checked = master.checked;
                });

                updateLabel();
            });

            checkboxes.forEach(function (checkbox) {
                checkbox.addEventListener('change', updateLabel);
            });

            multiselect.updateLabel = updateLabel;
            updateLabel();
        });

        function selectedCompanies() {
            return Array.from(
                document.querySelectorAll(
                    '[data-empresa-checkbox]:checked'
                )
            ).map(checkbox => checkbox.value);
        }

        function filterDependents() {
            const companies = selectedCompanies();

            [
                ['[data-unidad-option]', '[data-unidad-checkbox]'],
                ['[data-motorista-option]', '[data-motorista-checkbox]'],
            ].forEach(function (configuration) {
                document
                    .querySelectorAll(configuration[0])
                    .forEach(function (option) {
                        const visible = (
                            companies.length === 0
                            || companies.includes(
                                option.dataset.empresaId
                            )
                        );

                        option.hidden = ! visible;

                        if (! visible) {
                            const checkbox = option.querySelector(
                                configuration[1]
                            );

                            if (checkbox) {
                                checkbox.checked = false;
                            }
                        }
                    });
            });

            multiselects.forEach(multiselect => {
                multiselect.updateLabel?.();
            });
        }

        document
            .querySelectorAll('[data-empresa-checkbox]')
            .forEach(function (checkbox) {
                checkbox.addEventListener(
                    'change',
                    filterDependents
                );
            });

        document
            .querySelector(
                '[data-filter-type="empresa"] '
                + '[data-cc-filter-master]'
            )
            ?.addEventListener('change', function () {
                window.setTimeout(filterDependents, 0);
            });

        filterDependents();

        document.addEventListener('click', function (event) {
            if (
                event.target.closest(
                    '[data-cc-filter-multiselect]'
                )
            ) {
                return;
            }

            closeAll();
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                closeAll();
            }
        });
    });
</script>
