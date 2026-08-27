@php
    $esVentana = $esVentana ?? false;
    $queryParams = request()->query();
    $licencia = $unidad->licencia;
    $puntos = $unidad->puntosSeguridad->sortBy('orden');
    $rendimientoTeorico = $unidad->modelo_medicion === 'galones_viaje'
        ? 'Según ruta (gal/viaje)'
        : $unidad->rendimiento_teorico_reporte;
    $disponibilidadPendiente = in_array($unidad->disponibilidad_operativa, ['asignacion_inicial_pendiente', 'pendiente_activacion_operativa', 'licencia_pendiente_activacion'], true);
    $rutaRegreso = $esVentana ? route('reportes.unidades.ventana', $queryParams) : route('reportes.unidades.index', $queryParams);
@endphp

<section class="cc-card">
    <header class="cc-card-header cc-card-header-compact">
        <h1 class="cc-title cc-title-compact">Ficha completa de la unidad</h1>
        <div class="flex flex-wrap items-center justify-end gap-3">
            <a href="{{ $rutaRegreso }}" class="cc-btn-secondary cc-btn-wide">Volver al reporte</a>
            @unless ($esVentana)
                <a href="{{ route('reportes.unidades.show.ventana', array_merge(['unidad' => $unidad->id], $queryParams)) }}" target="_blank" rel="noopener noreferrer" class="cc-btn-secondary cc-btn-wide">Abrir en nueva pestaña</a>
            @endunless
        </div>
    </header>

    <div class="cc-profile-summary">
        <div>
            <div class="cc-profile-eyebrow">Ficha completa de la unidad</div>
            <div class="cc-profile-title">{{ $unidad->placa }}</div>
            <div class="cc-profile-meta">
                <span>{{ $unidad->marca ?: 'Sin marca registrada' }}</span>
                <span>Empresa: {{ $unidad->empresa?->nombre_comercial ?: ($unidad->empresa?->nombre_legal ?: 'Sin empresa') }}</span>
                <span>Modelo: {{ $unidad->modelo_medicion_texto }}</span>
            </div>
        </div>
        <div class="cc-profile-status flex flex-wrap justify-end gap-4">
            <div class="flex flex-col items-end gap-1">
                <span class="text-xs font-semibold text-[var(--cc-text-muted)]">Estado administrativo</span>
                <span @class(['cc-badge', 'cc-badge-pending' => $unidad->estado === 'registrada', 'cc-badge-active' => $unidad->estado === 'activa', 'cc-badge-inactive' => $unidad->estado === 'inactiva'])>{{ $unidad->estado_texto }}</span>
            </div>
            <div class="flex flex-col items-end gap-1">
                <span class="text-xs font-semibold text-[var(--cc-text-muted)]">Resultado operacional</span>
                <span @class(['cc-badge', 'cc-badge-active' => $unidad->es_operable, 'cc-badge-inactive' => ! $unidad->es_operable])>{{ $unidad->es_operable ? 'Operable' : 'No operable' }}</span>
            </div>
        </div>
    </div>

    <div class="cc-detail-layout">
        <section class="cc-detail-section" data-report-section="identificacion">
            <div class="cc-detail-section-header"><h5>Identificación</h5></div>
            <div class="cc-detail-grid">
                @foreach (['Empresa' => $unidad->empresa?->nombre_comercial ?: ($unidad->empresa?->nombre_legal ?: 'Sin empresa'), 'Nombre / Placa' => $unidad->placa, 'Marca' => $unidad->marca ?: 'No registrada', 'Modelo de medición' => $unidad->modelo_medicion_texto, 'Rendimiento Teórico' => $rendimientoTeorico, 'Capacidad total' => number_format((float) $unidad->capacidad_total, 2).' gal', 'Total de tanques' => $unidad->total_tanques] as $etiqueta => $valor)
                    <div class="cc-detail-item"><div class="cc-detail-label">{{ $etiqueta }}</div><div class="cc-detail-value">{{ $valor }}</div></div>
                @endforeach
            </div>
        </section>

        <section class="cc-detail-section" data-report-section="tanques">
            <div class="cc-detail-section-header"><h5>Tanques físicos</h5></div>
            <div class="cc-table-shell"><table class="cc-table">
                <thead><tr><th>Número</th><th class="text-right">Capacidad</th><th>Cubierto por licencia</th></tr></thead>
                <tbody>
                    @forelse ($unidad->tanquesUnidad as $tanque)
                        <tr><td>Tanque {{ $tanque->numero }}</td><td class="text-right">{{ number_format((float) $tanque->capacidad, 2) }} gal</td><td>{{ $tanque->cubierto_por_licencia ? 'Sí' : 'No' }}</td></tr>
                    @empty
                        <tr><td colspan="3" class="cc-table-empty">Sin tanques físicos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="cc-detail-section" data-report-section="licencia">
            <div class="cc-detail-section-header"><h5>Licencia</h5></div>
            @if ($licencia)
                <div class="cc-detail-grid">
                    @foreach (['Estado administrativo' => $licencia->estado_texto, 'Condición de vigencia' => $licencia->condicion_vigencia_texto, 'Período' => $licencia->periodo_vigencia_texto, 'Fecha de activación' => $licencia->fecha_activacion?->format('d/m/Y') ?? 'Pendiente', 'Fecha de vencimiento' => $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'Pendiente', 'Estado relativo del vencimiento' => $licencia->vencimiento_relativo_texto, 'Plantilla' => $licencia->plantilla_puntos_seguridad_texto, 'Puntos esperados' => $licencia->cantidad_puntos_seguridad_esperados ?? 'Pendiente', 'Tanques cubiertos' => $licencia->cantidad_tanques_cubiertos, 'Capacidad cubierta' => number_format((float) $licencia->capacidad_cubierta, 2).' gal'] as $etiqueta => $valor)
                        <div class="cc-detail-item"><div class="cc-detail-label">{{ $etiqueta }}</div><div class="cc-detail-value">{{ $valor }}</div></div>
                    @endforeach
                </div>
            @else
                <div class="cc-table-empty">Sin licencia</div>
            @endif
        </section>

        <section class="cc-detail-section" data-report-section="puntos-seguridad">
            <div class="cc-detail-section-header"><h5>Puntos de seguridad y marchamos actuales</h5></div>
            <div class="cc-detail-grid mb-4">
                @foreach (['Puntos de seguridad' => $unidad->total_puntos_que_requieren_marchamo, 'Marchamos asignados' => $unidad->total_puntos_con_marchamo_asignado, 'Marchamos pendientes' => $unidad->total_puntos_pendientes_marchamo, 'Cobertura' => $unidad->asignacion_marchamos_completa ? 'Completa' : 'Pendiente'] as $etiqueta => $valor)
                    <div class="cc-detail-item"><div class="cc-detail-label">{{ $etiqueta }}</div><div class="cc-detail-value">{{ $valor }}</div></div>
                @endforeach
            </div>
            <div class="cc-table-shell"><table class="cc-table">
                <thead><tr><th style="width: 4rem; min-width: 4rem;">Orden</th><th>Código / Punto</th><th>Grupo</th><th>Subgrupo</th><th>Posición / Tanque</th><th>Marchamo actual</th><th>Estado marchamo</th></tr></thead>
                <tbody>
                    @forelse ($puntos as $punto)
                        <tr data-report-security-point="{{ $punto->id }}">
                            <td>{{ $punto->orden }}</td><td>{{ $punto->codigo_punto ?: 'Sin código' }} · {{ $punto->nombre_punto }}</td><td>{{ $punto->grupo ?: '—' }}</td><td>{{ $punto->subgrupo ?: '—' }}</td><td>{{ $punto->posicion_tanque ?: 'General' }}</td><td>{{ $punto->marchamoActual?->codigo_marchamo ?? 'Pendiente' }}</td>
                            <td><span @class(['cc-badge', 'cc-badge-active' => $punto->marchamoActual?->estado === 'activo', 'cc-badge-inactive' => $punto->marchamoActual && $punto->marchamoActual->estado !== 'activo', 'cc-badge-pending' => ! $punto->marchamoActual])>{{ $punto->marchamoActual?->estado_texto ?? 'Pendiente' }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="cc-table-empty">Sin puntos de seguridad registrados.</td></tr>
                    @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="cc-detail-section" data-report-section="estado-operacional">
            <div class="cc-detail-section-header"><h5>Estado operacional</h5></div>
            <div class="cc-detail-grid">
                @foreach (['Estado administrativo' => $unidad->estado_texto, 'Disponibilidad operativa' => $unidad->disponibilidad_operativa_texto, 'Descripción de disponibilidad' => $unidad->disponibilidad_operativa_descripcion, 'Condición de licencia' => $licencia?->condicion_vigencia_texto ?? 'Sin licencia', 'Marchamos asignados / requeridos' => $unidad->total_puntos_con_marchamo_asignado.' / '.$unidad->total_puntos_que_requieren_marchamo, 'Resultado final' => $unidad->es_operable ? 'Operable' : 'No operable'] as $etiqueta => $valor)
                    <div class="cc-detail-item"><div class="cc-detail-label">{{ $etiqueta }}</div><div class="cc-detail-value">{{ $valor }}</div></div>
                @endforeach
            </div>
        </section>
    </div>
</section>
