@php
    $esVentana = $esVentana ?? false;
    $apertura = $ciclo->abastecimientoAnterior;
    $query = request()->query();
    $rutaRegreso = $esVentana ? route('reportes.rendimiento-km-galon.ventana',$query) : route('reportes.rendimiento-km-galon.index',$query);
    $motoristaCambio = $apertura->motorista_nombre_snapshot !== $ciclo->motorista_nombre_snapshot;
    $puedeVerAbastecimientos = Auth::user()->tienePermiso('abastecimientos.consultar');
@endphp
<section class="cc-card">
    <header class="cc-card-header cc-card-header-compact"><h1 class="cc-title cc-title-compact">Detalle del ciclo km/gal</h1><div class="flex flex-wrap items-center justify-end gap-3"><a href="{{ $rutaRegreso }}" class="cc-btn-secondary cc-btn-wide">Volver al reporte</a>@unless($esVentana)<a href="{{ route('reportes.rendimiento-km-galon.show.ventana',array_merge(['ciclo'=>$ciclo->id],$query)) }}" target="_blank" rel="noopener noreferrer" class="cc-btn-secondary cc-btn-wide">Abrir en nueva pestaña</a>@endunless</div></header>
    <div class="cc-profile-summary"><div><div class="cc-profile-eyebrow">Ciclo completo #{{ $ciclo->id }}</div><div class="cc-profile-title">{{ $ciclo->unidad_placa_snapshot }}</div><div class="cc-profile-meta"><span>{{ $ciclo->empresa_nombre_snapshot }}</span><span>Motorista principal: {{ $ciclo->motorista_nombre_snapshot }}</span></div></div><div class="cc-profile-status flex flex-wrap justify-end gap-4"><span @class(['cc-badge','cc-badge-active'=>$ciclo->resultado_reporte==='Ahorro','cc-badge-danger'=>$ciclo->resultado_reporte==='Sobreconsumo','cc-badge-info'=>$ciclo->resultado_reporte==='En objetivo'])>{{ $ciclo->resultado_reporte }}</span><strong @class(['text-[var(--cc-success)]'=>$ciclo->resultado_reporte==='Ahorro','text-[var(--cc-danger)]'=>$ciclo->resultado_reporte==='Sobreconsumo'])>{{ $ciclo->resultado_reporte==='Sobreconsumo' ? '-' : ($ciclo->resultado_reporte==='Ahorro' ? '+' : '') }}${{ number_format($ciclo->impacto_economico_reporte,2) }}</strong></div></div>
    <div class="cc-detail-layout">
        @php
            $secciones = [
                'Identificación del ciclo' => ['ID / número técnico'=>'#'.$ciclo->id,'Inicio'=>$apertura->fecha_hora_abastecimiento->format('d/m/Y H:i'),'Cierre'=>$ciclo->fecha_hora_abastecimiento->format('d/m/Y H:i'),'Modelo de medición'=>'Kilómetros por galón','Estado'=>'Registrado'],
                'Lecturas' => ['Kilometraje inicial'=>number_format((float)$ciclo->kilometraje_anterior,2).' km','Kilometraje final'=>number_format((float)$ciclo->kilometraje_actual,2).' km','Kilómetros recorridos'=>number_format((float)$ciclo->diferencia_kilometraje,2).' km'],
                'Rendimiento' => ['Rendimiento Teórico'=>number_format((float)$ciclo->rendimiento_teorico_km_galon_snapshot,2).' km/gal','Rendimiento Real'=>number_format((float)$ciclo->kilometros_por_galon,2).' km/gal','Consumo Teórico'=>number_format((float)$ciclo->consumo_teorico_ciclo,2).' gal','Consumo Real'=>number_format((float)$ciclo->consumo_real_ciclo,2).' gal','Diferencia de galones'=>number_format($ciclo->diferencia_absoluta_reporte,2).' gal','Resultado'=>$ciclo->resultado_reporte,'Impacto económico'=>($ciclo->resultado_reporte==='Sobreconsumo'?'-':($ciclo->resultado_reporte==='Ahorro'?'+':'')).'$'.number_format($ciclo->impacto_economico_reporte,2)],
            ];
        @endphp
        @foreach($secciones as $titulo=>$datos)<section class="cc-detail-section"><div class="cc-detail-section-header"><h5>{{ $titulo }}</h5></div><div class="cc-detail-grid">@foreach($datos as $etiqueta=>$valor)<div class="cc-detail-item"><div class="cc-detail-label">{{ $etiqueta }}</div><div class="cc-detail-value">{{ $valor }}</div></div>@endforeach</div></section>@endforeach

        <section class="cc-detail-section"><div class="cc-detail-section-header"><h5>Motoristas</h5></div><div class="cc-detail-grid"><div class="cc-detail-item"><div class="cc-detail-label">Motorista apertura</div><div class="cc-detail-value">{{ $apertura->motorista_nombre_snapshot }}</div></div><div class="cc-detail-item"><div class="cc-detail-label">Motorista cierre · principal</div><div class="cc-detail-value">{{ $ciclo->motorista_nombre_snapshot }}</div></div>@if($motoristaCambio)<div class="cc-detail-item cc-detail-item-wide"><span class="cc-badge cc-badge-warning">Cambio de motorista durante el ciclo</span></div>@endif</div></section>

        @foreach([['Abastecimiento de apertura',$apertura],['Abastecimiento de cierre',$ciclo]] as [$titulo,$abastecimiento])
            <section class="cc-detail-section"><div class="cc-detail-section-header"><h5>{{ $titulo }}</h5></div><div class="cc-detail-grid">
                @foreach(['ID'=>'#'.$abastecimiento->id,'Fecha / hora'=>$abastecimiento->fecha_hora_abastecimiento->format('d/m/Y H:i'),'Motorista'=>$abastecimiento->motorista_nombre_snapshot,'Origen'=>$abastecimiento->origen_nombre_snapshot ?: ucfirst((string)$abastecimiento->tipo_origen),'Galones cargados'=>number_format((float)$abastecimiento->volumen_cargado,2).' gal','Valor disponible'=>!is_null($abastecimiento->valor_carga_snapshot)?'$'.number_format((float)$abastecimiento->valor_carga_snapshot,2):(!is_null($abastecimiento->total_pagado)?'$'.number_format((float)$abastecimiento->total_pagado,2).' '.($abastecimiento->moneda?:'USD'):'Sin valor disponible')] as $etiqueta=>$valor)<div class="cc-detail-item"><div class="cc-detail-label">{{ $etiqueta }}</div><div class="cc-detail-value">{{ $valor }}</div></div>@endforeach
                @if($puedeVerAbastecimientos)<div class="cc-detail-item cc-detail-item-wide"><a href="{{ route('abastecimientos.show',$abastecimiento) }}" class="cc-btn-secondary">Ver ficha del abastecimiento</a></div>@endif
            </div></section>
        @endforeach
    </div>
</section>
