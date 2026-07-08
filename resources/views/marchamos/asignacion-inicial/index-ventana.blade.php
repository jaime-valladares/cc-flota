<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Asignación de marchamos | CC-Flota</title>

        
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
                            $empresaId = request('empresa_id', '');
                            $placa = request('placa', '');
                            $hayFiltros = request()->has('consultar');
                        @endphp

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Asignación de marchamos
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
                                    Localice unidades listas para completar su primera asignación de marchamos.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('marchamos.asignacion-inicial.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver a Asignación
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="GET" action="{{ route('marchamos.asignacion-inicial.index.ventana') }}" class="mb-5">
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Filtros de asignación
                                    </div>
                                </div>

                                <div class="cc-filter-inline-grid">

                                    <div class="cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>

                                        <select id="empresa_id" name="empresa_id" class="cc-input">
                                            <option value="">Todas las empresas</option>

                                            @foreach (($empresas ?? collect()) as $empresa)
                                                <option value="{{ $empresa->id }}" @selected((string) $empresaId === (string) $empresa->id)>
                                                    {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="cc-field">
                                        <label for="placa">
                                            Unidad
                                        </label>

                                        <input id="placa"
                                               type="text"
                                               name="placa"
                                               value="{{ $placa }}"
                                               class="cc-input"
                                               placeholder="Buscar por placa">
                                    </div>

                                    <div class="cc-filter-inline-actions">
                                        <button type="submit" class="cc-btn-primary">
                                            Consultar
                                        </button>

                                        <a href="{{ route('marchamos.asignacion-inicial.index.ventana') }}" class="cc-btn-secondary">
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if (! $hayFiltros)
                            <section class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Búsqueda pendiente
                                </h5>

                                <p>
                                    Use los filtros para localizar una unidad específica, todas las unidades de una empresa o todas las unidades elegibles para asignación inicial.
                                </p>
                            </section>
                        @else
                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Unidades elegibles
                                    </h5>
                                    <p>
                                        Unidades con licencia, puntos de seguridad y marchamos pendientes de asignación inicial.
                                    </p>
                                </div>

                                <div class="overflow-x-auto">
                                    <div class="min-w-[62rem]">

                                        <div class="grid grid-cols-[1.05fr_1.15fr_1fr_1fr_0.9fr_0.9fr_10rem] items-center px-4 py-3 bg-[var(--cc-bg-soft)]"
                                             style="border-bottom: 1px solid var(--cc-border);">

                                            <div class="text-[0.78rem] font-extrabold uppercase tracking-[0.09em] text-[var(--cc-text-main)]">
                                                Unidad
                                            </div>

                                            <div class="text-[0.78rem] font-extrabold uppercase tracking-[0.09em] text-[var(--cc-text-main)]">
                                                Empresa
                                            </div>

                                            <div class="text-[0.78rem] font-extrabold uppercase tracking-[0.09em] text-[var(--cc-text-main)]">
                                                Licencia
                                            </div>

                                            <div class="text-[0.78rem] font-extrabold uppercase tracking-[0.09em] text-[var(--cc-text-main)]">
                                                Estado unidad
                                            </div>

                                            <div class="text-[0.78rem] font-extrabold uppercase tracking-[0.09em] text-[var(--cc-text-main)]">
                                                Puntos
                                            </div>

                                            <div class="text-[0.78rem] font-extrabold uppercase tracking-[0.09em] text-[var(--cc-text-main)]">
                                                Avance
                                            </div>

                                            <div></div>
                                        </div>

                                        <div>
                                            @forelse ($unidades as $unidad)
                                                @php
                                                    $totalPuntos = (int) ($unidad->total_puntos ?? 0);
                                                    $puntosAsignados = (int) ($unidad->puntos_asignados ?? 0);
                                                    $puntosPendientes = max($totalPuntos - $puntosAsignados, 0);

                                                    $porcentajeAvance = $totalPuntos > 0
                                                        ? round(($puntosAsignados / $totalPuntos) * 100)
                                                        : 0;
                                                @endphp

                                                <article class="grid grid-cols-[1.05fr_1.15fr_1fr_1fr_0.9fr_0.9fr_10rem] items-center gap-0 px-4 py-4"
                                                         style="{{ ! $loop->first ? 'border-top: 1px solid var(--cc-border-soft, var(--cc-border));' : '' }}">

                                                    <div>
                                                        <div class="font-bold text-[var(--cc-text-main)]">
                                                            {{ $unidad->placa }}
                                                        </div>

                                                        <div class="text-sm text-[var(--cc-text-muted)]">
                                                            {{ $unidad->marca ?: 'Sin marca' }}
                                                        </div>
                                                    </div>

                                                    <div>
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
                                                    </div>

                                                    <div>
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
                                                    </div>

                                                    <div>
                                                        @if ($unidad->estado === 'registrada')
                                                            <span class="cc-badge cc-badge-warning">
                                                                Registrada
                                                            </span>
                                                        @elseif ($unidad->estado === 'activa')
                                                            <span class="cc-badge cc-badge-active">
                                                                Activa
                                                            </span>
                                                        @else
                                                            <span class="cc-badge cc-badge-inactive">
                                                                Inactiva
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div>
                                                        <div class="font-bold text-[var(--cc-text-main)]">
                                                            {{ $puntosAsignados }} / {{ $totalPuntos }}
                                                        </div>

                                                        <div class="text-sm text-[var(--cc-text-muted)]">
                                                            {{ $puntosPendientes }} pendientes
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="font-bold text-[var(--cc-text-main)]">
                                                            {{ $porcentajeAvance }}%
                                                        </div>

                                                        @if ($puntosPendientes > 0)
                                                            <div class="text-sm text-[var(--cc-danger)]">
                                                                Pendiente
                                                            </div>
                                                        @else
                                                            <div class="text-sm text-[var(--cc-success)]">
                                                                Completa
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <div class="flex justify-end items-center">
                                                        <a href="{{ route('marchamos.asignacion-inicial.show.ventana', $unidad) }}"
                                                           class="cc-btn-primary cc-btn-table">
                                                            Asignación
                                                        </a>
                                                    </div>
                                                </article>
                                            @empty
                                                <div class="text-center text-[var(--cc-text-muted)] py-8">
                                                    No hay unidades elegibles para asignación inicial con los filtros seleccionados.
                                                </div>
                                            @endforelse
                                        </div>

                                    </div>
                                </div>

                                <div class="mt-6">
                                    {{ $unidades->appends(request()->query())->links() }}
                                </div>
                            </section>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>