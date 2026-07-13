<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Consulta de empresas | CC-Flota</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
            <div class="cc-page-wrapper">
                <div class="cc-window-container" style="max-width: 80rem;">
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Consulta de empresas
                                </h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('empresas.index', request()->query()) }}"
                                   class="cc-btn-secondary cc-btn-wide">
                                    Volver al Sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="cc-summary-strip">
                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    {{ $hayFiltros ? 'Resultados' : 'Total empresas' }}
                                </span>

                                <span class="cc-summary-strip-value">
                                    {{ $hayFiltros ? $empresas->total() : $totalEmpresas }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Activas
                                </span>

                                <span class="cc-summary-strip-value cc-summary-strip-value-success">
                                    {{ $empresasActivas }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Inactivas
                                </span>

                                <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                                    {{ $empresasInactivas }}
                                </span>
                            </div>
                        </div>

                        <form method="GET"
                              action="{{ route('empresas.consulta.ventana') }}"
                              class="mb-5">

                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Filtros de consulta
                                    </div>
                                </div>

                                <div class="cc-standard-filter-grid">

                                    <div class="cc-field">
                                        <label for="busqueda_empresa">
                                            Búsqueda de empresa por nombre
                                        </label>

                                        <input
                                            id="busqueda_empresa"
                                            name="busqueda_empresa"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $busquedaEmpresa ?? $busqueda ?? '' }}"
                                            maxlength="150"
                                            placeholder="Nombre legal o comercial"
                                        >

                                        @error('busqueda_empresa')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror

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
                                            <div class="cc-filter-multiselect"
                                                 data-cc-filter-multiselect>

                                                <button type="button"
                                                        class="cc-filter-multiselect-toggle"
                                                        data-cc-filter-toggle>

                                                    <span data-cc-filter-label
                                                          data-default-label="Todas">
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

                                                <div class="cc-filter-multiselect-menu"
                                                     data-cc-filter-menu>

                                                    <div class="cc-filter-multiselect-list">

                                                        <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                                            <input type="checkbox"
                                                                   data-cc-filter-master>

                                                            <span>
                                                                Seleccionar todo
                                                            </span>
                                                        </label>

                                                        @foreach ($empresasSelector as $empresaOpcion)
                                                            <label class="cc-filter-multiselect-option"
                                                                   data-cc-filter-option>

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
                                            <select class="cc-input" disabled>
                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option value="{{ $empresaOpcion->id }}" selected>
                                                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            @foreach ($empresaIds ?? [] as $empresaSeleccionadaId)
                                                <input type="hidden"
                                                       name="empresa_ids[]"
                                                       value="{{ $empresaSeleccionadaId }}">
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
                                        <label for="estado">
                                            Estado
                                        </label>

                                        <select id="estado"
                                                name="estado"
                                                class="cc-input">

                                            <option value="">
                                                Todos
                                            </option>

                                            <option value="activa"
                                                    @selected($estado === 'activa')>
                                                Activas
                                            </option>

                                            <option value="inactiva"
                                                    @selected($estado === 'inactiva')>
                                                Inactivas
                                            </option>
                                        </select>

                                        @error('estado')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-standard-filter-actions">
                                        <button type="submit"
                                                class="cc-btn-primary">
                                            Consultar
                                        </button>

                                        <a href="{{ route('empresas.consulta.ventana') }}"
                                           class="cc-btn-secondary">
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $empresas->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $empresas->firstItem() }}
                                </span>

                                -

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $empresas->lastItem() }}
                                </span>

                                de

                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $empresas->total() }}
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
                        @elseif ($empresas->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>

                                <p>
                                    No hay empresas que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-table-adaptive-wrapper">
                                <table class="cc-table-adaptive"
                                       style="min-width: 78rem;">

                                    <thead>
                                        <tr>
                                            <th style="width: 26%;">
                                                Nombre legal
                                            </th>

                                            <th style="width: 20%;">
                                                Nombre comercial
                                            </th>

                                            <th style="width: 20%;">
                                                Contacto
                                            </th>

                                            <th style="width: 16%;">
                                                Teléfono
                                            </th>

                                            <th style="width: 18%;">
                                                Unidades
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($empresas as $empresa)
                                            @php
                                                $unidadesActivas = $empresa->unidades_activas_count
                                                    ?? $empresa->unidadesActivas_count
                                                    ?? null;

                                                $unidadesRegistradas = $empresa->unidades_registradas_count
                                                    ?? $empresa->unidadesRegistradas_count
                                                    ?? null;

                                                if (is_null($unidadesActivas) || is_null($unidadesRegistradas)) {
                                                    $unidadesActivas = \App\Models\Unidad::query()
                                                        ->where('empresa_id', $empresa->id)
                                                        ->where('estado', 'activa')
                                                        ->count();

                                                    $unidadesRegistradas = \App\Models\Unidad::query()
                                                        ->where('empresa_id', $empresa->id)
                                                        ->where('estado', 'registrada')
                                                        ->count();
                                                }
                                            @endphp

                                            <tr>
                                                <td class="cc-table-adaptive-nowrap">
                                                    <div class="cc-table-adaptive-strong">
                                                        {{ $empresa->nombre_legal }}
                                                    </div>
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    {{ $empresa->nombre_comercial ?: '—' }}
                                                </td>

                                                <td class="cc-table-adaptive-break">
                                                    <div class="cc-table-adaptive-strong">
                                                        {{ $empresa->poc_nombre ?: '—' }}
                                                    </div>

                                                    @if ($empresa->correo_empresa)
                                                        <div class="cc-table-adaptive-muted">
                                                            {{ $empresa->correo_empresa }}
                                                        </div>
                                                    @endif
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    {{ $empresa->poc_telefono ?: $empresa->telefono_empresa ?: '—' }}
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    <div class="cc-table-adaptive-strong">
                                                        {{ $unidadesActivas }} activas
                                                    </div>

                                                    <div class="cc-table-adaptive-muted">
                                                        {{ $unidadesRegistradas }} registradas
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6">
                                {{ $empresas
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
            document.querySelectorAll('[data-cc-filter-multiselect]').forEach(function (multiselect) {
                const toggle = multiselect.querySelector('[data-cc-filter-toggle]');
                const menu = multiselect.querySelector('[data-cc-filter-menu]');
                const label = multiselect.querySelector('[data-cc-filter-label]');
                const master = multiselect.querySelector('[data-cc-filter-master]');

                const checkboxes = Array.from(
                    multiselect.querySelectorAll('[data-cc-filter-checkbox]')
                );

                const defaultLabel = label?.dataset.defaultLabel || 'Todas';

                function updateLabel() {
                    const selected = checkboxes.filter(function (checkbox) {
                        return checkbox.checked;
                    });

                    if (selected.length === 0) {
                        label.textContent = defaultLabel;
                    } else if (selected.length === 1) {
                        const selectedOption = selected[0].closest(
                            '[data-cc-filter-option]'
                        );

                        const selectedLabel = selectedOption
                            ? selectedOption.querySelector(
                                '[data-cc-filter-option-label]'
                            )
                            : null;

                        label.textContent = selectedLabel
                            ? selectedLabel.textContent.trim()
                            : '1 seleccionada';
                    } else {
                        label.textContent = selected.length + ' seleccionadas';
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

                function closeAllExceptCurrent() {
                    document
                        .querySelectorAll('[data-cc-filter-multiselect]')
                        .forEach(function (otherMultiselect) {
                            if (otherMultiselect === multiselect) {
                                return;
                            }

                            const otherToggle = otherMultiselect.querySelector(
                                '[data-cc-filter-toggle]'
                            );

                            const otherMenu = otherMultiselect.querySelector(
                                '[data-cc-filter-menu]'
                            );

                            if (otherToggle && otherMenu) {
                                otherToggle.classList.remove('is-open');
                                otherMenu.classList.remove('is-open');
                            }
                        });
                }

                if (toggle && menu) {
                    toggle.addEventListener('click', function () {
                        closeAllExceptCurrent();

                        toggle.classList.toggle('is-open');
                        menu.classList.toggle('is-open');
                    });
                }

                if (master) {
                    master.addEventListener('change', function () {
                        checkboxes.forEach(function (checkbox) {
                            checkbox.checked = master.checked;
                        });

                        updateLabel();
                    });
                }

                checkboxes.forEach(function (checkbox) {
                    checkbox.addEventListener('change', updateLabel);
                });

                updateLabel();
            });

            document.addEventListener('click', function (event) {
                if (event.target.closest('[data-cc-filter-multiselect]')) {
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
        </script>
    </body>
</html>