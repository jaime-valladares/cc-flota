@php
    $queryParams = request()->query();
    $modelosTexto = [
        'kilometros_galon' => 'Kilómetros por galón',
        'galones_hora' => 'Galones por hora',
        'galones_viaje' => 'Galones por viaje',
    ];
@endphp

<div class="cc-card-header cc-card-header-compact">
    <h3 class="cc-title cc-title-compact">Consultar ciclos</h3>
    <a
        href="{{ $modoVentana ? route('abastecimientos.administrar', $queryParams) : route('abastecimientos.administrar.ventana', $queryParams) }}"
        @if (! $modoVentana) target="_blank" rel="noopener noreferrer" @endif
        class="cc-btn-secondary cc-btn-wide"
    >
        {{ $modoVentana ? 'Volver al Sistema' : 'Abrir en nueva pestaña' }}
    </a>
</div>

<div class="cc-summary-strip">
    @foreach ([
        'Resultados' => $totalCiclos,
        'En proceso' => $totalEnProceso,
        'Completos' => $totalCompletos,
        'Unidades' => $totalUnidades,
    ] as $etiqueta => $total)
        <div class="cc-summary-strip-item">
            <span class="cc-summary-strip-label">{{ $etiqueta }}</span>
            <span class="cc-summary-strip-value">{{ $total }}</span>
        </div>
    @endforeach
</div>

<form method="GET" action="{{ $modoVentana ? route('abastecimientos.administrar.ventana') : route('abastecimientos.administrar') }}" class="mb-5">
    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">
        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
            <div class="cc-form-section-title">Filtros de ciclos</div>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div class="cc-field">
                <label for="empresa_id">Empresa</label>
                @if ($esUsuarioDieselCop)
                    <select id="empresa_id" name="empresa_id" class="cc-input">
                        <option value="">Todas</option>
                        @foreach ($empresasSelector as $empresa)
                            <option value="{{ $empresa->id }}" @selected(in_array((int) $empresa->id, $empresaIds, true))>
                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                            </option>
                        @endforeach
                    </select>
                @else
                    <input class="cc-input" value="{{ $empresaUsuario?->nombre_comercial ?: $empresaUsuario?->nombre_legal }}" disabled>
                @endif
            </div>

            <div class="cc-field">
                <label for="unidad_id">Unidad</label>
                <select id="unidad_id" name="unidad_id" class="cc-input">
                    <option value="">Todas</option>
                    @foreach ($unidadesSelector as $unidad)
                        <option value="{{ $unidad->id }}" @selected(in_array((int) $unidad->id, $unidadIds, true))>{{ $unidad->placa }}</option>
                    @endforeach
                </select>
            </div>

            <div class="cc-field">
                <label for="modelo_medicion">Modelo de medición</label>
                <select id="modelo_medicion" name="modelo_medicion" class="cc-input">
                    <option value="">Todos</option>
                    @foreach ($modelosTexto as $valor => $texto)
                        <option value="{{ $valor }}" @selected($modeloMedicion === $valor)>{{ $texto }}</option>
                    @endforeach
                </select>
            </div>

            <div class="cc-field">
                <label for="estado_ciclo">Estado del ciclo</label>
                <select id="estado_ciclo" name="estado_ciclo" class="cc-input">
                    <option value="">Todos</option>
                    <option value="en_proceso" @selected($estadoCiclo === 'en_proceso')>En proceso</option>
                    <option value="completo" @selected($estadoCiclo === 'completo')>Completo</option>
                </select>
            </div>

            <div class="cc-field">
                <label for="fecha_desde">Fecha desde</label>
                <input id="fecha_desde" name="fecha_desde" type="date" class="cc-input" value="{{ $fechaDesde }}">
            </div>

            <div class="cc-field">
                <label for="fecha_hasta">Fecha hasta</label>
                <input id="fecha_hasta" name="fecha_hasta" type="date" class="cc-input" value="{{ $fechaHasta }}">
            </div>
        </div>

        <div class="cc-actions cc-actions-compact">
            <button class="cc-btn-primary cc-btn-form-action" type="submit">Consultar</button>
            <a class="cc-btn-secondary cc-btn-form-action" href="{{ $modoVentana ? route('abastecimientos.administrar.ventana') : route('abastecimientos.administrar') }}">Limpiar</a>
        </div>
    </div>
