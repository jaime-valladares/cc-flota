<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">

            <div class="cc-card">
                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Consulta gasolineras
                        </h3>

                        <p class="cc-subtitle cc-subtitle-compact">
                            Consulte gasolineras internas, tanques asociados y disponibilidad general de inventario.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras.consulta.ventana', request()->query()) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="cc-alert cc-alert-danger">
                        <ul class="cc-alert-list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @php
                    $resultadosEncontrados = $hayFiltros ? $gasolineras->total() : 0;
                    $resultadosMostrados = $hayFiltros ? $gasolineras->count() : 0;

                    $resultadosActivosPagina = $hayFiltros
                        ? $gasolineras->getCollection()->where('estado', 'activa')->count()
                        : 0;

                    $resultadosInactivosPagina = $hayFiltros
                        ? $gasolineras->getCollection()->where('estado', 'inactiva')->count()
                        : 0;
                @endphp

                <div class="cc-summary-strip">
                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Resultados</span>
                        <span class="cc-summary-strip-value">{{ $resultadosEncontrados }}</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Mostrando</span>
                        <span class="cc-summary-strip-value">{{ $resultadosMostrados }}</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Activas página</span>
                        <span class="cc-summary-strip-value cc-summary-strip-value-success">{{ $resultadosActivosPagina }}</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Inactivas página</span>
                        <span class="cc-summary-strip-value {{ $resultadosInactivosPagina > 0 ? 'cc-summary-strip-value-danger' : '' }}">
                            {{ $resultadosInactivosPagina }}
                        </span>
                    </div>
                </div>

                <form method="GET" action="{{ route('gasolineras.index') }}" class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">
                    <div class="cc-filter-inline-grid">
                        @if ($esUsuarioDieselCop)
                            <div class="cc-field">
                                <label for="empresa_id" class="cc-label">Empresa</label>

                                <select id="empresa_id" name="empresa_id" class="cc-input">
                                    <option value="">Seleccione una empresa</option>

                                    @foreach ($empresasSelector as $empresa)
                                        <option value="{{ $empresa->id }}" @selected((string) $empresaId === (string) $empresa->id)>
                                            {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="cc-field">
                            <label for="nombre" class="cc-label">Nombre</label>

                            <input
                                id="nombre"
                                type="text"
                                name="nombre"
                                value="{{ $nombre }}"
                                class="cc-input"
                                placeholder="Ej. Gasolinera central"
                            >
                        </div>

                        <div class="cc-field">
                            <label for="estado" class="cc-label">Estado</label>

                            <select id="estado" name="estado" class="cc-input">
                                <option value="">Todos</option>
                                <option value="activa" @selected($estado === 'activa')>Activa</option>
                                <option value="inactiva" @selected($estado === 'inactiva')>Inactiva</option>
                            </select>
                        </div>

                        <div class="cc-filter-actions">
                            <button type="submit" class="cc-btn-primary">
                                Buscar
                            </button>

                            <a href="{{ route('gasolineras.index') }}" class="cc-btn-secondary">
                                Limpiar
                            </a>
                        </div>
                    </div>
                </form>

                @if (! $hayFiltros)
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <div class="cc-empty-title">
                            Seleccione un criterio de búsqueda
                        </div>

                        <p class="cc-empty-text">
                            Utilice los filtros para consultar gasolineras internas registradas en el sistema.
                        </p>
                    </div>
                @elseif ($gasolineras->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <div class="cc-empty-title">
                            Sin resultados
                        </div>

                        <p class="cc-empty-text">
                            No se encontraron gasolineras con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-results-list">
                        @foreach ($gasolineras as $gasolinera)
                            @php
                                $tanques = $gasolinera->tanques ?? collect();

                                $capacidadTotal = $tanques->sum(fn ($tanque) => (float) $tanque->capacidad_total);
                                $volumenActual = $tanques->sum(fn ($tanque) => (float) $tanque->volumen_actual);

                                $porcentajeDisponible = $capacidadTotal > 0
                                    ? round(($volumenActual / $capacidadTotal) * 100, 2)
                                    : 0;

                                $tanquesBajoAlerta = $tanques
                                    ->filter(fn ($tanque) => (float) $tanque->volumen_actual <= (float) $tanque->volumen_minimo_alerta)
                                    ->count();

                                $estadoClase = $gasolinera->estado === 'activa'
                                    ? 'cc-badge-active'
                                    : 'cc-badge-inactive';
                            @endphp

                            <article class="cc-result-card cc-result-card-compact" style="padding: 1.15rem 1.25rem;">
                                <div style="display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr) minmax(0, 1fr); gap: 1.35rem; align-items: start;">

                                    <div style="grid-column: 1 / 3;">
                                        <div class="cc-result-title">
                                            {{ $gasolinera->nombre }}
                                        </div>

                                        <div class="cc-result-subtitle">
                                            Resumen operativo de ubicación, tanques e inventario disponible.
                                        </div>
                                    </div>

                                    <div style="display: flex; justify-content: flex-end; align-items: flex-start;">
                                        <span class="cc-badge {{ $estadoClase }}">
                                            {{ ucfirst($gasolinera->estado) }}
                                        </span>
                                    </div>

                                    <div>
                                        <div class="cc-result-label">Empresa</div>
                                        <div class="cc-result-value">
                                            {{ $gasolinera->empresa?->nombre_comercial ?: $gasolinera->empresa?->nombre_legal }}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="cc-result-label">Ubicación</div>
                                        <div class="cc-result-value">
                                            {{ $gasolinera->direccion }}
                                        </div>
                                    </div>

                                    <div>
                                        <div class="cc-result-label">Tanques</div>
                                        <div class="cc-result-value">
                                            {{ $gasolinera->tanques_activos_count ?? 0 }} activos / {{ $gasolinera->tanques_count ?? $tanques->count() }} total
                                        </div>
                                    </div>

                                    <div style="grid-column: 1 / 4; border-top: 1px solid var(--cc-card-border); margin-top: 0.1rem;"></div>

                                    <div>
                                        <div class="cc-result-label">Inventario disponible</div>
                                        <div class="cc-result-value">
                                            {{ number_format($volumenActual, 2) }} gal
                                        </div>
                                    </div>

                                    <div>
                                        <div class="cc-result-label">Capacidad total</div>
                                        <div class="cc-result-value">
                                            {{ number_format($capacidadTotal, 2) }} gal
                                        </div>
                                    </div>

                                    <div>
                                        <div class="cc-result-label">Disponibilidad</div>
                                        <div class="cc-result-value">
                                            {{ number_format($porcentajeDisponible, 2) }}%
                                        </div>
                                    </div>

                                    @if ($tanquesBajoAlerta > 0)
                                        <div class="cc-alert cc-alert-warning" style="grid-column: 1 / 4; margin-top: 0.2rem;">
                                            {{ $tanquesBajoAlerta }} tanque(s) se encuentran en nivel mínimo o por debajo del mínimo definido.
                                        </div>
                                    @endif

                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="cc-pagination">
                        {{ $gasolineras->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>