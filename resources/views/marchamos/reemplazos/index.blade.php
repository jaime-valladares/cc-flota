<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                @php
                    $rutaVentanaAdministracion = \Illuminate\Support\Facades\Route::has('marchamos.reemplazos.index.ventana')
                        ? route('marchamos.reemplazos.index.ventana', request()->query())
                        : route('marchamos.reemplazos.index', request()->query());

                    $totalDisponibles = $hayFiltros ? $unidadesDisponibles->count() : 0;
                @endphp

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Administración de marchamos
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Localice unidades activas para registrar reemplazos operativos de marchamos.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ $rutaVentanaAdministracion }}"
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

                <form method="GET" action="{{ route('marchamos.reemplazos.index') }}" class="mb-5">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                            <div class="cc-form-section-title">
                                Búsqueda administrativa
                            </div>
                        </div>

                        <div class="cc-filter-inline-grid">

                            <div class="cc-field">
                                <label for="empresa_id">
                                    Empresa
                                </label>

                                <select id="empresa_id" name="empresa_id" class="cc-input">
                                    <option value="">Todas las empresas</option>

                                    @foreach ($empresas as $empresa)
                                        <option value="{{ $empresa->id }}" @selected((string) $empresaId === (string) $empresa->id)>
                                            {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="cc-field">
                                <label for="unidad_id">
                                    Unidad
                                </label>

                                <select id="unidad_id" name="unidad_id" class="cc-input">
                                    <option value="">Todas las unidades activas</option>

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

                            <div class="cc-filter-inline-actions">
                                <button type="submit" class="cc-btn-primary">
                                    Consultar
                                </button>

                                <a href="{{ route('marchamos.reemplazos.index') }}" class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $totalDisponibles > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            1
                        </span>
                        -
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                            {{ $totalDisponibles }}
                        </span>
                        de
                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                            {{ $totalDisponibles }}
                        </span>
                    </div>
                @endif

                @if (! $hayFiltros)
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Búsqueda pendiente
                        </h5>

                        <p>
                            Los resultados permanecerán vacíos hasta que localice una unidad activa por empresa o placa.
                        </p>
                    </div>
                @elseif ($unidadesDisponibles->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>

                        <p>
                            No hay unidades activas con cobertura completa para los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($unidadesDisponibles as $unidad)
                            @php
                                $totalPuntos = (int) ($unidad->total_puntos ?? 0);
                                $puntosAsignados = (int) ($unidad->puntos_asignados ?? 0);
                                $puntosPendientes = max($totalPuntos - $puntosAsignados, 0);
                            @endphp

                            <article class="cc-result-card cc-result-card-compact">
                                <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">

                                    <div class="flex-1 min-w-0">
                                        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-start">

                                            <div>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h5 class="cc-result-title cc-cell-truncate">
                                                        {{ $unidad->placa }}
                                                    </h5>

                                                    <span class="cc-badge cc-badge-active">
                                                        Activa
                                                    </span>
                                                </div>

                                                <div class="cc-result-subtitle cc-cell-truncate">
                                                    {{ $unidad->marca ?: 'Sin marca registrada' }}
                                                </div>
                                            </div>

                                            <div>
                                                <div class="cc-result-label">
                                                    Empresa
                                                </div>

                                                <div class="cc-result-value cc-cell-truncate">
                                                    @if ($unidad->empresa)
                                                        {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                    @else
                                                        Sin empresa
                                                    @endif
                                                </div>

                                                @if ($unidad->empresa?->nit)
                                                    <div class="cc-result-value-muted cc-cell-truncate">
                                                        {{ $unidad->empresa->nit }}
                                                    </div>
                                                @endif
                                            </div>

                                            <div>
                                                <div class="cc-result-label">
                                                    Licencia
                                                </div>

                                                @if ($unidad->licencia)
                                                    <div class="cc-result-value">
                                                        {{ $unidad->licencia->periodo_vigencia_texto }}
                                                    </div>

                                                    <div class="cc-result-value-muted cc-cell-truncate">
                                                        {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                                    </div>
                                                @else
                                                    <div class="cc-result-value-muted">
                                                        Sin licencia
                                                    </div>
                                                @endif
                                            </div>

                                            <div>
                                                <div class="cc-result-label">
                                                    Puntos
                                                </div>

                                                <div class="cc-result-value">
                                                    {{ $puntosAsignados }} / {{ $totalPuntos }}
                                                </div>

                                                <div class="cc-result-value-muted">
                                                    {{ $puntosPendientes }} pendientes
                                                </div>
                                            </div>

                                            <div>
                                                <div class="cc-result-label">
                                                    Marchamos
                                                </div>

                                                <div class="cc-result-value">
                                                    {{ $unidad->marchamos_activos }} activos
                                                </div>

                                                <div class="cc-result-value-muted">
                                                    {{ $unidad->marchamos_historicos }} históricos
                                                </div>
                                            </div>

                                        </div>
                                    </div>

                                    <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 xl:justify-end xl:min-w-[10rem]">
                                        <a href="{{ route('marchamos.reemplazos.show', $unidad) }}"
                                           class="cc-btn-primary cc-btn-result">
                                            Reemplazar
                                        </a>
                                    </div>

                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>