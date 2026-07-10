<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Marchamos de unidad | CC-Flota</title>

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

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Marchamos de unidad
                                </h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('marchamos.consulta.ventana', [
                                    'empresa_id' => $unidad->empresa_id,
                                    'unidad_id' => $unidad->id,
                                    'consultar' => 1,
                                ]) }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver a consulta
                                </a>

                                <a href="{{ route('marchamos.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @php
                            $totalPuntos = (int) ($unidad->total_puntos ?? 0);
                            $puntosAsignados = (int) ($unidad->puntos_asignados ?? 0);
                            $puntosPendientes = max($totalPuntos - $puntosAsignados, 0);

                            $porcentajeAvance = $totalPuntos > 0
                                ? round(($puntosAsignados / $totalPuntos) * 100)
                                : 0;
                        @endphp

                        <section class="cc-detail-section">
                            <div class="cc-detail-section-header">
                                <h5>
                                    Resumen de unidad
                                </h5>
                                <p>
                                    Unidad, licencia, puntos de seguridad y estado de cobertura física.
                                </p>
                            </div>

                            <div class="cc-summary-strip">
                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">
                                        Unidad
                                    </span>
                                    <span class="cc-summary-strip-value">
                                        {{ $unidad->placa }}
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">
                                        Puntos
                                    </span>
                                    <span class="cc-summary-strip-value">
                                        {{ $puntosAsignados }} / {{ $totalPuntos }}
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">
                                        Marchamos activos
                                    </span>
                                    <span class="cc-summary-strip-value">
                                        {{ $unidad->marchamos_activos }}
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">
                                        Avance
                                    </span>
                                    <span class="cc-summary-strip-value">
                                        {{ $porcentajeAvance }}%
                                    </span>
                                </div>
                            </div>

                            <div class="cc-detail-grid mt-5">
                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">
                                        Unidad
                                    </div>

                                    <div class="cc-detail-value">
                                        {{ $unidad->placa }}
                                        <span class="text-[var(--cc-text-muted)]">
                                            · {{ $unidad->marca ?: 'Sin marca' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">
                                        Empresa
                                    </div>

                                    <div class="cc-detail-value">
                                        @if ($unidad->empresa)
                                            {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                        @else
                                            Sin empresa
                                        @endif
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">
                                        Estado
                                    </div>

                                    <div class="cc-detail-value">
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
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">
                                        Licencia
                                    </div>

                                    <div class="cc-detail-value">
                                        @if ($unidad->licencia)
                                            {{ $unidad->licencia->periodo_vigencia_texto }}
                                            <span class="text-[var(--cc-text-muted)]">
                                                · {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                            </span>
                                        @else
                                            Sin licencia
                                        @endif
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">
                                        Puntos de seguridad
                                    </div>

                                    <div class="cc-detail-value">
                                        {{ $puntosAsignados }} / {{ $totalPuntos }}
                                        <span class="text-[var(--cc-text-muted)]">
                                            · {{ $puntosPendientes }} pendientes
                                        </span>
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">
                                        Marchamos
                                    </div>

                                    <div class="cc-detail-value">
                                        {{ $unidad->marchamos_activos }} activos
                                        <span class="text-[var(--cc-text-muted)]">
                                            · {{ $unidad->marchamos_historicos }} históricos
                                        </span>
                                    </div>
                                </div>

                                <div class="cc-detail-item cc-detail-item-wide">
                                    <div class="cc-detail-label">
                                        Cobertura
                                    </div>

                                    <div class="cc-detail-value">
                                        {{ $porcentajeAvance }}%

                                        @if ($puntosPendientes === 0 && $totalPuntos > 0)
                                            <span class="text-[var(--cc-success)]">
                                                · Completa
                                            </span>
                                        @elseif ($totalPuntos > 0)
                                            <span class="text-[var(--cc-danger)]">
                                                · Pendiente
                                            </span>
                                        @else
                                            <span class="text-[var(--cc-text-muted)]">
                                                · Sin puntos registrados
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="cc-detail-section mt-6">
                            <div class="cc-detail-section-header">
                                <h5>
                                    Marchamos registrados
                                </h5>
                                <p>
                                    {{ $totalMarchamos }} registros encontrados para la unidad seleccionada.
                                </p>
                            </div>

                            <div class="cc-table-adaptive-wrapper">
                                <table class="cc-table-adaptive" style="min-width: 88rem;">
                                    <thead>
                                        <tr>
                                            <th class="cc-table-adaptive-nowrap" style="width: 10rem;">
                                                Marchamo
                                            </th>

                                            <th class="cc-table-adaptive-nowrap" style="width: 16rem;">
                                                Empresa
                                            </th>

                                            <th class="cc-table-adaptive-nowrap" style="width: 13rem;">
                                                Unidad
                                            </th>

                                            <th class="cc-table-adaptive-nowrap" style="width: 24rem;">
                                                Punto de seguridad
                                            </th>

                                            <th class="cc-table-adaptive-nowrap" style="width: 10rem;">
                                                Estado
                                            </th>

                                            <th class="cc-table-adaptive-nowrap" style="width: 15rem;">
                                                Origen
                                            </th>

                                            <th class="cc-table-adaptive-nowrap" style="width: 11rem;">
                                                Activación
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse ($marchamos as $marchamo)
                                            <tr>
                                                <td class="cc-table-adaptive-nowrap">
                                                    <div class="cc-table-adaptive-strong">
                                                        {{ $marchamo->codigo_marchamo }}
                                                    </div>

                                                    <div class="cc-table-adaptive-muted">
                                                        ID {{ $marchamo->id }}
                                                    </div>
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    @if ($marchamo->empresa)
                                                        <div class="cc-table-adaptive-strong">
                                                            {{ $marchamo->empresa->nombre_comercial ?: $marchamo->empresa->nombre_legal }}
                                                        </div>

                                                        <div class="cc-table-adaptive-muted">
                                                            {{ $marchamo->empresa->nit }}
                                                        </div>
                                                    @else
                                                        <span class="cc-table-adaptive-muted">
                                                            Sin empresa
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    @if ($marchamo->unidad)
                                                        <span class="cc-table-adaptive-strong">
                                                            {{ $marchamo->unidad->placa }}
                                                        </span>

                                                        <div class="cc-table-adaptive-muted">
                                                            {{ $marchamo->unidad->marca ?: 'Sin marca' }}
                                                        </div>
                                                    @else
                                                        <span class="cc-table-adaptive-muted">
                                                            Sin unidad
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    @if ($marchamo->puntoSeguridad)
                                                        <div class="cc-table-adaptive-strong">
                                                            {{ $marchamo->puntoSeguridad->nombre_punto }}
                                                        </div>

                                                        <div class="cc-table-adaptive-muted">
                                                            Orden {{ $marchamo->puntoSeguridad->orden }}

                                                            @if ($marchamo->puntoSeguridad->codigo_punto)
                                                                · {{ $marchamo->puntoSeguridad->codigo_punto }}
                                                            @endif
                                                        </div>
                                                    @else
                                                        <span class="cc-table-adaptive-muted">
                                                            Sin punto
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    @if ($marchamo->estado === 'activo')
                                                        <span class="cc-badge cc-badge-active">
                                                            Activo
                                                        </span>
                                                    @elseif ($marchamo->estado === 'reemplazado')
                                                        <span class="cc-badge cc-badge-warning">
                                                            Reemplazado
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-inactive">
                                                            Anulado
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    {{ $marchamo->origen_creacion_texto }}
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    @if ($marchamo->fecha_activacion)
                                                        <div class="cc-table-adaptive-strong">
                                                            {{ $marchamo->fecha_activacion->format('d/m/Y') }}
                                                        </div>

                                                        <div class="cc-table-adaptive-muted">
                                                            {{ $marchamo->fecha_activacion->format('H:i') }}
                                                        </div>
                                                    @else
                                                        <span class="cc-table-adaptive-muted">
                                                            Sin fecha
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="7" class="text-center text-[var(--cc-text-muted)] py-8">
                                                    No se encontraron marchamos para la unidad seleccionada.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-8 px-4">
                                {{ $marchamos->links() }}
                            </div>
                        </section>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>