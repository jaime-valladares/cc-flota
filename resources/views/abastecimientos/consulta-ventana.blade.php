<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        Consulta de abastecimientos · CC-Flota
    </title>

    @include('layouts.partials.favicon')

    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
        rel="stylesheet"
    >

    @vite([
        'resources/css/app.css',
        'resources/js/app.js',
    ])
</head>

<body class="antialiased">
    <main
        class="min-h-screen"
        style="background: var(--cc-bg-main);"
    >
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

        $modelosTexto = [
            'kilometros_galon' => 'Kilómetros por galón',
            'galones_hora' => 'Horas por galón',
            'galones_viaje' => 'Galones por viaje',
        ];

        $origenesTexto = [
            'interno' => 'Gasolinera interna',
            'externo' => 'Gasolinera externa',
        ];
    @endphp

    <div class="cc-page-wrapper">
        <div
            class="cc-window-container"
            style="width: 100%; max-width: 80rem;"
        >
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Consulta de abastecimientos
                        </h3>

                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a
                            href="{{ route(
                                'abastecimientos.consulta',
                                $queryParams
                            ) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver al sistema
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

                <div class="cc-summary-strip">
                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            {{ $hayFiltros ? 'Resultados' : 'Total' }}
                        </span>

                        <span class="cc-summary-strip-value">
                            {{
                                $hayFiltros
                                    ? $abastecimientos->total()
                                    : $totalAbastecimientos
                            }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Registrados
                        </span>

                        <span
                            class="cc-summary-strip-value
                                   cc-summary-strip-value-success"
                        >
                            {{ $totalRegistrados }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Anulados
                        </span>

                        <span
                            class="cc-summary-strip-value
                                   cc-summary-strip-value-danger"
                        >
                            {{ $totalAnulados }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Origen interno
                        </span>

                        <span class="cc-summary-strip-value">
                            {{ $totalInternos }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Origen externo
                        </span>

                        <span class="cc-summary-strip-value">
                            {{ $totalExternos }}
                        </span>
                    </div>
                </div>

                <form
                    method="GET"
                    action="{{ route('abastecimientos.consulta.ventana') }}"
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
                                Filtros de consulta
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
                                                            value="{{
                                                                $empresaOpcion->id
                                                            }}"
                                                            @checked(
                                                                in_array(
                                                                    (int)
                                                                    $empresaOpcion->id,
                                                                    $empresaIds
                                                                        ?? [],
                                                                    true
                                                                )
                                                            )
                                                            data-cc-filter-checkbox
                                                            data-empresa-checkbox
                                                        >

                                                        <span
                                                            data-cc-filter-option-label
                                                        >
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
                                                    $empresaOpcion
                                                        ->nombre_comercial
                                                    ?: $empresaOpcion
                                                        ->nombre_legal
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
                                            value="{{
                                                $empresaSeleccionadaId
                                            }}"
                                        >
                                    @endforeach
                                @endif
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
                                                $unidadesSelector
                                                as $unidadOpcion
                                            )
                                                <label
                                                    class="cc-filter-multiselect-option"
                                                    data-cc-filter-option
                                                    data-unidad-option
                                                    data-empresa-id="{{
                                                        $unidadOpcion
                                                            ->empresa_id
                                                    }}"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="unidad_ids[]"
                                                        value="{{
                                                            $unidadOpcion->id
                                                        }}"
                                                        @checked(
                                                            in_array(
                                                                (int)
                                                                $unidadOpcion->id,
                                                                $unidadIds
                                                                    ?? [],
                                                                true
                                                            )
                                                        )
                                                        data-cc-filter-checkbox
                                                        data-unidad-checkbox
                                                    >

                                                    <span
                                                        data-cc-filter-option-label
                                                    >
                                                        {{
                                                            $unidadOpcion->placa
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
                                                $motoristasSelector
                                                as $motoristaOpcion
                                            )
                                                <label
                                                    class="cc-filter-multiselect-option"
                                                    data-cc-filter-option
                                                    data-motorista-option
                                                    data-empresa-id="{{
                                                        $motoristaOpcion
                                                            ->empresa_id
                                                    }}"
                                                >
                                                    <input
                                                        type="checkbox"
                                                        name="motorista_ids[]"
                                                        value="{{
                                                            $motoristaOpcion->id
                                                        }}"
                                                        @checked(
                                                            in_array(
                                                                (int)
                                                                $motoristaOpcion
                                                                    ->id,
                                                                $motoristaIds
                                                                    ?? [],
                                                                true
                                                            )
                                                        )
                                                        data-cc-filter-checkbox
                                                        data-motorista-checkbox
                                                    >

                                                    <span
                                                        data-cc-filter-option-label
                                                    >
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
                                <label for="tipo_origen">
                                    Origen
                                </label>

                                <select
                                    id="tipo_origen"
                                    name="tipo_origen"
                                    class="cc-input"
                                >
                                    <option value="">
                                        Todos
                                    </option>

                                    <option
                                        value="interno"
                                        @selected(
                                            $tipoOrigen === 'interno'
                                        )
                                    >
                                        Gasolinera interna
                                    </option>

                                    <option
                                        value="externo"
                                        @selected(
                                            $tipoOrigen === 'externo'
                                        )
                                    >
                                        Gasolinera externa
                                    </option>
                                </select>
                            </div>

                            <div class="cc-field">
                                <label for="modelo_medicion">
                                    Modelo de medición
                                </label>

                                <select
                                    id="modelo_medicion"
                                    name="modelo_medicion"
                                    class="cc-input"
                                >
                                    <option value="">
                                        Todos
                                    </option>

                                    <option
                                        value="kilometros_galon"
                                        @selected(
                                            $modeloMedicion
                                                === 'kilometros_galon'
                                        )
                                    >
                                        Kilómetros por galón
                                    </option>

                                    <option
                                        value="galones_hora"
                                        @selected(
                                            $modeloMedicion
                                                === 'galones_hora'
                                        )
                                    >
                                        Horas por galón
                                    </option>

                                    <option
                                        value="galones_viaje"
                                        @selected(
                                            $modeloMedicion
                                                === 'galones_viaje'
                                        )
                                    >
                                        Galones por viaje
                                    </option>
                                </select>
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
                                        value="registrado"
                                        @selected(
                                            $estado === 'registrado'
                                        )
                                    >
                                        Registrados
                                    </option>

                                    <option
                                        value="anulado"
                                        @selected(
                                            $estado === 'anulado'
                                        )
                                    >
                                        Anulados
                                    </option>
                                </select>
                            </div>

                            <div class="cc-field">
                                <label for="fecha_desde">
                                    Fecha desde
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
                                    Fecha hasta
                                </label>

                                <input
                                    id="fecha_hasta"
                                    type="date"
                                    name="fecha_hasta"
                                    value="{{ $fechaHasta }}"
                                    class="cc-input"
                                >
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
                                >
                                    Consultar
                                </button>

                                <a
                                    href="{{ route(
                                        'abastecimientos.consulta.ventana'
                                    ) }}"
                                    class="cc-btn-secondary"
                                >
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

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
                    <div
                        class="cc-empty-panel
                               cc-empty-panel-compact"
                    >
                        <h5>
                            Consulta pendiente
                        </h5>

                        <p>
                            Los resultados permanecerán vacíos hasta que
                            realice una búsqueda.
                        </p>
                    </div>
                @elseif ($abastecimientos->isEmpty())
                    <div
                        class="cc-empty-panel
                               cc-empty-panel-compact"
                    >
                        <h5>
                            Sin resultados
                        </h5>

                        <p>
                            No hay abastecimientos que coincidan con los
                            criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-table-adaptive-wrapper">
                        <table
                            class="cc-table-adaptive"
                            style="min-width: 100rem;"
                        >
                            <thead>
                                <tr>
                                    <th>
                                        Fecha y hora
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
                                        Origen
                                    </th>

                                    <th>
                                        Combustible
                                    </th>

                                    <th>
                                        Medición
                                    </th>

                                    <th>
                                        Estado
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
                                        $empresaNombre =
                                            $abastecimiento
                                                ->empresa_nombre_snapshot
                                            ?: (
                                                $abastecimiento->empresa
                                                    ? (
                                                        $abastecimiento
                                                            ->empresa
                                                            ->nombre_comercial
                                                        ?: $abastecimiento
                                                            ->empresa
                                                            ->nombre_legal
                                                    )
                                                    : 'Empresa no disponible'
                                            );

                                        $unidadPlaca =
                                            $abastecimiento
                                                ->unidad_placa_snapshot
                                            ?: (
                                                $abastecimiento->unidad
                                                    ?->placa
                                                ?: 'Unidad no disponible'
                                            );

                                        $motoristaNombre =
                                            $abastecimiento
                                                ->motorista_nombre_snapshot
                                            ?: (
                                                $abastecimiento->motorista
                                                    ?->nombre_completo
                                                ?: 'Motorista no disponible'
                                            );

                                        $origenNombre =
                                            $abastecimiento
                                                ->origen_nombre_snapshot
                                            ?: (
                                                $abastecimiento
                                                    ->esOrigenInterno()
                                                    ? (
                                                        $abastecimiento
                                                            ->gasolineraInterna
                                                            ?->nombre
                                                        ?: 'Gasolinera interna'
                                                    )
                                                    : (
                                                        $abastecimiento
                                                            ->gasolineraExterna
                                                            ?->compania
                                                        ?: 'Gasolinera externa'
                                                    )
                                            );

                                        $rutaFicha = route(
                                            'abastecimientos.show.ventana',
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
                                    @endphp

                                    <tr>
                                        <td
                                            class="cc-table-adaptive-nowrap"
                                        >
                                            <div
                                                class="cc-table-adaptive-strong"
                                            >
                                                {{
                                                    optional(
                                                        $abastecimiento
                                                            ->fecha_hora_abastecimiento
                                                    )->format(
                                                        'd/m/Y'
                                                    )
                                                    ?: '—'
                                                }}
                                            </div>

                                            <div
                                                class="cc-table-adaptive-muted"
                                            >
                                                {{
                                                    optional(
                                                        $abastecimiento
                                                            ->fecha_hora_abastecimiento
                                                    )->format(
                                                        'H:i'
                                                    )
                                                    ?: '—'
                                                }}
                                            </div>
                                        </td>

                                        <td
                                            class="cc-table-adaptive-break"
                                        >
                                            <div
                                                class="cc-table-adaptive-strong"
                                            >
                                                {{ $empresaNombre }}
                                            </div>
                                        </td>

                                        <td
                                            class="cc-table-adaptive-nowrap"
                                        >
                                            <div
                                                class="cc-table-adaptive-strong"
                                            >
                                                {{ $unidadPlaca }}
                                            </div>

                                            <div
                                                class="cc-table-adaptive-muted"
                                            >
                                                {{
                                                    $abastecimiento
                                                        ->unidad_marca_snapshot
                                                    ?: 'Sin marca'
                                                }}

                                                @if (
                                                    $abastecimiento
                                                        ->unidad_modelo_snapshot
                                                )
                                                    ·
                                                    {{
                                                        $abastecimiento
                                                            ->unidad_modelo_snapshot
                                                    }}
                                                @endif
                                            </div>
                                        </td>

                                        <td
                                            class="cc-table-adaptive-break"
                                        >
                                            <div
                                                class="cc-table-adaptive-strong"
                                            >
                                                {{ $motoristaNombre }}
                                            </div>

                                            <div
                                                class="cc-table-adaptive-muted"
                                            >
                                                Lic.
                                                {{
                                                    $abastecimiento
                                                        ->motorista_licencia_snapshot
                                                    ?: 'No disponible'
                                                }}
                                            </div>
                                        </td>

                                        <td
                                            class="cc-table-adaptive-break"
                                        >
                                            <div
                                                class="cc-table-adaptive-strong"
                                            >
                                                {{
                                                    $origenesTexto[
                                                        $abastecimiento
                                                            ->tipo_origen
                                                    ]
                                                    ?? 'No definido'
                                                }}
                                            </div>

                                            <div
                                                class="cc-table-adaptive-muted"
                                            >
                                                {{ $origenNombre }}
                                            </div>
                                        </td>

                                        <td
                                            class="cc-table-adaptive-nowrap"
                                        >
                                            <div
                                                class="cc-table-adaptive-strong"
                                            >
                                                {{
                                                    number_format(
                                                        (float)
                                                        $abastecimiento
                                                            ->volumen_cargado,
                                                        2
                                                    )
                                                }}
                                                gal
                                            </div>

                                            <div
                                                class="cc-table-adaptive-muted"
                                            >
                                                Final:
                                                {{
                                                    number_format(
                                                        (float)
                                                        $abastecimiento
                                                            ->volumen_final,
                                                        2
                                                    )
                                                }}
                                                gal
                                            </div>
                                        </td>

                                        <td
                                            class="cc-table-adaptive-break"
                                        >
                                            <div
                                                class="cc-table-adaptive-strong"
                                            >
                                                {{
                                                    $modelosTexto[
                                                        $abastecimiento
                                                            ->modelo_medicion
                                                    ]
                                                    ?? 'No definido'
                                                }}
                                            </div>

                                            <div
                                                class="cc-table-adaptive-muted"
                                            >
                                                Km:
                                                {{
                                                    number_format(
                                                        (float)
                                                        $abastecimiento
                                                            ->kilometraje_actual,
                                                        2
                                                    )
                                                }}

                                                @if (
                                                    ! is_null(
                                                        $abastecimiento
                                                            ->horometro_actual
                                                    )
                                                )
                                                    · H:
                                                    {{
                                                        number_format(
                                                            (float)
                                                            $abastecimiento
                                                                ->horometro_actual,
                                                            2
                                                        )
                                                    }}
                                                @endif
                                            </div>
                                        </td>

                                        <td
                                            class="cc-table-adaptive-nowrap"
                                        >
                                            @if (
                                                $abastecimiento
                                                    ->estado
                                                === 'registrado'
                                            )
                                                <span
                                                    class="cc-badge
                                                           cc-badge-active"
                                                >
                                                    Registrado
                                                </span>
                                            @else
                                                <span
                                                    class="cc-badge
                                                           cc-badge-inactive"
                                                >
                                                    Anulado
                                                </span>
                                            @endif
                                        </td>

                                        <td
                                            class="cc-table-adaptive-nowrap"
                                        >
                                            <a
                                                href="{{ $rutaFicha }}"
                                                class="cc-btn-secondary
                                                       cc-btn-result"
                                            >
                                                Ver ficha
                                            </a>
                                        </td>
                                    </tr>
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
                                        [
                                            'consultar' => 1,
                                        ]
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
        const multiselectsAbastecimiento = Array.from(
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
            return elementos.checkboxes.filter(
                function (checkbox) {
                    const option = checkbox.closest(
                        '[data-cc-filter-option]'
                    );

                    return option && ! option.hidden;
                }
            );
        }

        function actualizarEtiquetaMultiselect(
            multiselect
        ) {
            const elementos =
                obtenerElementosMultiselect(
                    multiselect
                );

            if (! elementos.label) {
                return;
            }

            const visibles =
                obtenerCheckboxesVisibles(
                    elementos
                );

            const seleccionados = visibles.filter(
                function (checkbox) {
                    return checkbox.checked;
                }
            );

            const defaultLabel =
                elementos.label.dataset.defaultLabel
                || 'Todos';

            const singularSuffix =
                elementos.label.dataset.singularSuffix
                || 'seleccionado';

            const pluralSuffix =
                elementos.label.dataset.pluralSuffix
                || 'seleccionados';

            if (seleccionados.length === 0) {
                elementos.label.textContent =
                    defaultLabel;
            } else if (
                seleccionados.length === 1
            ) {
                const option =
                    seleccionados[0].closest(
                        '[data-cc-filter-option]'
                    );

                const optionLabel =
                    option?.querySelector(
                        '[data-cc-filter-option-label]'
                    );

                elementos.label.textContent =
                    optionLabel
                        ? optionLabel.textContent.trim()
                        : '1 ' + singularSuffix;
            } else {
                elementos.label.textContent =
                    seleccionados.length
                    + ' '
                    + pluralSuffix;
            }

            if (elementos.master) {
                elementos.master.checked =
                    visibles.length > 0
                    && seleccionados.length
                        === visibles.length;

                elementos.master.indeterminate =
                    seleccionados.length > 0
                    && seleccionados.length
                        < visibles.length;
            }
        }

        function cerrarMultiselectsExcepto(
            actual
        ) {
            multiselectsAbastecimiento.forEach(
                function (multiselect) {
                    if (multiselect === actual) {
                        return;
                    }

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

        function obtenerEmpresasSeleccionadas() {
            const empresaMultiselect =
                document.querySelector(
                    '[data-filter-type="empresa"]'
                );

            if (! empresaMultiselect) {
                return [];
            }

            return Array.from(
                empresaMultiselect.querySelectorAll(
                    '[data-empresa-checkbox]:checked'
                )
            ).map(
                function (checkbox) {
                    return checkbox.value;
                }
            );
        }

        function filtrarCatalogosPorEmpresa() {
            const empresasSeleccionadas =
                obtenerEmpresasSeleccionadas();

            const filtrar =
                empresasSeleccionadas.length > 0;

            document
                .querySelectorAll(
                    '[data-unidad-option]'
                )
                .forEach(
                    function (option) {
                        const visible =
                            ! filtrar
                            || empresasSeleccionadas
                                .includes(
                                    option.dataset
                                        .empresaId
                                );

                        option.hidden = ! visible;

                        const checkbox =
                            option.querySelector(
                                '[data-unidad-checkbox]'
                            );

                        if (! visible && checkbox) {
                            checkbox.checked = false;
                        }
                    }
                );

            document
                .querySelectorAll(
                    '[data-motorista-option]'
                )
                .forEach(
                    function (option) {
                        const visible =
                            ! filtrar
                            || empresasSeleccionadas
                                .includes(
                                    option.dataset
                                        .empresaId
                                );

                        option.hidden = ! visible;

                        const checkbox =
                            option.querySelector(
                                '[data-motorista-checkbox]'
                            );

                        if (! visible && checkbox) {
                            checkbox.checked = false;
                        }
                    }
                );

            [
                'unidad',
                'motorista',
            ].forEach(
                function (tipo) {
                    const multiselect =
                        document.querySelector(
                            '[data-filter-type="'
                            + tipo
                            + '"]'
                        );

                    if (multiselect) {
                        actualizarEtiquetaMultiselect(
                            multiselect
                        );
                    }
                }
            );
        }

        multiselectsAbastecimiento.forEach(
            function (multiselect) {
                const elementos =
                    obtenerElementosMultiselect(
                        multiselect
                    );

                elementos.toggle?.addEventListener(
                    'click',
                    function () {
                        cerrarMultiselectsExcepto(
                            multiselect
                        );

                        elementos.toggle.classList
                            .toggle(
                                'is-open'
                            );

                        elementos.menu?.classList
                            .toggle(
                                'is-open'
                            );
                    }
                );

                elementos.master?.addEventListener(
                    'change',
                    function () {
                        obtenerCheckboxesVisibles(
                            elementos
                        ).forEach(
                            function (checkbox) {
                                checkbox.checked =
                                    elementos.master
                                        .checked;
                            }
                        );

                        actualizarEtiquetaMultiselect(
                            multiselect
                        );

                        if (
                            multiselect.dataset
                                .filterType
                            === 'empresa'
                        ) {
                            filtrarCatalogosPorEmpresa();
                        }
                    }
                );

                elementos.checkboxes.forEach(
                    function (checkbox) {
                        checkbox.addEventListener(
                            'change',
                            function () {
                                actualizarEtiquetaMultiselect(
                                    multiselect
                                );

                                if (
                                    multiselect.dataset
                                        .filterType
                                    === 'empresa'
                                ) {
                                    filtrarCatalogosPorEmpresa();
                                }
                            }
                        );
                    }
                );

                actualizarEtiquetaMultiselect(
                    multiselect
                );
            }
        );

        filtrarCatalogosPorEmpresa();

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

                multiselectsAbastecimiento.forEach(
                    function (multiselect) {
                        const elementos =
                            obtenerElementosMultiselect(
                                multiselect
                            );

                        elementos.toggle?.classList
                            .remove(
                                'is-open'
                            );

                        elementos.menu?.classList
                            .remove(
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

                multiselectsAbastecimiento.forEach(
                    function (multiselect) {
                        const elementos =
                            obtenerElementosMultiselect(
                                multiselect
                            );

                        elementos.toggle?.classList
                            .remove(
                                'is-open'
                            );

                        elementos.menu?.classList
                            .remove(
                                'is-open'
                            );
                    }
                );
            }
        );
    </script>
    </main>
</body>
</html>