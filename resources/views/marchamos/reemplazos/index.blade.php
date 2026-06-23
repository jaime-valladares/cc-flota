<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Administración de marchamos
                        </h3>
                        <p class="cc-subtitle">
                            Seleccione una unidad activa para registrar reemplazos de marchamos.
                        </p>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <section class="cc-filter-panel">
                    <form method="GET" action="{{ route('marchamos.reemplazos.index') }}">
                        <input type="hidden" name="consultar" value="1">

                        <div class="flex flex-col lg:flex-row lg:items-end gap-4">
                            <div class="cc-field flex-1">
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

                            <div class="cc-field flex-1">
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

                            <div class="flex items-center gap-3 lg:pb-[0.05rem]">
                                <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                    Consultar
                                </button>

                                <a href="{{ route('marchamos.reemplazos.index') }}" class="cc-btn-secondary cc-btn-form-action">
                                    Limpiar
                                </a>
                            </div>
                        </div>
                    </form>
                </section>

                @if (! $hayFiltros)
                    <section class="cc-empty-panel mt-6">
                        <h5>
                            Inicie una consulta
                        </h5>

                        <p>
                            Use los filtros para localizar unidades activas con cobertura completa. Desde esta pantalla podrá iniciar un reemplazo operativo de marchamos.
                        </p>
                    </section>
                @endif

                @if ($hayFiltros)
                    <section class="cc-detail-section mt-6">
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
                                                    <div class="font-bold text-[var(--cc-text-main)]">
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

                                                    <div class="text-sm text-[var(--cc-text-muted)]">
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
                                                <div class="flex justify-end gap-2">
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