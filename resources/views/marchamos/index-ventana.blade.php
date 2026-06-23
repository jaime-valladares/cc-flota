<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Consulta de marchamos | CC-Flota</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
            <div class="cc-page-wrapper">
                <div class="cc-window-container" style="max-width: 73rem;">
                    <div class="cc-card">

                        <div class="cc-card-header">
                            <div>
                                <h3 class="cc-title">
                                    Consulta de marchamos
                                </h3>
                                <p class="cc-subtitle">
                                    Consulte la cobertura de marchamos por empresa y unidad.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('marchamos.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <section class="cc-filter-panel">
                            <form method="GET" action="{{ route('marchamos.consulta.ventana') }}">
                                <input type="hidden" name="consultar" value="1">

                                <div class="flex flex-col lg:flex-row lg:items-end gap-4">
                                    <div class="cc-field flex-1">
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

                                    <div class="cc-field flex-1">
                                        <label for="unidad_id">
                                            Unidad
                                        </label>
                                        <select id="unidad_id" name="unidad_id" class="cc-input">
                                            <option value="">Todas las unidades</option>

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

                                    <div class="flex items-center gap-3 lg:pb-[0.05rem]">
                                        <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                            Consultar
                                        </button>

                                        <a href="{{ route('marchamos.consulta.ventana') }}" class="cc-btn-secondary cc-btn-form-action">
                                            Limpiar
                                        </a>
                                    </div>
                                </div>
                            </form>
                        </section>

                        @if (! $hayFiltros)
                            <section class="cc-empty-panel mt-6">
                                <h5>
                                    Inicie una consulta
                                </h5>

                                <p>
                                    Use los filtros para consultar la cobertura de marchamos por empresa o unidad. También puede presionar Consultar sin filtros para ver todas las unidades con cobertura registrada.
                                </p>
                            </section>
                        @endif

                        @if ($hayFiltros)
                            <section class="cc-detail-section mt-6">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Cobertura por unidad
                                    </h5>
                                    <p>
                                        Vista operativa de unidades con licencia, puntos de seguridad y estado de cobertura por marchamos.
                                    </p>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="cc-table">
                                        <thead>
                                            <tr>
                                                <th>Unidad</th>
                                                <th>Empresa</th>
                                                <th>Estado unidad</th>
                                                <th>Licencia</th>
                                                <th>Puntos</th>
                                                <th>Marchamos</th>
                                                <th>Avance</th>
                                                <th class="text-right">Acciones</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @forelse ($unidadesConCobertura as $unidad)
                                                @php
                                                    $totalPuntos = (int) ($unidad->total_puntos ?? 0);
                                                    $puntosAsignados = (int) ($unidad->puntos_asignados ?? 0);
                                                    $puntosPendientes = max($totalPuntos - $puntosAsignados, 0);

                                                    $porcentajeAvance = $totalPuntos > 0
                                                        ? round(($puntosAsignados / $totalPuntos) * 100)
                                                        : 0;
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
                                                            <div class="font-bold text-[var(--cc-text-main)]">
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
                                                    </td>

                                                    <td>
                                                        @if ($unidad->licencia)
                                                            <div class="font-bold text-[var(--cc-text-main)]">
                                                                {{ $unidad->licencia->periodo_vigencia_texto }}
                                                            </div>

                                                            <div class="text-sm text-[var(--cc-text-muted)]">
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
                                                        <div class="font-bold text-[var(--cc-text-main)]">
                                                            {{ $porcentajeAvance }}%
                                                        </div>

                                                        @if ($puntosPendientes === 0 && $totalPuntos > 0)
                                                            <div class="text-sm text-[var(--cc-success)]">
                                                                Cobertura completa
                                                            </div>
                                                        @elseif ($totalPuntos > 0)
                                                            <div class="text-sm text-[var(--cc-danger)]">
                                                                Cobertura pendiente
                                                            </div>
                                                        @else
                                                            <div class="text-sm text-[var(--cc-text-muted)]">
                                                                Sin puntos
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td class="text-right">
                                                        <div class="flex justify-end gap-2">
                                                            <a href="{{ route('marchamos.detalle-unidad.ventana', $unidad) }}"
                                                               class="cc-btn-secondary cc-btn-table">
                                                                Ver marchamos
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center text-[var(--cc-text-muted)] py-8">
                                                        No hay unidades con puntos de seguridad generados para los filtros aplicados.
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