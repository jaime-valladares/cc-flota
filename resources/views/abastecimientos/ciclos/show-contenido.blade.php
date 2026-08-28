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
    $lecturaInicial = $esHoras ? $apertura->horometro_actual : $apertura->kilometraje_actual;
    $lecturaFinal = $cierre ? ($esHoras ? $cierre->horometro_actual : $cierre->kilometraje_actual) : null;
    $recorrido = $cierre ? ($esHoras ? $cierre->diferencia_horometro : $cierre->diferencia_kilometraje) : null;
    $rendimientoTeorico = $esHoras
        ? $apertura->rendimiento_teorico_gal_hora_snapshot
        : $apertura->rendimiento_teorico_km_galon_snapshot;
    $rendimientoReal = $cierre ? match ($apertura->modelo_medicion) {
        'kilometros_galon' => $cierre->kilometros_por_galon,
        'galones_hora' => $cierre->galones_por_hora,
        default => null,
    } : null;
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
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Lectura inicial:</span> <span class="cc-detail-value">{{ number_format((float) $lecturaInicial, 2) }} {{ $esHoras ? 'h' : 'km' }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Lectura final:</span> <span class="cc-detail-value">{{ is_null($lecturaFinal) ? 'Pendiente' : number_format((float) $lecturaFinal, 2).' '.($esHoras ? 'h' : 'km') }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Distancia / horas:</span> <span class="cc-detail-value">{{ is_null($recorrido) ? 'Pendiente' : number_format((float) $recorrido, 2).' '.($esHoras ? 'h' : 'km') }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Rendimiento teórico:</span> <span class="cc-detail-value">{{ is_null($rendimientoTeorico) ? 'No definido' : number_format((float) $rendimientoTeorico, 2) }}</span></div>
        <div class="cc-detail-item cc-cycle-pair"><span class="cc-detail-label">Rendimiento real:</span> <span class="cc-detail-value">{{ is_null($rendimientoReal) ? 'Pendiente' : number_format((float) $rendimientoReal, 2) }}</span></div>
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
