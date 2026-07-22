@php
    $esVentana = $esVentana ?? false;

    $rutaConsulta = $esVentana
        ? route('analisis.rendimientos.index.ventana')
        : route('analisis.rendimientos.index');

    $rutaAlterna = $esVentana
        ? route(
            'analisis.rendimientos.index',
            request()->query()
        )
        : route(
            'analisis.rendimientos.index.ventana',
            request()->query()
        );

    $textoAlterna = $esVentana
        ? 'Volver al sistema'
        : 'Abrir en nueva pestaña';

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

    $modelosTexto = [
        'galones_viaje' => 'Galones por viaje',
        'galones_kilometro' => 'Kilómetros por galón',
        'galones_hora' => 'Galones por hora',
    ];

    $formatoNumero = fn (
        mixed $valor,
        int $decimales = 2
    ): string => number_format(
        (float) ($valor ?? 0),
        $decimales
    );
@endphp


<style>
    .cc-analytics-header {
        display: grid !important;
        grid-template-columns: minmax(0, 1fr) auto !important;
        align-items: start !important;
        gap: 1rem !important;
    }

    .cc-analytics-header > div:last-child {
        display: flex;
        justify-content: flex-end;
        min-width: 0;
    }

    .cc-analytics-summary {
        display: grid !important;
        grid-template-columns:
            repeat(auto-fit, minmax(11.5rem, 1fr)) !important;
        width: 100%;
    }

    .cc-analytics-summary .cc-summary-strip-item {
        display: flex !important;
        min-width: 0;
        min-height: 5.6rem;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        gap: .45rem !important;
        padding: 1rem .8rem !important;
        text-align: center !important;
    }

    .cc-analytics-summary .cc-summary-strip-label,
    .cc-analytics-summary .cc-summary-strip-value {
        display: block !important;
        width: 100%;
        margin: 0 !important;
        text-align: center !important;
    }

    .cc-analytics-summary .cc-summary-strip-label {
        line-height: 1.25 !important;
        white-space: normal !important;
    }

    .cc-analytics-summary .cc-summary-strip-value {
        line-height: 1.15 !important;
        overflow-wrap: anywhere;
        white-space: normal !important;
    }

    .cc-analytics-actions {
        justify-content: flex-end !important;
    }

    @media (max-width: 760px) {
        .cc-analytics-header {
            grid-template-columns: minmax(0, 1fr) !important;
        }

        .cc-analytics-header > div:last-child {
            width: 100%;
            justify-content: stretch;
        }

        .cc-analytics-header > div:last-child > a {
            width: 100%;
        }

        .cc-analytics-summary {
            grid-template-columns:
                repeat(2, minmax(0, 1fr)) !important;
        }

        .cc-analytics-actions {
            display: grid !important;
            grid-template-columns:
                repeat(2, minmax(0, 1fr)) !important;
            gap: .75rem !important;
        }

        .cc-analytics-actions > * {
            width: 100% !important;
            min-width: 0 !important;
        }
    }

    @media (max-width: 430px) {
        .cc-analytics-summary {
            grid-template-columns:
                minmax(0, 1fr) !important;
        }

        .cc-analytics-actions {
            grid-template-columns:
                minmax(0, 1fr) !important;
        }
    }

    /*
     * Mantiene el multiselect abierto por encima de los demás
     * controles del grid. Sin este nivel en el contenedor padre,
     * los campos de la siguiente fila pueden dibujarse sobre el menú.
     */
    .cc-card .cc-filter-panel,
    .cc-card .cc-filter-panel-inline {
        overflow: visible;
    }

    .cc-card .cc-filter-multiselect {
        position: relative;
        z-index: 1;
    }

    .cc-card .cc-filter-multiselect.is-open {
        z-index: 1000;
    }

    .cc-card .cc-filter-multiselect-menu {
        z-index: 1001;
    }
</style>