</form>

<div class="cc-table-adaptive-wrapper">
    <table class="cc-table-adaptive" style="min-width: 92rem;">
        <thead>
            <tr>
                <th>Empresa / Unidad</th>
                <th>Estado</th>
                <th>Inicio / Cierre</th>
                <th>Medición inicial / final</th>
                <th>Recorrido</th>
                <th>Combustible</th>
                <th>Consumo</th>
                <th>Rendimiento real</th>
                <th>Acción</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($ciclos as $apertura)
                @php
                    $cierre = $apertura->cierreCiclo;
                    $esHoras = $apertura->modelo_medicion === 'galones_hora';
                    $lecturaInicial = $esHoras ? $apertura->horometro_actual : $apertura->kilometraje_actual;
                    $lecturaFinal = $cierre ? ($esHoras ? $cierre->horometro_actual : $cierre->kilometraje_actual) : null;
                    $recorrido = $cierre ? ($esHoras ? $cierre->diferencia_horometro : $cierre->diferencia_kilometraje) : null;
                    $rendimientoReal = $cierre ? match ($apertura->modelo_medicion) {
                        'kilometros_galon' => $cierre->kilometros_por_galon,
                        'galones_hora' => $cierre->galones_por_hora,
                        default => null,
                    } : null;
                    $unidadRendimiento = $apertura->modelo_medicion === 'kilometros_galon' ? 'km/gal' : 'gal/h';
                @endphp
                <tr>
                    <td>
                        <div class="cc-table-adaptive-strong">{{ $apertura->empresa_nombre_snapshot ?: $apertura->empresa?->nombre_comercial ?: $apertura->empresa?->nombre_legal }}</div>
                        <div class="cc-table-adaptive-muted">{{ $apertura->unidad_placa_snapshot ?: $apertura->unidad?->placa }}</div>
                    </td>
                    <td><span class="cc-badge {{ $cierre ? 'cc-badge-active' : 'cc-badge-warning' }}">{{ $cierre ? 'Completo' : 'En proceso' }}</span></td>
                    <td class="cc-table-adaptive-nowrap">
                        <div class="cc-table-adaptive-strong">{{ $apertura->fecha_hora_abastecimiento?->format('d/m/Y H:i') }}</div>
                        <div class="cc-table-adaptive-muted">{{ $cierre?->fecha_hora_abastecimiento?->format('d/m/Y H:i') ?: 'Pendiente' }}</div>
                    </td>
                    <td>
                        <div>{{ number_format((float) $lecturaInicial, 2) }}</div>
                        <div class="cc-table-adaptive-muted">{{ is_null($lecturaFinal) ? 'Pendiente' : number_format((float) $lecturaFinal, 2) }}</div>
                    </td>
                    <td>{{ is_null($recorrido) ? 'Pendiente' : number_format((float) $recorrido, 2).' '.($esHoras ? 'h' : 'km') }}</td>
                    <td>
                        <div>{{ number_format((float) $apertura->volumen_final, 2) }} gal iniciales</div>
                        <div class="cc-table-adaptive-muted">{{ $cierre ? number_format((float) $cierre->volumen_cargado, 2).' gal al cierre' : 'Carga de cierre pendiente' }}</div>
                    </td>
                    <td>{{ $cierre && ! is_null($cierre->consumo_real_ciclo) ? number_format((float) $cierre->consumo_real_ciclo, 2).' gal' : 'Pendiente' }}</td>
                    <td>{{ is_null($rendimientoReal) ? 'Pendiente' : number_format((float) $rendimientoReal, 2).' '.$unidadRendimiento }}</td>
                    <td>
                        <a href="{{ $modoVentana ? route('abastecimientos.ciclos.show.ventana', $apertura) : route('abastecimientos.ciclos.show', $apertura) }}" class="cc-btn-secondary cc-btn-form-action">Ver ficha</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="py-8 text-center text-[var(--cc-text-muted)]">No se encontraron ciclos con los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-5">{{ $ciclos->links() }}</div>
