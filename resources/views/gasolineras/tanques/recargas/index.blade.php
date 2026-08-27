<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Recarga de tanques
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route('gasolineras.tanques.recargas.index.ventana', request()->query()) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cc-btn-secondary cc-btn-wide"
                        >
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

                <div class="cc-summary-strip">
                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Tanques recargables
                        </span>

                        <span class="cc-summary-strip-value">
                            {{ $tanquesRecargables }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Bajo mínimo
                        </span>

                        <span class="cc-summary-strip-value {{ $tanquesBajoAlerta > 0 ? 'cc-summary-strip-value-danger' : 'cc-summary-strip-value-success' }}">
                            {{ $tanquesBajoAlerta }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Espacio disponible
                        </span>

                        <span class="cc-summary-strip-value">
                            {{ number_format($capacidadDisponible, 2) }} gal
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Resultados
                        </span>

                        <span class="cc-summary-strip-value">
                            {{ $hayFiltros ? $tanques->total() : 0 }}
                        </span>
                    </div>
                </div>

                <form
                    method="GET"
                    action="{{ route('gasolineras.tanques.recargas.index') }}"
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
                                    <div
                                        class="cc-filter-multiselect"
                                        data-cc-filter-multiselect
                                        data-all-text="Todas"
                                        data-singular-text="seleccionada"
                                        data-plural-text="seleccionadas"
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
                                                Todas
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
                                                        data-cc-filter-text="{{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}"
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
                                                            {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                                        </span>
                                                    </label>
                                                @endforeach

                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <select
                                        id="empresa_id_visible"
                                        class="cc-input"
                                        disabled
                                    >
                                        @forelse ($empresasSelector as $empresaOpcion)
                                            <option
                                                value="{{ $empresaOpcion->id }}"
                                                selected
                                            >
                                                {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
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

                                <div
                                    class="cc-filter-multiselect"
                                    data-cc-filter-multiselect
                                    data-all-text="Todas"
                                    data-singular-text="seleccionada"
                                    data-plural-text="seleccionadas"
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
                                            Todas
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

                                            @foreach ($gasolinerasSelector as $gasolineraOpcion)
                                                @php
                                                    $empresaGasolinera = $gasolineraOpcion->empresa;

                                                    $nombreEmpresaGasolinera =
                                                        $empresaGasolinera?->nombre_comercial
                                                        ?: $empresaGasolinera?->nombre_legal;
                                                @endphp

                                                <label
                                                    class="cc-filter-multiselect-option"
                                                    data-cc-filter-option
                                                    data-cc-filter-text="{{ $gasolineraOpcion->nombre }} {{ $nombreEmpresaGasolinera }}"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="gasolinera_ids[]"
                                                        value="{{ $gasolineraOpcion->id }}"
                                                        @checked(
                                                            in_array(
                                                                (string) $gasolineraOpcion->id,
                                                                array_map(
                                                                    'strval',
                                                                    $gasolineraIds ?? []
                                                                ),
                                                                true
                                                            )
                                                        )
                                                        data-cc-filter-checkbox
                                                    >

                                                    <span data-cc-filter-option-label>
                                                        {{ $gasolineraOpcion->nombre }}

                                                        @if ($esUsuarioDieselCop && $nombreEmpresaGasolinera)
                                                            — {{ $nombreEmpresaGasolinera }}
                                                        @endif
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
                                <button
                                    type="submit"
                                    class="cc-btn-primary"
                                >
                                    Consultar
                                </button>

                                <a
                                    href="{{ route('gasolineras.tanques.recargas.index') }}"
                                    class="cc-btn-secondary"
                                >
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $tanques->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando

                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            {{ $tanques->firstItem() }}
                        </span>

                        -

                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            {{ $tanques->lastItem() }}
                        </span>

                        de

                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                            {{ $tanques->total() }}
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
                @elseif ($tanques->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>

                        <p>
                            No hay tanques activos disponibles para recarga con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-admin-result-list">
                        @foreach ($tanques as $tanque)
                            @php
                                $gasolinera = $tanque->gasolinera;
                                $empresa = $gasolinera?->empresa;

                                $capacidadTotal = (float) $tanque->capacidad_total;
                                $volumenActual = (float) $tanque->volumen_actual;
                                $volumenMinimoAlerta = (float) $tanque->volumen_minimo_alerta;

                                $espacioDisponible = max(
                                    $capacidadTotal - $volumenActual,
                                    0
                                );

                                $porcentajeInventario = $tanque->porcentajeDisponible();
                                $bajoAlerta = $tanque->estaBajoAlerta();

                                $operativamenteRecargable =
                                    $tanque->estado === 'activo'
                                    && $gasolinera?->estado === 'activa'
                                    && $empresa?->estado === 'activa';
                            @endphp

                            <article class="cc-admin-result-card">
                                <div class="grid gap-5 xl:grid-cols-12 xl:items-start">

                                    <div class="min-w-0 xl:col-span-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h5 class="cc-admin-result-title">
                                                {{ $tanque->nombre }}
                                            </h5>

                                            @if ($operativamenteRecargable)
                                                <span class="cc-badge cc-badge-active">
                                                    Disponible
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    No disponible
                                                </span>
                                            @endif

                                            @if ($bajoAlerta)
                                                <span class="cc-badge cc-badge-warning">
                                                    Bajo mínimo
                                                </span>
                                            @endif
                                        </div>

                                        <div class="cc-admin-result-subtitle">
                                            {{ $operativamenteRecargable ? 'Tanque recargable' : 'Operación bloqueada' }}
                                        </div>
                                    </div>

                                    <div class="min-w-0 xl:col-span-3">
                                        <div class="cc-admin-result-label">
                                            Gasolinera
                                        </div>

                                        <div class="cc-admin-result-value">
                                            {{ $gasolinera?->nombre ?: 'Sin gasolinera' }}
                                        </div>

                                        <div class="cc-admin-result-value-muted">
                                            {{ $empresa?->nombre_comercial ?: $empresa?->nombre_legal ?: 'Sin empresa' }}
                                        </div>
                                    </div>

                                    <div class="min-w-0 sm:grid sm:grid-cols-3 sm:gap-5 xl:col-span-4 xl:grid-cols-3">

                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Inventario actual
                                            </div>

                                            <div class="cc-admin-result-value">
                                                {{ number_format($volumenActual, 2) }} gal
                                            </div>

                                            <div class="cc-admin-result-value-muted">
                                                {{ number_format($porcentajeInventario, 2) }}% de capacidad
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Espacio disponible
                                            </div>

                                            <div class="cc-admin-result-value">
                                                {{ number_format($espacioDisponible, 2) }} gal
                                            </div>

                                            <div class="cc-admin-result-value-muted">
                                                Disponible para recarga
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Mínimo de alerta
                                            </div>

                                            <div class="cc-admin-result-value {{ $bajoAlerta ? 'text-[var(--cc-danger)]' : '' }}">
                                                {{ number_format($volumenMinimoAlerta, 2) }} gal
                                            </div>

                                            <div class="cc-admin-result-value-muted">
                                                Capacidad {{ number_format($capacidadTotal, 2) }} gal
                                            </div>
                                        </div>

                                    </div>

                                    <div class="flex flex-col gap-3 sm:flex-row xl:col-span-2 xl:self-center xl:justify-end">
                                        @if ($gasolinera && $operativamenteRecargable)
                                            <a
                                                href="{{ route('gasolineras.tanques.recargas.create', [
                                                    'gasolinera' => $gasolinera,
                                                    'tanque_id' => $tanque->id,
                                                ]) }}"
                                                class="cc-btn-primary cc-btn-result w-full sm:w-auto"
                                            >
                                                Registrar recarga
                                            </a>
                                        @else
                                            <span class="cc-admin-result-value-muted">
                                                Sin acciones disponibles
                                            </span>
                                        @endif
                                    </div>

                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $tanques->links() }}
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
                    label?.dataset.defaultLabel
                    || multiselect.dataset.allText
                    || 'Todos';

                const singularText =
                    multiselect.dataset.singularText
                    || 'seleccionado';

                const pluralText =
                    multiselect.dataset.pluralText
                    || 'seleccionados';

                function updateLabel() {
                    const selected = checkboxes.filter(
                        function (checkbox) {
                            return checkbox.checked;
                        }
                    );

                    if (label) {
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
                                : `1 ${singularText}`;
                        } else {
                            label.textContent =
                                `${selected.length} ${pluralText}`;
                        }
                    }

                    if (master) {
                        master.checked =
                            checkboxes.length > 0
                            && selected.length === checkboxes.length;

                        master.indeterminate =
                            selected.length > 0
                            && selected.length < checkboxes.length;
                    }
                }

                function closeOtherMenus() {
                    document
                        .querySelectorAll('[data-cc-filter-multiselect]')
                        .forEach(function (otherMultiselect) {
                            if (otherMultiselect === multiselect) {
                                return;
                            }

                            otherMultiselect
                                .querySelector('[data-cc-filter-toggle]')
                                ?.classList.remove('is-open');

                            otherMultiselect
                                .querySelector('[data-cc-filter-menu]')
                                ?.classList.remove('is-open');
                        });
                }

                toggle?.addEventListener('click', function () {
                    closeOtherMenus();

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
</x-app-layout>