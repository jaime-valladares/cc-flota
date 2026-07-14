<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar motoristas | CC-Flota</title>

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
            <div class="cc-page-wrapper">
                <div
                    class="cc-window-container"
                    style="max-width: 80rem;"
                >
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Administrar motoristas
                                </h3>

                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    href="{{ route('motoristas.administrar', request()->query()) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver a Administrar
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
                                    {{ $hayFiltros ? 'Resultados' : 'Total motoristas' }}
                                </span>

                                <span class="cc-summary-strip-value">
                                    {{ $hayFiltros ? $motoristas->total() : $totalMotoristas }}
                                </span>
                            </div>

                            <div
                                class="cc-summary-strip-item"
                                style="justify-content: flex-start; gap: .65rem;"
                            >
                                <span class="cc-summary-strip-label">
                                    Activos
                                </span>

                                <span class="cc-summary-strip-value cc-summary-strip-value-success">
                                    {{ $motoristasActivos }}
                                </span>
                            </div>

                            <div
                                class="cc-summary-strip-item"
                                style="justify-content: flex-start; gap: .65rem;"
                            >
                                <span class="cc-summary-strip-label">
                                    Inactivos
                                </span>

                                <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                                    {{ $motoristasInactivos }}
                                </span>
                            </div>
                        </div>

                        <form
                            method="GET"
                            action="{{ route('motoristas.administrar.ventana') }}"
                            class="mb-5"
                        >
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div
                                    class="cc-form-section cc-form-section-compact"
                                    style="margin-top: 0;"
                                >
                                    <div class="cc-form-section-title">
                                        Filtros de administración
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
                                                                            array_map('strval', $empresaIds ?? []),
                                                                            true
                                                                        )
                                                                    )
                                                                    data-cc-filter-checkbox
                                                                >

                                                                <span data-cc-filter-option-label>
                                                                    {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
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
                                                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
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
                                            Motorista
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
                                                    @if (! empty($motoristaIds))
                                                        {{ count($motoristaIds) }} seleccionados
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

                                                    @foreach ($motoristasSelector as $motoristaOpcion)
                                                        <label
                                                            class="cc-filter-multiselect-option"
                                                            data-cc-filter-option
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                name="motorista_ids[]"
                                                                value="{{ $motoristaOpcion->id }}"
                                                                @checked(
                                                                    in_array(
                                                                        (string) $motoristaOpcion->id,
                                                                        array_map('strval', $motoristaIds ?? []),
                                                                        true
                                                                    )
                                                                )
                                                                data-cc-filter-checkbox
                                                            >

                                                            <span data-cc-filter-option-label>
                                                                {{ $motoristaOpcion->nombre_completo }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>

                                        @error('motorista_ids')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        @error('motorista_ids.*')
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
                                                            value="activo"
                                                            @checked(
                                                                in_array(
                                                                    'activo',
                                                                    $estadoIds ?? [],
                                                                    true
                                                                )
                                                            )
                                                            data-cc-filter-checkbox
                                                        >

                                                        <span data-cc-filter-option-label>
                                                            Activo
                                                        </span>
                                                    </label>

                                                    <label
                                                        class="cc-filter-multiselect-option"
                                                        data-cc-filter-option
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            name="estado_ids[]"
                                                            value="inactivo"
                                                            @checked(
                                                                in_array(
                                                                    'inactivo',
                                                                    $estadoIds ?? [],
                                                                    true
                                                                )
                                                            )
                                                            data-cc-filter-checkbox
                                                        >

                                                        <span data-cc-filter-option-label>
                                                            Inactivo
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
                                            href="{{ route('motoristas.administrar.ventana') }}"
                                            class="cc-btn-secondary"
                                        >
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $motoristas->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $motoristas->firstItem() }}
                                </span>

                                -

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $motoristas->lastItem() }}
                                </span>

                                de

                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $motoristas->total() }}
                                </span>
                            </div>
                        @endif

                        @if (! $hayFiltros)
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Administración pendiente
                                </h5>

                                <p>
                                    Seleccione uno o más filtros para cargar los motoristas.
                                </p>
                            </div>
                        @elseif ($motoristas->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>

                                <p>
                                    No hay motoristas que coincidan con los filtros seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-table-adaptive-wrapper">
                                <div
                                    class="cc-admin-result-list"
                                    style="min-width: 92rem;"
                                >
                                    @foreach ($motoristas as $motorista)
                                        <article
                                            class="cc-admin-result-card"
                                            style="min-width: 92rem; box-sizing: border-box;"
                                        >
                                            <div
                                                style="display: grid; grid-template-columns: 18rem 17rem 13rem 10rem 8rem 16rem; gap: 1rem; align-items: center;"
                                            >

                                                <div style="min-width: 0;">
                                                    <div class="cc-admin-result-label">
                                                        Motorista
                                                    </div>

                                                    <h5
                                                        class="cc-admin-result-title"
                                                        style="margin: 0; white-space: nowrap;"
                                                    >
                                                        {{ $motorista->nombre_completo }}
                                                    </h5>
                                                </div>

                                                <div style="min-width: 0;">
                                                    <div class="cc-admin-result-label">
                                                        Empresa
                                                    </div>

                                                    <div
                                                        class="cc-admin-result-value"
                                                        style="white-space: nowrap;"
                                                    >
                                                        {{ $motorista->empresa->nombre_comercial ?: $motorista->empresa->nombre_legal }}
                                                    </div>
                                                </div>

                                                <div style="min-width: 0;">
                                                    <div class="cc-admin-result-label">
                                                        Licencia
                                                    </div>

                                                    <div
                                                        class="cc-admin-result-value"
                                                        style="white-space: nowrap;"
                                                    >
                                                        {{ $motorista->licencia }}
                                                    </div>
                                                </div>

                                                <div style="min-width: 0;">
                                                    <div class="cc-admin-result-label">
                                                        Teléfono
                                                    </div>

                                                    <div
                                                        class="cc-admin-result-value"
                                                        style="white-space: nowrap;"
                                                    >
                                                        {{ $motorista->telefono }}
                                                    </div>
                                                </div>

                                                <div style="min-width: 0;">
                                                    <div class="cc-admin-result-label">
                                                        Estado
                                                    </div>

                                                    <div style="white-space: nowrap;">
                                                        @if ($motorista->estado === 'activo')
                                                            <span class="cc-badge cc-badge-active">
                                                                Activo
                                                            </span>
                                                        @else
                                                            <span class="cc-badge cc-badge-inactive">
                                                                Inactivo
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div
                                                    style="display: flex; gap: .75rem; justify-content: flex-end; align-items: center; white-space: nowrap; min-width: 16rem;"
                                                >
                                                    <a
                                                        href="{{ route(
                                                            'motoristas.show.ventana',
                                                            array_merge(
                                                                ['motorista' => $motorista],
                                                                request()->query()
                                                            )
                                                        ) }}"
                                                        class="cc-btn-secondary cc-btn-result"
                                                    >
                                                        Ver ficha
                                                    </a>

                                                    @if ($motorista->estado === 'activo')
                                                        <a
                                                            href="{{ route(
                                                                'motoristas.edit.ventana',
                                                                array_merge(
                                                                    ['motorista' => $motorista],
                                                                    request()->query()
                                                                )
                                                            ) }}"
                                                            class="cc-btn-primary cc-btn-result"
                                                        >
                                                            Editar
                                                        </a>
                                                    @endif
                                                </div>

                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-6">
                                {{ $motoristas
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
                            const seleccionados = checkboxes.filter(function (checkbox) {
                                return checkbox.checked;
                            });

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
                                .querySelectorAll('[data-cc-filter-multiselect]')
                                .forEach(function (otroMultiselect) {
                                    if (otroMultiselect === multiselect) {
                                        return;
                                    }

                                    otroMultiselect
                                        .querySelector('[data-cc-filter-toggle]')
                                        ?.classList.remove('is-open');

                                    otroMultiselect
                                        .querySelector('[data-cc-filter-menu]')
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