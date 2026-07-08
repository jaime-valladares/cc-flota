<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Consulta de motoristas | CC-Flota</title>

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
                                    Consulta de motoristas
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
                                    Consulte los motoristas disponibles para solicitudes de abastecimiento.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('motoristas.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al Consulta
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
                                    Total motoristas
                                </span>
                                <span class="cc-summary-strip-value">
                                    {{ $totalMotoristas }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Activos
                                </span>
                                <span class="cc-summary-strip-value cc-summary-strip-value-success">
                                    {{ $motoristasActivos }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Inactivos
                                </span>
                                <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                                    {{ $motoristasInactivos }}
                                </span>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('motoristas.consulta.ventana') }}" class="mb-5">
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Filtros de consulta
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
                                        <label for="buscar">
                                            Buscar
                                        </label>
                                        <input
                                            id="buscar"
                                            name="buscar"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $buscar }}"
                                            maxlength="150"
                                            placeholder="Nombre, licencia o teléfono"
                                        >

                                        @error('buscar')
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

                                        <a href="{{ route('motoristas.consulta.ventana') }}" class="cc-btn-secondary">
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $motoristas->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $motoristas->firstItem() }}</span>
                                -
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $motoristas->lastItem() }}</span>
                                de
                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $motoristas->total() }}</span>
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
                        @elseif ($motoristas->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>
                                <p>
                                    No hay motoristas que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-table-wrapper">
                                <table class="cc-table">
                                    <colgroup>
                                        <col style="width: 26%;">
                                        <col style="width: 24%;">
                                        <col style="width: 18%;">
                                        <col style="width: 16%;">
                                        <col style="width: 16%;">
                                    </colgroup>

                                    <thead>
                                        <tr>
                                            <th class="cc-text-left">Motorista</th>
                                            <th class="cc-text-left">Empresa</th>
                                            <th class="cc-text-left">Licencia</th>
                                            <th class="cc-text-left">Teléfono</th>
                                            <th class="cc-text-left">Estado</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($motoristas as $motorista)
                                            <tr>
                                                <td class="cc-text-left cc-cell-truncate">
                                                    <span class="cc-table-strong">
                                                        {{ $motorista->nombre_completo }}
                                                    </span>
                                                </td>

                                                <td class="cc-text-left cc-cell-truncate">
                                                    {{ $motorista->empresa->nombre_comercial ?: $motorista->empresa->nombre_legal }}
                                                </td>

                                                <td class="cc-text-left cc-cell-truncate">
                                                    {{ $motorista->licencia }}
                                                </td>

                                                <td class="cc-text-left cc-cell-truncate">
                                                    {{ $motorista->telefono }}
                                                </td>

                                                <td class="cc-text-left">
                                                    @if ($motorista->estado === 'activo')
                                                        <span class="cc-badge cc-badge-active">
                                                            Activo
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-inactive">
                                                            Inactivo
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6">
                                {{ $motoristas->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>