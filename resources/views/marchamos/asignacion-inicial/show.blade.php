<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Asignación inicial de marchamos
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Registre, revise o corrija los marchamos físicos instalados en cada punto de seguridad de la unidad.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('marchamos.asignacion-inicial.show.ventana', $unidad) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('marchamos.asignacion-inicial.index', [
                            'empresa_id' => $unidad->empresa_id,
                            'unidad_id' => $unidad->id,
                            'consultar' => 1,
                        ]) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a asignación
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="cc-alert-danger">
                        <div class="font-bold">
                            Revise la información ingresada.
                        </div>

                        <ul class="mt-2 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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

                            @if ($unidad->empresa)
                                <span>
                                    <strong>Empresa:</strong>
                                    {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                </span>
                            @else
                                <span>
                                    <strong>Empresa:</strong>
                                    Sin empresa
                                </span>
                            @endif

                            @if ($unidad->licencia)
                                <span>
                                    <strong>Licencia:</strong>
                                    {{ $unidad->licencia->periodo_vigencia_texto }}
                                </span>

                                <span>
                                    <strong>Plantilla:</strong>
                                    {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                </span>
                            @else
                                <span>
                                    <strong>Licencia:</strong>
                                    Sin licencia
                                </span>

                                <span>
                                    <strong>Plantilla:</strong>
                                    Sin plantilla
                                </span>
                            @endif
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

                <section class="cc-detail-section mt-5">
                    <div class="cc-detail-section-header">
                        <h5>
                            Avance de asignación
                        </h5>
                        <p>
                            Estado actual de la instalación física de marchamos en los puntos de seguridad.
                        </p>
                    </div>

                    <div class="cc-summary-strip">
                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">
                                Avance
                            </span>
                            <span class="cc-summary-strip-value">
                                {{ $porcentajeAvance }}%
                            </span>
                        </div>

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
                                Asignados
                            </span>
                            <span class="cc-summary-strip-value">
                                {{ $puntosAsignados }}
                            </span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">
                                Pendientes
                            </span>
                            <span class="cc-summary-strip-value">
                                {{ $puntosPendientes }}
                            </span>
                        </div>
                    </div>
                </section>

                <section class="cc-detail-section mt-6">
                    <div class="cc-detail-section-header">
                        <h5>
                            Registro de marchamos
                        </h5>
                        <p>
                            Puede guardar avances parciales o corregir códigos antes de finalizar. Los códigos deben tener exactamente 7 dígitos, conservando ceros a la izquierda.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('marchamos.asignacion-inicial.guardar-avance', $unidad) }}">
                        @csrf

                        <div class="cc-table-adaptive-wrapper">
                            <table class="cc-table-adaptive" style="min-width: 73rem;">
                                <thead>
                                    <tr>
                                        <th class="cc-table-adaptive-nowrap" style="width: 7rem;">
                                            Orden
                                        </th>

                                        <th class="cc-table-adaptive-nowrap" style="width: 26rem;">
                                            Punto de seguridad
                                        </th>

                                        <th class="cc-table-adaptive-nowrap" style="width: 11rem;">
                                            Código punto
                                        </th>

                                        <th class="cc-table-adaptive-nowrap" style="width: 11rem;">
                                            Posición
                                        </th>

                                        <th class="cc-table-adaptive-nowrap" style="width: 11rem;">
                                            Estado
                                        </th>

                                        <th class="cc-table-adaptive-nowrap" style="width: 12rem;">
                                            Marchamo
                                        </th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($unidad->puntosSeguridad as $punto)
                                        @php
                                            $codigoActual = $punto->marchamoActual?->codigo_marchamo;
                                        @endphp

                                        <tr>
                                            <td class="cc-table-adaptive-nowrap">
                                                <div class="cc-table-adaptive-strong">
                                                    {{ $punto->orden }}
                                                </div>
                                            </td>

                                            <td>
                                                <div class="cc-table-adaptive-strong">
                                                    {{ $punto->nombre_punto }}
                                                </div>

                                                @if ($punto->grupo || $punto->subgrupo)
                                                    <div class="cc-table-adaptive-muted">
                                                        {{ $punto->grupo }}

                                                        @if ($punto->subgrupo)
                                                            · {{ $punto->subgrupo }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>

                                            <td class="cc-table-adaptive-nowrap">
                                                {{ $punto->codigo_punto ?: 'No definido' }}
                                            </td>

                                            <td class="cc-table-adaptive-nowrap">
                                                {{ $punto->posicion_tanque ?: 'No definida' }}
                                            </td>

                                            <td class="cc-table-adaptive-nowrap">
                                                @if ($codigoActual)
                                                    <span class="cc-badge cc-badge-active">
                                                        Asignado
                                                    </span>
                                                @else
                                                    <span class="cc-badge cc-badge-warning">
                                                        Pendiente
                                                    </span>
                                                @endif
                                            </td>

                                            <td class="cc-table-adaptive-nowrap">
                                                <input
                                                    type="text"
                                                    name="marchamos[{{ $punto->id }}]"
                                                    value="{{ old('marchamos.' . $punto->id, $codigoActual) }}"
                                                    class="cc-input"
                                                    placeholder="0006387"
                                                    maxlength="7"
                                                    inputmode="numeric"
                                                    pattern="\d{7}">

                                                @if ($codigoActual)
                                                    <div class="cc-table-adaptive-muted mt-1">
                                                        Código editable
                                                    </div>
                                                @else
                                                    <div class="cc-table-adaptive-muted mt-1">
                                                        Pendiente
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="cc-actions cc-actions-compact mt-6 mb-6">
                            <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                Guardar avance
                            </button>
                        </div>
                    </form>
                </section>

                <section class="cc-detail-section mt-6">
                    <div class="cc-detail-section-header">
                        <h5>
                            Finalización de asignación inicial
                        </h5>

                        @if ($puntosPendientes > 0)
                            <p>
                                La unidad aún tiene puntos pendientes. Para finalizar, todos los puntos activos deben tener un marchamo asignado.
                            </p>
                        @else
                            <p>
                                Todos los puntos de seguridad tienen marchamo asignado. Puede finalizar la asignación inicial para activar la unidad.
                            </p>
                        @endif
                    </div>

                    <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                            Esta acción cambia la unidad de Registrada a Activa. Debe usarse únicamente cuando la instalación física esté completa.
                        </p>

                        <form method="POST"
                              action="{{ route('marchamos.asignacion-inicial.finalizar', $unidad) }}"
                              onsubmit="return confirm('¿Está seguro de finalizar la asignación inicial? La unidad pasará a estado activa.');">
                            @csrf

                            <button type="submit"
                                    class="cc-btn-success cc-btn-form-action">
                                Finalizar asignación inicial
                            </button>
                        </form>
                    </div>
                </section>

            </div>
        </div>
    </div>
</x-app-layout>