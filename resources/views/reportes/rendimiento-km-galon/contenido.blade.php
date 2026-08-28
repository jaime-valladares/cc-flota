@php
    $esVentana = $esVentana ?? false;
    $rutaConsulta = $esVentana ? route('reportes.rendimiento-km-galon.ventana') : route('reportes.rendimiento-km-galon.index');
    $rutaAlterna = $esVentana ? route('reportes.rendimiento-km-galon.index', request()->query()) : route('reportes.rendimiento-km-galon.ventana', request()->query());
@endphp
<section class="cc-card" style="position: relative; isolation: isolate;">
    <header class="cc-card-header cc-card-header-compact">
        <div><h1 class="cc-title cc-title-compact">Rendimiento en kilómetros por galón</h1><p class="cc-subtitle cc-subtitle-compact">Ciclos completos evaluados por fecha de cierre.</p></div>
        <a href="{{ $rutaAlterna }}" @unless($esVentana) target="_blank" rel="noopener noreferrer" @endunless class="cc-btn-secondary cc-btn-wide">{{ $esVentana ? 'Volver al sistema' : 'Abrir en nueva pestaña' }}</a>
    </header>

    @if ($hayConsulta)
        <div class="mb-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
            @foreach ([['Ciclos evaluados',$resumen['ciclos']],['Kilómetros recorridos',number_format($resumen['kilometros'],2).' km'],['Consumo teórico',number_format($resumen['consumo_teorico'],2).' gal'],['Consumo real',number_format($resumen['consumo_real'],2).' gal']] as [$etiqueta,$valor])
                <div class="cc-metric-card cc-metric-card-compact" style="padding: .72rem .9rem;" data-summary-label="{{ $etiqueta }}" data-summary-value="{{ $valor }}"><div class="cc-metric-label">{{ $etiqueta }}</div><div class="cc-metric-value cc-metric-value-compact" style="font-size: 1.35rem;">{{ $valor }}</div></div>
            @endforeach
        </div>
        <div class="cc-metric-grid cc-metric-grid-compact" style="gap: .75rem; margin-bottom: 1.15rem;">
            @foreach ([['Galones ahorrados',number_format($resumen['ahorro_galones'],2).' gal','cc-metric-value-success'],['Sobreconsumo',number_format($resumen['sobreconsumo_galones'],2).' gal','cc-metric-value-danger'],['Impacto económico neto',($resumen['impacto_neto']<0?'-':'').'$'.number_format(abs($resumen['impacto_neto']),2),$resumen['impacto_neto']>0?'cc-metric-value-success':($resumen['impacto_neto']<0?'cc-metric-value-danger':'')]] as [$etiqueta,$valor,$clase])
                <div class="cc-metric-card cc-metric-card-compact" style="padding: .72rem .9rem;" data-summary-label="{{ $etiqueta }}" data-summary-value="{{ $valor }}"><div class="cc-metric-label">{{ $etiqueta }}</div><div class="cc-metric-value cc-metric-value-compact {{ $clase }}" style="font-size: 1.35rem;">{{ $valor }}</div></div>
            @endforeach
        </div>
    @endif

    <form method="GET" action="{{ $rutaConsulta }}" class="mb-5">
        <input type="hidden" name="consultar" value="1">
        <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">
            <div class="cc-form-section cc-form-section-compact" style="margin-top:0;"><div class="cc-form-section-title">Filtros de consulta</div></div>
            <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-12">
                <div class="cc-field xl:col-span-3"><label for="busqueda">Búsqueda</label><input id="busqueda" name="busqueda" class="cc-input" value="{{ $busqueda }}" maxlength="150" placeholder="Empresa, placa, marca, motorista o ID"></div>
                <div class="cc-field xl:col-span-3"><label>Empresa</label>
                    @if ($esDieselCop)
                        <div class="cc-filter-multiselect" data-cc-filter-multiselect data-all-text="Todas" data-singular-text="seleccionada" data-plural-text="seleccionadas"><button type="button" class="cc-filter-multiselect-toggle" data-cc-filter-toggle><span data-cc-filter-label>{{ $empresaIds===[]?'Todas':count($empresaIds).' seleccionadas' }}</span><span class="cc-filter-multiselect-arrow">⌄</span></button><div class="cc-filter-multiselect-menu" data-cc-filter-menu><div class="cc-filter-multiselect-list"><label class="cc-filter-multiselect-option cc-filter-multiselect-option-master"><input type="checkbox" data-cc-filter-master data-cc-filter-check-all><span>Seleccionar todo</span></label>@foreach($empresas as $empresa)<label class="cc-filter-multiselect-option" data-cc-filter-option><input type="checkbox" name="empresa_ids[]" value="{{ $empresa->id }}" @checked(in_array((int)$empresa->id,$empresaIds,true)) data-cc-filter-checkbox><span data-cc-filter-option-label>{{ $empresa->nombre_comercial?:$empresa->nombre_legal }}</span></label>@endforeach</div></div></div>
                    @else
                        <select class="cc-input" disabled>@foreach($empresas as $empresa)<option>{{ $empresa->nombre_comercial?:$empresa->nombre_legal }}</option>@endforeach</select>@foreach($empresaIds as $id)<input type="hidden" name="empresa_ids[]" value="{{ $id }}">@endforeach
                    @endif
                </div>
                @foreach ([['Nombre / Placa','unidad_ids',$unidadesSelector,'placa'],['Motorista','motorista_ids',$motoristasSelector,'nombre_completo']] as [$etiqueta,$nombre,$opciones,$atributo])
                    @php($seleccionados=$nombre==='unidad_ids'?$unidadIds:$motoristaIds)
                    <div class="cc-field xl:col-span-3"><label>{{ $etiqueta }}</label><div class="cc-filter-multiselect" data-cc-filter-multiselect data-all-text="Todos" data-singular-text="seleccionado" data-plural-text="seleccionados"><button type="button" class="cc-filter-multiselect-toggle" data-cc-filter-toggle><span data-cc-filter-label>{{ $seleccionados===[]?'Todos':count($seleccionados).' seleccionados' }}</span><span class="cc-filter-multiselect-arrow">⌄</span></button><div class="cc-filter-multiselect-menu" data-cc-filter-menu><div class="cc-filter-multiselect-list"><label class="cc-filter-multiselect-option cc-filter-multiselect-option-master"><input type="checkbox" data-cc-filter-master data-cc-filter-check-all><span>Seleccionar todo</span></label>@foreach($opciones as $opcion)<label class="cc-filter-multiselect-option" data-cc-filter-option><input type="checkbox" name="{{ $nombre }}[]" value="{{ $opcion->id }}" @checked(in_array((int)$opcion->id,$seleccionados,true)) data-cc-filter-checkbox><span data-cc-filter-option-label>{{ $opcion->{$atributo} }}</span></label>@endforeach</div></div></div></div>
                @endforeach
            </div>
            <div class="mt-3 grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2 xl:grid-cols-12">
                <div class="cc-field xl:col-span-3"><label for="fecha_desde">Fecha desde</label><input id="fecha_desde" name="fecha_desde" type="date" class="cc-input" value="{{ $fechaDesde }}"></div>
                <div class="cc-field xl:col-span-3"><label for="fecha_hasta">Fecha hasta</label><input id="fecha_hasta" name="fecha_hasta" type="date" class="cc-input" value="{{ $fechaHasta }}"></div>
                <div class="cc-field xl:col-span-3"><label for="resultado">Resultado</label><select id="resultado" name="resultado" class="cc-input"><option value="">Todos</option><option value="ahorro" @selected($resultado==='ahorro')>Ahorro</option><option value="sobreconsumo" @selected($resultado==='sobreconsumo')>Sobreconsumo</option><option value="en_objetivo" @selected($resultado==='en_objetivo')>En objetivo</option></select></div>
                <div class="cc-standard-filter-actions xl:col-span-3"><button class="cc-btn-primary" type="submit">Consultar</button><a href="{{ $rutaConsulta }}" class="cc-btn-secondary">Limpiar</a></div>
            </div>
        </div>
    </form>

    @if (!$hayConsulta)
        <div class="cc-empty-panel cc-empty-panel-compact"><h5>Sin resultados</h5><p>No hay resultados para mostrar. Utilice los filtros y presione Consultar.</p></div>
    @elseif ($ciclos->isEmpty())
        <div class="cc-empty-panel cc-empty-panel-compact"><h5>Sin resultados</h5><p>No hay ciclos completos que coincidan con los filtros seleccionados.</p></div>
    @else
        <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">Mostrando <strong class="mx-1 text-[var(--cc-text-main)]">{{ $ciclos->firstItem() }}</strong>-<strong class="mx-1 text-[var(--cc-text-main)]">{{ $ciclos->lastItem() }}</strong> de <strong class="ml-1 text-[var(--cc-text-main)]">{{ $ciclos->total() }}</strong></div>
        <div class="cc-table-adaptive-wrapper"><table class="cc-table-adaptive" style="min-width:108rem;"><thead><tr><th>Empresa</th><th>Nombre / Placa</th><th>Motorista</th><th>Periodo</th><th>Km recorridos</th><th>Rendimiento</th><th>Consumo</th><th>Diferencia</th><th>Resultado</th><th>Impacto económico</th><th>Acción</th></tr></thead><tbody>
            @foreach($ciclos as $ciclo)<tr data-report-cycle-row="{{ $ciclo->id }}">
                <td>{{ $ciclo->empresa_nombre_snapshot }}</td><td><strong class="whitespace-nowrap">{{ $ciclo->unidad_placa_snapshot }}</strong></td><td>{{ $ciclo->motorista_nombre_snapshot }}</td>
                <td class="whitespace-nowrap"><span class="font-semibold text-[var(--cc-text-muted)]">Inicio</span> {{ $ciclo->abastecimientoAnterior->fecha_hora_abastecimiento->format('d/m/Y H:i') }}<br><span class="font-semibold text-[var(--cc-text-muted)]">Cierre</span> {{ $ciclo->fecha_hora_abastecimiento->format('d/m/Y H:i') }}</td>
                <td class="text-right">{{ number_format((float)$ciclo->diferencia_kilometraje,2) }} km</td>
                <td class="text-right"><span class="font-semibold text-[var(--cc-text-muted)]">Teórico</span> {{ number_format((float)$ciclo->rendimiento_teorico_km_galon_snapshot,2) }}<br><span class="font-semibold text-[var(--cc-text-muted)]">Real</span> {{ number_format((float)$ciclo->kilometros_por_galon,2) }} km/gal</td>
                <td class="text-right"><span class="font-semibold text-[var(--cc-text-muted)]">Teórico</span> {{ number_format((float)$ciclo->consumo_teorico_ciclo,2) }}<br><span class="font-semibold text-[var(--cc-text-muted)]">Real</span> {{ number_format((float)$ciclo->consumo_real_ciclo,2) }} gal</td>
                <td class="text-right">{{ number_format($ciclo->diferencia_absoluta_reporte,2) }} gal</td><td><span @class(['cc-badge','cc-badge-active'=>$ciclo->resultado_reporte==='Ahorro','cc-badge-danger'=>$ciclo->resultado_reporte==='Sobreconsumo','cc-badge-info'=>$ciclo->resultado_reporte==='En objetivo'])>{{ $ciclo->resultado_reporte }}</span></td>
                <td class="text-right"><span class="font-semibold" @class(['text-[var(--cc-success)]'=>$ciclo->resultado_reporte==='Ahorro','text-[var(--cc-danger)]'=>$ciclo->resultado_reporte==='Sobreconsumo'])>{{ $ciclo->resultado_reporte==='Sobreconsumo'?'-':($ciclo->resultado_reporte==='Ahorro'?'+':'') }}${{ number_format($ciclo->impacto_economico_reporte,2) }}</span></td>
                <td><a href="{{ route('reportes.rendimiento-km-galon.show',array_merge(['ciclo'=>$ciclo->id],request()->query())) }}" class="cc-btn-secondary whitespace-nowrap">Ver detalle</a></td>
            </tr>@endforeach
        </tbody></table></div><div class="mt-6">{{ $ciclos->links() }}</div>
    @endif
</section>
