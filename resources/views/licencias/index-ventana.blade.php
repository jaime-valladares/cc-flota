<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Consulta licencias | CC-Flota</title>

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
                                    Consulta licencias
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
                                    Consulte la cobertura Diesel Cop asignada a las unidades registradas.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('licencias.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver a Consulta
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
                                    {{ $hayFiltros ? 'Resultados' : 'Total licencias' }}
                                </span>
                                <span class="cc-summary-strip-value">
                                    {{ $resumenLicencias['total'] ?? $totalLicencias }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Activas
                                </span>
                                <span class="cc-summary-strip-value cc-summary-strip-value-success">
                                    {{ $resumenLicencias['activas'] ?? $totalActivas }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Inactivas
                                </span>
                                <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                                    {{ $resumenLicencias['inactivas'] ?? $totalInactivas }}
                                </span>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('licencias.consulta.ventana') }}" class="mb-5">
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Filtros de consulta
                                    </div>
                                </div>

                                <div class="cc-filter-inline-grid-unidades">

                                    <div class="cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>
                                        <select id="empresa_id" name="empresa_id" class="cc-input">
                                            <option value="">Todas</option>

                                            @foreach ($empresas as $empresa)
                                                <option value="{{ $empresa->id }}"
                                                        @selected((string) $empresaId === (string) $empresa->id)>
                                                    {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="cc-field">
                                        <label for="placa">
                                            Placa
                                        </label>
                                        <input id="placa"
                                               type="text"
                                               name="placa"
                                               value="{{ $placa }}"
                                               class="cc-input"
                                               placeholder="Ej. C123ABC">
                                    </div>

                                    <div class="cc-field">
                                        <label for="estado">
                                            Estado licencia
                                        </label>
                                        <select id="estado" name="estado" class="cc-input">
                                            <option value="">Todos</option>
                                            <option value="activa" @selected($estado === 'activa')>
                                                Activa
                                            </option>
                                            <option value="inactiva" @selected($estado === 'inactiva')>
                                                Inactiva
                                            </option>
                                        </select>
                                    </div>

                                    <div class="cc-field">
                                        <label for="periodo_vigencia_meses">
                                            Período de vigencia
                                        </label>
                                        <select id="periodo_vigencia_meses" name="periodo_vigencia_meses" class="cc-input">
                                            <option value="">Todos</option>

                                            @foreach ($periodosVigencia as $valor => $texto)
                                                <option value="{{ $valor }}" @selected((string) $periodoVigencia === (string) $valor)>
                                                    {{ $texto }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="cc-filter-inline-actions">
                                        <button type="submit" class="cc-btn-primary">
                                            Consultar
                                        </button>

                                        <a href="{{ route('licencias.consulta.ventana') }}" class="cc-btn-secondary">
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $licencias->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->firstItem() }}</span>
                                -
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->lastItem() }}</span>
                                de
                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->total() }}</span>
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
                        @elseif ($licencias->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>
                                <p>
                                    No hay licencias que coincidan con los filtros seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-table-wrapper">
                                <table class="cc-table">
                                    <thead>
                                        <tr>
                                            <th>Placa</th>
                                            <th>Empresa</th>
                                            <th>Vigencia</th>
                                            <th>Vencimiento</th>
                                            <th>Plantilla</th>
                                            <th>Puntos</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($licencias as $licencia)
                                            <tr>
                                                <td>
                                                    <span class="cc-table-strong">
                                                        {{ $licencia->unidad->placa ?? 'Sin placa' }}
                                                    </span>
                                                    <div class="text-xs text-[var(--cc-text-muted)]">
                                                        {{ $licencia->unidad->marca ?? 'Sin marca' }}
                                                    </div>
                                                </td>

                                                <td>
                                                    @if ($licencia->empresa)
                                                        <span class="cc-cell-truncate">
                                                            {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                                        </span>
                                                    @else
                                                        <span class="text-[var(--cc-text-muted)]">
                                                            Sin empresa
                                                        </span>
                                                    @endif
                                                </td>

                                                <td>
                                                    {{ $licencia->periodo_vigencia_texto }}
                                                </td>

                                                <td>
                                                    {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'No registrado' }}
                                                </td>

                                                <td>
                                                    {{ $licencia->plantilla_puntos_seguridad_texto }}
                                                </td>

                                                <td>
                                                    {{ $licencia->cantidad_puntos_seguridad_esperados }}
                                                </td>

                                                <td>
                                                    @if ($licencia->estado === 'activa')
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
                                {{ $licencias->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>