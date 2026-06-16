<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Consulta unidades
                        </h3>
                        <p class="cc-subtitle">
                            Consulte las unidades registradas en el sistema. Esta pantalla es únicamente informativa.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('unidades.consulta.ventana') }}"
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

                <div class="cc-metric-grid">
                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Total unidades
                        </div>
                        <div class="cc-metric-value">
                            {{ $totalUnidades }}
                        </div>
                    </div>

                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Activas
                        </div>
                        <div class="cc-metric-value cc-metric-value-success">
                            {{ $totalActivas }}
                        </div>
                    </div>

                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Inactivas
                        </div>
                        <div class="cc-metric-value cc-metric-value-danger">
                            {{ $totalInactivas }}
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route('unidades.index') }}" class="mb-6">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel">

                        <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
                            <div class="cc-form-section-title">
                                Filtros de consulta
                            </div>
                            <div class="cc-form-section-note">
                                Utilice los filtros para localizar unidades por empresa, placa, estado o modelo de medición.
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">

                            <div class="lg:col-span-3 cc-field">
                                <label for="empresa_id">
                                    Empresa
                                </label>
                                <select id="empresa_id" name="empresa_id" class="cc-input">
                                    <option value="">Todas</option>

                                    @foreach ($empresas as $empresa)
                                        <option value="{{ $empresa->id }}"
                                                @selected((string) $empresaId === (string) $empresa->id)>
                                            {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="lg:col-span-2 cc-field">
                                <label for="placa">
                                    Placa
                                </label>
                                <input id="placa"
                                       type="text"
                                       name="placa"
                                       value="{{ $placa }}"
                                       class="cc-input"
                                       placeholder="Ej. C123ABC">
                            </div>

                            <div class="lg:col-span-2 cc-field">
                                <label for="estado">
                                    Estado
                                </label>
                                <select id="estado" name="estado" class="cc-input">
                                    <option value="">Todos</option>
                                    <option value="activo" @selected($estado === 'activo')>
                                        Activo
                                    </option>
                                    <option value="inactivo" @selected($estado === 'inactivo')>
                                        Inactivo
                                    </option>
                                </select>
                            </div>

                            <div class="lg:col-span-5 cc-field">
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

                        </div>

                        <div class="mt-5 border-t border-[var(--cc-card-border)] pt-5">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                    La consulta permite visualizar unidades, sin modificar información.
                                </p>

                                <div class="flex items-center gap-3">
                                    <button type="submit" class="cc-btn-primary">
                                        Buscar
                                    </button>

                                    <a href="{{ route('unidades.index') }}" class="cc-btn-secondary">
                                        Resetear
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="cc-section-heading">
                    <div>
                        <h4 class="cc-section-title">
                            Resultado de consulta
                        </h4>

                        <p class="cc-section-note">
                            @if (! $hayFiltros)
                                Seleccione filtros para consultar unidades.
                            @elseif ($unidades->total() === 0)
                                No se encontraron unidades con los criterios seleccionados.
                            @elseif ($unidades->total() === 1)
                                Se encontró 1 unidad.
                            @else
                                Se encontraron {{ $unidades->total() }} unidades.
                            @endif
                        </p>
                    </div>

                    @if ($hayFiltros && $unidades->total() > 0)
                        <div class="text-sm text-[var(--cc-text-muted)]">
                            Mostrando
                            <span class="font-bold text-[var(--cc-text-main)]">{{ $unidades->firstItem() }}</span>
                            -
                            <span class="font-bold text-[var(--cc-text-main)]">{{ $unidades->lastItem() }}</span>
                            de
                            <span class="font-bold text-[var(--cc-text-main)]">{{ $unidades->total() }}</span>
                        </div>
                    @endif
                </div>

                @if (! $hayFiltros)
                    <div class="cc-empty-panel">
                        <h5>
                            Consulta pendiente
                        </h5>
                        <p>
                            Los resultados permanecerán vacíos hasta que realice una búsqueda.
                        </p>
                    </div>
                @elseif ($unidades->isEmpty())
                    <div class="cc-empty-panel">
                        <h5>
                            Sin resultados
                        </h5>
                        <p>
                            No hay unidades que coincidan con los filtros seleccionados.
                        </p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($unidades as $unidad)
                            <article class="cc-result-card">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">

                                    <div class="lg:col-span-3 min-w-0">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <h5 class="font-[var(--cc-font-heading)] text-xl font-extrabold text-[var(--cc-text-heading)] tracking-[-0.03em] cc-cell-truncate">
                                                {{ $unidad->placa }}
                                            </h5>

                                            @if ($unidad->estado === 'activo')
                                                <span class="cc-badge cc-badge-active">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-1 text-sm font-medium text-[var(--cc-text-muted)] cc-cell-truncate">
                                            {{ $unidad->marca ?: 'Sin marca registrada' }}
                                        </div>
                                    </div>

                                    <div class="lg:col-span-2 min-w-0">
                                        <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                            Empresa
                                        </div>

                                        @if ($unidad->empresa)
                                            <div class="mt-1 font-bold text-[var(--cc-text-main)] cc-cell-truncate">
                                                {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                            </div>
                                        @else
                                            <div class="mt-1 text-sm text-[var(--cc-text-muted)]">
                                                Sin empresa
                                            </div>
                                        @endif
                                    </div>

                                    <div class="lg:col-span-2">
                                        <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                            Tanques
                                        </div>

                                        <div class="mt-1 font-bold text-[var(--cc-text-main)]">
                                            {{ $unidad->total_tanques }} totales
                                        </div>
                                    </div>

                                    <div class="lg:col-span-2">
                                        <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                            Cobertura
                                        </div>

                                        <div class="mt-1 font-bold text-[var(--cc-text-main)]">
                                            {{ $unidad->cantidad_tanques_con_licencia }} de {{ $unidad->total_tanques }} tanques
                                        </div>
                                    </div>

                                    <div class="lg:col-span-3 min-w-0">
                                        <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                            Modelo de medición
                                        </div>

                                        <div class="mt-1 font-bold text-[var(--cc-text-main)] cc-cell-truncate">
                                            {{ $unidad->modelo_medicion_texto }}
                                        </div>
                                    </div>

                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $unidades->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>