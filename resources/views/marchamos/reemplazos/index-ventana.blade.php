<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administración de marchamos | CC-Flota</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
            <div class="cc-page-wrapper">
                <div class="cc-window-container" style="max-width: 88rem;">
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Administración de marchamos
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
                                    Localice unidades activas para registrar reemplazos operativos de marchamos.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('marchamos.reemplazos.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
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
                                        Filtros de reemplazo
                                    </div>
                                </div>

                                <div class="cc-filter-inline-grid">

                                    <div class="cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>

                                        <select id="empresa_id" name="empresa_id" class="cc-input">
                                            <option value="">Todas las empresas</option>

                                            @foreach ($empresas as $empresa)
                                                <option value="{{ $empresa->id }}" @selected((string) $empresaId === (string) $empresa->id)>
                                                    {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="cc-field">
                                        <label for="unidad_id">
                                            Unidad
                                        </label>

                                        <select id="unidad_id" name="unidad_id" class="cc-input">
                                            <option value="">Todas las unidades activas</option>

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

                                    <div class="cc-filter-inline-actions">
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

                        @if (! $hayFiltros)
                            <section class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Inicie una consulta
                                </h5>

                                <p>
                                    Use los filtros para localizar unidades activas con cobertura completa. Desde esta pantalla podrá iniciar un reemplazo operativo de marchamos.
                                </p>
                            </section>
                        @endif

                        @if ($hayFiltros)
                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Unidades disponibles para reemplazo
                                    </h5>
                                    <p>
                                        Solo se muestran unidades activas con licencia, puntos de seguridad y cobertura completa de marchamos.
                                    </p>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="cc-table">
                                        <thead>
                                            <tr>
                                                <th>Unidad</th>
                                                <th>Empresa</th>
                                                <th>Licencia</th>
                                                <th>Puntos</th>
                                                <th>Marchamos</th>
                                                <th>Estado operativo</th>
                                                <th class="text-right">Acciones</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @forelse ($unidadesDisponibles as $unidad)
                                                @php
                                                    $totalPuntos = (int) ($unidad->total_puntos ?? 0);
                                                    $puntosAsignados = (int) ($unidad->puntos_asignados ?? 0);
                                                    $puntosPendientes = max($totalPuntos - $puntosAsignados, 0);
                                                @endphp

                                                <tr>
                                                    <td>
                                                        <span class="font-bold text-[var(--cc-text-main)]">
                                                            {{ $unidad->placa }}
                                                        </span>

                                                        <div class="text-sm text-[var(--cc-text-muted)]">
                                                            {{ $unidad->marca ?: 'Sin marca' }}
                                                        </div>
                                                    </td>

                                                    <td>
                                                        @if ($unidad->empresa)
                                                            <div class="font-bold text-[var(--cc-text-main)] cc-cell-truncate">
                                                                {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                            </div>

                                                            <div class="text-sm text-[var(--cc-text-muted)]">
                                                                {{ $unidad->empresa->nit }}
                                                            </div>
                                                        @else
                                                            <span class="text-[var(--cc-text-muted)]">
                                                                Sin empresa
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        @if ($unidad->licencia)
                                                            <div class="font-bold text-[var(--cc-text-main)]">
                                                                {{ $unidad->licencia->periodo_vigencia_texto }}
                                                            </div>

                                                            <div class="text-sm text-[var(--cc-text-muted)] cc-cell-truncate">
                                                                {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                                            </div>
                                                        @else
                                                            <span class="text-[var(--cc-text-muted)]">
                                                                Sin licencia
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        <div class="font-bold text-[var(--cc-text-main)]">
                                                            {{ $puntosAsignados }} / {{ $totalPuntos }}
                                                        </div>

                                                        <div class="text-sm text-[var(--cc-text-muted)]">
                                                            {{ $puntosPendientes }} pendientes
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <div class="font-bold text-[var(--cc-text-main)]">
                                                            {{ $unidad->marchamos_activos }} activos
                                                        </div>

                                                        <div class="text-sm text-[var(--cc-text-muted)]">
                                                            {{ $unidad->marchamos_historicos }} históricos
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <span class="cc-badge cc-badge-active">
                                                            Disponible
                                                        </span>

                                                        <div class="text-sm text-[var(--cc-text-muted)] mt-1">
                                                            Cobertura completa
                                                        </div>
                                                    </td>

                                                    <td class="text-right">
                                                        <div class="flex justify-end">
                                                            <a href="{{ route('marchamos.reemplazos.show', $unidad) }}"
                                                               class="cc-btn-primary cc-btn-table">
                                                                Reemplazar
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-[var(--cc-text-muted)] py-8">
                                                        No hay unidades activas con cobertura completa para los filtros aplicados.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </section>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>