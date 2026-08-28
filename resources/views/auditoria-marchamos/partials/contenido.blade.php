@php
    $esVentana = $esVentana ?? false;

    $rutaConsulta = $esVentana
        ? route('auditoria.marchamos.index.ventana')
        : route('auditoria.marchamos.index');

    $rutaAlterna = $esVentana
        ? route('auditoria.marchamos.index', request()->query())
        : route(
            'auditoria.marchamos.index.ventana',
            request()->query()
        );

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

@endphp


<div class="cc-card">
    <div
        class="cc-card-header
               cc-card-header-compact
               cc-audit-header cc-analytics-header"
    >
        <div>
            <h3 class="cc-title cc-title-compact">
                Auditoría de Reemplazo de Marchamos
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
                No fue posible completar la consulta.
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
                @php
                    $filtros = [
                        [
                            'etiqueta' => 'Empresa',
                            'nombre' => 'empresa_ids[]',
                            'seleccionados' => $empresaIds ?? [],
                            'opciones' => $empresasSelector,
                            'valor' => fn ($item) => $item->id,
                            'texto' => fn ($item) =>
                                $item->nombre_comercial
                                ?: $item->nombre_legal,
                            'empresa' => null,
                            'tipo' => 'empresa',
                        ],
                        [
                            'etiqueta' => 'Unidad',
                            'nombre' => 'unidad_ids[]',
                            'seleccionados' => $unidadIds ?? [],
                            'opciones' => $unidadesSelector,
                            'valor' => fn ($item) => $item->id,
                            'texto' => fn ($item) => $item->placa,
                            'empresa' => fn ($item) =>
                                $item->empresa_id,
                            'tipo' => 'unidad',
                        ],
                        [
                            'etiqueta' => 'Usuario',
                            'nombre' => 'usuario_ids[]',
                            'seleccionados' => $usuarioIds ?? [],
                            'opciones' => $usuariosSelector,
                            'valor' => fn ($item) => $item->id,
                            'texto' => fn ($item) =>
                                $item->name ?: $item->email,
                            'empresa' => null,
                            'tipo' => 'usuario',
                        ],
                    ];
                @endphp

                @foreach ($filtros as $config)
                    <div class="cc-field">
                        <label>{{ $config['etiqueta'] }}</label>

                        @if (
                            $config['tipo'] === 'empresa'
                            && ! $esUsuarioDieselCop
                        )
                            <select class="cc-input" disabled>
                                @foreach (
                                    $config['opciones']
                                    as $opcion
                                )
                                    <option selected>
                                        {{ $config['texto']($opcion) }}
                                    </option>
                                @endforeach
                            </select>

                            @foreach (
                                $config['seleccionados']
                                as $idSeleccionado
                            )
                                <input
                                    type="hidden"
                                    name="empresa_ids[]"
                                    value="{{ $idSeleccionado }}"
                                >
                            @endforeach
                        @else
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
                                        data-default-label="Todos"
                                        data-plural-suffix="seleccionados"
                                    >
                                        Todos
                                    </span>

                                    <span
                                        class="cc-filter-multiselect-arrow"
                                    >
                                        ⌄
                                    </span>
                                </button>

                                <div
                                    class="cc-filter-multiselect-menu"
                                    data-cc-filter-menu
                                >
                                    <div
                                        class="cc-filter-multiselect-list"
                                    >
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
                                            $config['opciones']
                                            as $opcion
                                        )
                                            <label
                                                class="cc-filter-multiselect-option"
                                                data-cc-filter-option
                                                @if (
                                                    ! is_null(
                                                        $config['empresa']
                                                    )
                                                )
                                                    data-dependent-option
                                                    data-empresa-id="{{
                                                        $config['empresa'](
                                                            $opcion
                                                        )
                                                    }}"
                                                @endif
                                            >
                                                <input
                                                    type="checkbox"
                                                    name="{{
                                                        $config['nombre']
                                                    }}"
                                                    value="{{
                                                        $config['valor'](
                                                            $opcion
                                                        )
                                                    }}"
                                                    @checked(
                                                        in_array(
                                                            (int)
                                                            $config['valor'](
                                                                $opcion
                                                            ),
                                                            $config[
                                                                'seleccionados'
                                                            ],
                                                            true
                                                        )
                                                    )
                                                    data-cc-filter-checkbox
                                                    @if (
                                                        $config['tipo']
                                                        === 'empresa'
                                                    )
                                                        data-empresa-checkbox
                                                    @endif
                                                >

                                                <span
                                                    data-cc-filter-option-label
                                                >
                                                    {{
                                                        $config['texto'](
                                                            $opcion
                                                        )
                                                    }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach

                @php
                    $filtrosCatalogo = [
                        [
                            'etiqueta' => 'Origen del evento',
                            'nombre' => 'origenes[]',
                            'seleccionados' => $origenes ?? [],
                            'opciones' => $opcionesOrigenes,
                        ],
                        [
                            'etiqueta' => 'Motivo',
                            'nombre' => 'motivos[]',
                            'seleccionados' => $motivos ?? [],
                            'opciones' => $opcionesMotivos,
                        ],
                        [
                            'etiqueta' => 'Estado',
                            'nombre' => 'estados[]',
                            'seleccionados' => $estados ?? [],
                            'opciones' => $opcionesEstados,
                        ],
                        [
                            'etiqueta' => 'Cantidad reemplazada',
                            'nombre' => 'cantidades[]',
                            'seleccionados' => $cantidades ?? [],
                            'opciones' => $opcionesCantidades,
                        ],
                    ];
                @endphp

                @foreach ($filtrosCatalogo as $config)
                    <div class="cc-field">
                        <label>{{ $config['etiqueta'] }}</label>

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

                                <span
                                    class="cc-filter-multiselect-arrow"
                                >
                                    ⌄
                                </span>
                            </button>

                            <div
                                class="cc-filter-multiselect-menu"
                                data-cc-filter-menu
                            >
                                <div
                                    class="cc-filter-multiselect-list"
                                >
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
                                        as $valor => $etiqueta
                                    )
                                        <label
                                            class="cc-filter-multiselect-option"
                                            data-cc-filter-option
                                        >
                                            <input
                                                type="checkbox"
                                                name="{{ $config['nombre'] }}"
                                                value="{{ $valor }}"
                                                @checked(
                                                    in_array(
                                                        $valor,
                                                        $config[
                                                            'seleccionados'
                                                        ],
                                                        true
                                                    )
                                                )
                                                data-cc-filter-checkbox
                                            >

                                            <span
                                                data-cc-filter-option-label
                                            >
                                                {{ $etiqueta }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

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
                        placeholder="Empresa, Nombre / Placa, usuario, código, punto o evento"
                    >
                </div>

                <div
                    class="cc-standard-filter-actions
                           cc-audit-actions"
                    style="
                        display: flex;
                        gap: .75rem;
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
                    'etiqueta' => 'Total reemplazado',
                    'valor' => $resumen['marchamos'],
                ],
                [
                    'etiqueta' => 'Unidades auditadas',
                    'valor' =>
                        $resumen['unidades_auditadas'],
                ],
                [
                    'etiqueta' =>
                        'Por abastecimiento',
                    'valor' =>
                        $resumen[
                            'marchamos_abastecimiento'
                        ],
                ],
                [
                    'etiqueta' =>
                        '% abastecimiento',
                    'valor' =>
                        $formatoNumero(
                            $resumen[
                                'porcentaje_abastecimiento'
                            ]
                        )
                        . ' %',
                ],
                [
                    'etiqueta' => 'Reemplazo manual',
                    'valor' =>
                        $resumen['marchamos_manuales'],
                ],
                [
                    'etiqueta' => '% manual',
                    'valor' =>
                        $formatoNumero(
                            $resumen[
                                'porcentaje_manual'
                            ]
                        )
                        . ' %',
                ],
            ];
        @endphp

        <div class="cc-summary-strip cc-audit-summary cc-analytics-summary">
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

        <section class="cc-audit-chart-card cc-analytics-chart">
            <h4 class="cc-audit-chart-title cc-analytics-chart-title">
                Distribución de reemplazos por categoría
            </h4>

            <div class="cc-audit-chart-subtitle cc-analytics-chart-subtitle">
                Porcentaje de marchamos reemplazados dentro del
                período y filtros seleccionados.
            </div>

            @if (
                ! empty($graficoAuditoria['resumen'])
            )
                <div class="cc-audit-chart-controls">
                    <div class="cc-audit-chart-grouping">
                        <label for="cc-audit-chart-group">
                            Agrupar por
                        </label>

                        <select
                            id="cc-audit-chart-group"
                            class="cc-input"
                            data-audit-chart-group
                        >
                            <option value="resumen">
                                Resumen del período
                            </option>

                            <option value="mes">
                                Mes
                            </option>

                            <option value="semana">
                                Semana
                            </option>

                            <option value="dia">
                                Día
                            </option>
                        </select>
                    </div>

                    <div class="cc-audit-chart-toolbar cc-analytics-chart-toolbar">
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

                        <span
                            class="cc-audit-chart-status"
                            data-chart-window-status
                        >
                            —
                        </span>
                    </div>
                </div>

                <div class="cc-audit-chart-area cc-analytics-chart-stage">
                    <canvas
                        class="cc-audit-chart-canvas"
                        data-audit-category-chart
                        data-chart-source="grafico-auditoria-categorias"
                        aria-label="Distribución de marchamos reemplazados por categoría"
                    ></canvas>
                </div>

                <div
                    class="cc-audit-chart-detail"
                    data-chart-detail
                >
                    Mueva el cursor sobre el gráfico para
                    consultar el detalle.
                </div>

                <div
                    class="cc-audit-chart-legend cc-analytics-chart-legend"
                    data-chart-legend
                ></div>

                <div class="cc-audit-chart-navigator cc-analytics-chart-navigator">
                    <span class="cc-audit-chart-navigator-label">
                        Inicio
                    </span>

                    <input
                        type="range"
                        min="0"
                        max="0"
                        value="0"
                        step="1"
                        class="cc-audit-chart-range"
                        data-chart-range
                    >

                    <span class="cc-audit-chart-navigator-label">
                        Fin
                    </span>
                </div>

                <div
                    class="cc-audit-chart-table-panel"
                    data-chart-table-panel
                    hidden
                >
                    <table class="cc-audit-chart-table">
                        <thead>
                            <tr>
                                <th>Período</th>
                                <th>Categoría</th>
                                <th>Marchamos</th>
                                <th>Porcentaje</th>
                                <th>Eventos</th>
                                <th>Unidades</th>
                            </tr>
                        </thead>

                        <tbody data-chart-table-body></tbody>
                    </table>
                </div>

                <script
                    type="application/json"
                    id="grafico-auditoria-categorias"
                >@json($graficoAuditoria)</script>
            @else
                <div class="cc-audit-chart-empty cc-analytics-chart-empty">
                    No hay información suficiente para construir
                    el gráfico.
                </div>
            @endif
        </section>

    @endif

    @if (! $hayFiltros)
        <section class="cc-empty-panel cc-empty-panel-compact">
            <h5>Auditoría pendiente</h5>

            <p>
                Seleccione los criterios necesarios y presione
                Consultar para revisar los reemplazos de marchamos.
            </p>
        </section>
    @elseif ($eventos->isEmpty())
        <section class="cc-empty-panel cc-empty-panel-compact">
            <h5>Sin resultados</h5>

            <p>
                No existen eventos que coincidan con los filtros
                seleccionados.
            </p>
        </section>
    @else
        <div class="cc-table-adaptive-wrapper">
            <table
                class="cc-table-adaptive cc-audit-events-table"
            >
                <thead>
                    <tr>
                        @foreach ([
                            'fecha' => 'Fecha',
                            'empresa' => 'Empresa',
                            'unidad' => 'Unidad',
                            'origen' => 'Origen',
                            'clasificacion' => 'Clasificación',
                            'cantidad' => 'Cantidad',
                            'usuario' => 'Usuario',
                        ] as $campo => $etiqueta)
                            <th>
                                <a
                                    href="{{ $urlOrden($campo) }}"
                                    class="cc-audit-sort-link"
                                >
                                    {{ $etiqueta }}

                                    <span class="cc-audit-sort-icon">
                                        {{ $indicadorOrden($campo) }}
                                    </span>
                                </a>
                            </th>
                        @endforeach

                        <th>Detalle</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($eventos as $evento)
                        @php
                            $detalleId =
                                'detalle-evento-'
                                . $evento['id'];

                        @endphp

                        <tr>
                            <td class="cc-audit-nowrap">
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        optional(
                                            $evento['fecha']
                                        )->format('d/m/Y')
                                        ?: '—'
                                    }}
                                </div>

                                <div class="cc-table-adaptive-muted">
                                    {{
                                        optional(
                                            $evento['fecha']
                                        )->format('H:i')
                                        ?: '—'
                                    }}
                                </div>
                            </td>

                            <td>
                                {{ $evento['empresa'] }}
                            </td>

                            <td class="cc-audit-nowrap">
                                {{ $evento['unidad'] }}
                            </td>

                            <td class="cc-audit-nowrap">
                                {{ $evento['origen'] }}
                            </td>

                            <td>
                                {{ $evento['clasificacion'] }}
                            </td>

                            <td class="cc-audit-number">
                                {{ $evento['cantidad'] }}
                            </td>

                            <td>
                                {{ $evento['usuario'] }}
                            </td>

                            <td>
                                <button
                                    type="button"
                                    class="cc-btn-secondary"
                                    data-audit-detail-toggle
                                    aria-expanded="false"
                                    aria-controls="{{ $detalleId }}"
                                >
                                    Ver detalle
                                </button>
                            </td>
                        </tr>

                        <tr
                            id="{{ $detalleId }}"
                            class="cc-audit-detail-row"
                            data-audit-detail-row
                            hidden
                        >
                            <td
                                colspan="8"
                                class="cc-audit-detail-cell"
                            >
                                <div class="cc-audit-detail-panel cc-analytics-panel">
                                    <div class="cc-audit-detail-heading cc-analytics-panel-header">
                                        <div
                                            class="cc-audit-detail-title cc-analytics-panel-title"
                                        >
                                            Evento #{{ $evento['id'] }}
                                        </div>

                                        <div
                                            class="cc-audit-detail-note cc-analytics-panel-subtitle"
                                        >
                                            {{
                                                $evento[
                                                    'cantidad_consistente'
                                                ]
                                                    ? 'Cantidad declarada y detalles consistentes.'
                                                    : 'La cantidad declarada no coincide con los detalles registrados.'
                                            }}
                                        </div>
                                    </div>

                                    <div
                                        class="cc-audit-detail-table-wrapper"
                                    >
                                        <table
                                            class="cc-audit-detail-table"
                                        >
                                            <thead>
                                                <tr>
                                                    <th>Punto</th>
                                                    <th>Código anterior</th>
                                                    <th>Código nuevo</th>
                                                    <th>Motivo</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>

                                            <tbody>
                                                @forelse (
                                                    $evento['detalle']
                                                    as $detalle
                                                )
                                                    <tr>
                                                        <td>
                                                            {{
                                                                $detalle[
                                                                    'punto'
                                                                ]
                                                            }}
                                                        </td>

                                                        <td
                                                            class="cc-audit-nowrap"
                                                        >
                                                            {{
                                                                $detalle[
                                                                    'codigo_anterior'
                                                                ]
                                                            }}
                                                        </td>

                                                        <td
                                                            class="cc-audit-nowrap"
                                                        >
                                                            {{
                                                                $detalle[
                                                                    'codigo_nuevo'
                                                                ]
                                                            }}
                                                        </td>

                                                        <td>
                                                            {{
                                                                $detalle[
                                                                    'motivo'
                                                                ]
                                                            }}
                                                        </td>

                                                        <td
                                                            class="cc-audit-nowrap"
                                                        >
                                                            {{
                                                                $detalle[
                                                                    'fecha'
                                                                ]
                                                            }}
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="5">
                                                            No existen detalles
                                                            asociados al evento.
                                                        </td>
                                                    </tr>
                                                @endforelse
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
                $eventos
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
                                label.dataset.defaultLabel
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
                ).map(
                    checkbox => checkbox.value
                );
            }

            function filtrarDependientes() {
                const empresas =
                    empresasSeleccionadas();

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
                        const checkbox =
                            opcion.querySelector(
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
            )?.addEventListener(
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

            document.querySelectorAll(
                '[data-audit-detail-toggle]'
            ).forEach(function (button) {
                button.addEventListener(
                    'click',
                    function () {
                        const detailId =
                            button.getAttribute(
                                'aria-controls'
                            );

                        const detailRow =
                            document.getElementById(
                                detailId
                            );

                        if (! detailRow) {
                            return;
                        }

                        const willOpen =
                            detailRow.hidden;

                        detailRow.hidden = ! willOpen;

                        button.setAttribute(
                            'aria-expanded',
                            willOpen
                                ? 'true'
                                : 'false'
                        );

                        button.textContent = willOpen
                            ? 'Ocultar detalle'
                            : 'Ver detalle';
                    }
                );
            });

            const chartCanvas = document.querySelector(
                '[data-audit-category-chart]'
            );

            if (chartCanvas) {
                const chartSource = document.getElementById(
                    chartCanvas.dataset.chartSource
                );

                const chartData = JSON.parse(
                    chartSource?.textContent || '{}'
                );

                const grouping = document.querySelector(
                    '[data-audit-chart-group]'
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

                const range = document.querySelector(
                    '[data-chart-range]'
                );

                const status = document.querySelector(
                    '[data-chart-window-status]'
                );

                const detail = document.querySelector(
                    '[data-chart-detail]'
                );

                const legend = document.querySelector(
                    '[data-chart-legend]'
                );

                const tablePanel = document.querySelector(
                    '[data-chart-table-panel]'
                );

                const tableBody = document.querySelector(
                    '[data-chart-table-body]'
                );

                const palette = [
                    '#0f4c5c',
                    '#d97706',
                    '#2563eb',
                    '#7c3aed',
                    '#059669',
                    '#dc2626',
                    '#64748b',
                    '#0891b2',
                ];

                let mode = 'resumen';
                let startIndex = 0;
                let windowSize = 1;
                let hitAreas = [];

                function periods() {
                    return Array.isArray(chartData[mode])
                        ? chartData[mode]
                        : [];
                }

                function defaultWindowSize() {
                    return matchMedia(
                        '(max-width: 760px)'
                    ).matches
                        ? (
                            mode === 'dia'
                                ? 5
                                : mode === 'semana'
                                    ? 4
                                    : mode === 'mes'
                                        ? 4
                                        : 1
                        )
                        : (
                            mode === 'dia'
                                ? 14
                                : mode === 'semana'
                                    ? 12
                                    : mode === 'mes'
                                        ? 12
                                        : 1
                        );
                }

                function clampWindow() {
                    const data = periods();

                    windowSize = Math.max(
                        1,
                        Math.min(
                            windowSize,
                            Math.max(data.length, 1)
                        )
                    );

                    startIndex = Math.max(
                        0,
                        Math.min(
                            startIndex,
                            Math.max(
                                data.length - windowSize,
                                0
                            )
                        )
                    );
                }

                function visiblePeriods() {
                    clampWindow();

                    return periods().slice(
                        startIndex,
                        startIndex + windowSize
                    );
                }

                function prepareCanvas() {
                    const rect =
                        chartCanvas.getBoundingClientRect();

                    const ratio =
                        window.devicePixelRatio || 1;

                    const width = Math.max(
                        rect.width,
                        320
                    );

                    const height = Math.max(
                        rect.height,
                        336
                    );

                    chartCanvas.width =
                        Math.floor(width * ratio);

                    chartCanvas.height =
                        Math.floor(height * ratio);

                    const context =
                        chartCanvas.getContext('2d');

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

                function updateControls() {
                    const data = periods();

                    const endIndex = Math.min(
                        startIndex + windowSize,
                        data.length
                    );

                    if (status) {
                        status.textContent =
                            data.length === 0
                                ? 'Sin períodos'
                                : (
                                    'Períodos '
                                    + (startIndex + 1)
                                    + '–'
                                    + endIndex
                                    + ' de '
                                    + data.length
                                );
                    }

                    if (range) {
                        range.max = Math.max(
                            data.length - windowSize,
                            0
                        );

                        range.value = startIndex;
                        range.disabled =
                            data.length <= windowSize;
                    }

                    previousButton.disabled =
                        startIndex <= 0;

                    nextButton.disabled =
                        endIndex >= data.length;

                    zoomInButton.disabled =
                        windowSize <= 1;

                    zoomOutButton.disabled =
                        windowSize >= data.length;
                }

                function updateLegend() {
                    if (! legend) {
                        return;
                    }

                    legend.innerHTML = '';

                    const categories =
                        chartData.categorias || [];

                    categories.forEach(
                        function (category, index) {
                            const item =
                                document.createElement(
                                    'span'
                                );

                            item.className =
                                'cc-audit-chart-legend-item';

                            const swatch =
                                document.createElement(
                                    'span'
                                );

                            swatch.className =
                                'cc-audit-chart-legend-swatch';

                            swatch.style.backgroundColor =
                                palette[
                                    index % palette.length
                                ];

                            const label =
                                document.createElement(
                                    'span'
                                );

                            label.textContent = category;

                            item.appendChild(swatch);
                            item.appendChild(label);
                            legend.appendChild(item);
                        }
                    );
                }

                function updateTable(data) {
                    if (! tableBody) {
                        return;
                    }

                    tableBody.innerHTML = '';

                    data.forEach(function (period) {
                        period.categorias.forEach(
                            function (category) {
                                const row =
                                    document.createElement(
                                        'tr'
                                    );

                                [
                                    period.etiqueta,
                                    category.categoria,
                                    category.marchamos,
                                    Number(
                                        category.porcentaje
                                    ).toFixed(2) + ' %',
                                    category.eventos,
                                    category.unidades,
                                ].forEach(function (value) {
                                    const cell =
                                        document.createElement(
                                            'td'
                                        );

                                    cell.textContent = value;
                                    row.appendChild(cell);
                                });

                                tableBody.appendChild(row);
                            }
                        );
                    });
                }

                function drawSummary(
                    context,
                    width,
                    height,
                    period
                ) {
                    const padding = {
                        top: 20,
                        right: 80,
                        bottom: 24,
                        left: 180,
                    };

                    const chartWidth =
                        width
                        - padding.left
                        - padding.right;

                    const chartHeight =
                        height
                        - padding.top
                        - padding.bottom;

                    const rows =
                        period?.categorias || [];

                    const rowHeight =
                        chartHeight
                        / Math.max(rows.length, 1);

                    hitAreas = [];

                    rows.forEach(
                        function (category, index) {
                            const y =
                                padding.top
                                + index * rowHeight
                                + rowHeight * .2;

                            const barHeight =
                                rowHeight * .58;

                            const barWidth =
                                Number(
                                    category.porcentaje
                                )
                                / 100
                                * chartWidth;

                            const colorIndex = (
                                chartData.categorias || []
                            ).indexOf(
                                category.categoria
                            );

                            context.fillStyle =
                                palette[
                                    Math.max(
                                        colorIndex,
                                        0
                                    ) % palette.length
                                ];

                            context.fillRect(
                                padding.left,
                                y,
                                barWidth,
                                barHeight
                            );

                            context.fillStyle =
                                '#64748b';

                            context.font =
                                '11px sans-serif';

                            context.textAlign =
                                'right';

                            context.textBaseline =
                                'middle';

                            context.fillText(
                                category.categoria,
                                padding.left - 10,
                                y + barHeight / 2
                            );

                            context.textAlign =
                                'left';

                            context.fillText(
                                Number(
                                    category.porcentaje
                                ).toFixed(2)
                                + ' %',
                                padding.left
                                + barWidth
                                + 8,
                                y + barHeight / 2
                            );

                            hitAreas.push({
                                x: padding.left,
                                y,
                                width: Math.max(
                                    barWidth,
                                    3
                                ),
                                height: barHeight,
                                period,
                                category,
                            });
                        }
                    );
                }

                function drawTemporal(
                    context,
                    width,
                    height,
                    data
                ) {
                    const padding = {
                        top: 20,
                        right: 20,
                        bottom: 70,
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

                    context.font = '11px sans-serif';
                    context.textBaseline = 'middle';

                    for (
                        let index = 0;
                        index <= 4;
                        index++
                    ) {
                        const percentage =
                            100 - index * 25;

                        const y =
                            padding.top
                            + index / 4 * chartHeight;

                        context.strokeStyle =
                            '#dbe3ea';

                        context.beginPath();
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
                            '#64748b';

                        context.textAlign =
                            'right';

                        context.fillText(
                            percentage + ' %',
                            padding.left - 8,
                            y
                        );
                    }

                    const slotWidth =
                        chartWidth
                        / Math.max(data.length, 1);

                    const barWidth = Math.min(
                        slotWidth * .62,
                        64
                    );

                    hitAreas = [];

                    data.forEach(
                        function (period, periodIndex) {
                            const x =
                                padding.left
                                + periodIndex * slotWidth
                                + (
                                    slotWidth - barWidth
                                ) / 2;

                            let currentY =
                                padding.top + chartHeight;

                            period.categorias.forEach(
                                function (category) {
                                    const segmentHeight =
                                        Number(
                                            category.porcentaje
                                        )
                                        / 100
                                        * chartHeight;

                                    currentY -= segmentHeight;

                                    const colorIndex = (
                                        chartData.categorias
                                        || []
                                    ).indexOf(
                                        category.categoria
                                    );

                                    context.fillStyle =
                                        palette[
                                            Math.max(
                                                colorIndex,
                                                0
                                            ) % palette.length
                                        ];

                                    context.fillRect(
                                        x,
                                        currentY,
                                        barWidth,
                                        segmentHeight
                                    );

                                    hitAreas.push({
                                        x,
                                        y: currentY,
                                        width: barWidth,
                                        height: Math.max(
                                            segmentHeight,
                                            2
                                        ),
                                        period,
                                        category,
                                    });
                                }
                            );

                            context.save();
                            context.translate(
                                x + barWidth / 2,
                                height
                                - padding.bottom
                                + 12
                            );

                            context.rotate(-.55);
                            context.fillStyle =
                                '#64748b';

                            context.textAlign =
                                'right';

                            context.textBaseline =
                                'middle';

                            const label = String(
                                period.etiqueta || ''
                            );

                            context.fillText(
                                label.length > 18
                                    ? label.slice(0, 18)
                                        + '…'
                                    : label,
                                0,
                                0
                            );

                            context.restore();
                        }
                    );
                }

                function drawChart() {
                    const data = visiblePeriods();

                    const {
                        context,
                        width,
                        height,
                    } = prepareCanvas();

                    context.clearRect(
                        0,
                        0,
                        width,
                        height
                    );

                    if (mode === 'resumen') {
                        drawSummary(
                            context,
                            width,
                            height,
                            data[0]
                        );
                    } else {
                        drawTemporal(
                            context,
                            width,
                            height,
                            data
                        );
                    }

                    updateControls();
                    updateTable(data);
                    updateLegend();
                }

                grouping?.addEventListener(
                    'change',
                    function () {
                        mode = grouping.value;
                        startIndex = 0;
                        windowSize = Math.min(
                            defaultWindowSize(),
                            Math.max(
                                periods().length,
                                1
                            )
                        );

                        drawChart();
                    }
                );

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
                            1,
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
                            periods().length,
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
                        startIndex = 0;
                        windowSize = Math.min(
                            defaultWindowSize(),
                            Math.max(
                                periods().length,
                                1
                            )
                        );

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

                chartCanvas.addEventListener(
                    'mousemove',
                    function (event) {
                        const rect =
                            chartCanvas
                                .getBoundingClientRect();

                        const x =
                            event.clientX - rect.left;

                        const y =
                            event.clientY - rect.top;

                        const hit = hitAreas.find(
                            function (area) {
                                return x >= area.x
                                    && x <= area.x
                                        + area.width
                                    && y >= area.y
                                    && y <= area.y
                                        + area.height;
                            }
                        );

                        if (! hit || ! detail) {
                            return;
                        }

                        detail.textContent =
                            hit.period.etiqueta
                            + ' · '
                            + hit.category.categoria
                            + ': '
                            + hit.category.marchamos
                            + ' marchamos · '
                            + Number(
                                hit.category.porcentaje
                            ).toFixed(2)
                            + ' % · '
                            + hit.category.eventos
                            + ' eventos · '
                            + hit.category.unidades
                            + ' unidades';
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

                windowSize = Math.min(
                    defaultWindowSize(),
                    Math.max(
                        periods().length,
                        1
                    )
                );

                drawChart();
            }

        }
    );
</script>
