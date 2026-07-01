<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Consulta de marchamos
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Consulte la cobertura física de marchamos por empresa y unidad.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('marchamos.consulta.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="GET" action="{{ route('marchamos.index') }}" class="mb-5">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                            <div class="cc-form-section-title">
                                Filtros de cobertura
                            </div>
                        </div>

                        <div class="cc-filter-inline-grid">

                            <div class="cc-field">
                                <label for="empresa_id">
                                    Empresa
                                </label>

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
                                <label for="unidad_id">
                                    Unidad
                                </label>

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

                            <div class="cc-filter-inline-actions">
                                <button type="submit" class="cc-btn-primary">
                                    Consultar
                                </button>

                                <a href="{{ route('marchamos.index') }}" class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if (! $hayFiltros)
                    <section class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Inicie una consulta
                        </h5>

                        <p>
                            Use los filtros para consultar la cobertura de marchamos por empresa o unidad. También puede consultar sin filtros para ver todas las unidades con cobertura registrada.
                        </p>
                    </section>
                @endif

                @if ($hayFiltros)
                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Cobertura por unidad
                            </h5>
                            <p>
                                Unidades con licencia, puntos de seguridad y avance de asignación de marchamos.
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="cc-table">
                                <thead>
                                    <tr>
                                        <th>Unidad</th>
                                        <th>Empresa</th>
                                        <th>Estado</th>
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
                                                    <div class="font-bold text-[var(--cc-text-main)] cc-cell-truncate">
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
                                            </td>

                                            <td>
                                                @if ($unidad->licencia)
                                                    <div class="font-bold text-[var(--cc-text-main)]">
                                                        {{ $unidad->licencia->periodo_vigencia_texto }}
                                                    </div>

                                                    <div class="text-sm text-[var(--cc-text-muted)] cc-cell-truncate">
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
                                                        Completa
                                                    </div>
                                                @elseif ($totalPuntos > 0)
                                                    <div class="text-sm text-[var(--cc-danger)]">
                                                        Pendiente
                                                    </div>
                                                @else
                                                    <div class="text-sm text-[var(--cc-text-muted)]">
                                                        Sin puntos
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="text-right">
                                                <div class="flex justify-end gap-2">
                                                    <a href="{{ route('marchamos.detalle-unidad', $unidad) }}"
                                                       class="cc-btn-secondary cc-btn-table">
                                                        Marchamos
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

            </div>
        </div>
    </div>
</x-app-layout>