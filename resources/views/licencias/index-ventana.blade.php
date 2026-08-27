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

        <title>Consulta licencias | CC-Flota</title>

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
    </head>

    <body class="antialiased">
        <div
            class="min-h-screen"
            style="background: var(--cc-bg-main);"
        >
            <div class="cc-page-wrapper">
                <div
                    class="cc-window-container"
                    style="max-width: 80rem;"
                >
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Consulta licencias
                                </h3>

                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-3">
                                <a
                                    href="{{ route(
                                        'licencias.index',
                                        request()->query()
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver al Sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="cc-alert-danger">
                                <div class="font-bold">
                                    Revise los filtros ingresados.
                                </div>

                                <ul class="mt-2 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>
                                            {{ $error }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="cc-summary-strip">
                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    {{ $hayFiltros ? 'Resultados' : 'Total licencias' }}
                                </span>

                                <span class="cc-summary-strip-value">
                                    {{ $resumenLicencias['total'] ?? $totalLicencias }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Activas
                                </span>

                                <span class="cc-summary-strip-value cc-summary-strip-value-success">
                                    {{ $resumenLicencias['activas'] ?? $totalActivas }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Inactivas
                                </span>

                                <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                                    {{ $resumenLicencias['inactivas'] ?? $totalInactivas }}
                                </span>
                            </div>
                        </div>

                        <form
                            method="GET"
                            action="{{ route('licencias.consulta.ventana') }}"
                            class="mb-5"
                        >
                            <input
                                type="hidden"
                                name="consultar"
                                value="1"
                            >

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div
                                    class="cc-form-section cc-form-section-compact"
                                    style="margin-top: 0;"
                                >
                                    <div class="cc-form-section-title">
                                        Filtros de consulta
                                    </div>
                                </div>

                                <div class="cc-standard-filter-grid cc-unidades-consulta-filter-grid">

                                    <div class="cc-field">
                                        <label for="busqueda">
                                            Buscar empresa o Nombre / Placa
                                        </label>

                                        <input
                                            id="busqueda"
                                            name="busqueda"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $busqueda ?? '' }}"
                                            maxlength="150"
                                            placeholder="Empresa o Nombre / Placa"
                                        >

                                        @error('busqueda')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label>
                                            Empresa
                                        </label>

                                        @if ($esUsuarioDieselCop)
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
                                                        data-default-label="Todas"
                                                    >
                                                        @if (! empty($empresaIds))
                                                            {{ count($empresaIds) }}
                                                            seleccionadas
                                                        @else
                                                            Todas
                                                        @endif
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

                                                        <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                                            <input
                                                                type="checkbox"
                                                                data-cc-filter-master
                                                            >

                                                            <span>
                                                                Seleccionar todo
                                                            </span>
                                                        </label>

                                                        @foreach ($empresas as $empresa)
                                                            <label
                                                                class="cc-filter-multiselect-option"
                                                                data-cc-filter-option
                                                            >
                                                                <input
                                                                    type="checkbox"
                                                                    name="empresa_ids[]"
                                                                    value="{{ $empresa->id }}"
                                                                    @checked(
                                                                        in_array(
                                                                            (int) $empresa->id,
                                                                            array_map(
                                                                                'intval',
                                                                                $empresaIds ?? []
                                                                            ),
                                                                            true
                                                                        )
                                                                    )
                                                                    data-cc-filter-checkbox
                                                                >

                                                                <span data-cc-filter-option-label>
                                                                    {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
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
                                                @foreach ($empresas as $empresa)
                                                    <option
                                                        value="{{ $empresa->id }}"
                                                        selected
                                                    >
                                                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
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

                                        @error('empresa_ids')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        @error('empresa_ids.*')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label>
                                            Nombre / Placa
                                        </label>

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
                                                    data-default-label="Todas"
                                                >
                                                    @if (! empty($unidadIds))
                                                        {{ count($unidadIds) }}
                                                        seleccionadas
                                                    @else
                                                        Todas
                                                    @endif
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

                                                    <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                                        <input
                                                            type="checkbox"
                                                            data-cc-filter-master
                                                        >

                                                        <span>
                                                            Seleccionar todo
                                                        </span>
                                                    </label>

                                                    @foreach ($unidadesSelector as $unidadOpcion)
                                                        <label
                                                            class="cc-filter-multiselect-option"
                                                            data-cc-filter-option
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
                                                            >

                                                            <span data-cc-filter-option-label>
                                                                {{ ($unidadOpcion->empresa_nombre ?? $unidadOpcion->empresa?->nombre_comercial ?? $unidadOpcion->empresa?->nombre_legal ?? "Empresa") . " · " . $unidadOpcion->placa }}
                                                            </span>
                                                        </label>
                                                    @endforeach

                                                </div>
                                            </div>
                                        </div>

                                        @error('unidad_ids')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        @error('unidad_ids.*')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label>
                                            Período de vigencia
                                        </label>

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
                                                >
                                                    @if (! empty($periodosVigenciaSeleccionados))
                                                        {{ count($periodosVigenciaSeleccionados) }}
                                                        seleccionados
                                                    @else
                                                        Todos
                                                    @endif
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

                                                    <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                                        <input
                                                            type="checkbox"
                                                            data-cc-filter-master
                                                        >

                                                        <span>
                                                            Seleccionar todo
                                                        </span>
                                                    </label>

                                                    @foreach ($periodosVigencia as $valor => $texto)
                                                        <label
                                                            class="cc-filter-multiselect-option"
                                                            data-cc-filter-option
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                name="periodos_vigencia[]"
                                                                value="{{ $valor }}"
                                                                @checked(
                                                                    in_array(
                                                                        (int) $valor,
                                                                        array_map(
                                                                            'intval',
                                                                            $periodosVigenciaSeleccionados ?? []
                                                                        ),
                                                                        true
                                                                    )
                                                                )
                                                                data-cc-filter-checkbox
                                                            >

                                                            <span data-cc-filter-option-label>
                                                                {{ $texto }}
                                                            </span>
                                                        </label>
                                                    @endforeach

                                                </div>
                                            </div>
                                        </div>

                                        @error('periodos_vigencia')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        @error('periodos_vigencia.*')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-standard-filter-actions">
                                        <button
                                            type="submit"
                                            class="cc-btn-primary"
                                        >
                                            Consultar
                                        </button>

                                        <a
                                            href="{{ route('licencias.consulta.ventana') }}"
                                            class="cc-btn-secondary"
                                        >
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $licencias->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $licencias->firstItem() }}
                                </span>

                                -

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $licencias->lastItem() }}
                                </span>

                                de

                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $licencias->total() }}
                                </span>
                            </div>
                        @endif

                        @if (! $hayFiltros)
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Consulta pendiente
                                </h5>

                                <p>
                                    Los resultados permanecerán vacíos hasta
                                    que realice una búsqueda.
                                </p>
                            </div>
                        @elseif ($licencias->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>

                                <p>
                                    No hay licencias que coincidan con los
                                    filtros seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-table-adaptive-wrapper">
                                <table
                                    class="cc-table-adaptive"
                                    style="min-width: 84rem;"
                                >
                                    <thead>
                                        <tr>
                                            <th style="width: 9rem;">
                                                Nombre / Placa
                                            </th>

                                            <th style="width: 14rem;">
                                                Empresa
                                            </th>

                                            <th style="width: 8rem;">
                                                Período
                                            </th>

                                            <th style="width: 10rem;">
                                                Activación
                                            </th>

                                            <th style="width: 12rem;">
                                                Vencimiento
                                            </th>

                                            <th style="width: 10rem;">
                                                Condición
                                            </th>

                                            <th style="width: 9rem;">
                                                Marchamos
                                            </th>

                                            <th style="width: 11rem;">
                                                Disponibilidad
                                            </th>

                                            <th style="width: 12rem;">
                                                Estado administrativo
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($licencias as $licencia)
                                            @php
                                                $unidad = $licencia->unidad;

                                                $condicionAdvertencia = in_array(
                                                    $licencia->condicion_vigencia,
                                                    [
                                                        'pendiente_activacion',
                                                        'proxima_vencer',
                                                    ],
                                                    true
                                                );

                                                $disponibilidadPendiente = $unidad
                                                    && in_array(
                                                        $unidad->disponibilidad_operativa,
                                                        [
                                                            'asignacion_inicial_pendiente',
                                                            'pendiente_activacion_operativa',
                                                        ],
                                                        true
                                                    );
                                            @endphp

                                            <tr>
                                                <td class="cc-table-adaptive-nowrap">
                                                    <div class="cc-table-adaptive-strong">
                                                        {{ $unidad->placa ?? 'Sin Nombre / Placa' }}
                                                    </div>

                                                    <div class="cc-table-adaptive-muted">
                                                        {{ $unidad->marca ?? 'Sin marca' }}
                                                    </div>
                                                </td>

                                                <td>
                                                    @if ($licencia->empresa)
                                                        <div class="cc-table-adaptive-strong">
                                                            {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                                        </div>
                                                    @else
                                                        <span class="text-[var(--cc-text-muted)]">
                                                            Sin empresa
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    {{ $licencia->periodo_vigencia_texto }}
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    {{ $licencia->fecha_activacion?->format('d/m/Y') ?? 'No registrada' }}
                                                </td>

                                                <td>
                                                    <div class="font-semibold whitespace-nowrap">
                                                        {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'No registrada' }}
                                                    </div>

                                                    <div class="cc-table-adaptive-muted whitespace-nowrap">
                                                        {{ $licencia->vencimiento_relativo_texto }}
                                                    </div>
                                                </td>

                                                <td>
                                                    <div
                                                        class="font-semibold whitespace-nowrap"
                                                        @style([
                                                            'color: var(--cc-success);'
                                                                => $licencia->esta_vigente,

                                                            'color: var(--cc-warning);'
                                                                => $condicionAdvertencia,

                                                            'color: var(--cc-danger);'
                                                                => ! $licencia->esta_vigente
                                                                    && ! $condicionAdvertencia,
                                                        ])
                                                    >
                                                        {{ $licencia->condicion_vigencia_texto }}
                                                    </div>
                                                </td>

                                                <td>
                                                    <div
                                                        class="font-semibold whitespace-nowrap"
                                                        @style([
                                                            'color: var(--cc-success);'
                                                                => $unidad?->asignacion_inicial_marchamos_completa,

                                                            'color: var(--cc-warning);'
                                                                => ! $unidad?->asignacion_inicial_marchamos_completa,
                                                        ])
                                                    >
                                                        {{ $unidad?->asignacion_inicial_marchamos_completa
                                                            ? 'Completa'
                                                            : 'Pendiente' }}
                                                    </div>

                                                    <div class="cc-table-adaptive-muted whitespace-nowrap">
                                                        {{ $unidad?->total_puntos_con_marchamo_asignado ?? 0 }}
                                                        de
                                                        {{ $unidad?->total_puntos_que_requieren_marchamo ?? 0 }}
                                                    </div>
                                                </td>

                                                <td>
                                                    @if (! $unidad)
                                                        <div class="font-semibold text-[var(--cc-danger)]">
                                                            No disponible
                                                        </div>
                                                    @else
                                                        <div
                                                            class="font-semibold whitespace-nowrap"
                                                            @style([
                                                                'color: var(--cc-success);'
                                                                    => $unidad->disponibilidad_operativa === 'operable',

                                                                'color: var(--cc-warning);'
                                                                    => $disponibilidadPendiente,

                                                                'color: var(--cc-danger);'
                                                                    => ! $disponibilidadPendiente
                                                                        && $unidad->disponibilidad_operativa !== 'operable',
                                                            ])
                                                        >
                                                            {{ $unidad->disponibilidad_operativa_texto }}
                                                        </div>
                                                    @endif
                                                </td>

                                                <td>
                                                    <div
                                                        class="font-semibold whitespace-nowrap"
                                                        @style([
                                                            'color: var(--cc-success);'
                                                                => $licencia->estado === 'activa',

                                                            'color: var(--cc-text-muted);'
                                                                => $licencia->estado === 'inactiva',
                                                        ])
                                                    >
                                                        {{ $licencia->estado_texto }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6">
                                {{ $licencias
                                    ->appends(
                                        array_merge(
                                            request()->query(),
                                            ['consultar' => 1]
                                        )
                                    )
                                    ->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <script>
            document
                .querySelectorAll('[data-cc-filter-multiselect]')
                .forEach(function (multiselect) {
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

                    const defaultLabel =
                        label?.dataset.defaultLabel || 'Todos';

                    function updateLabel() {
                        const selected = checkboxes.filter(
                            function (checkbox) {
                                return checkbox.checked;
                            }
                        );

                        if (selected.length === 0) {
                            label.textContent = defaultLabel;
                        } else if (selected.length === 1) {
                            const selectedOption =
                                selected[0].closest(
                                    '[data-cc-filter-option]'
                                );

                            const selectedLabel = selectedOption
                                ? selectedOption.querySelector(
                                    '[data-cc-filter-option-label]'
                                )
                                : null;

                            label.textContent = selectedLabel
                                ? selectedLabel.textContent.trim()
                                : '1 seleccionado';
                        } else {
                            label.textContent =
                                selected.length + ' seleccionados';
                        }

                        if (master) {
                            master.checked =
                                selected.length === checkboxes.length
                                && checkboxes.length > 0;

                            master.indeterminate =
                                selected.length > 0
                                && selected.length < checkboxes.length;
                        }
                    }

                    function closeOtherMenus() {
                        document
                            .querySelectorAll(
                                '[data-cc-filter-multiselect]'
                            )
                            .forEach(function (otherMultiselect) {
                                if (otherMultiselect === multiselect) {
                                    return;
                                }

                                const otherToggle =
                                    otherMultiselect.querySelector(
                                        '[data-cc-filter-toggle]'
                                    );

                                const otherMenu =
                                    otherMultiselect.querySelector(
                                        '[data-cc-filter-menu]'
                                    );

                                otherToggle?.classList.remove('is-open');
                                otherMenu?.classList.remove('is-open');
                            });
                    }

                    toggle?.addEventListener(
                        'click',
                        function () {
                            closeOtherMenus();

                            toggle.classList.toggle('is-open');
                            menu?.classList.toggle('is-open');
                        }
                    );

                    master?.addEventListener(
                        'change',
                        function () {
                            checkboxes.forEach(
                                function (checkbox) {
                                    checkbox.checked = master.checked;
                                }
                            );

                            updateLabel();
                        }
                    );

                    checkboxes.forEach(function (checkbox) {
                        checkbox.addEventListener(
                            'change',
                            updateLabel
                        );
                    });

                    updateLabel();
                });

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

                    document
                        .querySelectorAll(
                            '[data-cc-filter-toggle]'
                        )
                        .forEach(function (toggle) {
                            toggle.classList.remove('is-open');
                        });

                    document
                        .querySelectorAll(
                            '[data-cc-filter-menu]'
                        )
                        .forEach(function (menu) {
                            menu.classList.remove('is-open');
                        });
                }
            );

            document.addEventListener(
                'keydown',
                function (event) {
                    if (event.key !== 'Escape') {
                        return;
                    }

                    document
                        .querySelectorAll(
                            '[data-cc-filter-toggle]'
                        )
                        .forEach(function (toggle) {
                            toggle.classList.remove('is-open');
                        });

                    document
                        .querySelectorAll(
                            '[data-cc-filter-menu]'
                        )
                        .forEach(function (menu) {
                            menu.classList.remove('is-open');
                        });
                }
            );
        </script>
    </body>
</html>
