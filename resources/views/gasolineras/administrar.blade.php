<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Administrar gasolineras
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras.administrar.ventana', request()->query()) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
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
                        <ul class="cc-alert-list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="GET" action="{{ route('gasolineras.administrar') }}" class="mb-5">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
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

                                @error('busqueda_empresa')
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
                                    <div class="cc-filter-multiselect" data-cc-filter-multiselect>
                                        <button
                                            type="button"
                                            class="cc-filter-multiselect-toggle"
                                            data-cc-filter-toggle
                                        >
                                            <span data-cc-filter-label data-default-label="Todas">
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

                                        <div class="cc-filter-multiselect-menu" data-cc-filter-menu>
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

                                                @foreach ($empresasSelector as $empresa)
                                                    <label
                                                        class="cc-filter-multiselect-option"
                                                        data-cc-filter-option
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            name="empresa_ids[]"
                                                            value="{{ $empresa->id }}"
                                                            @checked(in_array((int) $empresa->id, $empresaIds ?? [], true))
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
                                        @foreach ($empresasSelector as $empresa)
                                            <option value="{{ $empresa->id }}" selected>
                                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>
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
                                <label for="busqueda_gasolinera">
                                    Buscar gasolinera
                                </label>

                                <input
                                    id="busqueda_gasolinera"
                                    name="busqueda_gasolinera"
                                    type="text"
                                    class="cc-input"
                                    value="{{ $busquedaGasolinera ?? '' }}"
                                    maxlength="150"
                                    placeholder="Nombre de gasolinera"
                                >

                                @error('busqueda_gasolinera')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-field">
                                <label>
                                    Gasolinera
                                </label>

                                <div class="cc-filter-multiselect" data-cc-filter-multiselect>
                                    <button
                                        type="button"
                                        class="cc-filter-multiselect-toggle"
                                        data-cc-filter-toggle
                                    >
                                        <span data-cc-filter-label data-default-label="Todas">
                                            @if (! empty($gasolineraIds))
                                                {{ count($gasolineraIds) }} seleccionadas
                                            @else
                                                Todas
                                            @endif
                                        </span>

                                        <span class="cc-filter-multiselect-arrow">
                                            ⌄
                                        </span>
                                    </button>

                                    <div class="cc-filter-multiselect-menu" data-cc-filter-menu>
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

                                            @foreach ($gasolinerasSelector as $gasolineraOpcion)
                                                <label
                                                    class="cc-filter-multiselect-option"
                                                    data-cc-filter-option
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="gasolinera_ids[]"
                                                        value="{{ $gasolineraOpcion->id }}"
                                                        @checked(in_array((int) $gasolineraOpcion->id, $gasolineraIds ?? [], true))
                                                        data-cc-filter-checkbox
                                                    >

                                                    <span data-cc-filter-option-label>
                                                        {{ $gasolineraOpcion->nombre }}
                                                    </span>
                                                </label>
                                            @endforeach

                                        </div>
                                    </div>
                                </div>

                                @error('gasolinera_ids')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror

                                @error('gasolinera_ids.*')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-standard-filter-actions">
                                <button type="submit" class="cc-btn-primary">
                                    Consultar
                                </button>

                                <a href="{{ route('gasolineras.administrar') }}"
                                   class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $gasolineras->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando

                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            {{ $gasolineras->firstItem() }}
                        </span>

                        -

                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            {{ $gasolineras->lastItem() }}
                        </span>

                        de

                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                            {{ $gasolineras->total() }}
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
                @elseif ($gasolineras->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>

                        <p>
                            No hay gasolineras que coincidan con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-admin-result-list">
                        @foreach ($gasolineras as $gasolinera)
                            @php
                                $tanques = $gasolinera->tanques ?? collect();

                                $capacidadTotal = $tanques->sum(
                                    fn ($tanque) => (float) $tanque->capacidad_total
                                );

                                $volumenActual = $tanques->sum(
                                    fn ($tanque) => (float) $tanque->volumen_actual
                                );

                                $tanquesActivos = $gasolinera->tanques_activos_count
                                    ?? $tanques->where('estado', 'activo')->count();

                                $tanquesTotal = $gasolinera->tanques_count
                                    ?? $tanques->count();

                                $porcentajeDisponible = $capacidadTotal > 0
                                    ? round(($volumenActual / $capacidadTotal) * 100, 2)
                                    : 0;

                                $tanquesBajoAlerta = $tanques
                                    ->filter(
                                        fn ($tanque) =>
                                            (float) $tanque->volumen_actual
                                            <= (float) $tanque->volumen_minimo_alerta
                                    )
                                    ->count();
                            @endphp

                            <article class="cc-admin-result-card">
                                <div class="grid gap-5 xl:grid-cols-12 xl:items-start">

                                    <div class="min-w-0 xl:col-span-3">
                                        <div class="flex flex-wrap items-center gap-2">

                                            <h5 class="cc-admin-result-title">
                                                {{ $gasolinera->nombre }}
                                            </h5>

                                            @if ($gasolinera->estado === 'activa')
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
                                            Gasolinera interna
                                        </div>
                                    </div>

                                    <div class="min-w-0 xl:col-span-3">
                                        <div class="cc-admin-result-label">
                                            Empresa
                                        </div>

                                        @if ($gasolinera->empresa)
                                            <div class="cc-admin-result-value">
                                                {{ $gasolinera->empresa->nombre_comercial ?: $gasolinera->empresa->nombre_legal }}
                                            </div>

                                            <div class="cc-admin-result-value-muted">
                                                {{ $gasolinera->direccion }}
                                            </div>
                                        @else
                                            <div class="cc-admin-result-value-muted">
                                                Sin empresa
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 sm:grid sm:grid-cols-3 sm:gap-5 xl:col-span-4 xl:grid-cols-3">

                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Tanques
                                            </div>

                                            <div class="cc-admin-result-value">
                                                {{ $tanquesActivos }} activos /
                                                {{ $tanquesTotal }} total
                                            </div>

                                            <div class="cc-admin-result-value-muted {{ $tanquesBajoAlerta > 0 ? 'text-[var(--cc-danger)]' : '' }}">
                                                {{ $tanquesBajoAlerta }} en alerta
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Inventario
                                            </div>

                                            <div class="cc-admin-result-value">
                                                {{ number_format($volumenActual, 2) }} gal
                                            </div>

                                            <div class="cc-admin-result-value-muted">
                                                Disponible
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Disponibilidad
                                            </div>

                                            <div class="cc-admin-result-value">
                                                {{ number_format($porcentajeDisponible, 2) }}%
                                            </div>

                                            <div class="cc-admin-result-value-muted">
                                                {{ number_format($capacidadTotal, 2) }} gal capacidad
                                            </div>
                                        </div>

                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row xl:col-span-2 xl:justify-end xl:self-center">
                                        <a
                                            href="{{ route('gasolineras.show', array_merge(['gasolinera' => $gasolinera], request()->query())) }}"
                                            class="{{ $gasolinera->estado === 'activa' ? 'cc-btn-primary' : 'cc-btn-secondary' }} cc-btn-result w-full sm:w-auto"
                                        >
                                            {{ $gasolinera->estado === 'activa' ? 'Administrar' : 'Ver ficha' }}
                                        </a>
                                    </div>

                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $gasolineras
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

            const defaultLabel = label.dataset.defaultLabel || 'Todos';

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

                    const selectedLabel = selectedOption?.querySelector(
                        '[data-cc-filter-option-label]'
                    );

                    label.textContent = selectedLabel
                        ? selectedLabel.textContent.trim()
                        : '1 seleccionado';
                } else {
                    label.textContent = selected.length + ' seleccionados';
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

                        otherToggle?.classList.remove('is-open');
                        otherMenu?.classList.remove('is-open');
                    });
            }

            toggle?.addEventListener('click', function () {
                closeAllExceptCurrent();

                toggle.classList.toggle('is-open');
                menu?.classList.toggle('is-open');
            });

            master?.addEventListener('change', function () {
                checkboxes.forEach(function (checkbox) {
                    checkbox.checked = master.checked;
                });

                updateLabel();
            });

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
</x-app-layout>