<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Consulta licencias
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('licencias.consulta.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="cc-summary-strip">
                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            {{ $hayFiltros ? 'Resultados' : 'Total licencias' }}
                        </span>
                        <span class="cc-summary-strip-value">
                            {{ $resumenLicencias['total'] ?? $totalLicencias }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Activas
                        </span>
                        <span class="cc-summary-strip-value cc-summary-strip-value-success">
                            {{ $resumenLicencias['activas'] ?? $totalActivas }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Inactivas
                        </span>
                        <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                            {{ $resumenLicencias['inactivas'] ?? $totalInactivas }}
                        </span>
                    </div>
                </div>

                <form method="GET" action="{{ route('licencias.index') }}" class="mb-5">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
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
                            </div>

                            <div class="cc-field">
                                <label>
                                    Empresa
                                </label>

                                @if ($esUsuarioDieselCop)
                                    <div class="cc-filter-multiselect" data-cc-filter-multiselect>
                                        <button type="button" class="cc-filter-multiselect-toggle" data-cc-filter-toggle>
                                            <span data-cc-filter-label data-default-label="Todas">
                                                @if (! empty($empresaIds))
                                                    {{ count($empresaIds) }} seleccionadas
                                                @else
                                                    Todas
                                                @endif
                                            </span>
                                            <span class="cc-filter-multiselect-arrow">⌄</span>
                                        </button>

                                        <div class="cc-filter-multiselect-menu" data-cc-filter-menu>
                                            <div class="cc-filter-multiselect-list">
                                                <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                                    <input type="checkbox" data-cc-filter-master>
                                                    <span>Seleccionar todo</span>
                                                </label>

                                                @foreach ($empresas as $empresa)
                                                    <label class="cc-filter-multiselect-option" data-cc-filter-option>
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
                                        @foreach ($empresas as $empresa)
                                            <option value="{{ $empresa->id }}" selected>
                                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <div class="cc-field">
                                <label>
                                    Placa
                                </label>

                                <div class="cc-filter-multiselect" data-cc-filter-multiselect>
                                    <button type="button" class="cc-filter-multiselect-toggle" data-cc-filter-toggle>
                                        <span data-cc-filter-label data-default-label="Todas">
                                            @if (! empty($placas))
                                                {{ count($placas) }} seleccionadas
                                            @else
                                                Todas
                                            @endif
                                        </span>
                                        <span class="cc-filter-multiselect-arrow">⌄</span>
                                    </button>

                                    <div class="cc-filter-multiselect-menu" data-cc-filter-menu>
                                        <div class="cc-filter-multiselect-list">
                                            <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                                <input type="checkbox" data-cc-filter-master>
                                                <span>Seleccionar todo</span>
                                            </label>

                                            @foreach ($placasSelector as $placaOpcion)
                                                <label class="cc-filter-multiselect-option" data-cc-filter-option>
                                                    <input
                                                        type="checkbox"
                                                        name="placas[]"
                                                        value="{{ $placaOpcion }}"
                                                        @checked(in_array($placaOpcion, $placas ?? [], true))
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

                            <div class="cc-field">
                                <label>
                                    Período de vigencia
                                </label>

                                <div class="cc-filter-multiselect" data-cc-filter-multiselect>
                                    <button type="button" class="cc-filter-multiselect-toggle" data-cc-filter-toggle>
                                        <span data-cc-filter-label data-default-label="Todos">
                                            @if (! empty($periodosVigenciaSeleccionados))
                                                {{ count($periodosVigenciaSeleccionados) }} seleccionados
                                            @else
                                                Todos
                                            @endif
                                        </span>
                                        <span class="cc-filter-multiselect-arrow">⌄</span>
                                    </button>

                                    <div class="cc-filter-multiselect-menu" data-cc-filter-menu>
                                        <div class="cc-filter-multiselect-list">
                                            <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                                <input type="checkbox" data-cc-filter-master>
                                                <span>Seleccionar todo</span>
                                            </label>

                                            @foreach ($periodosVigencia as $valor => $texto)
                                                <label class="cc-filter-multiselect-option" data-cc-filter-option>
                                                    <input
                                                        type="checkbox"
                                                        name="periodos_vigencia[]"
                                                        value="{{ $valor }}"
                                                        @checked(in_array((int) $valor, $periodosVigenciaSeleccionados ?? [], true))
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
                            </div>

                            <div class="cc-standard-filter-actions">
                                <button type="submit" class="cc-btn-primary">
                                    Consultar
                                </button>

                                <a href="{{ route('licencias.index') }}" class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $licencias->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->firstItem() }}</span>
                        -
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->lastItem() }}</span>
                        de
                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->total() }}</span>
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
                @elseif ($licencias->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>
                        <p>
                            No hay licencias que coincidan con los filtros seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-table-adaptive-wrapper">
                        <table class="cc-table-adaptive" style="min-width: 78rem;">
                            <thead>
                                <tr>
                                    <th style="width: 10rem;">Placa</th>
                                    <th style="width: 18rem;">Empresa</th>
                                    <th style="width: 12rem;">Vigencia</th>
                                    <th style="width: 12rem;">Vencimiento</th>
                                    <th style="width: 17rem;">Plantilla</th>
                                    <th style="width: 8rem;">Puntos</th>
                                    <th style="width: 9rem;">Estado</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($licencias as $licencia)
                                    <tr>
                                        <td class="cc-table-adaptive-nowrap">
                                            <div class="cc-table-adaptive-strong">
                                                {{ $licencia->unidad->placa ?? 'Sin placa' }}
                                            </div>

                                            <div class="cc-table-adaptive-muted">
                                                {{ $licencia->unidad->marca ?? 'Sin marca' }}
                                            </div>
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            @if ($licencia->empresa)
                                                {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                            @else
                                                <span class="text-[var(--cc-text-muted)]">
                                                    Sin empresa
                                                </span>
                                            @endif
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            {{ $licencia->periodo_vigencia_texto }}
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'No registrado' }}
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            {{ $licencia->plantilla_puntos_seguridad_texto }}
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            {{ $licencia->cantidad_puntos_seguridad_esperados }}
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            @if ($licencia->estado === 'activa')
                                                <span class="cc-badge cc-badge-active">
                                                    Activa
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Inactiva
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $licencias->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
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
            const checkboxes = Array.from(multiselect.querySelectorAll('[data-cc-filter-checkbox]'));
            const defaultLabel = label.dataset.defaultLabel || 'Todos';

            function updateLabel() {
                const selected = checkboxes.filter(function (checkbox) {
                    return checkbox.checked;
                });

                if (selected.length === 0) {
                    label.textContent = defaultLabel;
                } else if (selected.length === 1) {
                    const selectedOption = selected[0].closest('[data-cc-filter-option]');
                    const selectedLabel = selectedOption.querySelector('[data-cc-filter-option-label]');
                    label.textContent = selectedLabel ? selectedLabel.textContent.trim() : '1 seleccionado';
                } else {
                    label.textContent = selected.length + ' seleccionados';
                }

                if (master) {
                    master.checked = selected.length === checkboxes.length && checkboxes.length > 0;
                    master.indeterminate = selected.length > 0 && selected.length < checkboxes.length;
                }
            }

            function closeAllExceptCurrent() {
                document.querySelectorAll('[data-cc-filter-multiselect]').forEach(function (otherMultiselect) {
                    if (otherMultiselect === multiselect) {
                        return;
                    }

                    const otherToggle = otherMultiselect.querySelector('[data-cc-filter-toggle]');
                    const otherMenu = otherMultiselect.querySelector('[data-cc-filter-menu]');

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

            document.querySelectorAll('[data-cc-filter-toggle]').forEach(function (toggle) {
                toggle.classList.remove('is-open');
            });

            document.querySelectorAll('[data-cc-filter-menu]').forEach(function (menu) {
                menu.classList.remove('is-open');
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }

            document.querySelectorAll('[data-cc-filter-toggle]').forEach(function (toggle) {
                toggle.classList.remove('is-open');
            });

            document.querySelectorAll('[data-cc-filter-menu]').forEach(function (menu) {
                menu.classList.remove('is-open');
            });
        });
    </script>
</x-app-layout>