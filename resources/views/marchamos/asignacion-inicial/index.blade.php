<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                @php
                    $rutaVentanaAsignacion = \Illuminate\Support\Facades\Route::has('marchamos.asignacion-inicial.index.ventana')
                        ? route('marchamos.asignacion-inicial.index.ventana', request()->query())
                        : route('marchamos.asignacion-inicial.index', request()->query());
                @endphp

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Asignación de marchamos
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ $rutaVentanaAsignacion }}"
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

                <form method="GET" action="{{ route('marchamos.asignacion-inicial.index') }}" class="mb-5">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                            <div class="cc-form-section-title">
                                Filtros de asignación
                            </div>
                        </div>

                        <div class="cc-standard-filter-grid cc-unidades-consulta-filter-grid">

                            <div class="cc-field">
                                <label for="busqueda_empresa">
                                    Buscar empresa
                                </label>

                                <input
                                    id="busqueda_empresa"
                                    name="busqueda_empresa"
                                    type="text"
                                    class="cc-input"
                                    value="{{ $busquedaEmpresa ?? '' }}"
                                    maxlength="150"
                                    placeholder="Nombre de empresa"
                                >
                            </div>

                            <div class="cc-field">
                                <label for="empresa_id">
                                    Empresa
                                </label>

                                @if ($esUsuarioDieselCop)
                                    <select id="empresa_id" name="empresa_id" class="cc-input">
                                        <option value="">Todas</option>

                                        @foreach ($empresas as $empresa)
                                            <option value="{{ $empresa->id }}" @selected((string) $empresaId === (string) $empresa->id)>
                                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <select id="empresa_id" class="cc-input" disabled>
                                        @foreach ($empresas as $empresa)
                                            <option value="{{ $empresa->id }}" selected>
                                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <div class="cc-field">
                                <label for="busqueda_placa">
                                    Buscar placa
                                </label>

                                <input
                                    id="busqueda_placa"
                                    name="busqueda_placa"
                                    type="text"
                                    class="cc-input"
                                    value="{{ $busquedaPlaca ?? '' }}"
                                    maxlength="30"
                                    placeholder="Ej. C123ABC"
                                >
                            </div>

                            <div class="cc-field">
                                <label for="unidad_id">
                                    Placa
                                </label>

                                <select id="unidad_id" name="unidad_id" class="cc-input">
                                    <option value="">Todas</option>

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

                            <div class="cc-standard-filter-actions">
                                <button type="submit" class="cc-btn-primary">
                                    Consultar
                                </button>

                                <a href="{{ route('marchamos.asignacion-inicial.index') }}" class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if (! $hayFiltros)
                    <section class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Búsqueda pendiente
                        </h5>

                        <p>
                            Use los filtros para localizar una unidad registrada, todas las unidades de una empresa o todas las unidades elegibles para asignación inicial.
                        </p>
                    </section>
                @elseif ($unidadesDisponibles->isEmpty())
                    <section class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>

                        <p>
                            No hay unidades registradas con licencia activa y puntos de seguridad generados para los filtros seleccionados.
                        </p>
                    </section>
                @else
                    <div class="cc-admin-result-list">
                        @foreach ($unidadesDisponibles as $unidad)
                            @php
                                $totalPuntos = (int) ($unidad->total_puntos ?? 0);
                                $puntosAsignados = (int) ($unidad->puntos_asignados ?? 0);
                                $puntosPendientes = max($totalPuntos - $puntosAsignados, 0);

                                $porcentajeAvance = $totalPuntos > 0
                                    ? round(($puntosAsignados / $totalPuntos) * 100)
                                    : 0;
                            @endphp

                            <article class="cc-admin-result-card">
                                <div class="grid gap-5 xl:grid-cols-12 xl:items-start">

                                    <div class="min-w-0 xl:col-span-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h5 class="cc-admin-result-title">
                                                {{ $unidad->placa }}
                                            </h5>

                                            <span class="cc-badge cc-badge-warning">
                                                Registrada
                                            </span>
                                        </div>

                                        <div class="cc-admin-result-subtitle">
                                            {{ $unidad->marca ?: 'Sin marca registrada' }}
                                        </div>
                                    </div>

                                    <div class="min-w-0 xl:col-span-3">
                                        <div class="cc-admin-result-label">
                                            Empresa
                                        </div>

                                        @if ($unidad->empresa)
                                            <div class="cc-admin-result-value">
                                                {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                            </div>

                                            @if ($unidad->empresa->nit ?? false)
                                                <div class="cc-admin-result-value-muted">
                                                    NIT: {{ $unidad->empresa->nit }}
                                                </div>
                                            @endif
                                        @else
                                            <div class="cc-admin-result-value-muted">
                                                Sin empresa
                                            </div>
                                        @endif
                                    </div>

                                    <div class="min-w-0 sm:grid sm:grid-cols-3 sm:gap-5 xl:col-span-4 xl:grid-cols-3">
                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Licencia
                                            </div>

                                            @if ($unidad->licencia)
                                                <div class="cc-admin-result-value">
                                                    {{ $unidad->licencia->periodo_vigencia_texto }}
                                                </div>

                                                <div class="cc-admin-result-value-muted">
                                                    {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                                </div>
                                            @else
                                                <div class="cc-admin-result-value-muted">
                                                    Sin licencia
                                                </div>
                                            @endif
                                        </div>

                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Puntos
                                            </div>

                                            <div class="cc-admin-result-value">
                                                {{ $puntosAsignados }} / {{ $totalPuntos }}
                                            </div>

                                            <div class="cc-admin-result-value-muted">
                                                {{ $puntosPendientes }} pendientes
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Avance
                                            </div>

                                            <div class="cc-admin-result-value">
                                                {{ $porcentajeAvance }}%
                                            </div>

                                            @if ($puntosPendientes > 0)
                                                <div class="cc-admin-result-value-muted text-[var(--cc-danger)]">
                                                    Pendiente
                                                </div>
                                            @else
                                                <div class="cc-admin-result-value-muted text-[var(--cc-success)]">
                                                    Completa
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-3 xl:col-span-2 xl:justify-end xl:self-center">
                                        <a href="{{ route('marchamos.asignacion-inicial.show', $unidad) }}"
                                           class="cc-btn-primary cc-btn-result w-full sm:w-auto">
                                            Asignación
                                        </a>
                                    </div>

                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $unidadesDisponibles->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>