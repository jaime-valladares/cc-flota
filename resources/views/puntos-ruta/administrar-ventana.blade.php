<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar puntos de ruta | CC-Flota</title>

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

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Administrar puntos de ruta
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
                                    Gestione los puntos operativos disponibles como origen o destino para abastecimientos.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('puntos-ruta.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver a Administrar
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
                                    Total puntos
                                </span>
                                <span class="cc-summary-strip-value">
                                    {{ $totalPuntosRuta }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Activos
                                </span>
                                <span class="cc-summary-strip-value cc-summary-strip-value-success">
                                    {{ $puntosRutaActivos }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Inactivos
                                </span>
                                <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                                    {{ $puntosRutaInactivos }}
                                </span>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('puntos-ruta.administrar.ventana') }}" class="mb-5">
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Filtros de administración
                                    </div>
                                </div>

                                <div class="cc-filter-inline-grid">

                                    <div class="cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>

                                        @if ($esUsuarioDieselCop)
                                            <select id="empresa_id" name="empresa_id" class="cc-input">
                                                <option value="">Todas</option>

                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option value="{{ $empresaOpcion->id }}" @selected((string) $empresaId === (string) $empresaOpcion->id)>
                                                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <select id="empresa_id" name="empresa_id" class="cc-input" disabled>
                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option value="{{ $empresaOpcion->id }}" selected>
                                                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif

                                        @error('empresa_id')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="nombre">
                                            Nombre del punto
                                        </label>
                                        <input
                                            id="nombre"
                                            name="nombre"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $nombre }}"
                                            maxlength="150"
                                            placeholder="Buscar punto"
                                        >

                                        @error('nombre')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="estado">
                                            Estado
                                        </label>
                                        <select id="estado" name="estado" class="cc-input">
                                            <option value="">Seleccione</option>
                                            <option value="activo" @selected($estado === 'activo')>
                                                Activos
                                            </option>
                                            <option value="inactivo" @selected($estado === 'inactivo')>
                                                Inactivos
                                            </option>
                                        </select>

                                        @error('estado')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-filter-inline-actions">
                                        <button type="submit" class="cc-btn-primary">
                                            Consultar
                                        </button>

                                        <a href="{{ route('puntos-ruta.administrar.ventana') }}" class="cc-btn-secondary">
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $puntosRuta->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $puntosRuta->firstItem() }}</span>
                                -
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $puntosRuta->lastItem() }}</span>
                                de
                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $puntosRuta->total() }}</span>
                            </div>
                        @endif

                        @if (! $hayFiltros)
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Administración pendiente
                                </h5>
                                <p>
                                    Use los filtros para cargar los puntos de ruta disponibles.
                                </p>
                            </div>
                        @elseif ($puntosRuta->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>
                                <p>
                                    No hay puntos de ruta que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-result-list">
                                @foreach ($puntosRuta as $puntoRuta)
                                    <div class="cc-result-card cc-result-card-compact">
                                        <div class="cc-result-main">
                                            <div class="cc-result-eyebrow">
                                                Punto de ruta
                                            </div>

                                            <h4 class="cc-result-title">
                                                {{ $puntoRuta->nombre }}
                                            </h4>

                                            <div class="cc-result-meta">
                                                <span>
                                                    Empresa: {{ $puntoRuta->empresa->nombre_comercial ?: $puntoRuta->empresa->nombre_legal }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="cc-result-side">
                                            @if ($puntoRuta->estado === 'activo')
                                                <span class="cc-badge cc-badge-active">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Inactivo
                                                </span>
                                            @endif

                                            <div class="cc-result-actions">
                                                <a href="{{ route('puntos-ruta.show.ventana', $puntoRuta) }}" class="cc-btn-secondary cc-btn-result">
                                                    Ver ficha
                                                </a>

                                                <a href="{{ route('puntos-ruta.edit.ventana', $puntoRuta) }}" class="cc-btn-primary cc-btn-result">
                                                    Editar
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                {{ $puntosRuta->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>