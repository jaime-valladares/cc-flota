<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar unidad | CC-Flota</title>

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
                                    Administrar unidad
                                </h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    href="{{ route(
                                        'unidades.administrar',
                                        request()->query()
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver a administrar
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form
                            method="GET"
                            action="{{ route('unidades.administrar.ventana') }}"
                            class="mb-5"
                        >
                            <input type="hidden" name="consultar" value="1">

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
                                            Buscar empresa o placa
                                        </label>

                                        <input
                                            id="busqueda"
                                            name="busqueda"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $busqueda ?? '' }}"
                                            maxlength="150"
                                            placeholder="Empresa o placa"
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
                                                                            (string) $empresa->id,
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
                                                                    {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                                </span>
                                                            </label>
                                                        @endforeach

                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <select class="cc-input" disabled>
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
                                            Placa
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
                                                    @if (! empty($placas))
                                                        {{ count($placas) }} seleccionadas
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

                                                    @foreach ($placasSelector as $placaOpcion)
                                                        <label
                                                            class="cc-filter-multiselect-option"
                                                            data-cc-filter-option
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                name="placas[]"
                                                                value="{{ $placaOpcion }}"
                                                                @checked(
                                                                    in_array(
                                                                        $placaOpcion,
                                                                        $placas ?? [],
                                                                        true
                                                                    )
                                                                )
                                                                data-cc-filter-checkbox
                                                            >

                                                            <span data-cc-filter-option-label>
                                                                {{ $placaOpcion }}
                                                            </span>
                                                        </label>
                                                    @endforeach

                                                </div>
                                            </div>
                                        </div>

                                        @error('placas')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        @error('placas.*')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label>
                                            Modelo de medición
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
                                                    @if (! empty($modelosMedicionSeleccionados))
                                                        {{ count($modelosMedicionSeleccionados) }} seleccionados
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

                                                    @foreach ($modelosMedicion as $valor => $texto)
                                                        <label
                                                            class="cc-filter-multiselect-option"
                                                            data-cc-filter-option
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                name="modelos_medicion[]"
                                                                value="{{ $valor }}"
                                                                @checked(
                                                                    in_array(
                                                                        $valor,
                                                                        $modelosMedicionSeleccionados ?? [],
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

                                        @error('modelos_medicion')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        @error('modelos_medicion.*')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="estado">
                                            Estado
                                        </label>

                                        <select
                                            id="estado"
                                            name="estado"
                                            class="cc-input"
                                        >
                                            <option value="">
                                                Todos
                                            </option>

                                            @foreach ($estadosUnidad as $valor => $texto)
                                                <option
                                                    value="{{ $valor }}"
                                                    @selected($estado === $valor)
                                                >
                                                    {{ $texto }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('estado')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                </div>

                                <div class="mt-5 flex w-full flex-wrap justify-end gap-3">
                                    <button
                                        type="submit"
                                        class="cc-btn-primary"
                                    >
                                        Consultar
                                    </button>

                                    <a
                                        href="{{ route('unidades.administrar.ventana') }}"
                                        class="cc-btn-secondary"
                                    >
                                        Limpiar
                                    </a>
                                </div>

                            </div>
                        </form>

                        @if ($hayFiltros && $unidades->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $unidades->firstItem() }}
                                </span>

                                -

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $unidades->lastItem() }}
                                </span>

                                de

                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $unidades->total() }}
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
                        @elseif ($unidades->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>

                                <p>
                                    No hay unidades que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-admin-result-list">
                                @foreach ($unidades as $unidad)
                                    <article class="cc-admin-result-card">
                                        <div class="cc-admin-result-grid">

                                            <div class="cc-admin-result-main">
                                                <div class="cc-admin-result-title-row">
                                                    <h5 class="cc-admin-result-title">
                                                        {{ $unidad->placa }}
                                                    </h5>

                                                    @if ($unidad->estado === 'registrada')
                                                        <span class="cc-badge cc-badge-pending">
                                                            Registrada
                                                        </span>
                                                    @elseif ($unidad->estado === 'activa')
                                                        <span class="cc-badge cc-badge-active">
                                                            Activa
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-inactive">
                                                            Inactiva
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="cc-admin-result-subtitle">
                                                    {{ $unidad->marca ?: 'Sin marca registrada' }}
                                                </div>
                                            </div>

                                            <div class="cc-admin-result-meta">
                                                <div class="cc-admin-result-label">
                                                    Empresa
                                                </div>

                                                @if ($unidad->empresa)
                                                    <div class="cc-admin-result-value">
                                                        {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                    </div>

                                                    @if ($unidad->empresa->nit ?? false)
                                                        <div class="cc-admin-result-value-muted">
                                                            NIT: {{ $unidad->empresa->nit }}
                                                        </div>
                                                    @endif
                                                @else
                                                    <div class="cc-admin-result-value-muted">
                                                        Sin empresa
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="cc-admin-result-meta">
                                                <div class="cc-admin-result-label">
                                                    Cobertura
                                                </div>

                                                <div class="cc-admin-result-value">
                                                    {{ $unidad->cantidad_tanques_con_licencia }}
                                                    de
                                                    {{ $unidad->total_tanques }}
                                                    tanques
                                                </div>

                                                <div class="cc-admin-result-value-muted">
                                                    {{ $unidad->modelo_medicion_texto }}
                                                </div>
                                            </div>

                                            <div class="cc-admin-result-actions">
                                                <a
                                                    href="{{ route(
                                                        'unidades.show.ventana',
                                                        array_merge(
                                                            request()->query(),
                                                            ['unidad' => $unidad]
                                                        )
                                                    ) }}"
                                                    class="cc-btn-primary cc-btn-result"
                                                >
                                                    Ver ficha
                                                </a>

                                                @if ($unidad->estado !== 'inactiva')
                                                    <a
                                                        href="{{ route(
                                                            'unidades.edit.ventana',
                                                            array_merge(
                                                                request()->query(),
                                                                ['unidad' => $unidad]
                                                            )
                                                        ) }}"
                                                        class="cc-btn-secondary cc-btn-result"
                                                    >
                                                        Editar
                                                    </a>
                                                @endif
                                            </div>

                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                {{ $unidades
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

                    const defaultLabel = label?.dataset.defaultLabel || 'Todos';

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

                    function closeAllExceptCurrent() {
                        document
                            .querySelectorAll('[data-cc-filter-multiselect]')
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
                        checkbox.addEventListener(
                            'change',
                            updateLabel
                        );
                    });

                    updateLabel();
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
        </script>
    </body>
</html>