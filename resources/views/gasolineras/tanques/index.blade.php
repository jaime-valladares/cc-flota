<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Gestión de tanques
                        </h3>

                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras.tanques.index.ventana', request()->query()) }}"
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

                <form method="GET" action="{{ route('gasolineras.tanques.index') }}" class="mb-5">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                            <div class="cc-form-section-title">
                                Filtros de consulta
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

                                @error('busqueda_empresa')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-field">
                                <label for="empresa_id">
                                    Empresa
                                </label>

                                @if ($esUsuarioDieselCop)
                                    <select id="empresa_id" name="empresa_id" class="cc-input">
                                        <option value="">Todas</option>

                                        @foreach ($empresasSelector as $empresa)
                                            <option value="{{ $empresa->id }}" @selected((string) $empresaId === (string) $empresa->id)>
                                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <select id="empresa_id_visible" class="cc-input" disabled>
                                        @foreach ($empresasSelector as $empresa)
                                            <option value="{{ $empresa->id }}" selected>
                                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
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
                                <label for="busqueda_gasolinera">
                                    Buscar gasolinera
                                </label>

                                <input
                                    id="busqueda_gasolinera"
                                    name="busqueda_gasolinera"
                                    type="text"
                                    class="cc-input"
                                    value="{{ $busquedaGasolinera ?? '' }}"
                                    maxlength="150"
                                    placeholder="Nombre de gasolinera"
                                >

                                @error('busqueda_gasolinera')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-field">
                                <label for="gasolinera_id">
                                    Gasolinera
                                </label>

                                <select id="gasolinera_id" name="gasolinera_id" class="cc-input">
                                    <option value="">Todas</option>

                                    @foreach ($gasolinerasSelector as $gasolineraOpcion)
                                        <option value="{{ $gasolineraOpcion->id }}" @selected((string) $gasolineraId === (string) $gasolineraOpcion->id)>
                                            {{ $gasolineraOpcion->nombre }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('gasolinera_id')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-standard-filter-actions">
                                <button type="submit" class="cc-btn-primary">
                                    Consultar
                                </button>

                                <a href="{{ route('gasolineras.tanques.index') }}" class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $tanques->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            {{ $tanques->firstItem() }}
                        </span>
                        -
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            {{ $tanques->lastItem() }}
                        </span>
                        de
                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                            {{ $tanques->total() }}
                        </span>
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
                @elseif ($tanques->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>

                        <p>
                            No hay tanques que coincidan con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-admin-result-list">
                        @foreach ($tanques as $tanque)
                            @php
                                $gasolinera = $tanque->gasolinera;
                                $empresa = $gasolinera?->empresa;

                                $capacidadTotal = (float) $tanque->capacidad_total;
                                $volumenActual = (float) $tanque->volumen_actual;
                                $volumenMinimoAlerta = (float) $tanque->volumen_minimo_alerta;

                                $porcentajeDisponible = $tanque->porcentajeDisponible();
                                $bajoAlerta = $tanque->estaBajoAlerta();

                                $estadoOperativo = $tanque->estado === 'activo'
                                    ? 'Disponible'
                                    : 'No disponible';
                            @endphp

                            <article class="cc-admin-result-card">
                                <div class="grid gap-5 xl:grid-cols-12 xl:items-start">

                                    <div class="min-w-0 xl:col-span-3">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <h5 class="cc-admin-result-title">
                                                {{ $tanque->nombre }}
                                            </h5>

                                            @if ($tanque->estado === 'activo')
                                                <span class="cc-badge cc-badge-active">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Inactivo
                                                </span>
                                            @endif

                                            @if ($bajoAlerta)
                                                <span class="cc-badge cc-badge-warning">
                                                    Bajo mínimo
                                                </span>
                                            @endif
                                        </div>

                                        <div class="cc-admin-result-subtitle">
                                            Tanque interno
                                        </div>
                                    </div>

                                    <div class="min-w-0 xl:col-span-3">
                                        <div class="cc-admin-result-label">
                                            Gasolinera
                                        </div>

                                        <div class="cc-admin-result-value">
                                            {{ $gasolinera?->nombre ?: 'Sin gasolinera' }}
                                        </div>

                                        <div class="cc-admin-result-value-muted">
                                            {{ $empresa?->nombre_comercial ?: $empresa?->nombre_legal ?: 'Sin empresa' }}
                                        </div>
                                    </div>

                                    <div class="min-w-0 sm:grid sm:grid-cols-3 sm:gap-5 xl:col-span-4 xl:grid-cols-3">
                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Inventario
                                            </div>

                                            <div class="cc-admin-result-value">
                                                {{ number_format($volumenActual, 2) }} gal
                                            </div>

                                            <div class="cc-admin-result-value-muted">
                                                Disponible
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Capacidad
                                            </div>

                                            <div class="cc-admin-result-value">
                                                {{ number_format($capacidadTotal, 2) }} gal
                                            </div>

                                            <div class="cc-admin-result-value-muted">
                                                {{ number_format($porcentajeDisponible, 2) }}% disponible
                                            </div>
                                        </div>

                                        <div class="min-w-0">
                                            <div class="cc-admin-result-label">
                                                Mínimo alerta
                                            </div>

                                            <div class="cc-admin-result-value {{ $bajoAlerta ? 'text-[var(--cc-danger)]' : '' }}">
                                                {{ number_format($volumenMinimoAlerta, 2) }} gal
                                            </div>

                                            <div class="cc-admin-result-value-muted">
                                                {{ $estadoOperativo }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row gap-3 xl:col-span-2 xl:justify-end xl:self-center">
                                        @if ($gasolinera)
                                            <a href="{{ route('gasolineras.tanques.show', [$gasolinera, $tanque]) }}"
                                               class="cc-btn-primary cc-btn-result w-full sm:w-auto">
                                                Administrar
                                            </a>
                                        @endif
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