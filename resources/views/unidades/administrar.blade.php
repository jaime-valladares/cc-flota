<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Administrar unidad
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('unidades.administrar.ventana') }}"
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

                <form method="GET" action="{{ route('unidades.administrar') }}" class="mb-5">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                            <div class="cc-form-section-title">
                                Filtros de Consulta
                            </div>
                        </div>

                        <div class="cc-standard-filter-grid cc-unidades-consulta-filter-grid">

                            <div class="cc-field">
                                <label for="busqueda">
                                    Buscar empresa o placa
                                </label>

                                <input
                                    id="busqueda"
                                    name="busqueda"
                                    type="text"
                                    class="cc-input"
                                    value="{{ $busqueda ?? '' }}"
                                    maxlength="150"
                                    placeholder="Empresa o placa"
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
                                            <option value="{{ $empresa->id }}"
                                                    @selected((string) $empresaId === (string) $empresa->id)>
                                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <select id="empresa_id" name="empresa_id" class="cc-input" disabled>
                                        @foreach ($empresas as $empresa)
                                            <option value="{{ $empresa->id }}" selected>
                                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>

                            <div class="cc-field">
                                <label for="placa">
                                    Placa
                                </label>

                                <select id="placa" name="placa" class="cc-input">
                                    <option value="">Todas</option>

                                    @foreach ($placasSelector as $placaOpcion)
                                        <option value="{{ $placaOpcion }}" @selected((string) $placa === (string) $placaOpcion)>
                                            {{ $placaOpcion }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="cc-field">
                                <label for="modelo_medicion">
                                    Modelo de medición
                                </label>

                                <select id="modelo_medicion" name="modelo_medicion" class="cc-input">
                                    <option value="">Todos</option>

                                    @foreach ($modelosMedicion as $valor => $texto)
                                        <option value="{{ $valor }}" @selected($modeloMedicion === $valor)>
                                            {{ $texto }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="cc-standard-filter-actions">
                                <button type="submit" class="cc-btn-primary">
                                    Consultar
                                </button>

                                <a href="{{ route('unidades.administrar') }}" class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $unidades->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $unidades->firstItem() }}</span>
                        -
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $unidades->lastItem() }}</span>
                        de
                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $unidades->total() }}</span>
                    </div>
                @endif

                @if (! $hayFiltros)
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Consulta pendiente
                        </h5>
                        <p>
                            Los resultados permanecerán vacíos hasta que realice una búsqueda.
                        </p>
                    </div>
                @elseif ($unidades->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>
                        <p>
                            No hay unidades que coincidan con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-admin-result-list">
                        @foreach ($unidades as $unidad)
                            <article class="cc-admin-result-card">
                                <div class="cc-admin-result-grid">

                                    <div class="cc-admin-result-main">
                                        <div class="cc-admin-result-title-row">
                                            <h5 class="cc-admin-result-title">
                                                {{ $unidad->placa }}
                                            </h5>

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
                                        </div>

                                        <div class="cc-admin-result-subtitle">
                                            {{ $unidad->marca ?: 'Sin marca registrada' }}
                                        </div>
                                    </div>

                                    <div class="cc-admin-result-meta">
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

                                    <div class="cc-admin-result-meta">
                                        <div class="cc-admin-result-label">
                                            Cobertura
                                        </div>

                                        <div class="cc-admin-result-value">
                                            {{ $unidad->cantidad_tanques_con_licencia }} de {{ $unidad->total_tanques }} tanques
                                        </div>

                                        <div class="cc-admin-result-value-muted">
                                            {{ $unidad->modelo_medicion_texto }}
                                        </div>
                                    </div>

                                    <div class="cc-admin-result-actions">
                                        <a href="{{ route('unidades.show', $unidad) }}" class="cc-btn-primary cc-btn-result">
                                            Ver ficha
                                        </a>

                                        <a href="{{ route('unidades.edit', $unidad) }}" class="cc-btn-secondary cc-btn-result">
                                            Editar
                                        </a>
                                    </div>

                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $unidades->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>