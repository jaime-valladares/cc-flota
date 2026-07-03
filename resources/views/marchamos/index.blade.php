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
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Consulta pendiente
                        </h5>

                        <p>
                            Los resultados permanecerán vacíos hasta que consulte la cobertura de marchamos por empresa o unidad.
                        </p>
                    </div>
                @elseif ($unidadesConCobertura->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>

                        <p>
                            No hay unidades con puntos de seguridad generados para los filtros aplicados.
                        </p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($unidadesConCobertura as $unidad)
                            @php
                                $totalPuntos = (int) ($unidad->total_puntos ?? 0);
                                $puntosAsignados = (int) ($unidad->puntos_asignados ?? 0);
                                $puntosPendientes = max($totalPuntos - $puntosAsignados, 0);

                                $porcentajeAvance = $totalPuntos > 0
                                    ? round(($puntosAsignados / $totalPuntos) * 100)
                                    : 0;
                            @endphp

                            <article class="cc-result-card cc-result-card-compact">
                                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">

                                    <div class="flex-1 min-w-0">
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-start">

                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h5 class="cc-result-title cc-cell-truncate">
                                                        {{ $unidad->placa }}
                                                    </h5>

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

                                                <div class="cc-result-subtitle cc-cell-truncate">
                                                    {{ $unidad->marca ?: 'Sin marca registrada' }}
                                                </div>
                                            </div>

                                            <div>
                                                <div class="cc-result-label">
                                                    Empresa
                                                </div>

                                                <div class="cc-result-value cc-cell-truncate">
                                                    @if ($unidad->empresa)
                                                        {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                    @else
                                                        Sin empresa
                                                    @endif
                                                </div>
                                            </div>

                                            <div>
                                                <div class="cc-result-label">
                                                    Marchamos
                                                </div>

                                                <div class="cc-result-value">
                                                    {{ $unidad->marchamos_activos }} activos
                                                </div>

                                                <div class="cc-result-value-muted">
                                                    {{ $unidad->marchamos_historicos }} históricos
                                                </div>
                                            </div>

                                            <div>
                                                <div class="cc-result-label">
                                                    Avance
                                                </div>

                                                <div class="cc-result-value">
                                                    {{ $porcentajeAvance }}%
                                                </div>

                                                @if ($puntosPendientes === 0 && $totalPuntos > 0)
                                                    <div class="cc-result-value-muted text-[var(--cc-success)]">
                                                        Completa
                                                    </div>
                                                @elseif ($totalPuntos > 0)
                                                    <div class="cc-result-value-muted text-[var(--cc-danger)]">
                                                        {{ $puntosPendientes }} pendientes
                                                    </div>
                                                @else
                                                    <div class="cc-result-value-muted">
                                                        Sin puntos
                                                    </div>
                                                @endif
                                            </div>

                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 xl:justify-end xl:min-w-[10rem]">
                                        <a href="{{ route('marchamos.detalle-unidad', $unidad) }}"
                                           class="cc-btn-secondary cc-btn-result">
                                            Ver Marchamos
                                        </a>
                                    </div>

                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>