<div class="cc-card">
    <div class="cc-card-header cc-card-header-compact cc-analytics-header">
        <div>
            <h3 class="cc-title cc-title-compact">
                Análisis de rendimientos
            </h3>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <a
                href="{{ $rutaAlterna }}"
                @unless($esVentana)
                    target="_blank"
                    rel="noopener noreferrer"
                @endunless
                class="cc-btn-secondary cc-btn-wide"
            >
                {{ $textoAlterna }}
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="cc-alert cc-alert-danger">
            <div class="font-bold">
                No fue posible completar el análisis.
            </div>

            <ul class="cc-alert-list">
                @foreach (
                    collect($errors->all())
                        ->unique()
                        ->values()
                    as $error
                )
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <form
        method="GET"
        action="{{ $rutaConsulta }}"
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
                    Filtros de análisis
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

                                    @foreach (
                                        $empresasSelector
                                        as $empresaOpcion
                                    )
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
                                                        (int) $empresaOpcion->id,
                                                        $empresaIds ?? [],
                                                        true
                                                    )
                                                )
                                                data-cc-filter-checkbox
                                                data-empresa-checkbox
                                            >

                                            <span data-cc-filter-option-label>
                                                {{
                                                    $empresaOpcion
                                                        ->nombre_comercial
                                                    ?: $empresaOpcion
                                                        ->nombre_legal
                                                }}
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
                            @foreach (
                                $empresasSelector
                                as $empresaOpcion
                            )
                                <option selected>
                                    {{
                                        $empresaOpcion->nombre_comercial
                                        ?: $empresaOpcion->nombre_legal
                                    }}
                                </option>
                            @endforeach
                        </select>

                        @foreach (
                            $empresaIds ?? []
                            as $empresaSeleccionadaId
                        )
                            <input
                                type="hidden"
                                name="empresa_ids[]"
                                value="{{ $empresaSeleccionadaId }}"
                            >
                        @endforeach
                    @endif
                </div>

                <div class="cc-field">
                    <label for="fecha_desde">
                        Fecha de inicio
                    </label>

                    <input
                        id="fecha_desde"
                        type="date"
                        name="fecha_desde"
                        value="{{ $fechaDesde }}"
                        class="cc-input"
                    >
                </div>

                <div class="cc-field">
                    <label for="fecha_hasta">
                        Fecha final
                    </label>

                    <input
                        id="fecha_hasta"
                        type="date"
                        name="fecha_hasta"
                        value="{{ $fechaHasta }}"
                        class="cc-input"
                    >
                </div>

                <div class="cc-field">
                    <label>
                        Motorista
                    </label>

                    <div
                        class="cc-filter-multiselect"
                        data-cc-filter-multiselect
                        data-filter-type="motorista"
                    >
                        <button
                            type="button"
                            class="cc-filter-multiselect-toggle"
                            data-cc-filter-toggle
                        >
                            <span
                                data-cc-filter-label
                                data-default-label="Todos"
                                data-singular-suffix="seleccionado"
                                data-plural-suffix="seleccionados"
                            >
                                Todos
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

                                @foreach (
                                    $motoristasSelector
                                    as $motoristaOpcion
                                )
                                    <label
                                        class="cc-filter-multiselect-option"
                                        data-cc-filter-option
                                        data-motorista-option
                                        data-empresa-id="{{
                                            $motoristaOpcion->empresa_id
                                        }}"
                                    >
                                        <input
                                            type="checkbox"
                                            name="motorista_ids[]"
                                            value="{{ $motoristaOpcion->id }}"
                                            @checked(
                                                in_array(
                                                    (int) $motoristaOpcion->id,
                                                    $motoristaIds ?? [],
                                                    true
                                                )
                                            )
                                            data-cc-filter-checkbox
                                            data-motorista-checkbox
                                        >

                                        <span data-cc-filter-option-label>
                                            {{
                                                $motoristaOpcion
                                                    ->nombre_completo
                                            }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cc-field">
                    <label>
                        Modelo de operación
                    </label>

                    <div
                        class="cc-filter-multiselect"
                        data-cc-filter-multiselect
                        data-filter-type="modelo"
                    >
                        <button
                            type="button"
                            class="cc-filter-multiselect-toggle"
                            data-cc-filter-toggle
                        >
                            <span
                                data-cc-filter-label
                                data-default-label="Todos"
                                data-singular-suffix="seleccionado"
                                data-plural-suffix="seleccionados"
                            >
                                Todos
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

                                @foreach (
                                    $opcionesModelos
                                    as $modeloValor => $modeloEtiqueta
                                )
                                    <label
                                        class="cc-filter-multiselect-option"
                                        data-cc-filter-option
                                    >
                                        <input
                                            type="checkbox"
                                            name="modelos_medicion[]"
                                            value="{{ $modeloValor }}"
                                            @checked(
                                                in_array(
                                                    $modeloValor,
                                                    $modelosMedicion ?? [],
                                                    true
                                                )
                                            )
                                            data-cc-filter-checkbox
                                        >

                                        <span data-cc-filter-option-label>
                                            {{ $modeloEtiqueta }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cc-field">
                    <label>
                        Unidad
                    </label>

                    <div
                        class="cc-filter-multiselect"
                        data-cc-filter-multiselect
                        data-filter-type="unidad"
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

                                @foreach (
                                    $unidadesSelector
                                    as $unidadOpcion
                                )
                                    <label
                                        class="cc-filter-multiselect-option"
                                        data-cc-filter-option
                                        data-unidad-option
                                        data-empresa-id="{{
                                            $unidadOpcion->empresa_id
                                        }}"
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
                                            data-unidad-checkbox
                                        >

                                        <span data-cc-filter-option-label>
                                            {{ $unidadOpcion->placa }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="cc-field"
                    style="grid-column: span 2;"
                >
                    <label for="busqueda">
                        Búsqueda general
                    </label>

                    <input
                        id="busqueda"
                        type="text"
                        name="busqueda"
                        value="{{ $busqueda }}"
                        maxlength="150"
                        class="cc-input"
                        placeholder="Empresa, placa, motorista, origen, ruta o número de abastecimiento"
                    >
                </div>

                <div
                    class="cc-standard-filter-actions cc-analytics-actions"
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
                    >
                        Consultar
                    </button>

                    <a
                        href="{{ $rutaConsulta }}"
                        class="cc-btn-secondary"
                    >
                        Limpiar
                    </a>
                </div>
            </div>
        </div>
    </form>

    @if ($hayFiltros)
        @php
            $tarjetas = [];

            if ($tipoResumen === 'viaje') {
                $tarjetas = [
                    [
                        'etiqueta' => 'Ciclos analizados',
                        'valor' => $resumen['viaje']['ciclos'],
                    ],
                    [
                        'etiqueta' => 'Galones reales',
                        'valor' => $formatoNumero(
                            $resumen['viaje']['galones_reales']
                        ) . ' gal',
                    ],
                    [
                        'etiqueta' => 'Galones teóricos',
                        'valor' => $formatoNumero(
                            $resumen['viaje']['galones_teoricos']
                        ) . ' gal',
                    ],
                    [
                        'etiqueta' => 'Diferencia total',
                        'valor' => $formatoNumero(
                            $resumen['viaje']['diferencia_total']
                        ) . ' gal',
                    ],
                    [
                        'etiqueta' => 'Variación promedio',
                        'valor' => $formatoNumero(
                            $resumen['viaje']['variacion_promedio']
                        ) . ' %',
                    ],
                    [
                        'etiqueta' => 'Sobre lo esperado',
                        'valor' => $resumen['viaje']['sobre_esperado'],
                    ],
                ];
            } elseif ($tipoResumen === 'kilometro') {
                $tarjetas = [
                    [
                        'etiqueta' => 'Ciclos analizados',
                        'valor' => $resumen['kilometro']['ciclos'],
                    ],
                    [
                        'etiqueta' => 'KM recorridos',
                        'valor' => $formatoNumero(
                            $resumen['kilometro']['kilometros_recorridos']
                        ) . ' km',
                    ],
                    [
                        'etiqueta' => 'Galones consumidos',
                        'valor' => $formatoNumero(
                            $resumen['kilometro']['galones_consumidos']
                        ) . ' gal',
                    ],
                    [
                        'etiqueta' => 'Promedio',
                        'valor' => $formatoNumero(
                            $resumen['kilometro']['rendimiento_promedio']
                        ) . ' km/gal',
                    ],
                    [
                        'etiqueta' => 'Mejor rendimiento',
                        'valor' => $formatoNumero(
                            $resumen['kilometro']['mejor_rendimiento']
                        ) . ' km/gal',
                    ],
                    [
                        'etiqueta' => 'Menor rendimiento',
                        'valor' => $formatoNumero(
                            $resumen['kilometro']['menor_rendimiento']
                        ) . ' km/gal',
                    ],
                ];
            } elseif ($tipoResumen === 'hora') {
                $tarjetas = [
                    [
                        'etiqueta' => 'Ciclos analizados',
                        'valor' => $resumen['hora']['ciclos'],
                    ],
                    [
                        'etiqueta' => 'Horas operadas',
                        'valor' => $formatoNumero(
                            $resumen['hora']['horas_operadas']
                        ) . ' h',
                    ],
                    [
                        'etiqueta' => 'Galones consumidos',
                        'valor' => $formatoNumero(
                            $resumen['hora']['galones_consumidos']
                        ) . ' gal',
                    ],
                    [
                        'etiqueta' => 'Consumo promedio',
                        'valor' => $formatoNumero(
                            $resumen['hora']['consumo_promedio']
                        ) . ' gal/h',
                    ],
                    [
                        'etiqueta' => 'Menor consumo',
                        'valor' => $formatoNumero(
                            $resumen['hora']['menor_consumo']
                        ) . ' gal/h',
                    ],
                    [
                        'etiqueta' => 'Mayor consumo',
                        'valor' => $formatoNumero(
                            $resumen['hora']['mayor_consumo']
                        ) . ' gal/h',
                    ],
                ];
            } else {
                $tarjetas = [
                    [
                        'etiqueta' => 'Registros',
                        'valor' => $resumen['comun']['registros'],
                    ],
                    [
                        'etiqueta' => 'Ciclos analizados',
                        'valor' => $resumen['comun']['ciclos'],
                    ],
                    [
                        'etiqueta' => 'Unidades',
                        'valor' => $resumen['comun']['unidades'],
                    ],
                    [
                        'etiqueta' => 'Motoristas',
                        'valor' => $resumen['comun']['motoristas'],
                    ],
                    [
                        'etiqueta' => 'Galones consumidos',
                        'valor' => $formatoNumero(
                            $resumen['comun']['galones_consumidos']
                        ) . ' gal',
                    ],
                    [
                        'etiqueta' => 'Líneas base',
                        'valor' => $resumen['comun']['lineas_base'],
                    ],
                ];
            }
        @endphp

        <div
            class="cc-summary-strip cc-analytics-summary"
            style="margin-bottom: 1.25rem;"
        >
            @foreach ($tarjetas as $tarjeta)
                <div class="cc-summary-strip-item">
                    <span class="cc-summary-strip-label">
                        {{ $tarjeta['etiqueta'] }}
                    </span>

                    <span class="cc-summary-strip-value">
                        {{ $tarjeta['valor'] }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    @if (
        $hayFiltros
        && $abastecimientos->total() > 0
    )
        <div
            class="mb-4 flex justify-end
                   text-sm text-[var(--cc-text-muted)]"
        >
            Mostrando

            <span
                class="mx-1 font-bold
                       text-[var(--cc-text-main)]"
            >
                {{ $abastecimientos->firstItem() }}
            </span>

            -

            <span
                class="mx-1 font-bold
                       text-[var(--cc-text-main)]"
            >
                {{ $abastecimientos->lastItem() }}
            </span>

            de

            <span
                class="ml-1 font-bold
                       text-[var(--cc-text-main)]"
            >
                {{ $abastecimientos->total() }}
            </span>
        </div>
    @endif

    @if (! $hayFiltros)
        <section
            class="cc-empty-panel
                   cc-empty-panel-compact"
        >
            <h5>
                Análisis pendiente
            </h5>

            <p>
                Seleccione los criterios necesarios para consultar
                los ciclos de rendimiento.
            </p>
        </section>
    @elseif ($abastecimientos->isEmpty())
        <section
            class="cc-empty-panel
                   cc-empty-panel-compact"
        >
            <h5>
                Sin resultados
            </h5>

            <p>
                No existen ciclos de rendimiento que coincidan
                con los filtros seleccionados.
            </p>
        </section>
    @else
        <div class="cc-table-adaptive-wrapper">
            <table
                class="cc-table-adaptive"
                style="min-width: 104rem;"
            >
                <thead>
                    <tr>
                        <th>
                            Fecha de cierre
                        </th>

                        <th>
                            Empresa
                        </th>

                        <th>
                            Unidad
                        </th>

                        <th>
                            Motorista
                        </th>

                        <th>
                            Modelo
                        </th>

                        <th>
                            Origen
                        </th>

                        <th>
                            Ciclo
                        </th>

                        <th>
                            Resultado
                        </th>

                        <th>
                            Condición
                        </th>

                        <th>
                            Acción
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @foreach (
                        $abastecimientos
                        as $abastecimiento
                    )
                        @php
                            $modeloTexto =
                                $modelosTexto[
                                    $abastecimiento
                                        ->modelo_medicion
                                ] ?? 'No definido';

                            $esLineaBase =
                                (bool) $abastecimiento
                                    ->es_linea_base_analitica;

                            $rutaFicha = route(
                                $esVentana
                                    ? 'abastecimientos.show.ventana'
                                    : 'abastecimientos.show',
                                array_merge(
                                    $queryParams,
                                    [
                                        'abastecimiento' =>
                                            $abastecimiento,

                                        'origen_retorno' =>
                                            'consulta',
                                    ]
                                )
                            );

                            $resultadoPrincipal = '—';
                            $resultadoSecundario = null;

                            if ($esLineaBase) {
                                $resultadoPrincipal =
                                    'Línea base';
                            } elseif (
                                $abastecimiento->modelo_medicion
                                === 'galones_kilometro'
                            ) {
                                $resultadoPrincipal =
                                    $formatoNumero(
                                        $abastecimiento
                                            ->kilometros_por_galon
                                    )
                                    . ' km/gal';

                                $resultadoSecundario =
                                    $formatoNumero(
                                        $abastecimiento
                                            ->diferencia_kilometraje
                                    )
                                    . ' km · '
                                    . $formatoNumero(
                                        $abastecimiento
                                            ->combustible_consumido_ciclo
                                    )
                                    . ' gal';
                            } elseif (
                                $abastecimiento->modelo_medicion
                                === 'galones_hora'
                            ) {
                                $resultadoPrincipal =
                                    $formatoNumero(
                                        $abastecimiento
                                            ->galones_por_hora
                                    )
                                    . ' gal/h';

                                $resultadoSecundario =
                                    $formatoNumero(
                                        $abastecimiento
                                            ->diferencia_horometro
                                    )
                                    . ' h · '
                                    . $formatoNumero(
                                        $abastecimiento
                                            ->combustible_consumido_ciclo
                                    )
                                    . ' gal';
                            } elseif (
                                $abastecimiento->modelo_medicion
                                === 'galones_viaje'
                            ) {
                                $resultadoPrincipal =
                                    $formatoNumero(
                                        $abastecimiento
                                            ->combustible_consumido_ciclo
                                    )
                                    . ' gal reales';

                                $resultadoSecundario =
                                    $formatoNumero(
                                        $abastecimiento
                                            ->galones_teoricos
                                    )
                                    . ' gal teóricos';

                                if (
                                    ! is_null(
                                        $abastecimiento
                                            ->variacion_galones_porcentaje
                                    )
                                ) {
                                    $resultadoSecundario .=
                                        ' · '
                                        . $formatoNumero(
                                            $abastecimiento
                                                ->variacion_galones_porcentaje
                                        )
                                        . ' %';
                                }
                            }
                        @endphp

                        <tr>
                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        optional(
                                            $abastecimiento
                                                ->fecha_hora_abastecimiento
                                        )->format('d/m/Y')
                                        ?: '—'
                                    }}
                                </div>

                                <div class="cc-table-adaptive-muted">
                                    {{
                                        optional(
                                            $abastecimiento
                                                ->fecha_hora_abastecimiento
                                        )->format('H:i')
                                        ?: '—'
                                    }}
                                </div>
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        $abastecimiento
                                            ->empresa_texto_analitico
                                    }}
                                </div>
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        $abastecimiento
                                            ->unidad_texto_analitico
                                    }}
                                </div>

                                <div class="cc-table-adaptive-muted">
                                    {{
                                        collect([
                                            $abastecimiento
                                                ->unidad_marca_snapshot,
                                            $abastecimiento
                                                ->unidad_modelo_snapshot,
                                        ])
                                            ->filter()
                                            ->implode(' · ')
                                        ?: 'Sin descripción'
                                    }}
                                </div>
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        $abastecimiento
                                            ->motorista_texto_analitico
                                    }}
                                </div>
                            </td>

                            <td>
                                <span class="cc-badge cc-badge-active">
                                    {{ $modeloTexto }}
                                </span>
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{
                                        $abastecimiento
                                            ->origen_texto_analitico
                                    }}
                                </div>

                                <div class="cc-table-adaptive-muted">
                                    {{
                                        $abastecimiento
                                            ->tipo_origen
                                        === 'interno'
                                            ? 'Interno'
                                            : 'Externo'
                                    }}
                                </div>
                            </td>

                            <td>
                                @if ($esLineaBase)
                                    <span class="cc-badge cc-badge-warning">
                                        Línea base
                                    </span>
                                @elseif (
                                    $abastecimiento
                                        ->modelo_medicion
                                    === 'galones_viaje'
                                )
                                    <div class="cc-table-adaptive-strong">
                                        {{
                                            (int)
                                            $abastecimiento
                                                ->total_rutas
                                        }}
                                        ruta{{
                                            (int)
                                            $abastecimiento
                                                ->total_rutas
                                            === 1
                                                ? ''
                                                : 's'
                                        }}
                                    </div>

                                    <div class="cc-table-adaptive-muted">
                                        {{
                                            $formatoNumero(
                                                $abastecimiento
                                                    ->kilometros_teoricos
                                            )
                                        }}
                                        km teóricos
                                    </div>
                                @elseif (
                                    $abastecimiento
                                        ->modelo_medicion
                                    === 'galones_hora'
                                )
                                    <div class="cc-table-adaptive-strong">
                                        {{
                                            $formatoNumero(
                                                $abastecimiento
                                                    ->horometro_anterior
                                            )
                                        }}
                                        →
                                        {{
                                            $formatoNumero(
                                                $abastecimiento
                                                    ->horometro_actual
                                            )
                                        }}
                                    </div>

                                    <div class="cc-table-adaptive-muted">
                                        Horómetro
                                    </div>
                                @else
                                    <div class="cc-table-adaptive-strong">
                                        {{
                                            $formatoNumero(
                                                $abastecimiento
                                                    ->kilometraje_anterior
                                            )
                                        }}
                                        →
                                        {{
                                            $formatoNumero(
                                                $abastecimiento
                                                    ->kilometraje_actual
                                            )
                                        }}
                                    </div>

                                    <div class="cc-table-adaptive-muted">
                                        Kilometraje
                                    </div>
                                @endif
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{ $resultadoPrincipal }}
                                </div>

                                @if ($resultadoSecundario)
                                    <div class="cc-table-adaptive-muted">
                                        {{ $resultadoSecundario }}
                                    </div>
                                @endif
                            </td>

                            <td>
                                @php
                                    $estadoAnalitico =
                                        $abastecimiento
                                            ->estado_analitico;

                                    $claseEstado = match (
                                        $estadoAnalitico
                                    ) {
                                        'Línea base' =>
                                            'cc-badge-warning',

                                        'Consumo superior a lo esperado',
                                        'Información incompleta' =>
                                            'cc-badge-inactive',

                                        default =>
                                            'cc-badge-active',
                                    };
                                @endphp

                                <span class="cc-badge {{ $claseEstado }}">
                                    {{ $estadoAnalitico }}
                                </span>

                                @if (
                                    $abastecimiento
                                        ->modelo_medicion
                                    === 'galones_viaje'
                                    && $abastecimiento
                                        ->rutas
                                        ->isNotEmpty()
                                )
                                    <button
                                        type="button"
                                        class="cc-btn-secondary mt-2"
                                        style="
                                            padding: .45rem .75rem;
                                            font-size: .78rem;
                                        "
                                        data-ruta-toggle="{{
                                            $abastecimiento->id
                                        }}"
                                    >
                                        Ver rutas
                                    </button>
                                @endif
                            </td>

                            <td>
                                <a
                                    href="{{ $rutaFicha }}"
                                    @unless($esVentana)
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    @endunless
                                    class="cc-btn-secondary"
                                    style="
                                        display: inline-flex;
                                        width: auto;
                                        min-width: 0;
                                        white-space: nowrap;
                                    "
                                >
                                    Ver ficha
                                </a>
                            </td>
                        </tr>

                        @if (
                            $abastecimiento
                                ->modelo_medicion
                            === 'galones_viaje'
                            && $abastecimiento
                                ->rutas
                                ->isNotEmpty()
                        )
                            <tr
                                id="detalle-rutas-{{
                                    $abastecimiento->id
                                }}"
                                data-ruta-detalle
                                hidden
                            >
                                <td colspan="10">
                                    <div
                                        class="cc-detail-section"
                                        style="margin: .5rem 0;"
                                    >
                                        <div
                                            class="cc-detail-section-header"
                                        >
                                            <h5>
                                                Recorrido consolidado
                                            </h5>
                                        </div>

                                        <div
                                            class="cc-table-adaptive-wrapper"
                                        >
                                            <table
                                                class="cc-table-adaptive"
                                                style="min-width: 62rem;"
                                            >
                                                <thead>
                                                    <tr>
                                                        <th>Orden</th>
                                                        <th>Recorrido</th>
                                                        <th>Tipo</th>
                                                        <th>KM base</th>
                                                        <th>Factor</th>
                                                        <th>KM aplicados</th>
                                                        <th>Galones base</th>
                                                        <th>Galones aplicados</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    @foreach (
                                                        $abastecimiento
                                                            ->rutas
                                                        as $rutaDetalle
                                                    )
                                                        <tr>
                                                            <td>
                                                                {{
                                                                    $rutaDetalle
                                                                        ->orden
                                                                }}
                                                            </td>

                                                            <td>
                                                                <div
                                                                    class="cc-table-adaptive-strong"
                                                                >
                                                                    {{
                                                                        $rutaDetalle
                                                                            ->recorrido_texto
                                                                    }}
                                                                </div>

                                                                <div
                                                                    class="cc-table-adaptive-muted"
                                                                >
                                                                    {{
                                                                        $rutaDetalle
                                                                            ->ruta_nombre_snapshot
                                                                        ?: 'Ruta histórica'
                                                                    }}
                                                                </div>
                                                            </td>

                                                            <td>
                                                                {{
                                                                    $rutaDetalle
                                                                        ->tipo_recorrido_texto
                                                                }}
                                                            </td>

                                                            <td>
                                                                {{
                                                                    $formatoNumero(
                                                                        $rutaDetalle
                                                                            ->kilometros_base_snapshot
                                                                    )
                                                                }}
                                                                km
                                                            </td>

                                                            <td>
                                                                ×{{
                                                                    $rutaDetalle
                                                                        ->factor_recorrido
                                                                }}
                                                            </td>

                                                            <td>
                                                                {{
                                                                    $formatoNumero(
                                                                        $rutaDetalle
                                                                            ->kilometros_aplicados
                                                                    )
                                                                }}
                                                                km
                                                            </td>

                                                            <td>
                                                                {{
                                                                    $formatoNumero(
                                                                        $rutaDetalle
                                                                            ->galones_base_snapshot
                                                                    )
                                                                }}
                                                                gal
                                                            </td>

                                                            <td>
                                                                {{
                                                                    $formatoNumero(
                                                                        $rutaDetalle
                                                                            ->galones_aplicados
                                                                    )
                                                                }}
                                                                gal
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{
                $abastecimientos
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

<script>
    document.addEventListener(
        'DOMContentLoaded',
        function () {
            const multiselects = Array.from(
                document.querySelectorAll(
                    '[data-cc-filter-multiselect]'
                )
            );

            function cerrarTodos(
                excepto = null
            ) {
                multiselects.forEach(
                    function (multiselect) {
                        if (
                            excepto
                            && multiselect === excepto
                        ) {
                            return;
                        }

                        multiselect.classList.remove(
                            'is-open'
                        );

                        multiselect
                            .querySelector(
                                '[data-cc-filter-toggle]'
                            )
                            ?.classList.remove('is-open');

                        multiselect
                            .querySelector(
                                '[data-cc-filter-menu]'
                            )
                            ?.classList.remove('is-open');
                    }
                );
            }

            multiselects.forEach(
                function (multiselect) {
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

                    const checkboxes =
                        Array.from(
                            multiselect.querySelectorAll(
                                '[data-cc-filter-checkbox]'
                            )
                        );

                    function checkboxesVisibles() {
                        return checkboxes.filter(
                            function (checkbox) {
                                return ! checkbox
                                    .closest(
                                        '[data-cc-filter-option]'
                                    )
                                    ?.hidden;
                            }
                        );
                    }

                    function actualizarEtiqueta() {
                        const visibles =
                            checkboxesVisibles();

                        const seleccionados =
                            visibles.filter(
                                function (checkbox) {
                                    return checkbox.checked;
                                }
                            );

                        const defaultLabel =
                            label?.dataset
                                .defaultLabel
                            || 'Todos';

                        if (label) {
                            if (
                                seleccionados.length === 0
                            ) {
                                label.textContent =
                                    defaultLabel;
                            } else if (
                                seleccionados.length === 1
                            ) {
                                const opcion =
                                    seleccionados[0]
                                        .closest(
                                            '[data-cc-filter-option]'
                                        );

                                label.textContent =
                                    opcion
                                        ?.querySelector(
                                            '[data-cc-filter-option-label]'
                                        )
                                        ?.textContent
                                        ?.trim()
                                    || '1 seleccionado';
                            } else {
                                const sufijo =
                                    label.dataset
                                        .pluralSuffix
                                    || 'seleccionados';

                                label.textContent =
                                    seleccionados.length
                                    + ' '
                                    + sufijo;
                            }
                        }

                        if (master) {
                            master.checked =
                                visibles.length > 0
                                && seleccionados.length
                                    === visibles.length;

                            master.indeterminate =
                                seleccionados.length > 0
                                && seleccionados.length
                                    < visibles.length;
                        }
                    }

                    toggle?.addEventListener(
                        'click',
                        function () {
                            cerrarTodos(multiselect);

                            const abrira =
                                ! multiselect.classList
                                    .contains('is-open');

                            multiselect.classList.toggle(
                                'is-open',
                                abrira
                            );

                            toggle.classList.toggle(
                                'is-open',
                                abrira
                            );

                            menu?.classList.toggle(
                                'is-open',
                                abrira
                            );
                        }
                    );

                    master?.addEventListener(
                        'change',
                        function () {
                            checkboxesVisibles()
                                .forEach(
                                    function (checkbox) {
                                        checkbox.checked =
                                            master.checked;
                                    }
                                );

                            actualizarEtiqueta();
                        }
                    );

                    checkboxes.forEach(
                        function (checkbox) {
                            checkbox.addEventListener(
                                'change',
                                actualizarEtiqueta
                            );
                        }
                    );

                    multiselect
                        .actualizarEtiqueta =
                            actualizarEtiqueta;

                    actualizarEtiqueta();
                }
            );

            function empresasSeleccionadas() {
                return Array.from(
                    document.querySelectorAll(
                        '[data-empresa-checkbox]:checked'
                    )
                ).map(
                    function (checkbox) {
                        return checkbox.value;
                    }
                );
            }

            function filtrarDependientes() {
                const empresas =
                    empresasSeleccionadas();

                [
                    [
                        '[data-unidad-option]',
                        '[data-unidad-checkbox]',
                    ],
                    [
                        '[data-motorista-option]',
                        '[data-motorista-checkbox]',
                    ],
                ].forEach(
                    function (configuracion) {
                        document
                            .querySelectorAll(
                                configuracion[0]
                            )
                            .forEach(
                                function (opcion) {
                                    const visible =
                                        empresas.length === 0
                                        || empresas.includes(
                                            opcion.dataset
                                                .empresaId
                                        );

                                    opcion.hidden =
                                        ! visible;

                                    if (! visible) {
                                        const checkbox =
                                            opcion.querySelector(
                                                configuracion[1]
                                            );

                                        if (checkbox) {
                                            checkbox.checked =
                                                false;
                                        }
                                    }
                                }
                            );
                    }
                );

                multiselects.forEach(
                    function (multiselect) {
                        multiselect
                            .actualizarEtiqueta
                            ?.();
                    }
                );
            }

            document
                .querySelectorAll(
                    '[data-empresa-checkbox]'
                )
                .forEach(
                    function (checkbox) {
                        checkbox.addEventListener(
                            'change',
                            filtrarDependientes
                        );
                    }
                );

            document
                .querySelector(
                    '[data-filter-type="empresa"] '
                    + '[data-cc-filter-master]'
                )
                ?.addEventListener(
                    'change',
                    function () {
                        window.setTimeout(
                            filtrarDependientes,
                            0
                        );
                    }
                );

            filtrarDependientes();

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

                    cerrarTodos();
                }
            );

            document.addEventListener(
                'keydown',
                function (event) {
                    if (event.key === 'Escape') {
                        cerrarTodos();
                    }
                }
            );

            document
                .querySelectorAll(
                    '[data-ruta-toggle]'
                )
                .forEach(
                    function (button) {
                        button.addEventListener(
                            'click',
                            function () {
                                const id =
                                    button.dataset
                                        .rutaToggle;

                                const detalle =
                                    document.getElementById(
                                        'detalle-rutas-'
                                        + id
                                    );

                                if (! detalle) {
                                    return;
                                }

                                detalle.hidden =
                                    ! detalle.hidden;

                                button.textContent =
                                    detalle.hidden
                                        ? 'Ver rutas'
                                        : 'Ocultar rutas';
                            }
                        );
                    }
                );
        }
    );
</script>