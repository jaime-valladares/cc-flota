<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Ficha administrativa de ruta
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('rutas.show.ventana', $ruta) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('rutas.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <div class="cc-profile-summary">
                    <div>
                        <div class="cc-profile-eyebrow">
                            Ruta operativa
                        </div>

                        <h4 class="cc-profile-title">
                            {{ $ruta->ruta }}
                        </h4>

                        <div class="cc-profile-meta">
                            <span>
                                Empresa: {{ $ruta->empresa->nombre_comercial ?: $ruta->empresa->nombre_legal }}
                            </span>

                            <span>
                                Origen: {{ $ruta->puntoOrigen->nombre }}
                            </span>

                            <span>
                                Destino: {{ $ruta->puntoDestino->nombre }}
                            </span>
                        </div>
                    </div>

                    <div class="cc-profile-status">
                        @if ($ruta->estado === 'activo')
                            <span class="cc-badge cc-badge-active">
                                Activo
                            </span>
                        @else
                            <span class="cc-badge cc-badge-inactive">
                                Inactivo
                            </span>
                        @endif
                    </div>
                </div>

                <div class="cc-detail-layout">

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Identificación de la ruta</h5>
                            <p>Datos principales de la ruta construida entre puntos operativos de la empresa.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Empresa
                                </div>
                                <div class="cc-detail-value">
                                    {{ $ruta->empresa->nombre_comercial ?: $ruta->empresa->nombre_legal }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Ruta
                                </div>
                                <div class="cc-detail-value">
                                    {{ $ruta->ruta }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Punto de origen
                                </div>
                                <div class="cc-detail-value">
                                    {{ $ruta->puntoOrigen->nombre }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Punto de destino
                                </div>
                                <div class="cc-detail-value">
                                    {{ $ruta->puntoDestino->nombre }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Valores estimados</h5>
                            <p>Parámetros operativos utilizados como referencia para cálculos de consumo por viaje.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Kilómetros estimados
                                </div>
                                <div class="cc-detail-value">
                                    {{ number_format((float) $ruta->kilometros_estimados, 2) }} km
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Galones estimados
                                </div>
                                <div class="cc-detail-value">
                                    {{ number_format((float) $ruta->galones_estimados, 2) }} gal
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Rendimiento estimado
                                </div>
                                <div class="cc-detail-value">
                                    @if ((float) $ruta->galones_estimados > 0)
                                        {{ number_format((float) $ruta->kilometros_estimados / (float) $ruta->galones_estimados, 2) }} km/gal
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Control administrativo</h5>
                            <p>Información de estado, creación, actualización e inactivación del registro.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Estado actual
                                </div>
                                <div class="cc-detail-value">
                                    @if ($ruta->estado === 'activo')
                                        <span class="cc-badge cc-badge-active">
                                            Activo
                                        </span>
                                    @else
                                        <span class="cc-badge cc-badge-inactive">
                                            Inactivo
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Fecha de creación
                                </div>
                                <div class="cc-detail-value">
                                    {{ optional($ruta->fecha_creacion)->format('d/m/Y H:i') ?? '—' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Creado por
                                </div>
                                <div class="cc-detail-value">
                                    {{ optional($ruta->creadoPor)->name ?? '—' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Fecha de actualización
                                </div>
                                <div class="cc-detail-value">
                                    {{ optional($ruta->fecha_actualizacion)->format('d/m/Y H:i') ?? '—' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Actualizado por
                                </div>
                                <div class="cc-detail-value">
                                    {{ optional($ruta->actualizadoPor)->name ?? '—' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Fecha de inactivación
                                </div>
                                <div class="cc-detail-value">
                                    {{ optional($ruta->fecha_inactivacion)->format('d/m/Y H:i') ?? '—' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Inactivado por
                                </div>
                                <div class="cc-detail-value">
                                    {{ optional($ruta->inactivadoPor)->name ?? '—' }}
                                </div>
                            </div>

                            <div class="cc-detail-item cc-detail-item-wide">
                                <div class="cc-detail-label">
                                    Motivo de inactivación
                                </div>
                                <div class="cc-detail-value">
                                    {{ $ruta->motivo_inactivacion ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </section>

                </div>

                <div class="cc-actions cc-actions-split">
                    <div class="cc-actions-normal">
                        <a href="{{ route('rutas.edit', $ruta) }}" class="cc-btn-primary cc-btn-form-action">
                            Editar ruta
                        </a>

                        <a href="{{ route('rutas.administrar') }}" class="cc-btn-secondary cc-btn-form-action">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                @if ($ruta->estado === 'activo')
                    <section class="cc-danger-zone">
                        <div class="cc-danger-zone-header">
                            <div>
                                <h5>Zona de riesgo</h5>
                                <p>
                                    Inactive la ruta únicamente cuando exista una razón operativa o administrativa válida.
                                </p>
                            </div>
                        </div>

                        <form method="POST"
                              action="{{ route('rutas.inactivar', $ruta) }}"
                              class="cc-danger-zone-form"
                              onsubmit="return confirmarInactivacionRuta();">
                            @csrf
                            @method('PATCH')

                            <div class="cc-danger-zone-field">
                                <label for="motivo_inactivacion">
                                    Motivo de inactivación
                                </label>

                                <select
                                    id="motivo_inactivacion"
                                    name="motivo_inactivacion"
                                    class="cc-input"
                                    required
                                >
                                    <option value="">Seleccione un motivo</option>
                                    <option value="No continúa en uso">No continúa en uso</option>
                                    <option value="Cambio operativo">Cambio operativo</option>
                                    <option value="Datos incorrectos en registro">Datos incorrectos en registro</option>
                                    <option value="Solicitud del cliente">Solicitud del cliente</option>
                                    <option value="Suspensión administrativa">Suspensión administrativa</option>
                                    <option value="Otro">Otro</option>
                                </select>

                                @error('motivo_inactivacion')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button type="submit" class="cc-btn-danger cc-btn-form-action">
                                Inactivar ruta
                            </button>
                        </form>
                    </section>
                @else
                    <div class="cc-actions">
                        <form method="POST"
                              action="{{ route('rutas.reactivar', $ruta) }}"
                              onsubmit="return confirm('¿Seguro que deseas reactivar esta ruta?');">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="cc-btn-success cc-btn-form-action">
                                Reactivar ruta
                            </button>
                        </form>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <script>
        function confirmarInactivacionRuta() {
            const motivo = document.getElementById('motivo_inactivacion').value;

            if (!motivo) {
                alert('Debe seleccionar un motivo de inactivación.');
                return false;
            }

            return confirm(`¿Seguro que deseas inactivar esta ruta por el motivo "${motivo}"?`);
        }
    </script>
</x-app-layout>