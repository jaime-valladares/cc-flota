<x-app-layout>
    <div class="cc-page-wrapper">
        <div
            class="cc-content-container"
            style="max-width: 80rem;"
        >
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Administrar licencias
                        </h3>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <a
                            href="{{ route(
                                'licencias.administrar.ventana',
                                request()->query()
                            ) }}"
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
                            Revise los filtros ingresados.
                        </div>

                        <ul class="mt-2 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
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

                <form
                    method="GET"
                    action="{{ route('licencias.administrar') }}"
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
                                                                    array_map(
                                                                        'intval',
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
                                    <select
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
                                    Período de vigencia
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
                                            @if (! empty($periodosVigenciaSeleccionados))
                                                {{ count($periodosVigenciaSeleccionados) }}
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

                                            @foreach ($periodosVigencia as $valor => $texto)
                                                <label
                                                    class="cc-filter-multiselect-option"
                                                    data-cc-filter-option
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="periodos_vigencia[]"
                                                        value="{{ $valor }}"
                                                        @checked(
                                                            in_array(
                                                                (int) $valor,
                                                                array_map(
                                                                    'intval',
                                                                    $periodosVigenciaSeleccionados ?? []
                                                                ),
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

                                @error('periodos_vigencia')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror

                                @error('periodos_vigencia.*')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-field">
                                <label for="estado">
                                    Estado administrativo
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
                                        Activa
                                    </option>

                                    <option
                                        value="inactiva"
                                        @selected($estado === 'inactiva')
                                    >
                                        Inactiva
                                    </option>
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
                                href="{{ route('licencias.administrar') }}"
                                class="cc-btn-secondary"
                            >
                                Limpiar
                            </a>
                        </div>

                    </div>
                </form>

                @if ($hayFiltros && $licencias->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando

                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            {{ $licencias->firstItem() }}
                        </span>

                        -

                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            {{ $licencias->lastItem() }}
                        </span>

                        de

                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                            {{ $licencias->total() }}
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
                @elseif ($licencias->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>

                        <p>
                            No hay licencias que coincidan con los criterios
                            seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-admin-result-list">

                        @foreach ($licencias as $licencia)
                            @php
                                $unidad = $licencia->unidad;

                                $licenciaEditable =
                                    $licencia->estado === 'activa'
                                    && ! $licencia->esta_vencida;

                                $parametrosFicha = array_merge(
                                    request()->query(),
                                    ['licencia' => $licencia]
                                );
                            @endphp

                            <article class="cc-admin-result-card">
                                <div
                                    class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-12 xl:items-center"
                                >
                                    <div class="min-w-0 xl:col-span-2">
                                        <h5 class="cc-admin-result-title">
                                            {{ $unidad->placa ?? 'Sin placa' }}
                                        </h5>

                                        <div class="cc-admin-result-subtitle">
                                            {{ $unidad->marca ?? 'Sin marca registrada' }}
                                        </div>
                                    </div>

                                    <div class="min-w-0 xl:col-span-3">
                                        <div class="cc-admin-result-label">
                                            Empresa
                                        </div>

                                        @if ($licencia->empresa)
                                            <div class="cc-admin-result-value">
                                                {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                            </div>
                                        @else
                                            <div class="cc-admin-result-value-muted">
                                                Sin empresa
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 xl:col-span-3">
                                        <div class="cc-admin-result-label">
                                            Vigencia
                                        </div>

                                        <div class="cc-admin-result-value">
                                            {{ $licencia->periodo_vigencia_texto }}
                                        </div>

                                        <div class="cc-admin-result-value-muted">
                                            Activa:
                                            {{ $licencia->fecha_activacion?->format('d/m/Y') ?? 'No registrada' }}
                                        </div>

                                        <div class="cc-admin-result-value-muted">
                                            Vence:
                                            {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'No registrada' }}
                                        </div>

                                        <div class="cc-admin-result-value-muted">
                                            {{ $licencia->vencimiento_relativo_texto }}
                                        </div>
                                    </div>

                                    <div class="min-w-0 xl:col-span-2">
                                        <div class="cc-admin-result-label">
                                            Marchamos
                                        </div>

                                        <div class="cc-admin-result-value">
                                            {{ $unidad?->total_puntos_con_marchamo_asignado ?? 0 }}
                                            de
                                            {{ $unidad?->total_puntos_que_requieren_marchamo ?? 0 }}
                                        </div>
                                    </div>

                                    <div
                                        class="flex min-w-0 flex-wrap items-center justify-end gap-3 md:col-span-2 xl:col-span-2"
                                    >
                                        <a
                                            href="{{ route(
                                                'licencias.show',
                                                $parametrosFicha
                                            ) }}"
                                            class="cc-btn-primary cc-btn-result"
                                        >
                                            Ver ficha
                                        </a>

                                        @if ($licenciaEditable)
                                            <a
                                                href="{{ route(
                                                    'licencias.edit',
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
                        {{ $licencias
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

                function updateLabel() {
                    const selected = checkboxes.filter(
                        function (checkbox) {
                            return checkbox.checked;
                        }
                    );

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

                function closeOtherMenus() {
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

                            otherToggle?.classList.remove('is-open');
                            otherMenu?.classList.remove('is-open');
                        });
                }

                toggle?.addEventListener(
                    'click',
                    function () {
                        closeOtherMenus();

                        toggle.classList.toggle('is-open');
                        menu?.classList.toggle('is-open');
                    }
                );

                master?.addEventListener(
                    'change',
                    function () {
                        checkboxes.forEach(
                            function (checkbox) {
                                checkbox.checked = master.checked;
                            }
                        );

                        updateLabel();
                    }
                );

                checkboxes.forEach(function (checkbox) {
                    checkbox.addEventListener(
                        'change',
                        updateLabel
                    );
                });

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
</x-app-layout>