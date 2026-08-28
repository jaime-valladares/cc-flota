<div class="cc-metric-grid">
    <div class="cc-metric-card">
        <div class="cc-metric-label">
            Total licencias
        </div>
        <div class="cc-metric-value">
            {{ $totalLicencias }}
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

<form method="GET" action="{{ $accionFormulario }}" class="mb-6">
    <input type="hidden" name="consultar" value="1">

    <div class="cc-filter-panel">

        <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
            <div class="cc-form-section-title">
                {{ $modoAdministrar ? 'Búsqueda administrativa' : 'Filtros de consulta' }}
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
                    Nombre / Placa
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
                    Estado licencia
                </label>
                <select id="estado" name="estado" class="cc-input">
                    <option value="">Todos</option>
                    <option value="activa" @selected($estado === 'activa')>
                        Activa
                    </option>
                    <option value="inactiva" @selected($estado === 'inactiva')>
                        Inactiva
                    </option>
                </select>
            </div>

            <div class="lg:col-span-5 cc-field">
                <label for="periodo_vigencia_meses">
                    Período de vigencia
                </label>
                <select id="periodo_vigencia_meses" name="periodo_vigencia_meses" class="cc-input">
                    <option value="">Todos</option>

                    @foreach ($periodosVigencia as $valor => $texto)
                        <option value="{{ $valor }}" @selected((string) $periodoVigencia === (string) $valor)>
                            {{ $texto }}
                        </option>
                    @endforeach
                </select>
            </div>

        </div>

        <div class="mt-5 border-t border-[var(--cc-card-border)] pt-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                    {{ $modoAdministrar ? 'Desde esta pantalla podrá acceder a la ficha administrativa o editar una licencia.' : 'La consulta permite visualizar licencias, sin modificar información.' }}
                </p>

                <div class="flex items-center gap-3 lg:justify-end">
                    <button type="submit" class="cc-btn-primary">
                        Buscar
                    </button>

                    <a href="{{ $rutaReset }}" class="cc-btn-secondary">
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
            {{ $modoAdministrar ? 'Resultado administrativo' : 'Resultado de consulta' }}
        </h4>

        <p class="cc-section-note">
            @if (! $hayFiltros)
                {{ $modoAdministrar ? 'Seleccione filtros para buscar licencias administrables.' : 'Seleccione filtros para consultar licencias.' }}
            @elseif ($licencias->total() === 0)
                No se encontraron licencias con los criterios seleccionados.
            @elseif ($licencias->total() === 1)
                {{ $modoAdministrar ? 'Se encontró 1 licencia para administrar.' : 'Se encontró 1 licencia.' }}
            @else
                {{ $modoAdministrar ? 'Se encontraron ' . $licencias->total() . ' licencias para administrar.' : 'Se encontraron ' . $licencias->total() . ' licencias.' }}
            @endif
        </p>
    </div>

    @if ($hayFiltros && $licencias->total() > 0)
        <div class="text-sm text-[var(--cc-text-muted)]">
            Mostrando
            <span class="font-bold text-[var(--cc-text-main)]">{{ $licencias->firstItem() }}</span>
            -
            <span class="font-bold text-[var(--cc-text-main)]">{{ $licencias->lastItem() }}</span>
            de
            <span class="font-bold text-[var(--cc-text-main)]">{{ $licencias->total() }}</span>
        </div>
    @endif
</div>

@if (! $hayFiltros)
    <div class="cc-empty-panel">
        <h5>
            {{ $modoAdministrar ? 'Búsqueda pendiente' : 'Consulta pendiente' }}
        </h5>
        <p>
            Los resultados permanecerán vacíos hasta que realice una búsqueda.
        </p>
    </div>
@elseif ($licencias->isEmpty())
    <div class="cc-empty-panel">
        <h5>
            Sin resultados
        </h5>
        <p>
            No hay licencias que coincidan con los filtros seleccionados.
        </p>
    </div>
