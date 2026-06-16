<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Ficha administrativa de unidad
                        </h3>
                        <p class="cc-subtitle">
                            Consulte la información completa de la unidad, su cobertura Diesel Cop y su estado administrativo.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('unidades.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a administrar
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

                            <span>Modelo: {{ $unidad->modelo_medicion_texto }}</span>
                        </div>
                    </div>

                    <div class="cc-profile-status">
                        @if ($unidad->estado === 'activo')
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
                            <h5>Identificación</h5>
                            <p>Datos principales de identificación de la unidad y empresa propietaria.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Empresa</div>
                                <div class="cc-detail-value">
                                    @if ($unidad->empresa)
                                        {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                    @else
                                        Sin empresa
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">NIT empresa</div>
                                <div class="cc-detail-value">
                                    {{ $unidad->empresa->nit ?? 'No registrado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Placa</div>
                                <div class="cc-detail-value">
                                    {{ $unidad->placa }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Marca</div>
                                <div class="cc-detail-value">
                                    {{ $unidad->marca ?: 'No registrada' }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Tanques y cobertura Diesel Cop</h5>
                            <p>Relación entre capacidad física de la unidad y cobertura protegida por el servicio.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Total de tanques</div>
                                <div class="cc-detail-value">
                                    {{ $unidad->total_tanques }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Tanques protegidos</div>
                                <div class="cc-detail-value">
                                    {{ $unidad->cantidad_tanques_con_licencia }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Capacidad total</div>
                                <div class="cc-detail-value">
                                    {{ number_format((float) $unidad->capacidad_total, 2) }} galones
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Capacidad cubierta</div>
                                <div class="cc-detail-value">
                                    {{ number_format((float) $unidad->capacidad_cubierta, 2) }} galones
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Medición operativa</h5>
                            <p>Modelo utilizado para medir el consumo operativo de la unidad.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Modelo de medición</div>
                                <div class="cc-detail-value">
                                    {{ $unidad->modelo_medicion_texto }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Estado</div>
                                <div class="cc-detail-value">
                                    @if ($unidad->estado === 'activo')
                                        <span class="cc-badge cc-badge-active">
                                            {{ $unidad->estado_texto }}
                                        </span>
                                    @else
                                        <span class="cc-badge cc-badge-inactive">
                                            {{ $unidad->estado_texto }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Control administrativo</h5>
                            <p>Información de creación, actualización e inactivación administrativa del registro.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Creado por</div>
                                <div class="cc-detail-value">
                                    {{ $unidad->creadoPor->name ?? 'No registrado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Fecha creación</div>
                                <div class="cc-detail-value">
                                    {{ $unidad->created_at?->format('d/m/Y H:i') ?? 'No registrada' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Actualizado por</div>
                                <div class="cc-detail-value">
                                    {{ $unidad->actualizadoPor->name ?? 'No registrado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Fecha actualización</div>
                                <div class="cc-detail-value">
                                    {{ $unidad->updated_at?->format('d/m/Y H:i') ?? 'No registrada' }}
                                </div>
                            </div>

                            @if ($unidad->estado === 'inactivo')
                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Inactivado por</div>
                                    <div class="cc-detail-value">
                                        {{ $unidad->inactivadoPor->name ?? 'No registrado' }}
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Fecha inactivación</div>
                                    <div class="cc-detail-value">
                                        {{ $unidad->fecha_inactivacion?->format('d/m/Y H:i') ?? 'No registrada' }}
                                    </div>
                                </div>

                                <div class="cc-detail-item cc-detail-item-wide">
                                    <div class="cc-detail-label">Motivo de inactivación</div>
                                    <div class="cc-detail-value">
                                        {{ $unidad->motivo_inactivacion ?: 'No registrado' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                </div>

                <div class="cc-actions cc-actions-split">
                    <div class="cc-actions-normal">
                        <a href="{{ route('unidades.edit', $unidad) }}" class="cc-btn-primary cc-btn-form-action">
                            Editar unidad
                        </a>

                        <a href="{{ route('unidades.administrar') }}" class="cc-btn-secondary cc-btn-form-action">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                <section class="cc-danger-zone">
                    <div class="cc-danger-zone-header">
                        <div>
                            <h5>Zona de riesgo</h5>
                            <p>
                                Modifique el estado de la unidad únicamente cuando exista una razón administrativa válida.
                            </p>
                        </div>
                    </div>

                    @if ($unidad->estado === 'activo')
                        <form method="POST"
                              action="{{ route('unidades.inactivar', $unidad) }}"
                              class="cc-danger-zone-form"
                              onsubmit="return confirm('¿Está seguro de inactivar esta unidad?');">
                            @csrf
                            @method('PATCH')

                            <div class="cc-danger-zone-field">
                                <label for="motivo_inactivacion">
                                    Motivo de inactivación <span class="cc-required">*</span>
                                </label>

                                <select id="motivo_inactivacion"
                                        name="motivo_inactivacion"
                                        class="cc-input"
                                        required>
                                    <option value="">Seleccione un motivo</option>
                                    <option value="Falta de uso">Falta de uso</option>
                                    <option value="Unidad vendida">Unidad vendida</option>
                                    <option value="Unidad fuera de operación">Unidad fuera de operación</option>
                                    <option value="Unidad reemplazada">Unidad reemplazada</option>
                                    <option value="Datos incorrectos en registro">Datos incorrectos en registro</option>
                                    <option value="Solicitud administrativa">Solicitud administrativa</option>
                                    <option value="Suspensión temporal">Suspensión temporal</option>
                                    <option value="Otro">Otro</option>
                                </select>

                                @error('motivo_inactivacion')
                                    <div class="cc-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="cc-btn-danger cc-btn-form-action">
                                Inactivar unidad
                            </button>
                        </form>
                    @else
                        <form method="POST"
                              action="{{ route('unidades.reactivar', $unidad) }}"
                              class="cc-danger-zone-form"
                              onsubmit="return confirm('¿Está seguro de reactivar esta unidad?');">
                            @csrf
                            @method('PATCH')

                            <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                Esta unidad se encuentra inactiva. Puede reactivarla para permitir nuevamente su uso administrativo.
                            </p>

                            <button type="submit" class="cc-btn-success cc-btn-form-action">
                                Reactivar unidad
                            </button>
                        </form>
                    @endif
                </section>

            </div>
        </div>
    </div>
</x-app-layout>