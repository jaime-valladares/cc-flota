@php
    $esCompleto = ! is_null($cierre);
    $esHoras = $apertura->modelo_medicion === 'galones_hora';
    $esViajes = $apertura->modelo_medicion === 'galones_viaje';
    $modeloTexto = match ($apertura->modelo_medicion) {
        'kilometros_galon' => 'Kilómetros por galón',
        'galones_hora' => 'Galones por hora',
        'galones_viaje' => 'Galones por viaje',
        default => 'No definido',
    };
    $totalViajes = $cierre?->total_viajes;
    $rendimientoTeoricoViaje = $esCompleto
        && ! is_null($cierre->galones_teoricos)
        && $totalViajes > 0
            ? (float) $cierre->galones_teoricos / $totalViajes
            : null;
    $rendimientoRealViaje = $esCompleto
        && ! is_null($cierre->consumo_real_ciclo)
        && $totalViajes > 0
            ? (float) $cierre->consumo_real_ciclo / $totalViajes
            : null;
    $rendimientoTeorico = match ($apertura->modelo_medicion) {
        'kilometros_galon' => $apertura->rendimiento_teorico_km_galon_snapshot,
        'galones_hora' => $apertura->rendimiento_teorico_gal_hora_snapshot,
        'galones_viaje' => $rendimientoTeoricoViaje,
        default => null,
    };
    $rendimientoReal = $cierre ? match ($apertura->modelo_medicion) {
        'kilometros_galon' => $cierre->kilometros_por_galon,
        'galones_hora' => $cierre->galones_por_hora,
        'galones_viaje' => $rendimientoRealViaje,
        default => null,
    } : null;
    $rendimientoUnidad = match ($apertura->modelo_medicion) {
        'kilometros_galon' => 'km/gal',
        'galones_hora' => 'gal/h',
        'galones_viaje' => 'gal/viaje',
        default => '',
    };
    [$costoUnitarioEtiqueta, $costoUnitarioSufijo] = match ($apertura->modelo_medicion) {
        'kilometros_galon' => ['Costo por kilómetro', '/km'],
        'galones_hora' => ['Costo por hora', '/h'],
        'galones_viaje' => ['Costo por viaje', '/viaje'],
        default => ['Costo operativo', ''],
    };
    $eventos = collect([$apertura, $cierre])->filter();
@endphp

<div class="cc-card-header cc-card-header-compact">
    <h3 class="cc-title cc-title-compact">Ficha del ciclo</h3>
    <div class="flex flex-wrap gap-3">
        @if (! $modoVentana)
            <a href="{{ route('abastecimientos.ciclos.show.ventana', $apertura) }}" target="_blank" rel="noopener noreferrer" class="cc-btn-secondary">Abrir en nueva pestaña</a>
        @endif
        <a href="{{ $modoVentana ? route('abastecimientos.ciclos.ventana') : route('abastecimientos.ciclos.index') }}" class="cc-btn-secondary">Volver a ciclos</a>
    </div>
</div>

