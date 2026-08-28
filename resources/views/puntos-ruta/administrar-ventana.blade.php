<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar punto de ruta | CC-Flota</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

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
            <div class="cc-page-wrapper cc-va-scope">
                <div
                    class="cc-window-container cc-operational-container"
                >
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Administrar punto de ruta
                                </h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    href="{{ route(
                                        'puntos-ruta.administrar',
                                        request()->query()
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="cc-alert cc-alert-danger">
                                <div class="font-bold">
                                    Revise la información proporcionada.
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

                        <form
                            method="GET"
                            action="{{ route(
                                'puntos-ruta.administrar.ventana'
                            ) }}"
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
                                                        data-singular-suffix="seleccionada"
                                                        data-plural-suffix="seleccionadas"
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
                                                @forelse ($empresasSelector as $empresaOpcion)
                                                    <option
                                                        value="{{ $empresaOpcion->id }}"
                                                        selected
                                                    >
                                                        {{ $empresaOpcion->nombre_comercial
                                                            ?: $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @empty
                                                    <option selected>
                                                        Empresa no disponible
                                                    </option>
                                                @endforelse
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
                                            Punto de ruta
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
                                                    data-singular-suffix="seleccionado"
                                                    data-plural-suffix="seleccionados"
                                                >
                                                    @if (! empty($puntoRutaIds))
                                                        {{ count($puntoRutaIds) }}
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

                                                    @foreach ($puntosRutaSelector as $puntoRutaOpcion)
                                                        @php
                                                            $nombreEmpresaPunto =
                                                                $puntoRutaOpcion->empresa?->nombre_comercial
                                                                ?: $puntoRutaOpcion->empresa?->nombre_legal;
                                                        @endphp

                                                        <label
                                                            class="cc-filter-multiselect-option"
                                                            data-cc-filter-option
                                                        >
                                                            <input
                                                                type="checkbox"
                                                                name="punto_ruta_ids[]"
                                                                value="{{ $puntoRutaOpcion->id }}"
                                                                @checked(
                                                                    in_array(
                                                                        (string) $puntoRutaOpcion->id,
                                                                        array_map(
                                                                            'strval',
                                                                            $puntoRutaIds ?? []
                                                                        ),
                                                                        true
                                                                    )
                                                                )
                                                                data-cc-filter-checkbox
                                                            >

                                                            <span data-cc-filter-option-label>
                                                                {{ $puntoRutaOpcion->nombre }}

                                                                @if (
                                                                    $esUsuarioDieselCop
                                                                    && $nombreEmpresaPunto
                                                                )
                                                                    — {{ $nombreEmpresaPunto }}
                                                                @endif
                                                            </span>
                                                        </label>
                                                    @endforeach

                                                </div>
                                            </div>
                                        </div>

                                        @error('punto_ruta_ids')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror

                                        @error('punto_ruta_ids.*')
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

                                            <option
                                                value="activo"
                                                @selected($estado === 'activo')
                                            >
                                                Activos
                                            </option>

                                            <option
                                                value="inactivo"
                                                @selected($estado === 'inactivo')
                                            >
                                                Inactivos
                                            </option>
                                        </select>

                                        @error('estado')
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
                                            href="{{ route(
                                                'puntos-ruta.administrar.ventana'
                                            ) }}"
                                            class="cc-btn-secondary"
                                        >
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $puntosRuta->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $puntosRuta->firstItem() }}
                                </span>

                                -

                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $puntosRuta->lastItem() }}
                                </span>

                                de

                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $puntosRuta->total() }}
                                </span>
                            </div>
                        @endif

                        @if (! $hayFiltros)
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Consulta pendiente
                                </h5>

                                <p>
                                    Los resultados permanecerán vacíos hasta que
                                    realice una búsqueda.
                                </p>
                            </div>
                        @elseif ($puntosRuta->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>

                                <p>
                                    No hay puntos de ruta que coincidan con los
                                    criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-admin-result-list">
                                @foreach ($puntosRuta as $puntoRuta)
                                    @php
                                        $nombreEmpresa =
                                            $puntoRuta->empresa?->nombre_comercial
                                            ?: $puntoRuta->empresa?->nombre_legal
                                            ?: 'Sin empresa';

                                        $puntoActivo =
                                            $puntoRuta->estado === 'activo';

                                        $parametrosFicha = array_merge(
                                            request()->query(),
                                            [
                                                'puntoRuta' => $puntoRuta,
                                            ]
                                        );
                                    @endphp

                                    <article class="cc-admin-result-card">
                                        <div class="cc-admin-result-grid">

                                            <div class="cc-admin-result-main">
                                                <div class="cc-admin-result-title-row">
                                                    <h5 class="cc-admin-result-title">
                                                        {{ $puntoRuta->nombre }}
                                                    </h5>

                                                    @if ($puntoActivo)
                                                        <span class="cc-badge cc-badge-active">
                                                            Activo
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-inactive">
                                                            Inactivo
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="cc-admin-result-subtitle">
                                                    {{ $nombreEmpresa }}
                                                </div>
                                            </div>

                                            <div class="cc-admin-result-meta">
                                                <div class="cc-admin-result-label">
                                                    Dirección
                                                </div>

                                                <div
                                                    class="cc-admin-result-value"
                                                    style="
                                                        white-space: normal;
                                                        overflow-wrap: anywhere;
                                                        line-height: 1.45;
                                                    "
                                                >
                                                    {{ $puntoRuta->direccion
                                                        ?: 'Sin dirección registrada' }}
                                                </div>
                                            </div>

                                            <div class="cc-admin-result-meta">
                                                <div class="cc-admin-result-label">
                                                    Disponibilidad
                                                </div>

                                                <div class="cc-admin-result-value">
                                                    {{ $puntoActivo
                                                        ? 'Disponible para rutas'
                                                        : 'Solo consulta' }}
                                                </div>

                                                <div class="cc-admin-result-value-muted">
                                                    {{ $puntoActivo
                                                        ? 'Puede utilizarse como origen o destino'
                                                        : 'Debe reactivarse antes de utilizarse' }}
                                                </div>
                                            </div>

                                            <div class="cc-admin-result-actions">
                                                <a
                                                    href="{{ route(
                                                        'puntos-ruta.show.ventana',
                                                        $parametrosFicha
                                                    ) }}"
                                                    class="cc-btn-primary cc-btn-result"
                                                >
                                                    Ver ficha
                                                </a>

                                                @if ($puntoActivo)
                                                    <a
                                                        href="{{ route(
                                                            'puntos-ruta.edit.ventana',
                                                            $parametrosFicha
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
                                {{ $puntosRuta
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

                    const singularSuffix =
                        label?.dataset.singularSuffix || 'seleccionado';

                    const pluralSuffix =
                        label?.dataset.pluralSuffix || 'seleccionados';

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
                                : `1 ${singularSuffix}`;
                        } else {
                            label.textContent =
                                selected.length
                                + ' '
                                + pluralSuffix;
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
                            .querySelectorAll(
                                '[data-cc-filter-multiselect]'
                            )
                            .forEach(function (otherMultiselect) {
                                if (
                                    otherMultiselect
                                    === multiselect
                                ) {
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
                                    otherToggle.classList.remove(
                                        'is-open'
                                    );

                                    otherMenu.classList.remove(
                                        'is-open'
                                    );
                                }
                            });
                    }

                    if (toggle && menu) {
                        toggle.addEventListener(
                            'click',
                            function () {
                                closeAllExceptCurrent();

                                toggle.classList.toggle('is-open');
                                menu.classList.toggle('is-open');
                            }
                        );
                    }

                    if (master) {
                        master.addEventListener(
                            'change',
                            function () {
                                checkboxes.forEach(
                                    function (checkbox) {
                                        checkbox.checked =
                                            master.checked;
                                    }
                                );

                                updateLabel();
                            }
                        );
                    }

                    checkboxes.forEach(
                        function (checkbox) {
                            checkbox.addEventListener(
                                'change',
                                updateLabel
                            );
                        }
                    );

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