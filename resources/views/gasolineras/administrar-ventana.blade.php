<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar gasolineras | CC-Flota</title>

        
        @include('layouts.partials.favicon')
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/cc-flota/favicon.png') }}?v=3">
        <link rel="shortcut icon" type="image/png" href="{{ asset('images/cc-flota/favicon.png') }}?v=3">
        <link rel="apple-touch-icon" href="{{ asset('images/cc-flota/favicon.png') }}?v=3">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        <div class="cc-window-wrapper" style="padding-top: 2.1rem;">
            <div class="cc-window-container" style="max-width: 80rem;">

                <div class="cc-card">
                    <div class="cc-card-header cc-card-header-compact">
                        <div>
                            <h3 class="cc-title cc-title-compact" style="font-size: 1.7rem; line-height: 1.2;">
                                Administrar gasolineras
                            </h3>

                            <p class="cc-subtitle cc-subtitle-compact">
                                Gestione gasolineras internas, tanques asociados, estado operativo e inventario disponible.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('gasolineras.create.ventana') }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="cc-btn-primary">
                                Nueva gasolinera
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
                            <ul class="cc-alert-list">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="cc-summary-strip">
                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">Gasolineras</span>
                            <span class="cc-summary-strip-value">{{ $totalGasolineras }}</span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">Activas</span>
                            <span class="cc-summary-strip-value">{{ $gasolinerasActivas }}</span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">Inactivas</span>
                            <span class="cc-summary-strip-value">{{ $gasolinerasInactivas }}</span>
                        </div>
                    </div>

                    <form method="GET" action="{{ route('gasolineras.administrar.ventana') }}" class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">
                        <div class="cc-filter-inline-grid">
                            @if ($esUsuarioDieselCop)
                                <div class="cc-field">
                                    <label for="empresa_id" class="cc-label">Empresa</label>

                                    <select id="empresa_id" name="empresa_id" class="cc-input">
                                        <option value="">Seleccione una empresa</option>

                                        @foreach ($empresasSelector as $empresa)
                                            <option value="{{ $empresa->id }}" @selected((string) $empresaId === (string) $empresa->id)>
                                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="cc-field">
                                <label for="nombre" class="cc-label">Nombre</label>

                                <input
                                    id="nombre"
                                    type="text"
                                    name="nombre"
                                    value="{{ $nombre }}"
                                    class="cc-input"
                                    placeholder="Ej. Gasolinera central"
                                >
                            </div>

                            <div class="cc-field">
                                <label for="estado" class="cc-label">Estado</label>

                                <select id="estado" name="estado" class="cc-input">
                                    <option value="">Todos</option>
                                    <option value="activa" @selected($estado === 'activa')>Activa</option>
                                    <option value="inactiva" @selected($estado === 'inactiva')>Inactiva</option>
                                </select>
                            </div>

                            <div class="cc-filter-actions">
                                <button type="submit" class="cc-btn-primary">
                                    Buscar
                                </button>

                                <a href="{{ route('gasolineras.administrar.ventana') }}" class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>

                    @if (! $hayFiltros)
                        <div class="cc-empty-panel cc-empty-panel-compact">
                            <div class="cc-empty-title">
                                Seleccione un criterio de búsqueda
                            </div>

                            <p class="cc-empty-text">
                                Utilice los filtros para localizar una gasolinera y administrar su operación.
                            </p>
                        </div>
                    @elseif ($gasolineras->isEmpty())
                        <div class="cc-empty-panel cc-empty-panel-compact">
                            <div class="cc-empty-title">
                                Sin resultados
                            </div>

                            <p class="cc-empty-text">
                                No se encontraron gasolineras con los criterios seleccionados.
                            </p>
                        </div>
                    @else
                        <div class="cc-results-list">
                            @foreach ($gasolineras as $gasolinera)
                                @php
                                    $tanques = $gasolinera->tanques ?? collect();

                                    $capacidadTotal = $tanques->sum(fn ($tanque) => (float) $tanque->capacidad_total);
                                    $volumenActual = $tanques->sum(fn ($tanque) => (float) $tanque->volumen_actual);

                                    $porcentajeDisponible = $capacidadTotal > 0
                                        ? round(($volumenActual / $capacidadTotal) * 100, 2)
                                        : 0;

                                    $tanquesBajoAlerta = $tanques
                                        ->filter(fn ($tanque) => (float) $tanque->volumen_actual <= (float) $tanque->volumen_minimo_alerta)
                                        ->count();
                                @endphp

                                <article class="cc-result-card cc-result-card-compact">
                                    <div class="cc-result-main">
                                        <div>
                                            <div class="cc-result-title">
                                                {{ $gasolinera->nombre }}
                                            </div>

                                            <div class="cc-result-meta">
                                                @if ($esUsuarioDieselCop)
                                                    <span>
                                                        {{ $gasolinera->empresa?->nombre_comercial ?: $gasolinera->empresa?->nombre_legal }}
                                                    </span>
                                                @endif

                                                <span>
                                                    {{ $gasolinera->direccion }}
                                                </span>
                                            </div>
                                        </div>

                                        <div class="cc-result-status">
                                            <span class="cc-status-pill {{ $gasolinera->estado === 'activa' ? 'cc-status-active' : 'cc-status-inactive' }}">
                                                {{ ucfirst($gasolinera->estado) }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="cc-result-grid">
                                        <div>
                                            <div class="cc-result-label">Tanques</div>
                                            <div class="cc-result-value">
                                                {{ $gasolinera->tanques_activos_count ?? 0 }} activos / {{ $gasolinera->tanques_count ?? $tanques->count() }} total
                                            </div>
                                        </div>

                                        <div>
                                            <div class="cc-result-label">Inventario disponible</div>
                                            <div class="cc-result-value">
                                                {{ number_format($volumenActual, 2) }} gal
                                            </div>
                                        </div>

                                        <div>
                                            <div class="cc-result-label">Capacidad total</div>
                                            <div class="cc-result-value">
                                                {{ number_format($capacidadTotal, 2) }} gal
                                            </div>
                                        </div>

                                        <div>
                                            <div class="cc-result-label">Disponibilidad</div>
                                            <div class="cc-result-value">
                                                {{ number_format($porcentajeDisponible, 2) }}%
                                            </div>
                                        </div>
                                    </div>

                                    @if ($tanquesBajoAlerta > 0)
                                        <div class="cc-alert cc-alert-warning" style="margin-top: 0.9rem;">
                                            {{ $tanquesBajoAlerta }} tanque(s) se encuentran en nivel mínimo o por debajo del mínimo definido.
                                        </div>
                                    @endif

                                    <div class="cc-result-actions">
                                        <a href="{{ route('gasolineras.show.ventana', $gasolinera) }}" class="cc-btn-result">
                                            Administrar
                                        </a>

                                        <a href="{{ route('gasolineras.edit.ventana', $gasolinera) }}" class="cc-btn-result">
                                            Editar
                                        </a>
                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="cc-pagination">
                            {{ $gasolineras->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </body>
</html>