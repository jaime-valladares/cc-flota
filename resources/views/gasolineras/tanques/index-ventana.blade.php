<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Gestión de tanques | CC-Flota</title>

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
                            <h3 class="cc-title cc-title-compact">
                                Gestión de tanques
                            </h3>

                            <p class="cc-subtitle cc-subtitle-compact">
                                Localice tanques registrados para revisar su estado operativo, capacidad y configuración de alerta.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('gasolineras.tanques.index', request()->query()) }}" class="cc-btn-secondary cc-btn-wide">
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
                            <ul class="cc-alert-list">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="GET" action="{{ route('gasolineras.tanques.index.ventana') }}" class="mb-5">
                        <input type="hidden" name="consultar" value="1">

                        <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                            <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                <div class="cc-form-section-title">
                                    Búsqueda de tanques
                                </div>
                            </div>

                            <div class="cc-filter-inline-grid">
                                @if ($esUsuarioDieselCop)
                                    <div class="cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>

                                        <select id="empresa_id" name="empresa_id" class="cc-input">
                                            <option value="">Todas</option>

                                            @foreach ($empresasSelector as $empresa)
                                                <option value="{{ $empresa->id }}" @selected((string) $empresaId === (string) $empresa->id)>
                                                    {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('empresa_id')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                @else
                                    <div class="cc-field">
                                        <label for="empresa_id_visible">
                                            Empresa
                                        </label>

                                        <select id="empresa_id_visible" class="cc-input" disabled>
                                            @foreach ($empresasSelector as $empresa)
                                                <option value="{{ $empresa->id }}" selected>
                                                    {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                <div class="cc-field">
                                    <label for="gasolinera_id">
                                        Gasolinera
                                    </label>

                                    <select id="gasolinera_id" name="gasolinera_id" class="cc-input">
                                        <option value="">Todas</option>

                                        @foreach ($gasolinerasSelector as $gasolinera)
                                            <option value="{{ $gasolinera->id }}" @selected((string) $gasolineraId === (string) $gasolinera->id)>
                                                {{ $gasolinera->nombre }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('gasolinera_id')
                                        <div class="cc-error">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="cc-field">
                                    <label for="nombre">
                                        Nombre del tanque
                                    </label>

                                    <input
                                        id="nombre"
                                        type="text"
                                        name="nombre"
                                        value="{{ $nombre }}"
                                        class="cc-input"
                                        placeholder="Ej. Tanque 1"
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

                                    <a href="{{ route('gasolineras.tanques.index.ventana') }}" class="cc-btn-secondary">
                                        Limpiar
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>

                    @if ($hayFiltros && $tanques->total() > 0)
                        <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                            Mostrando
                            <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $tanques->firstItem() }}</span>
                            -
                            <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $tanques->lastItem() }}</span>
                            de
                            <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $tanques->total() }}</span>
                        </div>
                    @endif

                    @if (! $hayFiltros)
                        <div class="cc-empty-panel cc-empty-panel-compact">
                            <h5>
                                Búsqueda pendiente
                            </h5>

                            <p>
                                Los resultados permanecerán vacíos hasta que localice tanques por empresa, gasolinera, nombre o estado.
                            </p>
                        </div>
                    @elseif ($tanques->isEmpty())
                        <div class="cc-empty-panel cc-empty-panel-compact">
                            <h5>
                                Sin resultados
                            </h5>

                            <p>
                                No hay tanques que coincidan con los criterios seleccionados.
                            </p>
                        </div>
                    @else
                        <div class="space-y-3">
                            @foreach ($tanques as $tanque)
                                @php
                                    $gasolinera = $tanque->gasolinera;
                                    $empresa = $gasolinera?->empresa;

                                    $capacidadTotal = (float) $tanque->capacidad_total;
                                    $volumenActual = (float) $tanque->volumen_actual;
                                    $volumenMinimoAlerta = (float) $tanque->volumen_minimo_alerta;

                                    $porcentajeDisponible = $capacidadTotal > 0
                                        ? round(($volumenActual / $capacidadTotal) * 100, 2)
                                        : 0;

                                    $bajoAlerta = $volumenActual <= $volumenMinimoAlerta;
                                @endphp

                                <article class="cc-result-card cc-result-card-compact">
                                    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">

                                        <div class="flex-1 min-w-0">
                                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center">

                                                <div>
                                                    <h5 class="cc-result-title cc-cell-truncate">
                                                        {{ $tanque->nombre }}
                                                    </h5>

                                                    <div class="cc-result-value-muted cc-cell-truncate">
                                                        {{ $gasolinera?->nombre ?: 'Sin gasolinera' }}
                                                    </div>
                                                </div>

                                                <div style="display: flex; align-items: center;">
                                                    @if ($tanque->estado === 'activo')
                                                        <span class="cc-badge cc-badge-active">
                                                            Activo
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-inactive">
                                                            Inactivo
                                                        </span>
                                                    @endif

                                                    @if ($bajoAlerta)
                                                        <span class="cc-badge cc-badge-warning" style="margin-left: 0.5rem;">
                                                            Bajo mínimo
                                                        </span>
                                                    @endif
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Empresa
                                                    </div>

                                                    <div class="cc-result-value cc-cell-truncate">
                                                        {{ $empresa?->nombre_comercial ?: $empresa?->nombre_legal ?: 'No registrada' }}
                                                    </div>

                                                    <div class="cc-result-value-muted cc-cell-truncate">
                                                        {{ $gasolinera?->direccion ?: 'Sin dirección' }}
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Inventario
                                                    </div>

                                                    <div class="cc-result-value">
                                                        {{ number_format($volumenActual, 2) }} gal
                                                    </div>

                                                    <div class="cc-result-value-muted">
                                                        {{ number_format($porcentajeDisponible, 2) }}% disponible
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Capacidad / alerta
                                                    </div>

                                                    <div class="cc-result-value">
                                                        {{ number_format($capacidadTotal, 2) }} gal
                                                    </div>

                                                    <div class="cc-result-value-muted">
                                                        Mínimo: {{ number_format($volumenMinimoAlerta, 2) }} gal
                                                    </div>
                                                </div>

                                            </div>
                                        </div>

                                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 xl:justify-end xl:min-w-[9rem]">
                                            <a href="{{ route('gasolineras.tanques.show.ventana', [$gasolinera, $tanque]) }}" class="cc-btn-primary cc-btn-result">
                                                Administrar
                                            </a>
                                        </div>

                                    </div>
                                </article>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $tanques->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </body>
</html>