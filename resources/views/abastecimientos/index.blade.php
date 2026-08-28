<x-app-layout>
    @php
        $queryParams = array_merge(
            request()->query(),
            [
                'consultar' => $hayFiltros ? 1 : null,
            ]
        );

        $queryParams = array_filter(
            $queryParams,
            fn ($value) => ! is_null($value)
        );

        $rutaVentanaAbastecimientos =
            \Illuminate\Support\Facades\Route::has(
                'abastecimientos.index.ventana'
            )
                ? route(
                    'abastecimientos.index.ventana',
                    $queryParams
                )
                : route(
                    'abastecimientos.index',
                    $queryParams
                );
    @endphp

    <div class="cc-page-wrapper cc-va-scope">
        <div
            class="cc-content-container cc-operational-container"
        >
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Abastecimiento de unidades
                        </h3>

                        <p class="cc-subtitle cc-subtitle-compact">
                            Localice unidades con disponibilidad operativa total
                            y registre su abastecimiento.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a
                            href="{{ $rutaVentanaAbastecimientos }}"
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
                        <div class="font-bold">
                            No fue posible completar la consulta.
                        </div>

                        <ul class="cc-alert-list">
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
                    action="{{ route('abastecimientos.index') }}"
                    class="mb-5"
                >
                    <input
                        type="hidden"
                        name="consultar"
                        value="1"
                    >

                    <div
                        class="cc-filter-panel
                               cc-filter-panel-compact
                               cc-filter-panel-inline"
                    >
                        <div
                            class="cc-form-section
                                   cc-form-section-compact"
                            style="margin-top: 0;"
                        >
                            <div class="cc-form-section-title">
                                Filtros de unidades
                            </div>
                        </div>

                        <div
                            style="
                                display: grid;
                                grid-template-columns:
                                    repeat(auto-fit, minmax(14rem, 1fr));
                                gap: 1rem;
                                align-items: end;
                            "
                        >
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
                                    @disabled(! $esUsuarioDieselCop)
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
                                                <label
                                                    class="cc-filter-multiselect-option
                                                           cc-filter-multiselect-option-master"
                                                >
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
                                                            {{ $empresa->nombre_comercial
                                                                ?: $empresa->nombre_legal }}
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
                                            <option selected>
                                                {{ $empresa->nombre_comercial
                                                    ?: $empresa->nombre_legal }}
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
                                    maxlength="50"
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
                                            <label
                                                class="cc-filter-multiselect-option
                                                       cc-filter-multiselect-option-master"
                                            >
                                                <input
                                                    type="checkbox"
                                                    data-cc-filter-master
                                                >

                                                <span>
                                                    Seleccionar todo
                                                </span>
                                            </label>

                                            @foreach ($unidadesSelector as $unidadOpcion)
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
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="cc-standard-filter-actions"
                                style="
                                    display: flex;
                                    flex-wrap: nowrap;
                                    gap: .75rem;
                                    align-items: center;
                                    justify-content: flex-end;
                                    white-space: nowrap;
                                    grid-column: 1 / -1;
                                    width: 100%;
                                "
                            >
                                <button
                                    type="submit"
                                    class="cc-btn-primary"
                                    style="flex: 0 0 auto;"
                                >
                                    Consultar
                                </button>

                                <a
                                    href="{{ route('abastecimientos.index') }}"
                                    class="cc-btn-secondary"
                                    style="flex: 0 0 auto;"
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
                            Búsqueda pendiente
                        </h5>

                        <p>
                            Use los filtros para localizar unidades que cumplen
                            todas las condiciones necesarias para recibir
                            combustible.
                        </p>
                    </section>
                @elseif ($unidades->isEmpty())
                    <section class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin unidades disponibles
                        </h5>

                        <p>
                            No se encontraron unidades con disponibilidad
                            operativa total para los filtros seleccionados.
                        </p>
                    </section>
                @else
                    <div class="cc-admin-result-list">
                        @foreach ($unidades as $unidad)
                            @php
                                $empresaNombre = $unidad->empresa
                                    ? (
                                        $unidad->empresa->nombre_comercial
                                        ?: $unidad->empresa->nombre_legal
                                    )
                                    : 'Sin empresa';

                                $modeloMedicionTexto = match (
                                    $unidad->modelo_medicion
                                ) {
                                    'kilometros_galon' =>
                                        'Kilómetros por galón',

                                    'galones_hora' =>
                                        'Horas por galón',

                                    'galones_viaje' =>
                                        'Galones por viaje',

                                    default =>
                                        'No definido',
                                };

                                $esPrimerAbastecimiento =
                                    ! $unidad
                                        ->tiene_abastecimientos_registrados;

                                $rutaRegistrar = route(
                                    'abastecimientos.create',
                                    array_merge(
                                        [
                                            'unidad' => $unidad,
                                        ],
                                        $queryParams
                                    )
                                );
                            @endphp

                            <article class="cc-admin-result-card">
                                <div
                                    style="
                                        display: grid;
                                        grid-template-columns:
                                            repeat(auto-fit, minmax(13rem, 1fr));
                                        gap: 1.25rem;
                                        align-items: center;
                                    "
                                >
                                    <div style="min-width: 0;">
                                        <div
                                            class="flex flex-wrap
                                                   items-center gap-2"
                                        >
                                            <h5
                                                class="cc-admin-result-title"
                                                style="margin: 0;"
                                            >
                                                {{ $unidad->placa }}
                                            </h5>

                                            <span class="cc-badge cc-badge-active">
                                                Operativa
                                            </span>
                                        </div>

                                        <div class="cc-admin-result-subtitle">
                                            {{ $unidad->marca
                                                ?: 'Sin marca registrada' }}
                                        </div>
                                    </div>

                                    <div style="min-width: 0;">
                                        <div class="cc-admin-result-label">
                                            Empresa
                                        </div>

                                        <div class="cc-admin-result-value">
                                            {{ $empresaNombre }}
                                        </div>
                                    </div>

                                    <div style="min-width: 0;">
                                        <div class="cc-admin-result-label">
                                            Modelo
                                        </div>

                                        <div class="cc-admin-result-value">
                                            {{ $modeloMedicionTexto }}
                                        </div>

                                        <div class="cc-admin-result-value-muted">
                                            Capacidad:
                                            {{ number_format(
                                                (float) $unidad
                                                    ->capacidad_cubierta,
                                                2
                                            ) }}
                                            gal
                                        </div>
                                    </div>

                                    <div style="min-width: 0;">
                                        <div class="cc-admin-result-label">
                                            Historial
                                        </div>

                                        <div class="cc-admin-result-value">
                                            @if ($esPrimerAbastecimiento)
                                                Primer abastecimiento
                                            @else
                                                Ciclo en seguimiento
                                            @endif
                                        </div>

                                        <div class="cc-admin-result-value-muted">
                                            @if ($esPrimerAbastecimiento)
                                                Se establecerá la línea base
                                            @else
                                                Se cerrará el ciclo anterior
                                            @endif
                                        </div>
                                    </div>

                                    <div
                                        style="
                                            display: flex;
                                            justify-content: flex-end;
                                            align-items: center;
                                            grid-column: 1 / -1;
                                            width: 100%;
                                            min-width: 0;
                                        "
                                    >
                                        <a
                                            href="{{ $rutaRegistrar }}"
                                            class="cc-btn-primary cc-btn-result"
                                            style="
                                                width: 100%;
                                                max-width: 15rem;
                                                text-align: center;
                                                white-space: nowrap;
                                            "
                                        >
                                            Registrar abastecimiento
                                        </a>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $unidades
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
        document.addEventListener('DOMContentLoaded', function () {
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
                        label?.dataset.defaultLabel || 'Todas';

                    function actualizarEtiqueta() {
                        const seleccionados = checkboxes.filter(
                            function (checkbox) {
                                return checkbox.checked;
                            }
                        );

                        if (! label) {
                            return;
                        }

                        if (seleccionados.length === 0) {
                            label.textContent = defaultLabel;
                        } else if (seleccionados.length === 1) {
                            const opcion = seleccionados[0].closest(
                                '[data-cc-filter-option]'
                            );

                            const texto = opcion?.querySelector(
                                '[data-cc-filter-option-label]'
                            );

                            label.textContent = texto
                                ? texto.textContent.trim()
                                : '1 seleccionado';
                        } else {
                            label.textContent =
                                seleccionados.length
                                + ' seleccionados';
                        }

                        if (master) {
                            master.checked =
                                seleccionados.length
                                === checkboxes.length
                                && checkboxes.length > 0;

                            master.indeterminate =
                                seleccionados.length > 0
                                && seleccionados.length
                                < checkboxes.length;
                        }
                    }

                    function cerrarOtros() {
                        document
                            .querySelectorAll(
                                '[data-cc-filter-multiselect]'
                            )
                            .forEach(function (otroMultiselect) {
                                if (otroMultiselect === multiselect) {
                                    return;
                                }

                                otroMultiselect
                                    .querySelector(
                                        '[data-cc-filter-toggle]'
                                    )
                                    ?.classList.remove('is-open');

                                otroMultiselect
                                    .querySelector(
                                        '[data-cc-filter-menu]'
                                    )
                                    ?.classList.remove('is-open');
                            });
                    }

                    toggle?.addEventListener('click', function () {
                        cerrarOtros();

                        toggle.classList.toggle('is-open');
                        menu?.classList.toggle('is-open');
                    });

                    master?.addEventListener('change', function () {
                        checkboxes.forEach(function (checkbox) {
                            checkbox.checked = master.checked;
                        });

                        actualizarEtiqueta();
                    });

                    checkboxes.forEach(function (checkbox) {
                        checkbox.addEventListener(
                            'change',
                            actualizarEtiqueta
                        );
                    });

                    actualizarEtiqueta();
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
        });
    </script>
</x-app-layout>