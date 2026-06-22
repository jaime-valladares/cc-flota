<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Consulta de marchamos
                        </h3>
                        <p class="cc-subtitle">
                            Consulte la cobertura de marchamos por unidad y revise el detalle histórico de cada marchamo registrado.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('marchamos.asignacion-inicial.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Asignación inicial
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="cc-metric-grid">
                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Unidades protegidas
                        </div>
                        <div class="cc-metric-value cc-metric-value-success">
                            {{ $unidadesProtegidas }}
                        </div>
                    </div>

                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Unidades con pendientes
                        </div>
                        <div class="cc-metric-value cc-metric-value-danger">
                            {{ $unidadesConPendientes }}
                        </div>
                    </div>

                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Marchamos activos
                        </div>
                        <div class="cc-metric-value cc-metric-value-success">
                            {{ $totalActivos }}
                        </div>
                    </div>

                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Históricos
                        </div>
                        <div class="cc-metric-value">
                            {{ $totalReemplazados + $totalAnulados }}
                        </div>
                    </div>
                </div>

                <section class="cc-form-section mt-6">
                    <div class="cc-section-heading">
                        <h4>Filtros de consulta</h4>
                        <p>
                            Seleccione empresa, unidad o condiciones específicas para consultar cobertura y marchamos registrados.
                        </p>
                    </div>

                    <form method="GET" action="{{ route('marchamos.index') }}">
                        <input type="hidden" name="consultar" value="1">

                        <div class="cc-grid cc-grid-3">
                            <div class="cc-field">
                                <label for="empresa_id">Empresa</label>
                                <select id="empresa_id" name="empresa_id" class="cc-input">
                                    <option value="">Todas las empresas</option>

                                    @foreach ($empresas as $empresa)
                                        <option value="{{ $empresa->id }}" @selected((string) $empresaId === (string) $empresa->id)>
                                            {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="cc-field">
                                <label for="unidad_id">Unidad</label>
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

                            <div class="cc-field">
                                <label for="codigo_marchamo">Código de marchamo</label>
                                <input
                                    id="codigo_marchamo"
                                    type="text"
                                    name="codigo_marchamo"
                                    value="{{ $codigo }}"
                                    class="cc-input"
                                    placeholder="Ej. 0006387"
                                    maxlength="7"
                                    inputmode="numeric">
                            </div>

                            <div class="cc-field">
                                <label for="estado">Estado</label>
                                <select id="estado" name="estado" class="cc-input">
                                    <option value="">Todos los estados</option>
                                    <option value="activo" @selected($estado === 'activo')>Activo</option>
                                    <option value="reemplazado" @selected($estado === 'reemplazado')>Reemplazado</option>
                                    <option value="anulado" @selected($estado === 'anulado')>Anulado</option>
                                </select>
                            </div>

                            <div class="cc-field">
                                <label for="origen_creacion">Origen</label>
                                <select id="origen_creacion" name="origen_creacion" class="cc-input">
                                    <option value="">Todos los orígenes</option>
                                    <option value="asignacion_inicial" @selected($origen === 'asignacion_inicial')>Asignación inicial</option>
                                    <option value="abastecimiento" @selected($origen === 'abastecimiento')>Abastecimiento</option>
                                    <option value="reemplazo_dano_desgaste" @selected($origen === 'reemplazo_dano_desgaste')>Reemplazo por daño/desgaste</option>
                                    <option value="correccion" @selected($origen === 'correccion')>Corrección</option>
                                </select>
                            </div>
                        </div>

                        <div class="cc-actions mt-6">
                            <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                Consultar
                            </button>

                            <a href="{{ route('marchamos.index') }}" class="cc-btn-secondary cc-btn-form-action">
                                Limpiar filtros
                            </a>
                        </div>
                    </form>
                </section>

                @if (! $hayFiltros)
                    <section class="cc-info-panel mt-6">
                        <div>
                            <h4>
                                Inicie una consulta
                            </h4>

                            <p>
                                Use los filtros para consultar la cobertura de marchamos por empresa o unidad. También puede presionar Consultar sin filtros para ver todas las unidades con cobertura registrada.
                            </p>
                        </div>
                    </section>
                @endif

                @if ($hayFiltros)
                    <section class="cc-detail-section mt-6">
                        <div class="cc-detail-section-header">
                            <h5>Cobertura por unidad</h5>
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
                                                <a href="{{ route('unidades.show', $unidad) }}"
                                                   class="font-bold text-[var(--cc-primary)] hover:underline">
                                                    {{ $unidad->placa }}
                                                </a>

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
                                                    <span class="cc-badge cc-badge-pending">
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
                                                    <a href="{{ route('marchamos.index', [
                                                            'empresa_id' => $unidad->empresa_id,
                                                            'unidad_id' => $unidad->id,
                                                            'consultar' => 1,
                                                            'ver_detalle' => 1,
                                                        ]) }}"
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

                @if ($mostrarDetalleMarchamos)
                    <section class="cc-detail-section mt-6">
                        <div class="cc-detail-section-header">
                            <h5>Marchamos registrados</h5>
                            <p>
                                {{ $totalMarchamosConsulta }} registros encontrados para la unidad seleccionada.
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
                                                    <span class="cc-badge cc-badge-pending">
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
                @endif

            </div>
        </div>
    </div>
</x-app-layout>