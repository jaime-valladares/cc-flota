<x-app-layout>
    <div class="cc-page-wrapper cc-va-scope">
        <div class="cc-content-container cc-operational-container">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Administrar empresa
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('empresas.administrar.ventana', request()->query()) }}"
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

                <form method="GET" action="{{ route('empresas.administrar') }}" class="mb-5">
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
                                    <div class="cc-filter-multiselect" data-cc-filter-multiselect>
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
                                    <select class="cc-input" disabled>
                                        @foreach ($empresasSelector as $empresaOpcion)
                                            <option value="{{ $empresaOpcion->id }}" selected>
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
                                        value="activa"
                                        @selected($estado === 'activa')
                                    >
                                        Activas
                                    </option>

                                    <option
                                        value="inactiva"
                                        @selected($estado === 'inactiva')
                                    >
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
                                <button
                                    type="submit"
                                    class="cc-btn-primary"
                                >
                                    Consultar
                                </button>

                                <a
                                    href="{{ route('empresas.administrar') }}"
                                    class="cc-btn-secondary"
                                >
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
                    <div class="cc-admin-result-list">
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

                            <article class="cc-admin-result-card">
                                <div class="cc-admin-result-grid">

                                    <div class="cc-admin-result-main">
                                        <div class="cc-admin-result-title-row">
                                            <h5 class="cc-admin-result-title">
                                                {{ $empresa->nombre_legal }}
                                            </h5>

                                            @if (
                                                    Auth::user()->tienePermiso('empresas.editar')
                                                    && $empresa->estado === 'activa'
                                                )
                                                <span class="cc-badge cc-badge-active">
                                                    Activa
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Inactiva
                                                </span>
                                            @endif
                                        </div>

                                        @if (
                                            $empresa->nombre_comercial
                                            && $empresa->nombre_comercial !== $empresa->nombre_legal
                                        )
                                            <div class="cc-admin-result-subtitle">
                                                {{ $empresa->nombre_comercial }}
                                            </div>
                                        @endif
                                    </div>

                                    <div class="cc-admin-result-meta">
                                        <div class="cc-admin-result-label">
                                            Contacto
                                        </div>

                                        <div class="cc-admin-result-value">
                                            {{ $empresa->poc_nombre ?: 'Sin contacto' }}
                                        </div>

                                        <div class="cc-admin-result-value-muted">
                                            {{ $empresa->poc_telefono ?: 'Sin teléfono' }}
                                        </div>
                                    </div>

                                    <div class="cc-admin-result-meta">
                                        <div class="cc-admin-result-label">
                                            Unidades
                                        </div>

                                        <div class="cc-admin-result-value">
                                            {{ $unidadesActivas }} activas
                                        </div>

                                        <div class="cc-admin-result-value-muted">
                                            {{ $unidadesRegistradas }} registradas
                                        </div>
                                    </div>

                                    <div class="cc-admin-result-actions">
                                        <a
                                            href="{{ route(
                                                'empresas.show',
                                                array_merge(
                                                    request()->query(),
                                                    ['empresa' => $empresa]
                                                )
                                            ) }}"
                                            class="cc-btn-primary cc-btn-result"
                                        >
                                            Ver ficha
                                        </a>

                                        @if ($empresa->estado === 'activa')
                                            <a
                                                href="{{ route(
                                                    'empresas.edit',
                                                    array_merge(
                                                        request()->query(),
                                                        ['empresa' => $empresa]
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
                    const selectedOption = selected[0].closest('[data-cc-filter-option]');
                    const selectedLabel = selectedOption
                        ? selectedOption.querySelector('[data-cc-filter-option-label]')
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
</x-app-layout>