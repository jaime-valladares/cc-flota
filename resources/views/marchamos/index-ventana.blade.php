<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Consulta de marchamos | CC-Flota</title>

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
            <div class="cc-page-wrapper">
                <div
                    class="cc-window-container"
                    style="max-width: 80rem;"
                >
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Consulta de marchamos
                                </h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    href="{{ route(
                                        'marchamos.index',
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
                                    No fue posible completar la consulta.
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
                            action="{{ route('marchamos.consulta.ventana') }}"
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
                                        <label for="busqueda_empresa">
                                            Buscar empresa
                                        </label>

                                        <input
                                            id="busqueda_empresa"
                                            name="busqueda_empresa"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $busquedaEmpresa ?? '' }}"
                                            maxlength="150"
                                            placeholder="Nombre de empresa"
                                        >
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
                                                        data-plural-label="seleccionadas"
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

                                                        @forelse ($empresas as $empresa)
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
                                                                            $empresaIds ?? [],
                                                                            true
                                                                        )
                                                                    )
                                                                    data-cc-filter-checkbox
                                                                >

                                                                <span data-cc-filter-option-label>
                                                                    {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                                </span>
                                                            </label>
                                                        @empty
                                                            <div class="px-3 py-3 text-sm text-[var(--cc-text-muted)]">
                                                                No hay empresas disponibles.
                                                            </div>
                                                        @endforelse

                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <select
                                                id="empresa_id"
                                                class="cc-input"
                                                disabled
                                            >
                                                @forelse ($empresas as $empresa)
                                                    <option
                                                        value="{{ $empresa->id }}"
                                                        selected
                                                    >
                                                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                    </option>
                                                @empty
                                                    <option value="">
                                                        Sin empresa disponible
                                                    </option>
                                                @endforelse
                                            </select>
                                        @endif
                                    </div>

                                    <div class="cc-field">
                                        <label for="busqueda_placa">
                                            Buscar placa
                                        </label>

                                        <input
                                            id="busqueda_placa"
                                            name="busqueda_placa"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $busquedaPlaca ?? '' }}"
                                            maxlength="30"
                                            placeholder="Ej. C123ABC"
                                        >
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
                                                    data-plural-label="seleccionadas"
                                                >
                                                    @if (! empty($placas))
                                                        {{ count($placas) }}
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

                                                    @forelse ($placasSelector as $placaOpcion)
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
                                                    @empty
                                                        <div class="px-3 py-3 text-sm text-[var(--cc-text-muted)]">
                                                            No hay placas disponibles.
                                                        </div>
                                                    @endforelse

                                                </div>
                                            </div>
                                        </div>
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
                                                'marchamos.consulta.ventana'
                                            ) }}"
                                            class="cc-btn-secondary"
                                        >
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if (! $hayFiltros)
                            <section class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Consulta pendiente
                                </h5>

                                <p>
                                    Use los filtros para localizar unidades y
                                    consultar su cobertura actual e historial
                                    de marchamos.
                                </p>
                            </section>
                        @elseif ($unidadesConCobertura->isEmpty())
                            <section class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>

                                <p>
                                    No se encontraron unidades con puntos de
                                    seguridad para los filtros seleccionados.
                                </p>
                            </section>
                        @else
                            <div class="cc-admin-result-list">

                                @foreach ($unidadesConCobertura as $unidad)
                                    @php
                                        $totalPuntos = (int) (
                                            $unidad->total_puntos ?? 0
                                        );

                                        $puntosAsignados = (int) (
                                            $unidad->puntos_asignados ?? 0
                                        );

                                        $puntosPendientes = max(
                                            $totalPuntos - $puntosAsignados,
                                            0
                                        );

                                        $marchamosActivos = (int) (
                                            $unidad->marchamos_activos ?? 0
                                        );

                                        $marchamosHistoricos = (int) (
                                            $unidad->marchamos_historicos ?? 0
                                        );

                                        $porcentajeAvance = $totalPuntos > 0
                                            ? round(
                                                (
                                                    $puntosAsignados
                                                    / $totalPuntos
                                                ) * 100
                                            )
                                            : 0;

                                        $coberturaCompleta =
                                            $totalPuntos > 0
                                            && $puntosPendientes === 0;

                                        $rutaDetalleVentana =
                                            \Illuminate\Support\Facades\Route::has(
                                                'marchamos.detalle-unidad.ventana'
                                            )
                                                ? route(
                                                    'marchamos.detalle-unidad.ventana',
                                                    $unidad
                                                )
                                                : route(
                                                    'marchamos.detalle-unidad',
                                                    $unidad
                                                );
                                    @endphp

                                    <article class="cc-admin-result-card">
                                        <div class="grid gap-4 xl:grid-cols-12 xl:items-center">

                                            <div class="min-w-0 xl:col-span-3">
                                                <div class="flex flex-wrap items-center gap-2">

                                                    <h5 class="cc-admin-result-title">
                                                        {{ $unidad->placa }}
                                                    </h5>

                                                    @if ($unidad->estado === 'registrada')
                                                        <span class="cc-badge cc-badge-warning">
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

                                            <div class="min-w-0 xl:col-span-2">
                                                <div class="cc-admin-result-label">
                                                    Empresa
                                                </div>

                                                @if ($unidad->empresa)
                                                    <div class="cc-admin-result-value">
                                                        {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                    </div>

                                                    @if ($unidad->empresa->estado === 'inactiva')
                                                        <div class="cc-admin-result-value-muted text-[var(--cc-danger)]">
                                                            Empresa inactiva
                                                        </div>
                                                    @endif
                                                @else
                                                    <div class="cc-admin-result-value-muted">
                                                        Sin empresa
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="min-w-0 sm:grid sm:grid-cols-3 sm:gap-4 xl:col-span-5 xl:grid-cols-3">

                                                <div class="min-w-0">
                                                    <div class="cc-admin-result-label">
                                                        Licencia
                                                    </div>

                                                    @if ($unidad->licencia)
                                                        <div class="cc-admin-result-value">
                                                            {{ $unidad->licencia->periodo_vigencia_texto }}
                                                        </div>

                                                        <div class="cc-admin-result-value-muted">
                                                            {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                                        </div>
                                                    @else
                                                        <div class="cc-admin-result-value-muted">
                                                            Sin licencia
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="min-w-0">
                                                    <div class="cc-admin-result-label">
                                                        Marchamos
                                                    </div>

                                                    <div class="cc-admin-result-value">
                                                        {{ $marchamosActivos }}
                                                        activos
                                                    </div>

                                                    <div class="cc-admin-result-value-muted">
                                                        {{ $marchamosHistoricos }}
                                                        históricos
                                                    </div>
                                                </div>

                                                <div class="min-w-0">
                                                    <div class="cc-admin-result-label">
                                                        Cobertura
                                                    </div>

                                                    <div class="cc-admin-result-value">
                                                        {{ $puntosAsignados }}
                                                        /
                                                        {{ $totalPuntos }}
                                                        ·
                                                        {{ $porcentajeAvance }}%
                                                    </div>

                                                    @if ($coberturaCompleta)
                                                        <div class="cc-admin-result-value-muted text-[var(--cc-success)]">
                                                            Completa
                                                        </div>
                                                    @elseif ($totalPuntos > 0)
                                                        <div class="cc-admin-result-value-muted text-[var(--cc-danger)]">
                                                            {{ $puntosPendientes }}
                                                            pendientes
                                                        </div>
                                                    @else
                                                        <div class="cc-admin-result-value-muted">
                                                            Sin puntos
                                                        </div>
                                                    @endif
                                                </div>

                                            </div>

                                            <div class="flex flex-col gap-3 sm:flex-row xl:col-span-2 xl:justify-end xl:self-center">
                                                <a
                                                    href="{{ $rutaDetalleVentana }}"
                                                    class="cc-btn-secondary cc-btn-result w-full sm:w-auto"
                                                >
                                                    Ver marchamos
                                                </a>
                                            </div>

                                        </div>
                                    </article>
                                @endforeach

                            </div>

                            @if (
                                method_exists(
                                    $unidadesConCobertura,
                                    'links'
                                )
                            )
                                <div class="mt-6">
                                    {{ $unidadesConCobertura->links() }}
                                </div>
                            @endif
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <script>
            document
                .querySelectorAll(
                    '[data-cc-filter-multiselect]'
                )
                .forEach(function (multiselect) {
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

                    const defaultLabel =
                        label?.dataset.defaultLabel
                        || 'Todas';

                    const pluralLabel =
                        label?.dataset.pluralLabel
                        || 'seleccionadas';

                    function updateLabel() {
                        const selected = checkboxes.filter(
                            function (checkbox) {
                                return checkbox.checked;
                            }
                        );

                        if (! label) {
                            return;
                        }

                        if (selected.length === 0) {
                            label.textContent = defaultLabel;
                        } else if (selected.length === 1) {
                            const selectedOption =
                                selected[0].closest(
                                    '[data-cc-filter-option]'
                                );

                            const selectedLabel =
                                selectedOption?.querySelector(
                                    '[data-cc-filter-option-label]'
                                );

                            label.textContent = selectedLabel
                                ? selectedLabel.textContent.trim()
                                : '1 seleccionada';
                        } else {
                            label.textContent =
                                selected.length
                                + ' '
                                + pluralLabel;
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

                    function closeOtherMultiselects() {
                        document
                            .querySelectorAll(
                                '[data-cc-filter-multiselect]'
                            )
                            .forEach(function (
                                otherMultiselect
                            ) {
                                if (
                                    otherMultiselect
                                    === multiselect
                                ) {
                                    return;
                                }

                                otherMultiselect
                                    .querySelector(
                                        '[data-cc-filter-toggle]'
                                    )
                                    ?.classList
                                    .remove('is-open');

                                otherMultiselect
                                    .querySelector(
                                        '[data-cc-filter-menu]'
                                    )
                                    ?.classList
                                    .remove('is-open');
                            });
                    }

                    if (toggle && menu) {
                        toggle.addEventListener(
                            'click',
                            function () {
                                const shouldOpen =
                                    ! menu.classList.contains(
                                        'is-open'
                                    );

                                closeOtherMultiselects();

                                toggle.classList.toggle(
                                    'is-open',
                                    shouldOpen
                                );

                                menu.classList.toggle(
                                    'is-open',
                                    shouldOpen
                                );
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
                            toggle.classList.remove(
                                'is-open'
                            );
                        });

                    document
                        .querySelectorAll(
                            '[data-cc-filter-menu]'
                        )
                        .forEach(function (menu) {
                            menu.classList.remove(
                                'is-open'
                            );
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
                            toggle.classList.remove(
                                'is-open'
                            );
                        });

                    document
                        .querySelectorAll(
                            '[data-cc-filter-menu]'
                        )
                        .forEach(function (menu) {
                            menu.classList.remove(
                                'is-open'
                            );
                        });
                }
            );
        </script>
    </body>
</html>