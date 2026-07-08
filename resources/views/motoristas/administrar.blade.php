<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Administrar motoristas
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Gestione los motoristas disponibles para solicitudes de abastecimiento.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('motoristas.administrar.ventana') }}"
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

                <div class="cc-summary-strip">
                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Total motoristas
                        </span>
                        <span class="cc-summary-strip-value">
                            {{ $totalMotoristas }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Activos
                        </span>
                        <span class="cc-summary-strip-value cc-summary-strip-value-success">
                            {{ $motoristasActivos }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Inactivos
                        </span>
                        <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                            {{ $motoristasInactivos }}
                        </span>
                    </div>
                </div>

                <form method="GET" action="{{ route('motoristas.administrar') }}" class="mb-5">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                            <div class="cc-form-section-title">
                                Filtros de administración
                            </div>
                        </div>

                        <div class="cc-filter-inline-grid">

                            <div class="cc-field">
                                <label for="empresa_id">
                                    Empresa
                                </label>

                                @if ($esUsuarioDieselCop)
                                    <select id="empresa_id" name="empresa_id" class="cc-input">
                                        <option value="">Todas</option>

                                        @foreach ($empresasSelector as $empresaOpcion)
                                            <option value="{{ $empresaOpcion->id }}" @selected((string) $empresaId === (string) $empresaOpcion->id)>
                                                {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <select id="empresa_id" name="empresa_id" class="cc-input" disabled>
                                        @foreach ($empresasSelector as $empresaOpcion)
                                            <option value="{{ $empresaOpcion->id }}" selected>
                                                {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif

                                @error('empresa_id')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-field">
                                <label for="buscar">
                                    Buscar
                                </label>
                                <input
                                    id="buscar"
                                    name="buscar"
                                    type="text"
                                    class="cc-input"
                                    value="{{ $buscar }}"
                                    maxlength="150"
                                    placeholder="Nombre, licencia o teléfono"
                                >

                                @error('buscar')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-field">
                                <label for="estado">
                                    Estado
                                </label>
                                <select id="estado" name="estado" class="cc-input">
                                    <option value="">Seleccione</option>
                                    <option value="activo" @selected($estado === 'activo')>
                                        Activos
                                    </option>
                                    <option value="inactivo" @selected($estado === 'inactivo')>
                                        Inactivos
                                    </option>
                                </select>

                                @error('estado')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-filter-inline-actions">
                                <button type="submit" class="cc-btn-primary">
                                    Consultar
                                </button>

                                <a href="{{ route('motoristas.administrar') }}" class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $motoristas->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $motoristas->firstItem() }}</span>
                        -
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $motoristas->lastItem() }}</span>
                        de
                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $motoristas->total() }}</span>
                    </div>
                @endif

                @if (! $hayFiltros)
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Administración pendiente
                        </h5>
                        <p>
                            Use los filtros para cargar los motoristas disponibles.
                        </p>
                    </div>
                @elseif ($motoristas->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>
                        <p>
                            No hay motoristas que coincidan con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-result-list">
                        @foreach ($motoristas as $motorista)
                            <div class="cc-result-card cc-result-card-compact">
                                <div class="cc-result-main">
                                    <div class="cc-result-eyebrow">
                                        Motorista
                                    </div>

                                    <h4 class="cc-result-title">
                                        {{ $motorista->nombre_completo }}
                                    </h4>

                                    <div class="cc-result-meta">
                                        <span>
                                            Empresa: {{ $motorista->empresa->nombre_comercial ?: $motorista->empresa->nombre_legal }}
                                        </span>

                                        <span>
                                            Licencia: {{ $motorista->licencia }}
                                        </span>

                                        <span>
                                            Teléfono: {{ $motorista->telefono }}
                                        </span>
                                    </div>
                                </div>

                                <div class="cc-result-side">
                                    @if ($motorista->estado === 'activo')
                                        <span class="cc-badge cc-badge-active">
                                            Activo
                                        </span>
                                    @else
                                        <span class="cc-badge cc-badge-inactive">
                                            Inactivo
                                        </span>
                                    @endif

                                    <div class="cc-result-actions">
                                        <a href="{{ route('motoristas.show', $motorista) }}" class="cc-btn-secondary cc-btn-result">
                                            Ver ficha
                                        </a>

                                        <a href="{{ route('motoristas.edit', $motorista) }}" class="cc-btn-primary cc-btn-result">
                                            Editar
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $motoristas->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>