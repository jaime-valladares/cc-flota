<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Detalle de marchamos
                        </h3>
                        <p class="cc-subtitle">
                            Consulte los marchamos registrados para la unidad seleccionada.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('marchamos.index', [
                            'empresa_id' => $unidad->empresa_id,
                            'unidad_id' => $unidad->id,
                            'consultar' => 1,
                        ]) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a consulta
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
                        <h5>Resumen de unidad</h5>
                        <p>
                            Información general de la unidad, licencia y cobertura actual de puntos de seguridad.
                        </p>
                    </div>

                    <div class="cc-detail-grid">
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
                                Estado de unidad
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
                                Avance de cobertura
                            </div>
                            <div class="cc-detail-value">
                                {{ $porcentajeAvance }}%

                                @if ($puntosPendientes === 0 && $totalPuntos > 0)
                                    <span class="text-[var(--cc-success)]">
                                        · Cobertura completa
                                    </span>
                                @elseif ($totalPuntos > 0)
                                    <span class="text-[var(--cc-danger)]">
                                        · Cobertura pendiente
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
                        <h5>Marchamos registrados</h5>
                        <p>
                            {{ $totalMarchamos }} registros encontrados para la unidad seleccionada.
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="cc-table">
                            <thead>
                                <tr>
                                    <th style="width: 150px;">Marchamo</th>
                                    <th>Empresa</th>
                                    <th style="width: 160px;">Unidad</th>
                                    <th>Punto de seguridad</th>
                                    <th style="width: 140px;">Estado</th>
                                    <th style="width: 180px;">Origen</th>
                                    <th style="width: 160px;">Activación</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($marchamos as $marchamo)
                                    <tr>
                                        <td>
                                            <div class="font-bold text-[var(--cc-text-main)]">
                                                {{ $marchamo->codigo_marchamo }}
                                            </div>

                                            <div class="text-xs text-[var(--cc-text-muted)]">
                                                ID {{ $marchamo->id }}
                                            </div>
                                        </td>

                                        <td>
                                            @if ($marchamo->empresa)
                                                <div class="font-bold text-[var(--cc-text-main)]">
                                                    {{ $marchamo->empresa->nombre_comercial ?: $marchamo->empresa->nombre_legal }}
                                                </div>

                                                <div class="text-sm text-[var(--cc-text-muted)]">
                                                    {{ $marchamo->empresa->nit }}
                                                </div>
                                            @else
                                                <span class="text-[var(--cc-text-muted)]">
                                                    Sin empresa
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($marchamo->unidad)
                                                <a href="{{ route('unidades.show', $marchamo->unidad) }}"
                                                   class="font-bold text-[var(--cc-primary)] hover:underline">
                                                    {{ $marchamo->unidad->placa }}
                                                </a>

                                                <div class="text-sm text-[var(--cc-text-muted)]">
                                                    {{ $marchamo->unidad->marca ?: 'Sin marca' }}
                                                </div>
                                            @else
                                                <span class="text-[var(--cc-text-muted)]">
                                                    Sin unidad
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($marchamo->puntoSeguridad)
                                                <div class="font-bold text-[var(--cc-text-main)]">
                                                    {{ $marchamo->puntoSeguridad->nombre_punto }}
                                                </div>

                                                <div class="text-sm text-[var(--cc-text-muted)]">
                                                    Orden {{ $marchamo->puntoSeguridad->orden }}

                                                    @if ($marchamo->puntoSeguridad->codigo_punto)
                                                        · {{ $marchamo->puntoSeguridad->codigo_punto }}
                                                    @endif
                                                </div>
                                            @else
                                                <span class="text-[var(--cc-text-muted)]">
                                                    Sin punto
                                                </span>
                                            @endif
                                        </td>

                                        <td>
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

                                        <td>
                                            {{ $marchamo->origen_creacion_texto }}
                                        </td>

                                        <td>
                                            @if ($marchamo->fecha_activacion)
                                                <div class="font-bold text-[var(--cc-text-main)]">
                                                    {{ $marchamo->fecha_activacion->format('d/m/Y') }}
                                                </div>

                                                <div class="text-sm text-[var(--cc-text-muted)]">
                                                    {{ $marchamo->fecha_activacion->format('H:i') }}
                                                </div>
                                            @else
                                                <span class="text-[var(--cc-text-muted)]">
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

                    <div class="mt-6">
                        {{ $marchamos->links() }}
                    </div>
                </section>

            </div>
        </div>
    </div>
</x-app-layout>