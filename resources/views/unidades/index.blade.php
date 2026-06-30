<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Consulta unidades
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Consulte las unidades registradas en el sistema.
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

                <div class="cc-summary-strip">
                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Total unidades
                        </span>
                        <span class="cc-summary-strip-value">
                            {{ $totalUnidades }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Registradas
                        </span>
                        <span class="cc-summary-strip-value">
                            {{ $totalRegistradas ?? 0 }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Activas
                        </span>
                        <span class="cc-summary-strip-value cc-summary-strip-value-success">
                            {{ $totalActivas }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Inactivas
                        </span>
                        <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                            {{ $totalInactivas }}
                        </span>
                    </div>
                </div>

                <form method="GET" action="{{ route('unidades.index') }}" class="mb-5">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                            <div class="cc-form-section-title">
                                Filtros de consulta
                            </div>
                        </div>

                        <div class="cc-filter-inline-grid-unidades">

                            <div class="cc-field">
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

                            <div class="cc-field">
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

                            <div class="cc-field">
                                <label for="estado">
                                    Estado
                                </label>
                                <select id="estado" name="estado" class="cc-input">
                                    <option value="">Todos</option>

                                    <option value="registrada" @selected($estado === 'registrada')>
                                        Registrada
                                    </option>

                                    <option value="activa" @selected($estado === 'activa')>
                                        Activa
                                    </option>

                                    <option value="inactiva" @selected($estado === 'inactiva')>
                                        Inactiva
                                    </option>
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

                            <div class="cc-filter-inline-actions">
                                <button type="submit" class="cc-btn-primary">
                                    Buscar
                                </button>

                                <a href="{{ route('unidades.index') }}" class="cc-btn-secondary">
                                    Resetear
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
                            No hay unidades que coincidan con los filtros seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-table-wrapper">
                        <table class="cc-table">
                            <thead>
                                <tr>
                                    <th>Placa</th>
                                    <th>Empresa</th>
                                    <th>Marca</th>
                                    <th>Tanques</th>
                                    <th>Cobertura</th>
                                    <th>Modelo de medición</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($unidades as $unidad)
                                    <tr>
                                        <td>
                                            <span class="cc-table-strong">
                                                {{ $unidad->placa }}
                                            </span>
                                        </td>

                                        <td>
                                            @if ($unidad->empresa)
                                                <span class="cc-cell-truncate">
                                                    {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                </span>
                                            @else
                                                <span class="text-[var(--cc-text-muted)]">
                                                    Sin empresa
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            {{ $unidad->marca ?: 'Sin marca' }}
                                        </td>

                                        <td>
                                            {{ $unidad->total_tanques }}
                                        </td>

                                        <td>
                                            {{ $unidad->cantidad_tanques_con_licencia }} de {{ $unidad->total_tanques }}
                                        </td>

                                        <td>
                                            {{ $unidad->modelo_medicion_texto }}
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $unidades->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>