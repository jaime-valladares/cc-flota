<x-app-layout>
    <div class="cc-page-wrapper cc-va-scope">
        <div
            class="cc-content-container cc-operational-container"
        >
            <div class="cc-card">

                @php
                    $totalPuntos = (int) (
                        $unidad->total_puntos ?? 0
                    );

                    $puntosAsignados = (int) (
                        $unidad->puntos_asignados ?? 0
                    );

                    $puntosPendientes = max(
                        $totalPuntos - $puntosAsignados,
                        0
                    );

                    $marchamosActivos = (int) (
                        $unidad->marchamos_activos ?? 0
                    );

                    $marchamosHistoricos = (int) (
                        $unidad->marchamos_historicos ?? 0
                    );

                    $porcentajeAvance = $totalPuntos > 0
                        ? round(
                            (
                                $puntosAsignados
                                / $totalPuntos
                            ) * 100
                        )
                        : 0;

                    $coberturaCompleta =
                        $totalPuntos > 0
                        && $puntosPendientes === 0;

                    $rutaVolverConsulta = route(
                        'marchamos.index',
                        [
                            'empresa_ids' => [
                                $unidad->empresa_id,
                            ],
                            'unidad_ids' => [
                                $unidad->id,
                            ],
                            'consultar' => 1,
                        ]
                    );

                    $rutaDetalleVentana =
                        \Illuminate\Support\Facades\Route::has(
                            'marchamos.detalle-unidad.ventana'
                        )
                            ? route(
                                'marchamos.detalle-unidad.ventana',
                                $unidad
                            )
                            : route(
                                'marchamos.detalle-unidad',
                                $unidad
                            );
                @endphp

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Marchamos de unidad
                        </h3>

                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <a
                            href="{{ $rutaDetalleVentana }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Abrir en nueva pestaña
                        </a>

                        <a
                            href="{{ $rutaVolverConsulta }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver a consulta
                        </a>
                    </div>
                </div>

                <div class="cc-profile-summary">
                    <div>
                        <div class="cc-profile-eyebrow">
                            Unidad
                        </div>

                        <div class="cc-profile-title">
                            {{ $unidad->placa }}
                        </div>

                        <div class="cc-profile-meta flex flex-wrap gap-x-5 gap-y-2">
                            <span>
                                <strong>Marca:</strong>
                                {{ $unidad->marca ?: 'Sin marca registrada' }}
                            </span>

                            <span>
                                <strong>Empresa:</strong>

                                @if ($unidad->empresa)
                                    {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                @else
                                    Sin empresa
                                @endif
                            </span>

                            <span>
                                <strong>Licencia:</strong>

                                @if ($unidad->licencia)
                                    {{ $unidad->licencia->periodo_vigencia_texto }}
                                @else
                                    Sin licencia
                                @endif
                            </span>

                            <span>
                                <strong>Plantilla:</strong>

                                @if ($unidad->licencia)
                                    {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                @else
                                    Sin plantilla
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="cc-profile-status">
                        @if ($unidad->estado === 'registrada')
                            <span class="cc-badge cc-badge-warning">
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
                    </div>
                </div>

                @if (
                    $unidad->empresa
                    && $unidad->empresa->estado === 'inactiva'
                )
                    <div class="cc-alert-danger mt-5">
                        La empresa se encuentra inactiva. La información permanece disponible únicamente para consulta histórica.
                    </div>
                @endif

                <section class="cc-detail-section mt-5">
                    <div class="cc-detail-section-header">
                        <h5>
                            Resumen de cobertura
                        </h5>

                        <p>
                            La cobertura representa la instalación física de marchamos y no sustituye las validaciones operativas de empresa, unidad y licencia.
                        </p>
                    </div>

                    <div class="cc-summary-strip">
                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">
                                Total puntos
                            </span>

                            <span class="cc-summary-strip-value">
                                {{ $totalPuntos }}
                            </span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">
                                Puntos cubiertos
                            </span>

                            <span class="cc-summary-strip-value">
                                {{ $puntosAsignados }}
                            </span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">
                                Marchamos activos
                            </span>

                            <span class="cc-summary-strip-value">
                                {{ $marchamosActivos }}
                            </span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">
                                Históricos
                            </span>

                            <span class="cc-summary-strip-value">
                                {{ $marchamosHistoricos }}
                            </span>
                        </div>
                    </div>

                    <div class="cc-detail-grid mt-5">
                        <div class="cc-detail-item">
                            <div class="cc-detail-label">
                                Estado de unidad
                            </div>

                            <div class="cc-detail-value">
                                {{ $unidad->estado_texto }}
                            </div>
                        </div>

                        <div class="cc-detail-item">
                            <div class="cc-detail-label">
                                Estado de empresa
                            </div>

                            <div class="cc-detail-value">
                                @if ($unidad->empresa)
                                    @if ($unidad->empresa->estado === 'activa')
                                        <span class="text-[var(--cc-success)]">
                                            Activa
                                        </span>
                                    @else
                                        <span class="text-[var(--cc-danger)]">
                                            Inactiva
                                        </span>
                                    @endif
                                @else
                                    Sin empresa
                                @endif
                            </div>
                        </div>

                        <div class="cc-detail-item">
                            <div class="cc-detail-label">
                                Puntos de seguridad
                            </div>

                            <div class="cc-detail-value">
                                {{ $puntosAsignados }} / {{ $totalPuntos }}

                                @if ($puntosPendientes > 0)
                                    <span class="text-[var(--cc-danger)]">
                                        · {{ $puntosPendientes }} pendientes
                                    </span>
                                @elseif ($totalPuntos > 0)
                                    <span class="text-[var(--cc-success)]">
                                        · Completos
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="cc-detail-item">
                            <div class="cc-detail-label">
                                Marchamos
                            </div>

                            <div class="cc-detail-value">
                                {{ $marchamosActivos }} activos

                                <span class="text-[var(--cc-text-muted)]">
                                    · {{ $marchamosHistoricos }} históricos
                                </span>
                            </div>
                        </div>

                        <div class="cc-detail-item cc-detail-item-wide">
                            <div class="cc-detail-label">
                                Cobertura física
                            </div>

                            <div class="cc-detail-value">
                                {{ $porcentajeAvance }}%

                                @if ($coberturaCompleta)
                                    <span class="text-[var(--cc-success)]">
                                        · Completa
                                    </span>
                                @elseif ($totalPuntos > 0)
                                    <span class="text-[var(--cc-danger)]">
                                        · Incompleta
                                    </span>
                                @else
                                    <span class="text-[var(--cc-text-muted)]">
                                        · Sin puntos registrados
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </section>

                <section class="cc-detail-section mt-6">
                    <div class="cc-detail-section-header">
                        <h5>
                            Historial de marchamos
                        </h5>

                        <p>
                            {{ $totalMarchamos }}
                            {{ $totalMarchamos === 1 ? 'registro encontrado' : 'registros encontrados' }}
                            para la unidad seleccionada.
                        </p>
                    </div>

                    <div class="cc-table-adaptive-wrapper">
                        <table
                            class="cc-table-adaptive"
                            style="min-width: 91rem;"
                        >
                            <thead>
                                <tr>
                                    <th
                                        class="cc-table-adaptive-nowrap"
                                        style="width: 12rem;"
                                    >
                                        Marchamo
                                    </th>

                                    <th
                                        class="cc-table-adaptive-nowrap"
                                        style="width: 27rem;"
                                    >
                                        Punto de seguridad
                                    </th>

                                    <th
                                        class="cc-table-adaptive-nowrap"
                                        style="width: 11rem;"
                                    >
                                        Estado
                                    </th>

                                    <th
                                        class="cc-table-adaptive-nowrap"
                                        style="width: 16rem;"
                                    >
                                        Origen
                                    </th>

                                    <th
                                        class="cc-table-adaptive-nowrap"
                                        style="width: 12rem;"
                                    >
                                        Activación
                                    </th>

                                    <th
                                        class="cc-table-adaptive-nowrap"
                                        style="width: 12rem;"
                                    >
                                        Desactivación
                                    </th>

                                    <th
                                        class="cc-table-adaptive-nowrap"
                                        style="width: 20rem;"
                                    >
                                        Motivo
                                    </th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($marchamos as $marchamo)
                                    <tr>
                                        <td class="cc-table-adaptive-nowrap">
                                            <div class="cc-table-adaptive-strong">
                                                {{ $marchamo->codigo_marchamo }}
                                            </div>
                                        </td>

                                        <td>
                                            @if ($marchamo->puntoSeguridad)
                                                <div class="cc-table-adaptive-strong">
                                                    {{ $marchamo->puntoSeguridad->nombre_punto }}
                                                </div>

                                                <div class="cc-table-adaptive-muted">
                                                    Orden {{ $marchamo->puntoSeguridad->orden }}

                                                    @if ($marchamo->puntoSeguridad->codigo_punto)
                                                        · {{ $marchamo->puntoSeguridad->codigo_punto }}
                                                    @endif

                                                    @if ($marchamo->puntoSeguridad->posicion_tanque)
                                                        · {{ $marchamo->puntoSeguridad->posicion_tanque }}
                                                    @endif
                                                </div>
                                            @else
                                                <span class="cc-table-adaptive-muted">
                                                    Sin punto asociado
                                                </span>
                                            @endif
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            @if (
                                                $marchamo->estado === 'activo'
                                                && $marchamo->activo_actual
                                            )
                                                <span class="cc-badge cc-badge-active">
                                                    Activo
                                                </span>
                                            @elseif ($marchamo->estado === 'reemplazado')
                                                <span class="cc-badge cc-badge-warning">
                                                    Reemplazado
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Anulado
                                                </span>
                                            @endif
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            {{ $marchamo->origen_creacion_texto }}
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            @if ($marchamo->fecha_activacion)
                                                <div class="cc-table-adaptive-strong">
                                                    {{ $marchamo->fecha_activacion->format('d/m/Y') }}
                                                </div>

                                                <div class="cc-table-adaptive-muted">
                                                    {{ $marchamo->fecha_activacion->format('H:i') }}
                                                </div>
                                            @else
                                                <span class="cc-table-adaptive-muted">
                                                    Sin fecha
                                                </span>
                                            @endif
                                        </td>

                                        <td class="cc-table-adaptive-nowrap">
                                            @if ($marchamo->fecha_desactivacion)
                                                <div class="cc-table-adaptive-strong">
                                                    {{ $marchamo->fecha_desactivacion->format('d/m/Y') }}
                                                </div>

                                                <div class="cc-table-adaptive-muted">
                                                    {{ $marchamo->fecha_desactivacion->format('H:i') }}
                                                </div>
                                            @elseif (
                                                $marchamo->estado === 'activo'
                                                && $marchamo->activo_actual
                                            )
                                                <span class="text-[var(--cc-success)]">
                                                    Vigente
                                                </span>
                                            @else
                                                <span class="cc-table-adaptive-muted">
                                                    Sin fecha
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            @if ($marchamo->motivo_desactivacion)
                                                <div class="cc-table-adaptive-strong">
                                                    {{ $marchamo->motivo_desactivacion }}
                                                </div>
                                            @elseif (
                                                $marchamo->estado === 'activo'
                                                && $marchamo->activo_actual
                                            )
                                                <span class="cc-table-adaptive-muted">
                                                    No aplica
                                                </span>
                                            @else
                                                <span class="cc-table-adaptive-muted">
                                                    No registrado
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td
                                            colspan="7"
                                            class="text-center text-[var(--cc-text-muted)] py-8"
                                        >
                                            No se encontraron marchamos para la unidad seleccionada.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($marchamos->hasPages())
                        <div class="mt-8 px-4">
                            {{ $marchamos->withQueryString()->links() }}
                        </div>
                    @endif
                </section>

            </div>
        </div>
    </div>
</x-app-layout>
