<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Ficha administrativa de usuario
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Consulta consolidada del usuario, su empresa asociada, rol asignado y estado administrativo.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('usuarios.administrar') }}" class="cc-btn-secondary cc-btn-wide">
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
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-800">
                        @foreach ($errors->all() as $error)
                            <div>{{ $error }}</div>
                        @endforeach
                    </div>
                @endif

                <div class="cc-profile-summary">
                    <div>
                        <div class="cc-profile-eyebrow">
                            Usuario
                        </div>

                        <h4 class="cc-profile-title">
                            {{ $usuario->name }} {{ $usuario->apellido }}
                        </h4>

                        <div class="cc-profile-meta">
                            <span>Correo: {{ $usuario->email }}</span>

                            @if ($usuario->empresa)
                                <span>Empresa: {{ $usuario->empresa->nombre_comercial ?: $usuario->empresa->nombre_legal }}</span>
                            @else
                                <span>Empresa: Diesel Cop</span>
                            @endif

                            @if ($usuario->role)
                                <span>Rol: {{ $usuario->role->nombre }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="cc-profile-status">
                        @if ($usuario->estado === 'activo')
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
                            <p>Datos principales de identificación del usuario.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Nombre</div>
                                <div class="cc-detail-value">{{ $usuario->name }}</div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Apellido</div>
                                <div class="cc-detail-value">{{ $usuario->apellido ?? '—' }}</div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Correo electrónico</div>
                                <div class="cc-detail-value">{{ $usuario->email }}</div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Teléfono</div>
                                <div class="cc-detail-value">{{ $usuario->telefono ?? '—' }}</div>
                            </div>

                            <div class="cc-detail-item cc-detail-item-wide">
                                <div class="cc-detail-label">Cargo</div>
                                <div class="cc-detail-value">{{ $usuario->cargo ?? '—' }}</div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Acceso y alcance</h5>
                            <p>Clasificación del usuario, empresa asociada y rol asignado.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Tipo de usuario</div>
                                <div class="cc-detail-value">
                                    @if ($usuario->tipo_usuario === 'diesel_cop')
                                        Diesel Cop
                                    @else
                                        Empresa
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Empresa</div>
                                <div class="cc-detail-value">
                                    @if ($usuario->empresa)
                                        {{ $usuario->empresa->nombre_comercial ?: $usuario->empresa->nombre_legal }}
                                    @else
                                        Diesel Cop
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Rol</div>
                                <div class="cc-detail-value">
                                    {{ $usuario->role->nombre ?? '—' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Código de rol</div>
                                <div class="cc-detail-value">
                                    {{ $usuario->role->codigo ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Control administrativo</h5>
                            <p>Información de estado, acceso, inactivación y trazabilidad básica del registro.</p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Estado actual</div>
                                <div class="cc-detail-value">
                                    @if ($usuario->estado === 'activo')
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
                                <div class="cc-detail-label">Último acceso</div>
                                <div class="cc-detail-value">{{ $usuario->ultimo_acceso ?? '—' }}</div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Fecha de inactivación</div>
                                <div class="cc-detail-value">{{ $usuario->fecha_inactivacion ?? '—' }}</div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Inactivado por</div>
                                <div class="cc-detail-value">
                                    @if ($usuario->inactivadoPor)
                                        {{ $usuario->inactivadoPor->name }} {{ $usuario->inactivadoPor->apellido }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item cc-detail-item-wide">
                                <div class="cc-detail-label">Motivo de inactivación</div>
                                <div class="cc-detail-value">{{ $usuario->motivo_inactivacion ?? '—' }}</div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Creado por</div>
                                <div class="cc-detail-value">
                                    @if ($usuario->creadoPor)
                                        {{ $usuario->creadoPor->name }} {{ $usuario->creadoPor->apellido }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">Actualizado por</div>
                                <div class="cc-detail-value">
                                    @if ($usuario->actualizadoPor)
                                        {{ $usuario->actualizadoPor->name }} {{ $usuario->actualizadoPor->apellido }}
                                    @else
                                        —
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                </div>

                <div class="cc-actions cc-actions-split">
                    <div class="cc-actions-normal">
                        <a href="{{ route('usuarios.edit', $usuario) }}" class="cc-btn-primary cc-btn-form-action">
                            Editar usuario
                        </a>

                        <a href="{{ route('usuarios.administrar') }}" class="cc-btn-secondary cc-btn-form-action">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                @if ($usuario->estado === 'activo')
                    <section class="cc-danger-zone">
                        <div class="cc-danger-zone-header">
                            <div>
                                <h5>Zona de riesgo</h5>
                                <p>
                                    Inactive el usuario únicamente cuando exista una razón administrativa válida.
                                </p>
                            </div>
                        </div>

                        <form method="POST"
                              action="{{ route('usuarios.inactivar', $usuario) }}"
                              class="cc-danger-zone-form"
                              onsubmit="return confirmarInactivacionUsuario();">
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
                                    <option value="Falta de uso">Falta de uso</option>
                                    <option value="Cambio de puesto">Cambio de puesto</option>
                                    <option value="Salida de la empresa">Salida de la empresa</option>
                                    <option value="Acceso duplicado">Acceso duplicado</option>
                                    <option value="Datos incorrectos en registro">Datos incorrectos en registro</option>
                                    <option value="Solicitud administrativa">Solicitud administrativa</option>
                                    <option value="Suspensión temporal">Suspensión temporal</option>
                                    <option value="Otro">Otro</option>
                                </select>

                                @error('motivo_inactivacion')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button type="submit" class="cc-btn-danger cc-btn-form-action">
                                Inactivar usuario
                            </button>
                        </form>
                    </section>
                @else
                    <div class="cc-actions">
                        <form method="POST"
                              action="{{ route('usuarios.reactivar', $usuario) }}"
                              onsubmit="return confirm('¿Seguro que deseas reactivar este usuario?');">
                            @csrf
                            @method('PATCH')

                            <button type="submit" class="cc-btn-success cc-btn-form-action">
                                Reactivar usuario
                            </button>
                        </form>
                    </div>
                @endif

            </div>
        </div>
    </div>

    <script>
        function confirmarInactivacionUsuario() {
            const motivo = document.getElementById('motivo_inactivacion').value;

            if (!motivo) {
                alert('Debe seleccionar un motivo de inactivación.');
                return false;
            }

            return confirm(`¿Seguro que deseas inactivar este usuario por el motivo "${motivo}"?`);
        }
    </script>
</x-app-layout>