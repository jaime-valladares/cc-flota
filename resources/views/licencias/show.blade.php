<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Ficha administrativa de licencia
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Consulte la cobertura Diesel Cop asociada a la unidad y su vigencia operativa.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('licencias.administrar') }}" class="cc-btn-secondary cc-btn-wide">
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
                            Licencia Diesel Cop
                        </div>

                        <div class="cc-profile-title">
                            {{ $licencia->unidad->placa ?? 'Sin placa' }}
                        </div>

                        <div class="cc-profile-meta">
                            <span>{{ $licencia->unidad->marca ?? 'Sin marca registrada' }}</span>

                            @if ($licencia->empresa)
                                <span>
                                    Empresa: {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                </span>
                            @else
                                <span>
                                    Empresa: Sin empresa
                                </span>
                            @endif

                            <span>
                                Vence: {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'No registrado' }}
                            </span>
                        </div>
                    </div>

                    <div class="cc-profile-status">
                        @if ($licencia->estado === 'activa')
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

                <div class="cc-detail-layout">

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Unidad licenciada
                            </h5>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Empresa
                                </div>
                                <div class="cc-detail-value">
                                    @if ($licencia->empresa)
                                        {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                    @else
                                        Sin empresa
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    NIT empresa
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->empresa->nit ?? 'No registrado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Placa
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->unidad->placa ?? 'Sin placa' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Marca
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->unidad->marca ?? 'No registrada' }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Vigencia
                            </h5>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Período
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->periodo_vigencia_texto }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Estado
                                </div>
                                <div class="cc-detail-value">
                                    @if ($licencia->estado === 'activa')
                                        <span class="cc-badge cc-badge-active">
                                            {{ $licencia->estado_texto }}
                                        </span>
                                    @else
                                        <span class="cc-badge cc-badge-inactive">
                                            {{ $licencia->estado_texto }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Fecha activación
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->fecha_activacion?->format('d/m/Y') ?? 'No registrada' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Fecha vencimiento
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'No registrada' }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Puntos de seguridad
                            </h5>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Plantilla
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->plantilla_puntos_seguridad_texto }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Puntos esperados
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->cantidad_puntos_seguridad_esperados }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Tanques protegidos
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->unidad->cantidad_tanques_con_licencia ?? 'No registrado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Capacidad cubierta
                                </div>
                                <div class="cc-detail-value">
                                    @if ($licencia->unidad)
                                        {{ number_format((float) $licencia->unidad->capacidad_cubierta, 2) }} galones
                                    @else
                                        No registrada
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Control administrativo
                            </h5>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Creado por
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->creadoPor->name ?? 'No registrado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Fecha creación
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->created_at?->format('d/m/Y H:i') ?? 'No registrada' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Actualizado por
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->actualizadoPor->name ?? 'No registrado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Fecha actualización
                                </div>
                                <div class="cc-detail-value">
                                    {{ $licencia->updated_at?->format('d/m/Y H:i') ?? 'No registrada' }}
                                </div>
                            </div>

                            @if ($licencia->estado === 'inactiva')
                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">
                                        Inactivada por
                                    </div>
                                    <div class="cc-detail-value">
                                        {{ $licencia->inactivadoPor->name ?? 'No registrado' }}
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">
                                        Fecha inactivación
                                    </div>
                                    <div class="cc-detail-value">
                                        {{ $licencia->fecha_inactivacion?->format('d/m/Y H:i') ?? 'No registrada' }}
                                    </div>
                                </div>

                                <div class="cc-detail-item cc-detail-item-wide">
                                    <div class="cc-detail-label">
                                        Motivo de inactivación
                                    </div>
                                    <div class="cc-detail-value">
                                        {{ $licencia->motivo_inactivacion ?: 'No registrado' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                </div>

                <div class="cc-actions cc-actions-split">
                    <div class="cc-actions-normal">
                        <a href="{{ route('licencias.edit', $licencia) }}" class="cc-btn-primary cc-btn-form-action">
                            Editar licencia
                        </a>

                        <a href="{{ route('licencias.administrar') }}" class="cc-btn-secondary cc-btn-form-action">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                <section class="cc-danger-zone">
                    <div class="cc-danger-zone-header">
                        <div>
                            <h5>
                                Zona de riesgo
                            </h5>
                            <p>
                                Modifique el estado de la licencia únicamente cuando exista una razón administrativa válida.
                            </p>
                        </div>
                    </div>

                    @if ($licencia->estado === 'activa')
                        <form method="POST"
                              action="{{ route('licencias.inactivar', $licencia) }}"
                              class="cc-danger-zone-form"
                              onsubmit="return confirm('¿Está seguro de inactivar esta licencia?');">
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
                                    <option value="Fin de cobertura">Fin de cobertura</option>
                                    <option value="Falta de pago">Falta de pago</option>
                                    <option value="Solicitud administrativa">Solicitud administrativa</option>
                                    <option value="Cambio operativo">Cambio operativo</option>
                                    <option value="Unidad fuera de servicio">Unidad fuera de servicio</option>
                                    <option value="Empresa inactiva">Empresa inactiva</option>
                                    <option value="Corrección de registro">Corrección de registro</option>
                                    <option value="Otro">Otro</option>
                                </select>

                                @error('motivo_inactivacion')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button type="submit" class="cc-btn-danger cc-btn-form-action">
                                Inactivar licencia
                            </button>
                        </form>
                    @else
                        <form method="POST"
                              action="{{ route('licencias.reactivar', $licencia) }}"
                              class="cc-danger-zone-form"
                              onsubmit="return confirm('¿Está seguro de reactivar esta licencia?');">
                            @csrf
                            @method('PATCH')

                            <div class="cc-danger-zone-field">
                                <label for="periodo_vigencia_meses">
                                    Período de vigencia <span class="cc-required">*</span>
                                </label>

                                <select id="periodo_vigencia_meses"
                                        name="periodo_vigencia_meses"
                                        class="cc-input"
                                        required>
                                    <option value="">Seleccione un período</option>
                                    <option value="3">3 meses</option>
                                    <option value="6">6 meses</option>
                                    <option value="12">12 meses</option>
                                </select>

                                @error('periodo_vigencia_meses')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-danger-zone-field">
                                <label for="fecha_activacion">
                                    Fecha de activación <span class="cc-required">*</span>
                                </label>

                                <input id="fecha_activacion"
                                       type="date"
                                       name="fecha_activacion"
                                       value="{{ now()->format('Y-m-d') }}"
                                       class="cc-input"
                                       required>

                                @error('fecha_activacion')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button type="submit" class="cc-btn-success cc-btn-form-action">
                                Reactivar licencia
                            </button>
                        </form>
                    @endif
                </section>

            </div>
        </div>
    </div>
</x-app-layout>