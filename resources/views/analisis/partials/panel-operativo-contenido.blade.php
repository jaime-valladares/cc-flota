@php
        $esVentana = $esVentana ?? false;

        $rutaConsulta = $esVentana
            ? route('analisis.panel-operativo.ventana')
            : route('analisis.panel-operativo');

        $rutaAlterna = $esVentana
            ? route(
                'analisis.panel-operativo',
                request()->query()
            )
            : route(
                'analisis.panel-operativo.ventana',
                request()->query()
            );

        $textoAlterna = $esVentana
            ? 'Volver al sistema'
            : 'Abrir en nueva pestaña';

        $consultaEjecutada = request()->boolean('consultar')
            || request()->hasAny([
                'empresa_ids',
                'unidad_ids',
                'modelos_medicion',
                'total_tanques',
                'busqueda',
                'empresa_sort',
                'empresa_direction',
                'unidad_sort',
                'unidad_direction',
                'empresa_page',
                'unidad_page',
            ]);

        $totalEmpresas = (int) (
            ($kpis['empresas_activas'] ?? 0)
            + ($kpis['empresas_inactivas'] ?? 0)
        );

        $totalUnidades = (int) (
            ($kpis['unidades_activas'] ?? 0)
            + ($kpis['unidades_registradas'] ?? 0)
            + ($kpis['unidades_inactivas'] ?? 0)
        );

        $unidadesOperativas = (int) (
            $kpis['unidades_cobertura_completa'] ?? 0
        );

        $unidadesNoOperativas = max(
            0,
            $totalUnidades - $unidadesOperativas
        );

        $coberturaCompleta = (int) (
            $kpis['unidades_cobertura_completa'] ?? 0
        );

        $coberturaIncompleta = (int) (
            $kpis['unidades_cobertura_incompleta'] ?? 0
        );

        $sinLicencia = (int) (
            $kpis['unidades_sin_licencia_activa'] ?? 0
        );

        $porcentajeCobertura = $totalUnidades > 0
            ? round(($coberturaCompleta / $totalUnidades) * 100, 1)
            : 0;

        $porcentajeNoOperativas = $totalUnidades > 0
            ? round(($unidadesNoOperativas / $totalUnidades) * 100, 1)
            : 0;

        $tarjetas = [
            [
                'etiqueta' => 'Empresas analizadas',
                'valor' => $totalEmpresas,
            ],
            [
                'etiqueta' => 'Unidades totales',
                'valor' => $totalUnidades,
            ],
            [
                'etiqueta' => 'Unidades operativas',
                'valor' => $unidadesOperativas,
            ],
            [
                'etiqueta' => 'No operativas',
                'valor' => $unidadesNoOperativas
                    . ' · '
                    . $porcentajeNoOperativas
                    . ' %',
            ],
            [
                'etiqueta' => 'Cobertura completa',
                'valor' => $coberturaCompleta
                    . ' · '
                    . $porcentajeCobertura
                    . ' %',
            ],
            [
                'etiqueta' => 'Cobertura incompleta',
                'valor' => $coberturaIncompleta,
            ],
        ];

        $empresaSortActual = $empresaSort ?? 'alertas';
        $empresaDirectionActual = $empresaDirection ?? 'desc';

        $unidadSortActual = $unidadSort ?? 'prioridad';
        $unidadDirectionActual = $unidadDirection ?? 'asc';

        $urlOrdenEmpresa = function (string $campo) use (
            $rutaConsulta,
            $empresaSortActual,
            $empresaDirectionActual
        ): string {
            $nuevaDireccion = (
                $empresaSortActual === $campo
                && $empresaDirectionActual === 'asc'
            )
                ? 'desc'
                : 'asc';

            return $rutaConsulta . '?' . http_build_query(
                array_merge(
                    request()->query(),
                    [
                        'consultar' => 1,
                        'empresa_sort' => $campo,
                        'empresa_direction' => $nuevaDireccion,
                        'empresa_page' => null,
                    ]
                )
            );
        };

        $urlOrdenUnidad = function (string $campo) use (
            $rutaConsulta,
            $unidadSortActual,
            $unidadDirectionActual
        ): string {
            $nuevaDireccion = (
                $unidadSortActual === $campo
                && $unidadDirectionActual === 'asc'
            )
                ? 'desc'
                : 'asc';

            return $rutaConsulta . '?' . http_build_query(
                array_merge(
                    request()->query(),
                    [
                        'consultar' => 1,
                        'unidad_sort' => $campo,
                        'unidad_direction' => $nuevaDireccion,
                        'unidad_page' => null,
                    ]
                )
            );
        };

        $indicadorOrdenEmpresa = function (string $campo) use (
            $empresaSortActual,
            $empresaDirectionActual
        ): string {
            if ($empresaSortActual !== $campo) {
                return '↕';
            }

            return $empresaDirectionActual === 'asc'
                ? '↑'
                : '↓';
        };

        $indicadorOrdenUnidad = function (string $campo) use (
            $unidadSortActual,
            $unidadDirectionActual
        ): string {
            if ($unidadSortActual !== $campo) {
                return '↕';
            }

            return $unidadDirectionActual === 'asc'
                ? '↑'
                : '↓';
        };

        $alertas = collect([
            [
                'titulo' => 'Unidades sin licencia activa',
                'valor' => $sinLicencia,
                'porcentaje' => $totalUnidades > 0
                    ? round(($sinLicencia / $totalUnidades) * 100, 1)
                    : 0,
                'prioridad' => $sinLicencia > 0 ? 'alta' : 'normal',
                'detalle' => 'Requieren revisión documental antes de operar.',
            ],
            [
                'titulo' => 'Cobertura incompleta',
                'valor' => $coberturaIncompleta,
                'porcentaje' => $totalUnidades > 0
                    ? round(($coberturaIncompleta / $totalUnidades) * 100, 1)
                    : 0,
                'prioridad' => $coberturaIncompleta > 0
                    ? 'alta'
                    : 'normal',
                'detalle' => 'Unidades con puntos de seguridad pendientes.',
            ],
            [
                'titulo' => 'Registradas pendientes',
                'valor' => (int) ($kpis['unidades_registradas'] ?? 0),
                'porcentaje' => $totalUnidades > 0
                    ? round(
                        (
                            ($kpis['unidades_registradas'] ?? 0)
                            / $totalUnidades
                        ) * 100,
                        1
                    )
                    : 0,
                'prioridad' => ($kpis['unidades_registradas'] ?? 0) > 0
                    ? 'media'
                    : 'normal',
                'detalle' => 'Pendientes de completar activación operativa.',
            ],
            [
                'titulo' => 'Unidades inactivas',
                'valor' => (int) ($kpis['unidades_inactivas'] ?? 0),
                'porcentaje' => $totalUnidades > 0
                    ? round(
                        (
                            ($kpis['unidades_inactivas'] ?? 0)
                            / $totalUnidades
                        ) * 100,
                        1
                    )
                    : 0,
                'prioridad' => 'informativa',
                'detalle' => 'Disponibles únicamente para consulta histórica.',
            ],
        ]);
    @endphp


    <div class="cc-card">
                <div
                    class="cc-card-header
                           cc-card-header-compact
                           cc-operational-header cc-analytics-header"
                >
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Control Operativo de Flota
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
                                    repeat(
                                        auto-fit,
                                        minmax(14rem, 1fr)
                                    );
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
                                    >
                                        <button
                                            type="button"
                                            class="cc-filter-multiselect-toggle"
                                            data-cc-filter-toggle
                                        >
                                            <span
                                                data-cc-filter-label
                                                data-default-label="Todas"
                                                data-plural-suffix="seleccionadas"
                                            >
                                                Todas
                                            </span>

                                            <span
                                                class="cc-filter-multiselect-arrow"
                                            >
                                                ⌄
                                            </span>
                                        </button>

                                        <div
                                            class="cc-filter-multiselect-menu"
                                            data-cc-filter-menu
                                        >
                                            <div
                                                class="cc-filter-multiselect-list"
                                            >
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
                                                    $empresasFiltro
                                                    as $empresa
                                                )
                                                    <label
                                                        class="cc-filter-multiselect-option"
                                                        data-cc-filter-option
                                                    >
                                                        <input
                                                            type="checkbox"
                                                            name="empresa_ids[]"
                                                            value="{{ $empresa->id }}"
                                                            @checked(
                                                                $empresasSeleccionadas
                                                                    ->contains(
                                                                        (int) $empresa->id
                                                                    )
                                                            )
                                                            data-cc-filter-checkbox
                                                        >

                                                        <span
                                                            data-cc-filter-option-label
                                                        >
                                                            {{
                                                                $empresa
                                                                    ->nombre_legal
                                                                ?: $empresa
                                                                    ->nombre_comercial
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
                                        <option selected>
                                            {{
                                                $empresaUsuario
                                                    ? (
                                                        $empresaUsuario
                                                            ->nombre_legal
                                                        ?: $empresaUsuario
                                                            ->nombre_comercial
                                                    )
                                                    : 'Empresa no disponible'
                                            }}
                                        </option>
                                    </select>

                                    <input
                                        type="hidden"
                                        name="empresa_ids[]"
                                        value="{{ auth()->user()->empresa_id }}"
                                    >
                                @endif
                            </div>

                            <div class="cc-field">
                                <label>
                                    Unidad
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
                                            data-plural-suffix="seleccionadas"
                                        >
                                            Todas
                                        </span>

                                        <span
                                            class="cc-filter-multiselect-arrow"
                                        >
                                            ⌄
                                        </span>
                                    </button>

                                    <div
                                        class="cc-filter-multiselect-menu"
                                        data-cc-filter-menu
                                    >
                                        <div
                                            class="cc-filter-multiselect-list"
                                        >
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
                                                $unidadesFiltro
                                                as $unidadFiltro
                                            )
                                                <label
                                                    class="cc-filter-multiselect-option"
                                                    data-cc-filter-option
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="unidad_ids[]"
                                                        value="{{
                                                            $unidadFiltro->id
                                                        }}"
                                                        @checked(
                                                            $unidadesSeleccionadas
                                                                ->contains(
                                                                    (int)
                                                                    $unidadFiltro
                                                                        ->id
                                                                )
                                                        )
                                                        data-cc-filter-checkbox
                                                    >

                                                    <span
                                                        data-cc-filter-option-label
                                                    >
                                                        {{
                                                            $unidadFiltro
                                                                ->placa
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
                                    Modelo de medición
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
                                            data-plural-suffix="seleccionados"
                                        >
                                            Todos
                                        </span>

                                        <span
                                            class="cc-filter-multiselect-arrow"
                                        >
                                            ⌄
                                        </span>
                                    </button>

                                    <div
                                        class="cc-filter-multiselect-menu"
                                        data-cc-filter-menu
                                    >
                                        <div
                                            class="cc-filter-multiselect-list"
                                        >
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
                                                $modelosFiltro
                                                as $modelo
                                            )
                                                <label
                                                    class="cc-filter-multiselect-option"
                                                    data-cc-filter-option
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="modelos_medicion[]"
                                                        value="{{ $modelo }}"
                                                        @checked(
                                                            $modelosSeleccionados
                                                                ->contains(
                                                                    $modelo
                                                                )
                                                        )
                                                        data-cc-filter-checkbox
                                                    >

                                                    <span
                                                        data-cc-filter-option-label
                                                    >
                                                        @switch($modelo)
                                                            @case('galones_hora')
                                                                Galones por hora
                                                                @break

                                                            @case('kilometros_galon')
                                                                Kilómetros por galón
                                                                @break

                                                            @case('galones_viaje')
                                                                Galones por viaje
                                                                @break

                                                            @default
                                                                No definido
                                                        @endswitch
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="cc-field">
                                <label>
                                    Cantidad de tanques
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
                                            data-plural-suffix="seleccionadas"
                                        >
                                            Todas
                                        </span>

                                        <span
                                            class="cc-filter-multiselect-arrow"
                                        >
                                            ⌄
                                        </span>
                                    </button>

                                    <div
                                        class="cc-filter-multiselect-menu"
                                        data-cc-filter-menu
                                    >
                                        <div
                                            class="cc-filter-multiselect-list"
                                        >
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
                                                $tanquesFiltro
                                                as $totalTanques
                                            )
                                                <label
                                                    class="cc-filter-multiselect-option"
                                                    data-cc-filter-option
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="total_tanques[]"
                                                        value="{{
                                                            $totalTanques
                                                        }}"
                                                        @checked(
                                                            $tanquesSeleccionados
                                                                ->contains(
                                                                    (int)
                                                                    $totalTanques
                                                                )
                                                        )
                                                        data-cc-filter-checkbox
                                                    >

                                                    <span
                                                        data-cc-filter-option-label
                                                    >
                                                        {{ $totalTanques }}
                                                        {{
                                                            (int)
                                                            $totalTanques
                                                            === 1
                                                                ? 'tanque'
                                                                : 'tanques'
                                                        }}
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
                                    placeholder="Empresa, Nombre / Placa, estado, licencia, modelo o diagnóstico"
                                >
                            </div>

                            <div
                                class="cc-standard-filter-actions
                                       cc-operational-actions"
                                style="
                                    display: flex;
                                    flex-wrap: nowrap;
                                    gap: .75rem;
                                    align-items: center;
                                    justify-content: flex-end;
                                    white-space: nowrap;
                                    grid-column: span 2;
                                    width: 100%;
                                "
                            >
                                <button
                                    type="submit"
                                    class="cc-btn-primary"
                                >
                                    Consultar
                                </button>

                                <button
                                    type="button"
                                    class="cc-btn-secondary"
                                    data-cc-clear-filters
                                    data-clear-url="{{ $rutaConsulta }}"
                                >
                                    Limpiar
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                @if ($consultaEjecutada)
                    <div
                        class="cc-summary-strip cc-operational-summary cc-analytics-summary"
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

                <div class="cc-operational-grid cc-analytics-grid">
                    <section class="cc-operational-section cc-analytics-panel">
                        <div class="cc-operational-section-header cc-analytics-panel-header">
                            <div>
                                <h4 class="cc-operational-section-title cc-analytics-panel-title">
                                    Alertas operativas
                                </h4>

                                <div
                                    class="cc-operational-section-note cc-analytics-panel-subtitle"
                                >
                                    Condiciones que requieren seguimiento.
                                </div>
                            </div>
                        </div>

                        <div class="cc-operational-alerts cc-analytics-alert-grid">
                            @foreach ($alertas as $alerta)
                                <article
                                    class="cc-operational-alert cc-analytics-alert"
                                >
                                    <div
                                        class="cc-operational-alert-top"
                                    >
                                        <div
                                            class="cc-operational-alert-title"
                                        >
                                            {{ $alerta['titulo'] }}
                                        </div>

                                        <div
                                            class="cc-operational-alert-value"
                                        >
                                            {{ $alerta['valor'] }}
                                        </div>
                                    </div>

                                    <div
                                        class="cc-operational-alert-meta"
                                    >
                                        <span>
                                            {{
                                                $alerta['porcentaje']
                                            }}
                                            % del total
                                        </span>

                                        <span
                                            class="cc-operational-priority
                                                   cc-operational-priority-{{
                                                       $alerta['prioridad']
                                                   }}"
                                        >
                                            {{ $alerta['prioridad'] }}
                                        </span>
                                    </div>

                                    <p
                                        class="cc-operational-alert-detail"
                                    >
                                        {{ $alerta['detalle'] }}
                                    </p>
                                </article>
                            @endforeach
                        </div>
                    </section>

                    <section class="cc-operational-section cc-analytics-panel">
                        <div class="cc-operational-section-header cc-analytics-panel-header">
                            <div>
                                <h4 class="cc-operational-section-title cc-analytics-panel-title">
                                    Panorama operativo
                                </h4>

                                <div
                                    class="cc-operational-section-note cc-analytics-panel-subtitle"
                                >
                                    Distribución de unidades y cobertura.
                                </div>
                            </div>
                        </div>

                        @foreach ([
                            [
                                'titulo' => 'Estado de unidades',
                                'barras' => [
                                    [
                                        'label' => 'Activas',
                                        'value' => (int) (
                                            $kpis['unidades_activas'] ?? 0
                                        ),
                                    ],
                                    [
                                        'label' => 'Registradas',
                                        'value' => (int) (
                                            $kpis['unidades_registradas'] ?? 0
                                        ),
                                    ],
                                    [
                                        'label' => 'Inactivas',
                                        'value' => (int) (
                                            $kpis['unidades_inactivas'] ?? 0
                                        ),
                                    ],
                                ],
                            ],
                            [
                                'titulo' => 'Disponibilidad operacional',
                                'barras' => [
                                    [
                                        'label' => 'Cobertura completa',
                                        'value' => $coberturaCompleta,
                                    ],
                                    [
                                        'label' => 'Cobertura incompleta',
                                        'value' => $coberturaIncompleta,
                                    ],
                                    [
                                        'label' => 'Sin licencia',
                                        'value' => $sinLicencia,
                                    ],
                                ],
                            ],
                        ] as $grupoPanorama)
                            <div class="cc-operational-panorama-block">
                                <h5 class="cc-operational-panorama-title">
                                    {{ $grupoPanorama['titulo'] }}
                                </h5>

                                <div class="cc-operational-bars">
                                    @foreach (
                                        $grupoPanorama['barras']
                                        as $barra
                                    )
                                        @php
                                            $porcentaje = $totalUnidades > 0
                                                ? (
                                                    $barra['value']
                                                    / $totalUnidades
                                                ) * 100
                                                : 0;
                                        @endphp

                                        <div class="cc-operational-bar-row">
                                            <div
                                                class="cc-operational-bar-label"
                                            >
                                                {{ $barra['label'] }}
                                            </div>

                                            <div class="cc-operational-bar cc-analytics-progress">
                                                <span
                                                    style="width: {{
                                                        min(
                                                            100,
                                                            max(
                                                                0,
                                                                $porcentaje
                                                            )
                                                        )
                                                    }}%;"
                                                ></span>
                                            </div>

                                            <div
                                                class="cc-operational-bar-value"
                                            >
                                                {{ $barra['value'] }}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </section>
                </div>

                <section class="cc-operational-section cc-analytics-panel">
                    <div class="cc-operational-section-header cc-analytics-panel-header">
                        <div>
                            <h4 class="cc-operational-section-title cc-analytics-panel-title">
                                Resumen por empresa
                            </h4>

                            <div class="cc-operational-section-note cc-analytics-panel-subtitle">
                                Estado consolidado de la operación.
                            </div>
                        </div>

                        <span class="cc-operational-pill">
                            {{ $resumenConsolidado->total() }}
                            empresas
                        </span>
                    </div>

                    <div class="cc-table-adaptive-wrapper">
                        <table
                            class="cc-table-adaptive"
                            style="min-width: 72rem;"
                        >
                            <thead>
                                <tr>
                                    @foreach ([
                                        'empresa' => 'Empresa',
                                        'total_unidades' => 'Unidades',
                                        'operativas' => 'Operativas',
                                        'no_operativas' => 'No operativas',
                                        'cobertura_completa' => 'Cobertura completa',
                                        'cobertura_porcentaje' => 'Cobertura',
                                        'alertas' => 'Alertas',
                                    ] as $campo => $etiqueta)
                                        <th>
                                            <a
                                                href="{{ $urlOrdenEmpresa($campo) }}"
                                                class="cc-operational-sort-link"
                                            >
                                                {{ $etiqueta }}

                                                <span
                                                    class="cc-operational-sort-icon"
                                                >
                                                    {{
                                                        $indicadorOrdenEmpresa(
                                                            $campo
                                                        )
                                                    }}
                                                </span>
                                            </a>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @forelse (
                                    $resumenConsolidado
                                    as $resumen
                                )
                                    @php
                                        $totalEmpresa = (int) (
                                            $resumen['total_unidades']
                                        );

                                        $operativasEmpresa = (int) (
                                            $resumen[
                                                'cobertura_completa'
                                            ]
                                        );

                                        $noOperativasEmpresa = max(
                                            0,
                                            $totalEmpresa
                                            - $operativasEmpresa
                                        );

                                        $coberturaEmpresa =
                                            $totalEmpresa > 0
                                                ? round(
                                                    (
                                                        $operativasEmpresa
                                                        / $totalEmpresa
                                                    ) * 100,
                                                    1
                                                )
                                                : 0;

                                        $alertasEmpresa = (int) (
                                            $resumen[
                                                'cobertura_incompleta'
                                            ]
                                        );
                                    @endphp

                                    <tr>
                                        <td>
                                            <div
                                                class="cc-table-adaptive-strong"
                                            >
                                                {{
                                                    $resumen['empresa']
                                                        ->nombre_legal
                                                    ?: $resumen['empresa']
                                                        ->nombre_comercial
                                                }}
                                            </div>

                                            <div
                                                class="cc-table-adaptive-muted"
                                            >
                                                {{
                                                    $resumen['empresa']
                                                        ->estado
                                                    === 'activa'
                                                        ? 'Empresa activa'
                                                        : 'Empresa histórica'
                                                }}
                                            </div>
                                        </td>

                                        <td>
                                            {{ $totalEmpresa }}
                                        </td>

                                        <td>
                                            {{ $operativasEmpresa }}
                                        </td>

                                        <td>
                                            {{ $noOperativasEmpresa }}
                                        </td>

                                        <td>
                                            {{ $operativasEmpresa }}
                                        </td>

                                        <td>
                                            <div
                                                class="cc-operational-progress cc-analytics-progress"
                                            >
                                                <span
                                                    style="width: {{
                                                        min(
                                                            100,
                                                            max(
                                                                0,
                                                                $coberturaEmpresa
                                                            )
                                                        )
                                                    }}%;"
                                                ></span>
                                            </div>

                                            <div
                                                class="cc-table-adaptive-muted"
                                            >
                                                {{
                                                    $coberturaEmpresa
                                                }}
                                                %
                                            </div>
                                        </td>

                                        <td>
                                            <span
                                                class="cc-operational-priority
                                                       cc-operational-priority-{{
                                                           $alertasEmpresa
                                                           > 0
                                                               ? 'alta'
                                                               : 'normal'
                                                       }}"
                                            >
                                                {{ $alertasEmpresa }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            No hay información disponible.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($resumenConsolidado->hasPages())
                        <div class="mt-6">
                            {{
                                $resumenConsolidado->links()
                            }}
                        </div>
                    @endif
                </section>

                <section class="cc-operational-section cc-analytics-panel cc-analytics-panel-separation">
                    <div class="cc-operational-section-header cc-analytics-panel-header">
                        <div>
                            <h4 class="cc-operational-section-title cc-analytics-panel-title">
                                Diagnóstico por unidad
                            </h4>

                            <div class="cc-operational-section-note cc-analytics-panel-subtitle">
                                Condición actual y prioridad.
                            </div>
                        </div>

                        <span class="cc-operational-pill">
                            {{ $unidadesAnaliticas->total() }}
                            unidades
                        </span>
                    </div>

                    <div class="cc-table-adaptive-wrapper">
                        <table
                            class="cc-table-adaptive"
                            style="min-width: 100rem;"
                        >
                            <thead>
                                <tr>
                                    @foreach ([
                                        'unidad' => 'Unidad',
                                        'empresa' => 'Empresa',
                                        'disponibilidad' => 'Disponibilidad',
                                        'licencia' => 'Licencia',
                                        'cobertura' => 'Cobertura',
                                        'marchamos' => 'Marchamos',
                                        'diagnostico' => 'Diagnóstico',
                                        'prioridad' => 'Prioridad',
                                    ] as $campo => $etiqueta)
                                        <th>
                                            <a
                                                href="{{ $urlOrdenUnidad($campo) }}"
                                                class="cc-operational-sort-link"
                                            >
                                                {{ $etiqueta }}

                                                <span
                                                    class="cc-operational-sort-icon"
                                                >
                                                    {{
                                                        $indicadorOrdenUnidad(
                                                            $campo
                                                        )
                                                    }}
                                                </span>
                                            </a>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @forelse (
                                    $unidadesAnaliticas
                                    as $item
                                )
                                    @php
                                        $unidadOperativa = (
                                            $item['unidad']->estado
                                            === 'activa'
                                            && $item[
                                                'porcentaje_cobertura'
                                            ] >= 100
                                            && $item['licencia']
                                            && $item['licencia']->estado
                                                === 'activa'
                                        );

                                        $prioridad = $unidadOperativa
                                            ? 'normal'
                                            : (
                                                $item[
                                                    'licencia_texto'
                                                ] === 'Sin licencia'
                                                    ? 'alta'
                                                    : 'media'
                                            );
                                    @endphp

                                    <tr>
                                        <td>
                                            <div
                                                class="cc-table-adaptive-strong"
                                            >
                                                {{
                                                    $item['unidad']
                                                        ->placa
                                                }}
                                            </div>

                                            <div
                                                class="cc-table-adaptive-muted"
                                            >
                                                {{
                                                    $item[
                                                        'modelo_medicion'
                                                    ]
                                                }}
                                                ·
                                                {{
                                                    $item[
                                                        'total_tanques'
                                                    ]
                                                }}
                                                {{
                                                    (int)
                                                    $item[
                                                        'total_tanques'
                                                    ] === 1
                                                        ? 'tanque'
                                                        : 'tanques'
                                                }}
                                            </div>
                                        </td>

                                        <td
                                            class="cc-operational-company-nowrap"
                                        >
                                            {{
                                                $item['empresa']
                                                    ->nombre_legal
                                                ?: $item['empresa']
                                                    ->nombre_comercial
                                            }}
                                        </td>

                                        <td>
                                            <span
                                                class="cc-operational-priority
                                                       cc-operational-priority-{{
                                                           $unidadOperativa
                                                               ? 'normal'
                                                               : 'alta'
                                                       }}"
                                            >
                                                {{ ucfirst(strtolower(
                                                    $unidadOperativa
                                                        ? 'Operativa'
                                                        : 'No operativa'
                                                )) }}
                                            </span>
                                        </td>

                                        <td
                                            class="cc-operational-nowrap"
                                        >
                                            {{
                                                $item[
                                                    'licencia_texto'
                                                ]
                                            }}
                                        </td>

                                        <td>
                                            <div
                                                class="cc-operational-progress cc-analytics-progress"
                                            >
                                                <span
                                                    style="width: {{
                                                        min(
                                                            100,
                                                            max(
                                                                0,
                                                                $item[
                                                                    'porcentaje_cobertura'
                                                                ]
                                                            )
                                                        )
                                                    }}%;"
                                                ></span>
                                            </div>

                                            <div
                                                class="cc-table-adaptive-muted"
                                            >
                                                {{
                                                    $item[
                                                        'puntos_asignados'
                                                    ]
                                                }}
                                                /
                                                {{
                                                    $item[
                                                        'total_puntos'
                                                    ]
                                                }}
                                                ·
                                                {{
                                                    $item[
                                                        'porcentaje_cobertura'
                                                    ]
                                                }}
                                                %
                                            </div>
                                        </td>

                                        <td>
                                            {{
                                                $item[
                                                    'marchamos_activos'
                                                ]
                                            }}
                                        </td>

                                        <td>
                                            <div
                                                class="cc-table-adaptive-strong"
                                            >
                                                {{
                                                    $item[
                                                        'situacion'
                                                    ]
                                                }}
                                            </div>

                                            <div
                                                class="cc-table-adaptive-muted"
                                            >
                                                {{
                                                    $item[
                                                        'accion_sugerida'
                                                    ]
                                                }}
                                            </div>
                                        </td>

                                        <td>
                                            <span
                                                class="cc-operational-priority
                                                       cc-operational-priority-{{
                                                           $prioridad
                                                       }}"
                                            >
                                                {{ ucfirst(strtolower($prioridad)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8">
                                            No hay unidades disponibles.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($unidadesAnaliticas->hasPages())
                        <div class="mt-6">
                            {{
                                $unidadesAnaliticas->links()
                            }}
                        </div>
                    @endif
                </section>
                @else
                    <section class="cc-operational-empty-state cc-empty-panel cc-empty-panel-compact">
                        <div class="cc-operational-empty-state-title">
                            Seleccione los criterios de consulta
                        </div>

                        <div class="cc-operational-empty-state-text">
                            Utilice los filtros disponibles y presione
                            Consultar para mostrar el estado operativo
                            de la flota.
                        </div>
                    </section>
                @endif
    </div>

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const clearButton = document.querySelector(
                    '[data-cc-clear-filters]'
                );

                clearButton?.addEventListener(
                    'click',
                    function () {
                        const cleanUrl =
                            clearButton.dataset.clearUrl;

                        if (! cleanUrl) {
                            return;
                        }

                        const form = clearButton.closest('form');

                        form?.reset();

                        window.location.assign(cleanUrl);
                    }
                );

                const multiselects = Array.from(
                    document.querySelectorAll(
                        '[data-cc-filter-multiselect]'
                    )
                );

                function cerrarTodos(excepto = null) {
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

                        const checkboxes = Array.from(
                            multiselect.querySelectorAll(
                                '[data-cc-filter-checkbox]'
                            )
                        );

                        function actualizarEtiqueta() {
                            const seleccionados =
                                checkboxes.filter(
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
                                    label.textContent =
                                        seleccionados[0]
                                            .closest(
                                                '[data-cc-filter-option]'
                                            )
                                            ?.querySelector(
                                                '[data-cc-filter-option-label]'
                                            )
                                            ?.textContent
                                            ?.trim()
                                        || '1 seleccionado';
                                } else {
                                    label.textContent =
                                        seleccionados.length
                                        + ' '
                                        + (
                                            label.dataset
                                                .pluralSuffix
                                            || 'seleccionados'
                                        );
                                }
                            }

                            if (master) {
                                master.checked =
                                    checkboxes.length > 0
                                    && seleccionados.length
                                        === checkboxes.length;

                                master.indeterminate =
                                    seleccionados.length > 0
                                    && seleccionados.length
                                        < checkboxes.length;
                            }
                        }

                        toggle?.addEventListener(
                            'click',
                            function () {
                                cerrarTodos(multiselect);

                                const abrir =
                                    ! multiselect.classList
                                        .contains('is-open');

                                multiselect.classList.toggle(
                                    'is-open',
                                    abrir
                                );

                                toggle.classList.toggle(
                                    'is-open',
                                    abrir
                                );

                                menu?.classList.toggle(
                                    'is-open',
                                    abrir
                                );
                            }
                        );

                        master?.addEventListener(
                            'change',
                            function () {
                                checkboxes.forEach(
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

                        actualizarEtiqueta();
                    }
                );

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
            }
        );
    </script>
