<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Consulta de gasolineras externas | CC-Flota</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
            rel="stylesheet"
        >

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        <div
            class="min-h-screen"
            style="background: var(--cc-bg-main);"
        >
            <div class="cc-page-wrapper cc-va-scope">
                <div
                    class="cc-window-container cc-operational-container"
                >
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Consulta de gasolineras externas
                                </h3>

                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    href="{{ route(
                                        'gasolineras-externas.index',
                                        request()->query()
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver al Sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div
                            class="cc-summary-strip"
                            style="display: grid; grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr)); gap: 1rem; align-items: stretch;"
                        >
                            <div
                                class="cc-summary-strip-item"
                                style="justify-content: flex-start; gap: .65rem;"
                            >
                                <span class="cc-summary-strip-label">
                                    {{ $hayFiltros ? 'Resultados' : 'Total gasolineras' }}
                                </span>

                                <span class="cc-summary-strip-value">
                                    {{ $hayFiltros
                                        ? $gasolinerasExternas->total()
                                        : $totalGasolinerasExternas }}
                                </span>
                            </div>

                            <div
                                class="cc-summary-strip-item"
                                style="justify-content: flex-start; gap: .65rem;"
                            >
                                <span class="cc-summary-strip-label">
                                    Activas
                                </span>

                                <span class="cc-summary-strip-value cc-summary-strip-value-success">
                                    {{ $gasolinerasExternasActivas }}
                                </span>
                            </div>

                            <div
                                class="cc-summary-strip-item"
                                style="justify-content: flex-start; gap: .65rem;"
                            >
                                <span class="cc-summary-strip-label">
                                    Inactivas
                                </span>

                                <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                                    {{ $gasolinerasExternasInactivas }}
                                </span>
                            </div>
                        </div>

                        <form
                            method="GET"
                            action="{{ route('gasolineras-externas.consulta.ventana') }}"
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

                                <div class="cc-standard-filter-grid">

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
                                                            {{ count($empresaIds) }} seleccionadas
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
                                                                            (string) $empresaOpcion->id,
                                                                            array_map(
                                                                                'strval',
                                                                                $empresaIds ?? []
                                                                            ),
                                                                            true
                                                                        )
                                                                    )
                                                                    data-cc-filter-checkbox
                                                                >

                                                                <span data-cc-filter-option-label>
                                                                    {{ $empresaOpcion->nombre_comercial
                                                                        ?: $empresaOpcion->nombre_legal }}
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
                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option selected>
                                                        {{ $empresaOpcion->nombre_comercial
                                                            ?: $empresaOpcion->nombre_legal }}
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
                                            Gasolinera externa
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
                                                    @if (! empty($gasolineraExternaIds))
                                                        {{ count($gasolineraExternaIds) }} seleccionadas
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

                                                    @foreach ($gasolinerasExternasSelector as $gasolineraOpcion)
                                                        <label
                                                            class="cc-filter-multiselect-option"
                                                            data-cc-filter-option
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                name="gasolinera_externa_ids[]"
                                                                value="{{ $gasolineraOpcion->id }}"
                                                                @checked(
                                                                    in_array(
                                                                        (string) $gasolineraOpcion->id,
                                                                        array_map(
                                                                            'strval',
                                                                            $gasolineraExternaIds ?? []
                                                                        ),
                                                                        true
                                                                    )
                                                                )
                                                                data-cc-filter-checkbox
                                                            >

                                                            <span data-cc-filter-option-label>
                                                                {{ $gasolineraOpcion->compania }}
                                                                —
                                                                {{ $gasolineraOpcion->direccion }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        @error('gasolinera_externa_ids')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        @error('gasolinera_externa_ids.*')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label>
                                            Estado
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
                                                    @if (! empty($estadoIds))
                                                        {{ count($estadoIds) }} seleccionados
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

                                                    <label
                                                        class="cc-filter-multiselect-option"
                                                        data-cc-filter-option
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            name="estado_ids[]"
                                                            value="activa"
                                                            @checked(
                                                                in_array(
                                                                    'activa',
                                                                    $estadoIds ?? [],
                                                                    true
                                                                )
                                                            )
                                                            data-cc-filter-checkbox
                                                        >

                                                        <span data-cc-filter-option-label>
                                                            Activa
                                                        </span>
                                                    </label>

                                                    <label
                                                        class="cc-filter-multiselect-option"
                                                        data-cc-filter-option
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            name="estado_ids[]"
                                                            value="inactiva"
                                                            @checked(
                                                                in_array(
                                                                    'inactiva',
                                                                    $estadoIds ?? [],
                                                                    true
                                                                )
                                                            )
                                                            data-cc-filter-checkbox
                                                        >

                                                        <span data-cc-filter-option-label>
                                                            Inactiva
                                                        </span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        @error('estado_ids')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        @error('estado_ids.*')
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
                                            href="{{ route('gasolineras-externas.consulta.ventana') }}"
                                            class="cc-btn-secondary"
                                        >
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $gasolinerasExternas->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $gasolinerasExternas->firstItem() }}
                                </span>

                                -

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $gasolinerasExternas->lastItem() }}
                                </span>

                                de

                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $gasolinerasExternas->total() }}
                                </span>
                            </div>
                        @endif

                        @if (! $hayFiltros)
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Consulta pendiente
                                </h5>

                                <p>
                                    Los resultados permanecerán vacíos hasta que realice una búsqueda.
                                </p>
                            </div>
                        @elseif ($gasolinerasExternas->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>

                                <p>
                                    No hay gasolineras externas que coincidan con los filtros seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-table-adaptive-wrapper">
                                <table
                                    class="cc-table-adaptive"
                                    style="min-width: 72rem;"
                                >
                                    <thead>
                                        <tr>
                                            <th style="width: 14rem;">
                                                Compañía
                                            </th>

                                            <th style="width: 17rem;">
                                                Empresa
                                            </th>

                                            <th style="width: 32rem;">
                                                Dirección
                                            </th>

                                            <th style="width: 9rem;">
                                                Estado
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($gasolinerasExternas as $gasolineraExterna)
                                            <tr>
                                                <td>
                                                    <span class="cc-table-strong">
                                                        {{ $gasolineraExterna->compania }}
                                                    </span>
                                                </td>

                                                <td>
                                                    {{ $gasolineraExterna->empresa->nombre_comercial
                                                        ?: $gasolineraExterna->empresa->nombre_legal }}
                                                </td>

                                                <td>
                                                    {{ $gasolineraExterna->direccion }}
                                                </td>

                                                <td>
                                                    @if ($gasolineraExterna->estado === 'activa')
                                                        <span class="cc-badge cc-badge-active">
                                                            Activa
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-inactive">
                                                            Inactiva
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6">
                                {{ $gasolinerasExternas
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
            document.addEventListener('DOMContentLoaded', function () {
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

                        function actualizarEtiqueta() {
                            const seleccionados = checkboxes.filter(
                                function (checkbox) {
                                    return checkbox.checked;
                                }
                            );

                            if (! label) {
                                return;
                            }

                            if (seleccionados.length === 0) {
                                label.textContent = defaultLabel;
                            } else if (seleccionados.length === 1) {
                                const opcion = seleccionados[0].closest(
                                    '[data-cc-filter-option]'
                                );

                                const texto = opcion?.querySelector(
                                    '[data-cc-filter-option-label]'
                                );

                                label.textContent = texto
                                    ? texto.textContent.trim()
                                    : '1 seleccionado';
                            } else {
                                label.textContent =
                                    seleccionados.length + ' seleccionados';
                            }

                            if (master) {
                                master.checked =
                                    seleccionados.length === checkboxes.length
                                    && checkboxes.length > 0;

                                master.indeterminate =
                                    seleccionados.length > 0
                                    && seleccionados.length < checkboxes.length;
                            }
                        }

                        function cerrarOtros() {
                            document
                                .querySelectorAll(
                                    '[data-cc-filter-multiselect]'
                                )
                                .forEach(function (otroMultiselect) {
                                    if (otroMultiselect === multiselect) {
                                        return;
                                    }

                                    otroMultiselect
                                        .querySelector(
                                            '[data-cc-filter-toggle]'
                                        )
                                        ?.classList.remove('is-open');

                                    otroMultiselect
                                        .querySelector(
                                            '[data-cc-filter-menu]'
                                        )
                                        ?.classList.remove('is-open');
                                });
                        }

                        if (toggle && menu) {
                            toggle.addEventListener('click', function () {
                                cerrarOtros();

                                toggle.classList.toggle('is-open');
                                menu.classList.toggle('is-open');
                            });
                        }

                        if (master) {
                            master.addEventListener('change', function () {
                                checkboxes.forEach(function (checkbox) {
                                    checkbox.checked = master.checked;
                                });

                                actualizarEtiqueta();
                            });
                        }

                        checkboxes.forEach(function (checkbox) {
                            checkbox.addEventListener(
                                'change',
                                actualizarEtiqueta
                            );
                        });

                        actualizarEtiqueta();
                    });

                document.addEventListener('click', function (event) {
                    if (
                        event.target.closest(
                            '[data-cc-filter-multiselect]'
                        )
                    ) {
                        return;
                    }

                    document
                        .querySelectorAll('[data-cc-filter-toggle]')
                        .forEach(function (toggle) {
                            toggle.classList.remove('is-open');
                        });

                    document
                        .querySelectorAll('[data-cc-filter-menu]')
                        .forEach(function (menu) {
                            menu.classList.remove('is-open');
                        });
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key !== 'Escape') {
                        return;
                    }

                    document
                        .querySelectorAll('[data-cc-filter-toggle]')
                        .forEach(function (toggle) {
                            toggle.classList.remove('is-open');
                        });

                    document
                        .querySelectorAll('[data-cc-filter-menu]')
                        .forEach(function (menu) {
                            menu.classList.remove('is-open');
                        });
                });
            });
        </script>
    </body>
</html>