<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Administrar licencia
                        </h3>
                        <p class="cc-subtitle">
                            Localice una licencia para consultar su ficha, editar su vigencia o gestionar su estado.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('licencias.administrar.ventana') }}"
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

                <form method="GET" action="{{ route('licencias.administrar') }}" class="mb-6">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel">

                        <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
                            <div class="cc-form-section-title">
                                Búsqueda administrativa
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
                            <div class="flex items-center justify-end gap-3">
                                <button type="submit" class="cc-btn-primary">
                                    Buscar
                                </button>

                                <a href="{{ route('licencias.administrar') }}" class="cc-btn-secondary">
                                    Resetear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $licencias->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->firstItem() }}</span>
                        -
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->lastItem() }}</span>
                        de
                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->total() }}</span>
                    </div>
                @endif

                @if (! $hayFiltros)
                    <div class="cc-empty-panel">
                        <h5>
                            Búsqueda pendiente
                        </h5>
                        <p>
                            Los resultados permanecerán vacíos hasta que localice una licencia por empresa, placa, estado o período de vigencia.
                        </p>
                    </div>
                @elseif ($licencias->isEmpty())
                    <div class="cc-empty-panel">
                        <h5>
                            Sin resultados
                        </h5>
                        <p>
                            No hay licencias que coincidan con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($licencias as $licencia)
                            <article class="cc-result-card">
                                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">

                                    <div class="flex-1 min-w-0">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <h5 class="font-[var(--cc-font-heading)] text-xl font-extrabold text-[var(--cc-text-heading)] tracking-[-0.03em] cc-cell-truncate">
                                                {{ $licencia->unidad->placa ?? 'Sin placa' }}
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
                                                <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                                    Empresa
                                                </div>

                                                <div class="mt-1 text-sm font-bold text-[var(--cc-text-main)] cc-cell-truncate">
                                                    @if ($licencia->empresa)
                                                        {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                                    @else
                                                        Sin empresa
                                                    @endif
                                                </div>
                                            </div>

                                            <div>
                                                <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                                    Vigencia
                                                </div>

                                                <div class="mt-1 text-sm font-bold text-[var(--cc-text-main)]">
                                                    {{ $licencia->periodo_vigencia_texto }}
                                                </div>
                                            </div>

                                            <div>
                                                <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                                    Vencimiento
                                                </div>

                                                <div class="mt-1 text-sm font-bold text-[var(--cc-text-main)]">
                                                    {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'No registrado' }}
                                                </div>
                                            </div>

                                            <div>
                                                <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                                    Puntos esperados
                                                </div>

                                                <div class="mt-1 text-sm font-bold text-[var(--cc-text-main)]">
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
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $licencias->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>