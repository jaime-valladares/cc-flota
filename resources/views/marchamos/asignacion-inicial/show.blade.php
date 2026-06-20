<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Asignación inicial de marchamos
                        </h3>
                        <p class="cc-subtitle">
                            Registre los marchamos físicos instalados en cada punto de seguridad de la unidad.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('unidades.show', $unidad) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a unidad
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

                        <div class="cc-profile-meta">
                            <span>{{ $unidad->marca ?: 'Sin marca registrada' }}</span>

                            @if ($unidad->empresa)
                                <span>Empresa: {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}</span>
                            @else
                                <span>Empresa: Sin empresa</span>
                            @endif

                            @if ($unidad->licencia)
                                <span>Licencia: {{ $unidad->licencia->periodo_vigencia_texto }}</span>
                                <span>{{ $unidad->licencia->plantilla_puntos_seguridad_texto }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="cc-profile-status">
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
                    </div>
                </div>

                <div class="cc-metric-grid">
                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Total puntos
                        </div>
                        <div class="cc-metric-value">
                            {{ $totalPuntos }}
                        </div>
                    </div>

                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Asignados
                        </div>
                        <div class="cc-metric-value cc-metric-value-success">
                            {{ $puntosAsignados }}
                        </div>
                    </div>

                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Pendientes
                        </div>
                        <div class="cc-metric-value cc-metric-value-danger">
                            {{ $puntosPendientes }}
                        </div>
                    </div>

                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Avance
                        </div>
                        <div class="cc-metric-value">
                            {{ $porcentajeAvance }}%
                        </div>
                    </div>
                </div>

                <section class="cc-detail-section mt-6">
                    <div class="cc-detail-section-header">
                        <h5>Registro de marchamos</h5>
                        <p>
                            Puede guardar avances parciales. Los códigos deben tener exactamente 7 dígitos, conservando ceros a la izquierda.
                        </p>
                    </div>

                    <form method="POST" action="{{ route('marchamos.asignacion-inicial.guardar-avance', $unidad) }}">
                        @csrf

                        <div class="overflow-x-auto">
                            <table class="cc-table">
                                <thead>
                                    <tr>
                                        <th style="width: 80px;">Orden</th>
                                        <th>Punto de seguridad</th>
                                        <th style="width: 180px;">Código punto</th>
                                        <th style="width: 180px;">Posición</th>
                                        <th style="width: 180px;">Estado</th>
                                        <th style="width: 220px;">Marchamo</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($unidad->puntosSeguridad as $punto)
                                        <tr>
                                            <td>
                                                <span class="font-bold">
                                                    {{ $punto->orden }}
                                                </span>
                                            </td>

                                            <td>
                                                <div class="font-bold text-[var(--cc-text-main)]">
                                                    {{ $punto->nombre_punto }}
                                                </div>

                                                @if ($punto->grupo || $punto->subgrupo)
                                                    <div class="text-sm text-[var(--cc-text-muted)]">
                                                        {{ $punto->grupo }}
                                                        @if ($punto->subgrupo)
                                                            · {{ $punto->subgrupo }}
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>

                                            <td>
                                                {{ $punto->codigo_punto ?: 'No definido' }}
                                            </td>

                                            <td>
                                                {{ $punto->posicion_tanque ?: 'No definida' }}
                                            </td>

                                            <td>
                                                @if ($punto->marchamo_actual_id)
                                                    <span class="cc-badge cc-badge-active">
                                                        Asignado
                                                    </span>
                                                @else
                                                    <span class="cc-badge cc-badge-pending">
                                                        Pendiente
                                                    </span>
                                                @endif
                                            </td>

                                            <td>
                                                @if ($punto->marchamo_actual_id && $punto->marchamoActual)
                                                    <div class="font-bold text-[var(--cc-text-main)]">
                                                        {{ $punto->marchamoActual->codigo_marchamo }}
                                                    </div>
                                                    <div class="text-xs text-[var(--cc-text-muted)]">
                                                        Activo
                                                    </div>
                                                @else
                                                    <input
                                                        type="text"
                                                        name="marchamos[{{ $punto->id }}]"
                                                        value="{{ old('marchamos.' . $punto->id) }}"
                                                        class="cc-input"
                                                        placeholder="Ej. 0006387"
                                                        maxlength="7"
                                                        inputmode="numeric"
                                                        pattern="\d{7}">
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="cc-actions cc-actions-split mt-6">
                            <div class="cc-actions-normal">
                                <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                    Guardar avance
                                </button>

                                <a href="{{ route('unidades.show', $unidad) }}" class="cc-btn-secondary cc-btn-form-action">
                                    Volver a unidad
                                </a>
                            </div>
                        </div>
                    </form>
                </section>

                <section class="cc-detail-section mt-6">
                    <div class="cc-detail-section-header">
                        <h5>Finalización de asignación inicial</h5>

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

                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                            Esta acción cambia la unidad de Registrada a Activa. Debe usarse únicamente cuando la instalación física esté completa.
                        </p>

                        <form method="POST"
                              action="{{ route('marchamos.asignacion-inicial.finalizar', $unidad) }}"
                              onsubmit="return confirm('¿Está seguro de finalizar la asignación inicial? La unidad pasará a estado activa.');">
                            @csrf

                            <button type="submit"
                                    class="cc-btn-success cc-btn-form-action"
                                    @disabled($puntosPendientes > 0)>
                                Finalizar asignación inicial
                            </button>
                        </form>
                    </div>
                </section>

            </div>
        </div>
    </div>
</x-app-layout>