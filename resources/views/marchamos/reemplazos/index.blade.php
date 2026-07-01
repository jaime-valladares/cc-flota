<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                @php
                    $rutaVentanaAdministracion = \Illuminate\Support\Facades\Route::has('marchamos.reemplazos.index.ventana')
                        ? route('marchamos.reemplazos.index.ventana', request()->query())
                        : route('marchamos.reemplazos.index', request()->query());
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
                                Filtros de reemplazo
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

                @if (! $hayFiltros)
                    <section class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Inicie una consulta
                        </h5>

                        <p>
                            Use los filtros para localizar unidades activas con cobertura completa. Desde esta pantalla podrá iniciar un reemplazo operativo de marchamos.
                        </p>
                    </section>
                @endif

                @if ($hayFiltros)
                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Unidades disponibles para reemplazo
                            </h5>
                            <p>
                                Solo se muestran unidades activas con licencia, puntos de seguridad y cobertura completa de marchamos.
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="cc-table">
                                <thead>
                                    <tr>
                                        <th>Unidad</th>
                                        <th>Empresa</th>
                                        <th>Licencia</th>
                                        <th>Puntos</th>
                                        <th>Marchamos</th>
                                        <th>Estado operativo</th>
                                        <th class="text-right">Acciones</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($unidadesDisponibles as $unidad)
                                        @php
                                            $totalPuntos = (int) ($unidad->total_puntos ?? 0);
                                            $puntosAsignados = (int) ($unidad->puntos_asignados ?? 0);
                                            $puntosPendientes = max($totalPuntos - $puntosAsignados, 0);
                                        @endphp

                                        <tr>
                                            <td>
                                                <a href="{{ route('unidades.show', $unidad) }}"
                                                   class="font-bold text-[var(--cc-primary)] hover:underline">
                                                    {{ $unidad->placa }}
                                                </a>

                                                <div class="text-sm text-[var(--cc-text-muted)]">
                                                    {{ $unidad->marca ?: 'Sin marca' }}
                                                </div>
                                            </td>

                                            <td>
                                                @if ($unidad->empresa)
                                                    <div class="font-bold text-[var(--cc-text-main)] cc-cell-truncate">
                                                        {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                    </div>

                                                    <div class="text-sm text-[var(--cc-text-muted)]">
                                                        {{ $unidad->empresa->nit }}
                                                    </div>
                                                @else
                                                    <span class="text-[var(--cc-text-muted)]">
                                                        Sin empresa
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                @if ($unidad->licencia)
                                                    <div class="font-bold text-[var(--cc-text-main)]">
                                                        {{ $unidad->licencia->periodo_vigencia_texto }}
                                                    </div>

                                                    <div class="text-sm text-[var(--cc-text-muted)] cc-cell-truncate">
                                                        {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                                    </div>
                                                @else
                                                    <span class="text-[var(--cc-text-muted)]">
                                                        Sin licencia
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                <div class="font-bold text-[var(--cc-text-main)]">
                                                    {{ $puntosAsignados }} / {{ $totalPuntos }}
                                                </div>

                                                <div class="text-sm text-[var(--cc-text-muted)]">
                                                    {{ $puntosPendientes }} pendientes
                                                </div>
                                            </td>

                                            <td>
                                                <div class="font-bold text-[var(--cc-text-main)]">
                                                    {{ $unidad->marchamos_activos }} activos
                                                </div>

                                                <div class="text-sm text-[var(--cc-text-muted)]">
                                                    {{ $unidad->marchamos_historicos }} históricos
                                                </div>
                                            </td>

                                            <td>
                                                <span class="cc-badge cc-badge-active">
                                                    Disponible
                                                </span>

                                                <div class="text-sm text-[var(--cc-text-muted)] mt-1">
                                                    Cobertura completa
                                                </div>
                                            </td>

                                            <td class="text-right">
                                                <div class="flex justify-end">
                                                    <a href="{{ route('marchamos.reemplazos.show', $unidad) }}"
                                                       class="cc-btn-primary cc-btn-table">
                                                        Reemplazar
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-[var(--cc-text-muted)] py-8">
                                                No hay unidades activas con cobertura completa para los filtros aplicados.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </section>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>