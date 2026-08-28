@php
    $esVentana = $esVentana ?? false;
    $rutaConsulta = $esVentana
        ? route('reportes.unidades.ventana')
        : route('reportes.unidades.index');
    $rutaLimpiar = $esVentana
        ? route('reportes.unidades.ventana')
        : route('reportes.unidades.index');
    $rutaAlterna = $esVentana
        ? route('reportes.unidades.index', request()->query())
        : route('reportes.unidades.ventana', request()->query());
    $rutaPdf = route('reportes.unidades.pdf', request()->except('page'));
@endphp

<section class="cc-card cc-analytics-root-card">
    <header class="cc-card-header cc-card-header-compact">
        <div>
            <h1 class="cc-title cc-title-compact">Reporte de unidades</h1>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            @if ($hayConsulta)
                <a href="{{ $rutaPdf }}" class="cc-btn-secondary cc-btn-wide">Descargar PDF</a>
            @endif
            <a href="{{ $rutaAlterna }}" @unless ($esVentana) target="_blank" rel="noopener noreferrer" @endunless class="cc-btn-secondary cc-btn-wide">
                {{ $esVentana ? 'Volver al sistema' : 'Abrir en nueva pestaña' }}
            </a>
        </div>
    </header>

    <form method="GET" action="{{ $rutaConsulta }}" class="mb-5">
        <input type="hidden" name="consultar" value="1">
        <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">
            <div class="cc-form-section cc-form-section-compact cc-analytics-filter-heading">
                <div class="cc-form-section-title">Filtros de consulta</div>
            </div>

            <div class="cc-standard-filter-grid cc-analytics-filter-grid">
                <div class="cc-field">
                    <label for="busqueda">Buscar empresa o Nombre / Placa</label>
                    <input id="busqueda" name="busqueda" type="text" class="cc-input" value="{{ $busqueda }}" maxlength="150" placeholder="Empresa o Nombre / Placa">
                </div>

                <div class="cc-field">
                    <label>Empresa</label>
                    @if ($esDieselCop)
                        <div class="cc-filter-multiselect" data-cc-filter-multiselect data-all-text="Todas" data-singular-text="seleccionada" data-plural-text="seleccionadas">
                            <button type="button" class="cc-filter-multiselect-toggle" data-cc-filter-toggle>
                                <span data-cc-filter-label>{{ $empresaIds === [] ? 'Todas' : count($empresaIds).' seleccionadas' }}</span>
                                <span class="cc-filter-multiselect-arrow">⌄</span>
                            </button>
                            <div class="cc-filter-multiselect-menu" data-cc-filter-menu>
                                <div class="cc-filter-multiselect-list">
                                    <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                        <input type="checkbox" data-cc-filter-master data-cc-filter-check-all>
                                        <span>Seleccionar todo</span>
                                    </label>
                                    @foreach ($empresas as $empresa)
                                        <label class="cc-filter-multiselect-option" data-cc-filter-option>
                                            <input type="checkbox" name="empresa_ids[]" value="{{ $empresa->id }}" @checked(in_array((int) $empresa->id, $empresaIds, true)) data-cc-filter-checkbox>
                                            <span data-cc-filter-option-label>{{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @else
                        <select class="cc-input" disabled>
                            @foreach ($empresas as $empresa)
                                <option selected>{{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}</option>
                            @endforeach
                        </select>
                        @foreach ($empresaIds as $empresaId)
                            <input type="hidden" name="empresa_ids[]" value="{{ $empresaId }}">
                        @endforeach
                    @endif
                </div>

                <div class="cc-field">
                    <label>Nombre / Placa</label>
                    <div class="cc-filter-multiselect" data-cc-filter-multiselect data-all-text="Todas" data-singular-text="seleccionada" data-plural-text="seleccionadas">
                        <button type="button" class="cc-filter-multiselect-toggle" data-cc-filter-toggle>
                            <span data-cc-filter-label>{{ $unidadIds === [] ? 'Todas' : count($unidadIds).' seleccionadas' }}</span>
                            <span class="cc-filter-multiselect-arrow">⌄</span>
                        </button>
                        <div class="cc-filter-multiselect-menu" data-cc-filter-menu>
                            <div class="cc-filter-multiselect-list">
                                <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                    <input type="checkbox" data-cc-filter-master data-cc-filter-check-all>
                                    <span>Seleccionar todo</span>
                                </label>
                                @foreach ($unidadesSelector as $unidadOpcion)
                                    <label class="cc-filter-multiselect-option" data-cc-filter-option>
                                        <input type="checkbox" name="unidad_ids[]" value="{{ $unidadOpcion->id }}" @checked(in_array((int) $unidadOpcion->id, $unidadIds, true)) data-cc-filter-checkbox>
                                        <span data-cc-filter-option-label>{{ $unidadOpcion->empresa?->nombre_comercial ?: $unidadOpcion->empresa?->nombre_legal }} · {{ $unidadOpcion->placa }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="cc-field">
                    <label for="estado">Estado administrativo</label>
                    <select id="estado" name="estado" class="cc-input">
                        <option value="">Todos</option>
                        <option value="registrada" @selected($estado === 'registrada')>Registrada</option>
                        <option value="activa" @selected($estado === 'activa')>Activa</option>
                        <option value="inactiva" @selected($estado === 'inactiva')>Inactiva</option>
                    </select>
                </div>

                <div class="cc-field">
                    <label for="disponibilidad">Disponibilidad operativa</label>
                    <select id="disponibilidad" name="disponibilidad" class="cc-input">
                        <option value="">Todas</option>
                        <option value="operable" @selected($disponibilidad === 'operable')>Operable</option>
                        <option value="no_operable" @selected($disponibilidad === 'no_operable')>No operable</option>
                    </select>
                </div>

                <div class="cc-field">
                    <label for="modelo_medicion">Modelo de medición</label>
                    <select id="modelo_medicion" name="modelo_medicion" class="cc-input">
                        <option value="">Todos</option>
                        @foreach ($modelosMedicion as $codigo => $texto)
                            <option value="{{ $codigo }}" @selected($modeloMedicion === $codigo)>{{ $texto }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-5 flex w-full flex-wrap justify-end gap-3">
                <button type="submit" class="cc-btn-primary">Consultar</button>
                <a href="{{ $rutaLimpiar }}" class="cc-btn-secondary">Limpiar</a>
            </div>
        </div>
    </form>

    @if ($hayConsulta && $unidades->isNotEmpty())
        <div class="cc-summary-strip" data-report-summary>
            @foreach ([
                [$hayFiltros ? 'Resultados' : 'Total unidades', $resumen['total'], ''],
                ['Registradas', $resumen['registradas'], ''],
                ['Activas', $resumen['activas'], 'cc-summary-strip-value-success'],
                ['Inactivas', $resumen['inactivas'], 'cc-summary-strip-value-danger'],
                ['Operables', $resumen['operables'], 'cc-summary-strip-value-success'],
            ] as [$etiqueta, $valor, $clase])
                <div class="cc-summary-strip-item" data-summary-label="{{ $etiqueta }}" data-summary-value="{{ $valor }}">
                    <span class="cc-summary-strip-label">{{ $etiqueta }}</span>
                    <span class="cc-summary-strip-value {{ $clase }}">{{ $valor }}</span>
                </div>
            @endforeach
        </div>
    @endif

    @if (! $hayConsulta)
        <div class="cc-empty-panel cc-empty-panel-compact">
            <h5>Sin resultados</h5>
            <p>No hay resultados para mostrar. Utilice los filtros y presione Consultar.</p>
        </div>
    @elseif ($unidades->isEmpty())
        <div class="cc-empty-panel cc-empty-panel-compact">
            <h5>Sin resultados</h5>
            <p>No hay unidades que coincidan con los filtros seleccionados.</p>
        </div>
    @else
        <div class="cc-result-count">
            Mostrando <strong class="cc-result-count-value">{{ $unidades->firstItem() }}</strong>-<strong class="cc-result-count-value">{{ $unidades->lastItem() }}</strong> de <strong class="cc-result-count-value">{{ $unidades->total() }}</strong>
        </div>

        <div class="cc-table-adaptive-wrapper">
            <table class="cc-table-adaptive cc-analytics-table-units">
                <thead>
                    <tr>
                        <th>Empresa</th><th>Nombre / Placa</th><th>Marca</th><th>Estado</th>
                        <th class="cc-table-column-long">Disponibilidad operativa</th>
                        <th class="cc-table-column-medium">Modelo de medición</th>
                        <th class="cc-table-column-medium">Rendimiento Teórico</th>
                        <th>Tanques</th><th>Capacidad total</th>
                        <th>Licencia</th><th>Marchamos</th><th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($unidades as $unidad)
                        @php

                            $disponibilidadPendiente = in_array(
                                $unidad->disponibilidad_operativa,
                                [
                                    'asignacion_inicial_pendiente',
                                    'pendiente_activacion_operativa',
                                    'licencia_pendiente_activacion',
                                ],
                                true
                            );

                            $condicionLicencia = $unidad->licencia?->condicion_vigencia;
                        @endphp
                        <tr
                            data-report-unit-row="{{ $unidad->id }}"
                            @style(['background-color: var(--cc-bg-soft);' => $loop->even])
                        >
                            <td>{{ $unidad->empresa?->nombre_comercial ?: ($unidad->empresa?->nombre_legal ?: '—') }}</td>
                            <td><strong class="cc-table-adaptive-strong whitespace-nowrap">{{ $unidad->placa }}</strong></td>
                            <td>{{ $unidad->marca ?: '—' }}</td>
                            <td>
                                <span @class([
                                    'cc-badge',
                                    'cc-badge-pending' => $unidad->estado === 'registrada',
                                    'cc-badge-active' => $unidad->estado === 'activa',
                                    'cc-badge-inactive' => $unidad->estado === 'inactiva',
                                ])>{{ $unidad->estado_texto }}</span>
                            </td>
                            <td>
                                <span @class([
                                    'cc-badge',
                                    'cc-badge-active' => $unidad->disponibilidad_operativa === 'operable',
                                    'cc-badge-pending' => $disponibilidadPendiente,
                                    'cc-badge-inactive' => $unidad->disponibilidad_operativa !== 'operable' && ! $disponibilidadPendiente,
                                ])>{{ $unidad->disponibilidad_operativa_texto }}</span>
                            </td>
                            <td>{{ $unidad->modelo_medicion_texto }}</td>
                            <td class="whitespace-nowrap text-right">{{ $unidad->modelo_medicion === 'galones_viaje' ? 'Según ruta (gal/viaje)' : $unidad->rendimiento_teorico_reporte }}</td>
                            <td class="text-right">{{ $unidad->total_tanques }}</td>
                            <td class="whitespace-nowrap text-right">{{ number_format((float) $unidad->capacidad_total, 2) }} gal</td>
                            <td>
                                <span @class([
                                    'cc-badge',
                                    'cc-badge-active' => in_array($condicionLicencia, ['vigente', 'proxima_vencer'], true),
                                    'cc-badge-pending' => $condicionLicencia === 'pendiente_activacion',
                                    'cc-badge-inactive' => ! in_array($condicionLicencia, ['vigente', 'proxima_vencer', 'pendiente_activacion'], true),
                                ])>{{ $unidad->licencia?->condicion_vigencia_texto ?? 'Sin licencia' }}</span>
                            </td>
                            <td class="whitespace-nowrap">
                                <span @class([
                                    'cc-badge',
                                    'cc-badge-active' => $unidad->asignacion_inicial_marchamos_completa,
                                    'cc-badge-pending' => ! $unidad->asignacion_inicial_marchamos_completa,
                                ])>{{ $unidad->total_puntos_con_marchamo_asignado }} / {{ $unidad->total_puntos_que_requieren_marchamo }}</span>
                            </td>
                            <td>
                                <a
                                    href="{{ route('reportes.unidades.show', array_merge(['unidad' => $unidad->id], request()->query())) }}"
                                    class="cc-btn-secondary whitespace-nowrap"
                                >Ver ficha</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">{{ $unidades->links() }}</div>
    @endif
</section>
