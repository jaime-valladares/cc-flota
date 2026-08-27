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
                            Consulta de rutas
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route(
                                'rutas.consulta.ventana',
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
                    <div class="cc-alert cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="cc-summary-strip">
                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            {{ $hayFiltros ? 'Resultados' : 'Total rutas' }}
                        </span>

                        <span class="cc-summary-strip-value">
                            {{ $hayFiltros ? $rutas->total() : $totalRutas }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Activas
                        </span>

                        <span class="cc-summary-strip-value cc-summary-strip-value-success">
                            {{ $rutasActivas }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Inactivas
                        </span>

                        <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                            {{ $rutasInactivas }}
                        </span>
                    </div>
                </div>

                <form
                    method="GET"
                    action="{{ route('rutas.index') }}"
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
                                        data-filter-type="empresa"
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
                                                            data-empresa-checkbox
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
                                        @foreach ($empresasSelector as $empresaOpcion)
                                            <option
                                                value="{{ $empresaOpcion->id }}"
                                                selected
                                            >
                                                {{ $empresaOpcion->nombre_comercial
                                                    ?: $empresaOpcion->nombre_legal }}
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
                                    Ruta
                                </label>

                                <div
                                    class="cc-filter-multiselect"
                                    data-cc-filter-multiselect
                                    data-filter-type="ruta"
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

                                            @foreach ($rutasSelector as $rutaOpcion)
                                                <label
                                                    class="cc-filter-multiselect-option"
                                                    data-cc-filter-option
                                                    data-ruta-option
                                                    data-empresa-id="{{ $rutaOpcion->empresa_id }}"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="ruta_ids[]"
                                                        value="{{ $rutaOpcion->id }}"
                                                        @checked(
                                                            in_array(
                                                                (string) $rutaOpcion->id,
                                                                array_map(
                                                                    'strval',
                                                                    $rutaIds ?? []
                                                                ),
                                                                true
                                                            )
                                                        )
                                                        data-cc-filter-checkbox
                                                        data-ruta-checkbox
                                                    >

                                                    <span data-cc-filter-option-label>
                                                        {{ $rutaOpcion->ruta }}
                                                    </span>
                                                </label>
                                            @endforeach

                                        </div>
                                    </div>
                                </div>

                                @error('ruta_ids')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror

                                @error('ruta_ids.*')
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
                                        Activas
                                    </option>

                                    <option
                                        value="inactivo"
                                        @selected($estado === 'inactivo')
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
                                    href="{{ route('rutas.index') }}"
                                    class="cc-btn-secondary"
                                >
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $rutas->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando

                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            {{ $rutas->firstItem() }}
                        </span>

                        -

                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            {{ $rutas->lastItem() }}
                        </span>

                        de

                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                            {{ $rutas->total() }}
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
                @elseif ($rutas->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>

                        <p>
                            No hay rutas que coincidan con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-table-adaptive-wrapper">
                        <table
                            class="cc-table-adaptive"
                            style="min-width: 78rem;"
                        >
                            <thead>
                                <tr>
                                    <th style="width: 20%;">
                                        Empresa
                                    </th>

                                    <th style="width: 20%;">
                                        Ruta
                                    </th>

                                    <th style="width: 16%;">
                                        Punto registrado 1
                                    </th>

                                    <th style="width: 16%;">
                                        Punto registrado 2
                                    </th>

                                    <th style="width: 10%;">
                                        Kilómetros
                                    </th>

                                    <th style="width: 10%;">
                                        Galones
                                    </th>

                                    <th style="width: 8%;">
                                        Estado
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($rutas as $ruta)
                                    <tr>
                                        <td class="cc-table-adaptive-break">
                                            <div class="cc-table-adaptive-strong">
                                                {{ $ruta->empresa?->nombre_comercial
                                                    ?: $ruta->empresa?->nombre_legal
                                                    ?: 'Empresa no disponible' }}
                                            </div>
                                        </td>

                                        <td class="cc-table-adaptive-break">
                                            <div class="cc-table-adaptive-strong">
                                                {{ $ruta->ruta }}
                                            </div>
                                        </td>

                                        <td class="cc-table-adaptive-break">
                                            {{ $ruta->puntoOrigen?->nombre
                                                ?: 'Punto no disponible' }}
                                        </td>

                                        <td class="cc-table-adaptive-break">
                                            {{ $ruta->puntoDestino?->nombre
                                                ?: 'Punto no disponible' }}
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            {{ number_format(
                                                (float) $ruta->kilometros_estimados,
                                                2
                                            ) }} km
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            {{ number_format(
                                                (float) $ruta->galones_estimados,
                                                2
                                            ) }} gal
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            @if ($ruta->estado === 'activo')
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
                        {{ $rutas
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
        const multiselectsRuta = Array.from(
            document.querySelectorAll(
                '[data-cc-filter-multiselect]'
            )
        );

        function obtenerElementosMultiselect(multiselect) {
            return {
                toggle: multiselect.querySelector(
                    '[data-cc-filter-toggle]'
                ),
                menu: multiselect.querySelector(
                    '[data-cc-filter-menu]'
                ),
                label: multiselect.querySelector(
                    '[data-cc-filter-label]'
                ),
                master: multiselect.querySelector(
                    '[data-cc-filter-master]'
                ),
                checkboxes: Array.from(
                    multiselect.querySelectorAll(
                        '[data-cc-filter-checkbox]'
                    )
                ),
            };
        }

        function obtenerCheckboxesVisibles(elementos) {
            return elementos.checkboxes.filter(function (checkbox) {
                const option = checkbox.closest(
                    '[data-cc-filter-option]'
                );

                return option && ! option.hidden;
            });
        }

        function actualizarEtiquetaMultiselect(multiselect) {
            const elementos = obtenerElementosMultiselect(
                multiselect
            );

            if (! elementos.label) {
                return;
            }

            const checkboxesVisibles = obtenerCheckboxesVisibles(
                elementos
            );

            const seleccionados = checkboxesVisibles.filter(
                function (checkbox) {
                    return checkbox.checked;
                }
            );

            const defaultLabel =
                elementos.label.dataset.defaultLabel || 'Todas';

            const singularSuffix =
                elementos.label.dataset.singularSuffix
                || 'seleccionado';

            const pluralSuffix =
                elementos.label.dataset.pluralSuffix
                || 'seleccionados';

            if (seleccionados.length === 0) {
                elementos.label.textContent = defaultLabel;
            } else if (seleccionados.length === 1) {
                const selectedOption = seleccionados[0].closest(
                    '[data-cc-filter-option]'
                );

                const selectedLabel = selectedOption
                    ? selectedOption.querySelector(
                        '[data-cc-filter-option-label]'
                    )
                    : null;

                elementos.label.textContent = selectedLabel
                    ? selectedLabel.textContent.trim()
                    : '1 ' + singularSuffix;
            } else {
                elementos.label.textContent =
                    seleccionados.length
                    + ' '
                    + pluralSuffix;
            }

            if (elementos.master) {
                elementos.master.checked =
                    checkboxesVisibles.length > 0
                    && seleccionados.length
                        === checkboxesVisibles.length;

                elementos.master.indeterminate =
                    seleccionados.length > 0
                    && seleccionados.length
                        < checkboxesVisibles.length;
            }
        }

        function cerrarMultiselectsExcepto(actual) {
            multiselectsRuta.forEach(function (multiselect) {
                if (multiselect === actual) {
                    return;
                }

                const elementos = obtenerElementosMultiselect(
                    multiselect
                );

                elementos.toggle?.classList.remove('is-open');
                elementos.menu?.classList.remove('is-open');
            });
        }

        function obtenerEmpresasSeleccionadas() {
            const empresaMultiselect = document.querySelector(
                '[data-filter-type="empresa"]'
            );

            if (! empresaMultiselect) {
                return [];
            }

            return Array.from(
                empresaMultiselect.querySelectorAll(
                    '[data-empresa-checkbox]:checked'
                )
            ).map(function (checkbox) {
                return checkbox.value;
            });
        }

        function filtrarRutasPorEmpresa() {
            const empresasSeleccionadas =
                obtenerEmpresasSeleccionadas();

            const filtrarPorEmpresa =
                empresasSeleccionadas.length > 0;

            document
                .querySelectorAll('[data-ruta-option]')
                .forEach(function (option) {
                    const empresaId =
                        option.dataset.empresaId || '';

                    const visible =
                        ! filtrarPorEmpresa
                        || empresasSeleccionadas.includes(
                            empresaId
                        );

                    option.hidden = ! visible;

                    const checkbox = option.querySelector(
                        '[data-ruta-checkbox]'
                    );

                    if (! visible && checkbox) {
                        checkbox.checked = false;
                    }
                });

            const rutaMultiselect = document.querySelector(
                '[data-filter-type="ruta"]'
            );

            if (rutaMultiselect) {
                actualizarEtiquetaMultiselect(
                    rutaMultiselect
                );
            }
        }

        multiselectsRuta.forEach(function (multiselect) {
            const elementos = obtenerElementosMultiselect(
                multiselect
            );

            if (elementos.toggle && elementos.menu) {
                elementos.toggle.addEventListener(
                    'click',
                    function () {
                        cerrarMultiselectsExcepto(
                            multiselect
                        );

                        elementos.toggle.classList.toggle(
                            'is-open'
                        );

                        elementos.menu.classList.toggle(
                            'is-open'
                        );
                    }
                );
            }

            if (elementos.master) {
                elementos.master.addEventListener(
                    'change',
                    function () {
                        obtenerCheckboxesVisibles(elementos)
                            .forEach(function (checkbox) {
                                checkbox.checked =
                                    elementos.master.checked;
                            });

                        actualizarEtiquetaMultiselect(
                            multiselect
                        );

                        if (
                            multiselect.dataset.filterType
                            === 'empresa'
                        ) {
                            filtrarRutasPorEmpresa();
                        }
                    }
                );
            }

            elementos.checkboxes.forEach(
                function (checkbox) {
                    checkbox.addEventListener(
                        'change',
                        function () {
                            actualizarEtiquetaMultiselect(
                                multiselect
                            );

                            if (
                                multiselect.dataset.filterType
                                === 'empresa'
                            ) {
                                filtrarRutasPorEmpresa();
                            }
                        }
                    );
                }
            );

            actualizarEtiquetaMultiselect(multiselect);
        });

        filtrarRutasPorEmpresa();

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

                multiselectsRuta.forEach(
                    function (multiselect) {
                        const elementos =
                            obtenerElementosMultiselect(
                                multiselect
                            );

                        elementos.toggle?.classList.remove(
                            'is-open'
                        );

                        elementos.menu?.classList.remove(
                            'is-open'
                        );
                    }
                );
            }
        );

        document.addEventListener(
            'keydown',
            function (event) {
                if (event.key !== 'Escape') {
                    return;
                }

                multiselectsRuta.forEach(
                    function (multiselect) {
                        const elementos =
                            obtenerElementosMultiselect(
                                multiselect
                            );

                        elementos.toggle?.classList.remove(
                            'is-open'
                        );

                        elementos.menu?.classList.remove(
                            'is-open'
                        );
                    }
                );
            }
        );
    </script>
</x-app-layout>