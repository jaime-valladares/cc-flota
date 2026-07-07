<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Recarga de tanques
                        </h3>

                        <p class="cc-subtitle cc-subtitle-compact">
                            Localice tanques activos para registrar entradas de combustible de forma controlada.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras.tanques.recargas.index.ventana', request()->query()) }}"
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

                <form method="GET" action="{{ route('gasolineras.tanques.recargas.index') }}" class="mb-5">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                            <div class="cc-form-section-title">
                                Búsqueda de tanques recargables
                            </div>
                        </div>

                        <div
                            class="cc-filter-inline-grid"
                            style="grid-template-columns: minmax(240px, 2fr) minmax(220px, 1.4fr) auto auto; align-items: end;"
                        >
                            @if ($esUsuarioDieselCop)
                                <div class="cc-field" style="margin-bottom: 0;">
                                    <label for="empresa_id">
                                        Empresa
                                    </label>

                                    <select id="empresa_id" name="empresa_id" class="cc-input">
                                        <option value="">Todas</option>

                                        @foreach ($empresasSelector as $empresa)
                                            <option value="{{ $empresa->id }}" @selected((string) $empresaId === (string) $empresa->id)>
                                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('empresa_id')
                                        <div class="cc-error">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            @else
                                <div class="cc-field" style="margin-bottom: 0;">
                                    <label for="empresa_id_visible">
                                        Empresa
                                    </label>

                                    <select id="empresa_id_visible" class="cc-input" disabled>
                                        @foreach ($empresasSelector as $empresa)
                                            <option value="{{ $empresa->id }}" selected>
                                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="cc-field" style="margin-bottom: 0;">
                                <label for="gasolinera_id">
                                    Gasolinera
                                </label>

                                <select id="gasolinera_id" name="gasolinera_id" class="cc-input">
                                    <option value="">Todas</option>

                                    @foreach ($gasolinerasSelector as $gasolinera)
                                        <option value="{{ $gasolinera->id }}" @selected((string) $gasolineraId === (string) $gasolinera->id)>
                                            {{ $gasolinera->nombre }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('gasolinera_id')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div style="display: flex; align-items: end;">
                                <button type="submit" class="cc-btn-primary">
                                    Consultar
                                </button>
                            </div>

                            <div style="display: flex; align-items: end;">
                                <a href="{{ route('gasolineras.tanques.recargas.index') }}" class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $tanques->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $tanques->firstItem() }}</span>
                        -
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $tanques->lastItem() }}</span>
                        de
                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $tanques->total() }}</span>
                    </div>
                @endif

                @if (! $hayFiltros)
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Búsqueda pendiente
                        </h5>

                        <p>
                            Los resultados permanecerán vacíos hasta que localice tanques activos por empresa o gasolinera.
                        </p>
                    </div>
                @elseif ($tanques->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>

                        <p>
                            No hay tanques activos disponibles para recarga con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($tanques as $tanque)
                            @php
                                $gasolinera = $tanque->gasolinera;
                                $empresa = $gasolinera?->empresa;

                                $capacidadTotal = (float) $tanque->capacidad_total;
                                $volumenActual = (float) $tanque->volumen_actual;
                                $volumenMinimoAlerta = (float) $tanque->volumen_minimo_alerta;
                                $capacidadDisponibleTanque = max(0, $capacidadTotal - $volumenActual);

                                $porcentajeDisponible = $capacidadTotal > 0
                                    ? round(($volumenActual / $capacidadTotal) * 100, 2)
                                    : 0;

                                $bajoAlerta = $volumenActual <= $volumenMinimoAlerta;
                            @endphp

                            <article class="cc-result-card cc-result-card-compact">
                                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">

                                    <div class="flex-1 min-w-0">
                                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center">

                                            <div>
                                                <h5 class="cc-result-title cc-cell-truncate">
                                                    {{ $tanque->nombre }}
                                                </h5>

                                                <div class="cc-result-value-muted cc-cell-truncate">
                                                    {{ $gasolinera?->nombre ?: 'Sin gasolinera' }}
                                                </div>
                                            </div>

                                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                                <span class="cc-badge cc-badge-active">
                                                    Recargable
                                                </span>

                                                @if ($bajoAlerta)
                                                    <span class="cc-badge cc-badge-warning">
                                                        Bajo mínimo
                                                    </span>
                                                @endif
                                            </div>

                                            <div>
                                                <div class="cc-result-label">
                                                    Empresa
                                                </div>

                                                <div class="cc-result-value cc-cell-truncate">
                                                    {{ $empresa?->nombre_comercial ?: $empresa?->nombre_legal ?: 'No registrada' }}
                                                </div>

                                                <div class="cc-result-value-muted cc-cell-truncate">
                                                    {{ $gasolinera?->direccion ?: 'Sin dirección' }}
                                                </div>
                                            </div>

                                            <div>
                                                <div class="cc-result-label">
                                                    Inventario actual
                                                </div>

                                                <div class="cc-result-value">
                                                    {{ number_format($volumenActual, 2) }} gal
                                                </div>

                                                <div class="cc-result-value-muted">
                                                    {{ number_format($porcentajeDisponible, 2) }}% ocupado
                                                </div>
                                            </div>

                                            <div>
                                                <div class="cc-result-label">
                                                    Disponible para recarga
                                                </div>

                                                <div class="cc-result-value">
                                                    {{ number_format($capacidadDisponibleTanque, 2) }} gal
                                                </div>

                                                <div class="cc-result-value-muted">
                                                    Capacidad: {{ number_format($capacidadTotal, 2) }} gal
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 xl:justify-end xl:min-w-[9rem]">
                                        <a href="{{ route('gasolineras.tanques.recargas.show', [$gasolinera, $tanque]) }}" class="cc-btn-primary cc-btn-result">
                                            Recargar
                                        </a>
                                    </div>

                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $tanques->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>