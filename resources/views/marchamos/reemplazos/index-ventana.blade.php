<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administración de marchamos | CC-Flota</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
            <div class="cc-page-wrapper">
                <div class="cc-window-container" style="max-width: 80rem;">
                    <div class="cc-card">

                        @php
                            $totalDisponibles = $hayFiltros ? $unidadesDisponibles->count() : 0;
                        @endphp

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Administración de marchamos
                                </h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('marchamos.reemplazos.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver a Administración
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="GET" action="{{ route('marchamos.reemplazos.index.ventana') }}" class="mb-5">
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Filtros de Consulta
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
                                    </div>

                                    <div class="cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>

                                        @if ($esUsuarioDieselCop)
                                            <select id="empresa_id" name="empresa_id" class="cc-input">
                                                <option value="">Todas</option>

                                                @foreach ($empresas as $empresa)
                                                    <option value="{{ $empresa->id }}" @selected((string) $empresaId === (string) $empresa->id)>
                                                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <select id="empresa_id" class="cc-input" disabled>
                                                @foreach ($empresas as $empresa)
                                                    <option value="{{ $empresa->id }}" selected>
                                                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    <div class="cc-field">
                                        <label for="busqueda_placa">
                                            Buscar placa
                                        </label>

                                        <input
                                            id="busqueda_placa"
                                            name="busqueda_placa"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $busquedaPlaca ?? '' }}"
                                            maxlength="30"
                                            placeholder="Ej. C123ABC"
                                        >
                                    </div>

                                    <div class="cc-field">
                                        <label for="unidad_id">
                                            Placa
                                        </label>

                                        <select id="unidad_id" name="unidad_id" class="cc-input">
                                            <option value="">Todas</option>

                                            @foreach ($unidades as $unidad)
                                                <option value="{{ $unidad->id }}" @selected((string) $unidadId === (string) $unidad->id)>
                                                    {{ $unidad->placa }}

                                                    @if ($unidad->marca)
                                                        · {{ $unidad->marca }}
                                                    @endif
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="cc-standard-filter-actions">
                                        <button type="submit" class="cc-btn-primary">
                                            Consultar
                                        </button>

                                        <a href="{{ route('marchamos.reemplazos.index.ventana') }}" class="cc-btn-secondary">
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $totalDisponibles > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    1
                                </span>
                                -
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $totalDisponibles }}
                                </span>
                                de
                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                                    {{ $totalDisponibles }}
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
                        @elseif ($unidadesDisponibles->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>

                                <p>
                                    No hay unidades activas con cobertura completa para los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-admin-result-list">
                                @foreach ($unidadesDisponibles as $unidad)
                                    @php
                                        $totalPuntos = (int) ($unidad->total_puntos ?? 0);
                                        $puntosAsignados = (int) ($unidad->puntos_asignados ?? 0);
                                        $puntosPendientes = max($totalPuntos - $puntosAsignados, 0);
                                    @endphp

                                    <article class="cc-admin-result-card">
                                        <div class="grid gap-5 xl:grid-cols-12 xl:items-start">

                                            <div class="min-w-0 xl:col-span-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h5 class="cc-admin-result-title">
                                                        {{ $unidad->placa }}
                                                    </h5>

                                                    <span class="cc-badge cc-badge-active">
                                                        Activa
                                                    </span>
                                                </div>

                                                <div class="cc-admin-result-subtitle">
                                                    {{ $unidad->marca ?: 'Sin marca registrada' }}
                                                </div>
                                            </div>

                                            <div class="min-w-0 xl:col-span-3">
                                                <div class="cc-admin-result-label">
                                                    Empresa
                                                </div>

                                                @if ($unidad->empresa)
                                                    <div class="cc-admin-result-value">
                                                        {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                    </div>

                                                    @if ($unidad->empresa->nit ?? false)
                                                        <div class="cc-admin-result-value-muted">
                                                            NIT: {{ $unidad->empresa->nit }}
                                                        </div>
                                                    @endif
                                                @else
                                                    <div class="cc-admin-result-value-muted">
                                                        Sin empresa
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="min-w-0 sm:grid sm:grid-cols-3 sm:gap-5 xl:col-span-4 xl:grid-cols-3">
                                                <div class="min-w-0">
                                                    <div class="cc-admin-result-label">
                                                        Licencia
                                                    </div>

                                                    @if ($unidad->licencia)
                                                        <div class="cc-admin-result-value">
                                                            {{ $unidad->licencia->periodo_vigencia_texto }}
                                                        </div>

                                                        <div class="cc-admin-result-value-muted">
                                                            {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                                        </div>
                                                    @else
                                                        <div class="cc-admin-result-value-muted">
                                                            Sin licencia
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="min-w-0">
                                                    <div class="cc-admin-result-label">
                                                        Puntos
                                                    </div>

                                                    <div class="cc-admin-result-value">
                                                        {{ $puntosAsignados }} / {{ $totalPuntos }}
                                                    </div>

                                                    <div class="cc-admin-result-value-muted">
                                                        {{ $puntosPendientes }} pendientes
                                                    </div>
                                                </div>

                                                <div class="min-w-0">
                                                    <div class="cc-admin-result-label">
                                                        Marchamos
                                                    </div>

                                                    <div class="cc-admin-result-value">
                                                        {{ $unidad->marchamos_activos }} activos
                                                    </div>

                                                    <div class="cc-admin-result-value-muted">
                                                        {{ $unidad->marchamos_historicos }} históricos
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex flex-col sm:flex-row gap-3 xl:col-span-2 xl:justify-end xl:self-center">
                                                <a href="{{ route('marchamos.reemplazos.show.ventana', $unidad) }}"
                                                   class="cc-btn-primary cc-btn-result w-full sm:w-auto">
                                                    Reemplazar
                                                </a>
                                            </div>

                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>