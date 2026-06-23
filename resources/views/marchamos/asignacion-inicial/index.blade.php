<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Asignación de marchamos
                        </h3>
                        <p class="cc-subtitle">
                            Complete la primera asignación de marchamos para unidades con licencia activa y puntos pendientes.
                        </p>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="cc-table">
                        <thead>
                            <tr>
                                <th>Unidad</th>
                                <th>Empresa</th>
                                <th>Licencia</th>
                                <th>Estado unidad</th>
                                <th>Puntos</th>
                                <th>Avance</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>

                        <tbody>
                            @forelse ($unidades as $unidad)
                                @php
                                    $totalPuntos = (int) ($unidad->total_puntos ?? 0);
                                    $puntosAsignados = (int) ($unidad->puntos_asignados ?? 0);
                                    $puntosPendientes = max($totalPuntos - $puntosAsignados, 0);
                                    $porcentajeAvance = $totalPuntos > 0
                                        ? round(($puntosAsignados / $totalPuntos) * 100)
                                        : 0;
                                @endphp

                                <tr>
                                    <td>
                                        <div class="font-bold text-[var(--cc-text-main)]">
                                            {{ $unidad->placa }}
                                        </div>
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
                                            Sin empresa
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
                                            Sin licencia
                                        @endif
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
                                            {{ $porcentajeAvance }}%
                                        </div>
                                    </td>

                                    <td class="text-right">
                                        <a href="{{ route('marchamos.asignacion-inicial.show', $unidad) }}"
                                           class="cc-btn-secondary cc-btn-table">
                                            Asignación
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-[var(--cc-text-muted)] py-8">
                                        No hay unidades pendientes de asignación inicial.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $unidades->links() }}
                </div>

            </div>
        </div>
    </div>
</x-app-layout>