<section class="cc-detail-section">
    <div class="cc-detail-section-header"><h5>Identidad del ciclo</h5></div>
    <div class="cc-detail-grid">
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Empresa:</span> <span class="cc-detail-value">{{ $apertura->empresa_nombre_snapshot ?: $apertura->empresa?->nombre_comercial ?: $apertura->empresa?->nombre_legal }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Unidad:</span> <span class="cc-detail-value">{{ $apertura->unidad_placa_snapshot ?: $apertura->unidad?->placa }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Modelo:</span> <span class="cc-detail-value">{{ $modeloTexto }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Estado:</span> <span class="cc-detail-value">{{ $esCompleto ? 'Completo' : 'En proceso' }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Inicio:</span> <span class="cc-detail-value">{{ $apertura->fecha_hora_abastecimiento?->format('d/m/Y H:i') }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Cierre:</span> <span class="cc-detail-value">{{ $cierre?->fecha_hora_abastecimiento?->format('d/m/Y H:i') ?: 'Pendiente' }}</span></div>
    </div>
</section>

<section class="cc-detail-section mt-6">
    <div class="cc-detail-section-header"><h5>Medición</h5></div>
    <div class="cc-detail-grid">
        @if ($esHoras)
            <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Lectura inicial de horómetro:</span> <span class="cc-detail-value">{{ number_format((float) $apertura->horometro_actual, 2) }} h</span></div>
            <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Lectura final de horómetro:</span> <span class="cc-detail-value">{{ $cierre ? number_format((float) $cierre->horometro_actual, 2).' h' : 'Pendiente' }}</span></div>
            <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Horas contabilizadas:</span> <span class="cc-detail-value">{{ $cierre ? number_format((float) $cierre->diferencia_horometro, 2).' h' : 'Pendiente' }}</span></div>
            <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Distancia recorrida:</span> <span class="cc-detail-value">{{ $cierre ? number_format((float) $cierre->diferencia_kilometraje, 2).' km' : 'Pendiente' }}</span></div>
        @else
            <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Lectura inicial:</span> <span class="cc-detail-value">{{ number_format((float) $apertura->kilometraje_actual, 2) }} km</span></div>
            <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Lectura final:</span> <span class="cc-detail-value">{{ $cierre ? number_format((float) $cierre->kilometraje_actual, 2).' km' : 'Pendiente' }}</span></div>
            <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Distancia recorrida:</span> <span class="cc-detail-value">{{ $cierre ? number_format((float) $cierre->diferencia_kilometraje, 2).' km' : 'Pendiente' }}</span></div>
        @endif
        @if ($esViajes)
            <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Viajes contabilizados:</span> <span class="cc-detail-value">{{ $cierre ? number_format((int) $totalViajes) : 'Pendiente' }}</span></div>
        @endif
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Galones consumidos:</span> <span class="cc-detail-value">{{ $cierre && ! is_null($cierre->consumo_real_ciclo) ? number_format((float) $cierre->consumo_real_ciclo, 2).' gal' : 'Pendiente' }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Rendimiento teórico:</span> <span class="cc-detail-value">{{ is_null($rendimientoTeorico) ? 'No disponible' : number_format((float) $rendimientoTeorico, 2).' '.$rendimientoUnidad }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Rendimiento real:</span> <span class="cc-detail-value">{{ ! $esCompleto ? 'Pendiente' : (is_null($rendimientoReal) ? 'No disponible' : number_format((float) $rendimientoReal, 2).' '.$rendimientoUnidad) }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Variación vs. teórico:</span> <span class="cc-detail-value">{{ $cierre && ! is_null($cierre->diferencia_galones_ciclo) ? number_format((float) $cierre->diferencia_galones_ciclo, 2).' gal' : 'Pendiente' }}</span></div>
    </div>
</section>

<section class="cc-detail-section mt-6">
    <div class="cc-detail-section-header"><h5>Combustible</h5></div>
    <div class="cc-detail-grid">
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Capacidad contractual:</span> <span class="cc-detail-value">{{ number_format((float) $apertura->capacidad_cubierta_snapshot, 2) }} gal</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Combustible inicial:</span> <span class="cc-detail-value">{{ number_format((float) $apertura->volumen_final, 2) }} gal</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Carga de cierre:</span> <span class="cc-detail-value">{{ $cierre ? number_format((float) $cierre->volumen_cargado, 2).' gal' : 'Pendiente' }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Combustible final:</span> <span class="cc-detail-value">{{ $cierre ? number_format((float) $cierre->volumen_final, 2).' gal' : 'Pendiente' }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Combustible consumido:</span> <span class="cc-detail-value">{{ $cierre && ! is_null($cierre->consumo_real_ciclo) ? number_format((float) $cierre->consumo_real_ciclo, 2).' gal' : 'Pendiente' }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Eventos involucrados:</span> <span class="cc-detail-value">{{ $eventos->count() }}</span></div>
    </div>
</section>

<section class="cc-detail-section mt-6">
    <div class="cc-detail-section-header"><h5>Economía</h5></div>
    <div class="cc-detail-grid">
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Valor inicial a bordo:</span> <span class="cc-detail-value">{{ is_null($apertura->valor_abordo_resultante) ? 'No disponible' : '$'.number_format((float) $apertura->valor_abordo_resultante, 2) }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Costo de carga:</span> <span class="cc-detail-value">{{ $cierre && ! is_null($cierre->valor_carga_snapshot) ? '$'.number_format((float) $cierre->valor_carga_snapshot, 2) : 'Pendiente' }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Costo promedio final:</span> <span class="cc-detail-value">{{ $cierre && ! is_null($cierre->costo_promedio_abordo_resultante) ? '$'.number_format((float) $cierre->costo_promedio_abordo_resultante, 2) : 'Pendiente' }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Valor final a bordo:</span> <span class="cc-detail-value">{{ $cierre && ! is_null($cierre->valor_abordo_resultante) ? '$'.number_format((float) $cierre->valor_abordo_resultante, 2) : 'Pendiente' }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Costo consumido:</span> <span class="cc-detail-value">{{ $cierre && ! is_null($cierre->costo_combustible_consumido_ciclo) ? '$'.number_format((float) $cierre->costo_combustible_consumido_ciclo, 2) : 'Pendiente' }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">{{ $costoUnitarioEtiqueta }}:</span> <span class="cc-detail-value">{{ $cierre && ! is_null($cierre->costo_unitario_ciclo) ? '$'.number_format((float) $cierre->costo_unitario_ciclo, 2).$costoUnitarioSufijo : 'Pendiente' }}</span></div>
    </div>
</section>

<section class="cc-detail-section mt-6">
    <div class="cc-detail-section-header"><h5>Abastecimientos del ciclo</h5></div>
    <div class="cc-table-adaptive-wrapper">
        <table class="cc-table-adaptive">
            <thead><tr><th>Evento</th><th>Fecha</th><th>Carga</th><th>Acción</th></tr></thead>
            <tbody>
                @foreach ($eventos as $indice => $evento)
                    <tr>
                        <td>{{ $indice === 0 ? 'Apertura' : 'Cierre' }}</td>
                        <td>{{ $evento->fecha_hora_abastecimiento?->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format((float) $evento->volumen_cargado, 2) }} gal</td>
                        <td><a href="{{ $modoVentana ? route('abastecimientos.show.ventana', $evento) : route('abastecimientos.show', $evento) }}" class="cc-btn-secondary cc-btn-form-action">Ver ficha de abastecimiento</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @unless ($esCompleto)
        <div class="cc-status-strip cc-status-strip-warning mt-4"><div><strong>Cierre pendiente</strong><span>El ciclo permanece abierto hasta el próximo abastecimiento confirmado.</span></div></div>
    @endunless
</section>
