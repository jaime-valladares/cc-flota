<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                @php
                    $rutaVentanaAdministracion = \Illuminate\Support\Facades\Route::has(
                        'marchamos.reemplazos.index.ventana'
                    )
                        ? route(
                            'marchamos.reemplazos.index.ventana',
                            request()->query()
                        )
                        : route(
                            'marchamos.reemplazos.index',
                            request()->query()
                        );
                @endphp

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Administración de marchamos
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ $rutaVentanaAdministracion }}"
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
                    action="{{ route('marchamos.reemplazos.index') }}"
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
                                Filtros de administración
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
                                                @endforeach

                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <select
                                        id="empresa_id"
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
                            </div>

                            <div class="cc-standard-filter-actions">
                                <button
                                    type="submit"
                                    class="cc-btn-primary"
                                >
                                    Consultar
                                </button>

                                <a
                                    href="{{ route('marchamos.reemplazos.index') }}"
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
                            Use los filtros para localizar unidades habilitadas
                            para el reemplazo de marchamos.
                        </p>
                    </section>
                @elseif ($unidadesDisponibles->isEmpty())
                    <section class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin unidades elegibles
                        </h5>

                        <p>
                            No se encontraron unidades con empresa activa,
                            unidad activa, licencia activa y vigente, y
                            cobertura completa para los filtros seleccionados.
                        </p>
                    </section>
                @else
                    <div class="cc-admin-result-list">

                        @foreach ($unidadesDisponibles as $unidad)
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

                                            <span class="cc-badge cc-badge-active">
                                                Activa
                                            </span>
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
                                                Cobertura
                                            </div>

                                            <div class="cc-admin-result-value">
                                                {{ $puntosAsignados }}
                                                /
                                                {{ $totalPuntos }}
                                            </div>

                                            @if ($coberturaCompleta)
                                                <div class="cc-admin-result-value-muted text-[var(--cc-success)]">
                                                    Completa
                                                </div>
                                            @else
                                                <div class="cc-admin-result-value-muted text-[var(--cc-danger)]">
                                                    {{ $puntosPendientes }} pendientes
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

                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row xl:col-span-2 xl:justify-end xl:self-center">
                                        <a
                                            href="{{ route(
                                                'marchamos.reemplazos.show',
                                                $unidad
                                            ) }}"
                                            class="cc-btn-primary cc-btn-result w-full sm:w-auto"
                                        >
                                            Reemplazar
                                        </a>
                                    </div>

                                </div>
                            </article>
                        @endforeach

                    </div>

                    <div class="mt-6">
                        {{ $unidadesDisponibles
                            ->appends(
                                array_merge(
                                    request()->query(),
                                    [
                                        'consultar' => 1,
                                    ]
                                )
                            )
                            ->links() }}
                    </div>
                @endif

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
                    label.dataset.defaultLabel || 'Todas';

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

                        const selectedLabel =
                            selectedOption.querySelector(
                                '[data-cc-filter-option-label]'
                            );

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

                function closeOtherMultiselects() {
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
                            closeOtherMultiselects();

                            toggle.classList.toggle(
                                'is-open'
                            );

                            menu.classList.toggle(
                                'is-open'
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