@else
    <div class="space-y-4">
        @foreach ($licencias as $licencia)
            <article class="cc-result-card">
                @if ($modoAdministrar)
                    <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">

                        <div class="flex-1 min-w-0">
                            <div class="flex flex-wrap items-center gap-3">
                                <h5 class="cc-va-result-title">
                                    {{ $licencia->unidad->placa ?? 'Sin Nombre / Placa' }}
                                </h5>

                                @if ($licencia->estado === 'activa')
                                    <span class="cc-badge cc-badge-active">
                                        Activa
                                    </span>
                                @else
                                    <span class="cc-badge cc-badge-inactive">
                                        Inactiva
                                    </span>
                                @endif
                            </div>

                            <div class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <div class="cc-va-structured-label">
                                        Empresa
                                    </div>
                                    <div class="cc-va-structured-value mt-1 cc-cell-truncate">
                                        @if ($licencia->empresa)
                                            {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                        @else
                                            Sin empresa
                                        @endif
                                    </div>
                                </div>

                                <div>
                                    <div class="cc-va-structured-label">
                                        Vigencia
                                    </div>
                                    <div class="cc-va-structured-value mt-1">
                                        {{ $licencia->periodo_vigencia_texto }}
                                    </div>
                                </div>

                                <div>
                                    <div class="cc-va-structured-label">
                                        Vencimiento
                                    </div>
                                    <div class="cc-va-structured-value mt-1">
                                        {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'No registrado' }}
                                    </div>
                                </div>

                                <div>
                                    <div class="cc-va-structured-label">
                                        Puntos esperados
                                    </div>
                                    <div class="cc-va-structured-value mt-1">
                                        {{ $licencia->cantidad_puntos_seguridad_esperados }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 xl:justify-end">
                            <a href="{{ route('licencias.show', $licencia) }}" class="cc-btn-primary cc-btn-wide">
                                Ver ficha
                            </a>

                            <a href="{{ route('licencias.edit', $licencia) }}" class="cc-btn-secondary cc-btn-wide">
                                Editar
                            </a>
                        </div>

                    </div>
                @else
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">

                        <div class="lg:col-span-3 min-w-0">
                            <div class="flex items-center gap-3 min-w-0">
                                <h5 class="cc-va-result-title">
                                    {{ $licencia->unidad->placa ?? 'Sin Nombre / Placa' }}
                                </h5>

                                @if ($licencia->estado === 'activa')
                                    <span class="cc-badge cc-badge-active">
                                        Activa
                                    </span>
                                @else
                                    <span class="cc-badge cc-badge-inactive">
                                        Inactiva
                                    </span>
                                @endif
                            </div>

                            <div class="cc-va-auxiliary-text mt-1 cc-cell-truncate">
                                {{ $licencia->unidad->marca ?? 'Sin marca registrada' }}
                            </div>
                        </div>

                        <div class="lg:col-span-2 min-w-0">
                            <div class="cc-va-structured-label">
                                Empresa
                            </div>

                            <div class="cc-va-structured-value mt-1 cc-cell-truncate">
                                @if ($licencia->empresa)
                                    {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                @else
                                    Sin empresa
                                @endif
                            </div>
                        </div>

                        <div class="lg:col-span-2">
                            <div class="cc-va-structured-label">
                                Vigencia
                            </div>

                            <div class="cc-va-structured-value mt-1">
                                {{ $licencia->periodo_vigencia_texto }}
                            </div>
                        </div>

                        <div class="lg:col-span-2">
                            <div class="cc-va-structured-label">
                                Vencimiento
                            </div>

                            <div class="cc-va-structured-value mt-1">
                                {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'No registrado' }}
                            </div>
                        </div>

                        <div class="lg:col-span-3 min-w-0">
                            <div class="cc-va-structured-label">
                                Plantilla
                            </div>

                            <div class="cc-va-structured-value mt-1 cc-cell-truncate">
                                {{ $licencia->plantilla_puntos_seguridad_texto }}
                                · {{ $licencia->cantidad_puntos_seguridad_esperados }} puntos
                            </div>
                        </div>

                    </div>
                @endif
            </article>
        @endforeach
    </div>

    <div class="mt-6">
        {{ $licencias->links() }}
    </div>
@endif
