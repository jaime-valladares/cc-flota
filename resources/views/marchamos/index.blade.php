<x-app-layout>
    <div class="cc-page-wrapper cc-va-scope">
        <div class="cc-content-container cc-operational-container">
            <div class="cc-card">

                @php
                    $rutaVentanaConsulta = route(
                        'marchamos.consulta.ventana',
                        request()->query()
                    );
                @endphp

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Consulta de marchamos
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ $rutaVentanaConsulta }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Abrir en nueva pestaña
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
                    action="{{ route('marchamos.index') }}"
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
                                    Buscar Nombre / Placa
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
                                            data-plural-label="seleccionadas"
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

                                            @forelse ($unidadesSelector as $unidadOpcion)
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
                                            @empty
                                                <div class="px-3 py-3 text-sm text-[var(--cc-text-muted)]">
                                                    No hay unidades disponibles.
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
                                    href="{{ route('marchamos.index') }}"
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
                            Use los filtros para localizar unidades y consultar
                            su cobertura actual e historial de marchamos.
                        </p>
                    </section>
                @elseif ($unidadesConCobertura->isEmpty())
                    <section class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>

                        <p>
                            No se encontraron unidades con puntos de seguridad
                            para los filtros seleccionados.
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
                                                {{ $marchamosActivos }} activos
                                            </div>

                                            <div class="cc-admin-result-value-muted">
                                                {{ $marchamosHistoricos }} históricos
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
                                            href="{{ route(
                                                'marchamos.detalle-unidad',
                                                $unidad
                                            ) }}"
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
</x-app-layout>