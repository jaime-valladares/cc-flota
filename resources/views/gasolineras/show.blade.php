<x-app-layout>
    @php
        $empresaNombre = $gasolinera->empresa?->nombre_comercial ?: $gasolinera->empresa?->nombre_legal;
    @endphp

    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact" style="font-size: 1.8rem; line-height: 1.2;">
                            Administrar gasolinera
                        </h3>

                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras.show.ventana', $gasolinera) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('gasolineras.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="cc-alert cc-alert-danger">
                        <ul class="cc-alert-list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="cc-profile-summary" style="margin-bottom: 1.1rem;">
                    <div style="min-width: 0;">
                        <div class="cc-profile-eyebrow">
                            Gasolinera interna
                        </div>

                        <div class="cc-profile-title">
                            {{ $gasolinera->nombre }}
                        </div>

                        <div class="cc-profile-meta">
                            <span>
                                {{ $empresaNombre }}
                            </span>

                            <span>
                                {{ $gasolinera->direccion }}
                            </span>
                        </div>
                    </div>

                    <div class="cc-profile-status">
                        <span class="cc-badge {{ $gasolinera->estado === 'activa' ? 'cc-badge-active' : 'cc-badge-inactive' }}">
                            {{ ucfirst($gasolinera->estado) }}
                        </span>
                    </div>
                </div>

                <div class="cc-summary-strip">
                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Capacidad</span>
                        <span class="cc-summary-strip-value">{{ number_format($capacidadTotal, 2) }} gal</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Disponible</span>
                        <span class="cc-summary-strip-value">{{ number_format($volumenActual, 2) }} gal</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Disponibilidad</span>
                        <span class="cc-summary-strip-value">{{ number_format($porcentajeDisponible, 2) }}%</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Alertas</span>
                        <span class="cc-summary-strip-value {{ $tanquesBajoAlerta > 0 ? 'cc-summary-strip-value-danger' : 'cc-summary-strip-value-success' }}">
                            {{ $tanquesBajoAlerta }}
                        </span>
                    </div>
                </div>

                <div class="cc-detail-layout">

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Datos de gasolinera</h5>
                            <p>Actualice la identificación, ubicación y contacto operativo desde un solo punto.</p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            <form method="POST" action="{{ route('gasolineras.update', $gasolinera) }}" novalidate>
                                @csrf
                                @method('PUT')

                                <input type="hidden" name="empresa_id" value="{{ $gasolinera->empresa_id }}">

                                <div class="cc-grid cc-grid-compact">

                                    <div class="cc-form-section-slim">
                                        <div class="cc-form-section-title">
                                            Identificación
                                        </div>
                                    </div>

                                    <div class="cc-field">
                                        <label for="empresa_id_visible">
                                            Empresa
                                        </label>

                                        <input
                                            id="empresa_id_visible"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $empresaNombre }}"
                                            disabled
                                        >
                                    </div>

                                    <div class="cc-field">
                                        <label for="nombre">
                                            Nombre de gasolinera <span class="cc-required">*</span>
                                        </label>

                                        <input
                                            id="nombre"
                                            type="text"
                                            name="nombre"
                                            value="{{ old('nombre', $gasolinera->nombre) }}"
                                            class="cc-input"
                                            maxlength="150"
                                            required
                                            placeholder="Ej. Gasolinera central"
                                        >

                                        @error('nombre')
                                            <div class="cc-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="cc-field cc-col-span-2">
                                        <label for="direccion">
                                            Dirección <span class="cc-required">*</span>
                                        </label>

                                        <input
                                            id="direccion"
                                            type="text"
                                            name="direccion"
                                            value="{{ old('direccion', $gasolinera->direccion) }}"
                                            class="cc-input"
                                            maxlength="255"
                                            required
                                            placeholder="Dirección física de la gasolinera"
                                        >

                                        @error('direccion')
                                            <div class="cc-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="cc-form-section-slim">
                                        <div class="cc-form-section-title">
                                            Contacto operativo
                                        </div>
                                    </div>

                                    <div class="cc-field">
                                        <label for="encargado">
                                            Encargado
                                        </label>

                                        <input
                                            id="encargado"
                                            type="text"
                                            name="encargado"
                                            value="{{ old('encargado', $gasolinera->encargado) }}"
                                            class="cc-input"
                                            maxlength="150"
                                            placeholder="Nombre del encargado"
                                        >

                                        @error('encargado')
                                            <div class="cc-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="telefono">
                                            Teléfono
                                        </label>

                                        <input
                                            id="telefono"
                                            type="text"
                                            name="telefono"
                                            value="{{ old('telefono', $gasolinera->telefono) }}"
                                            class="cc-input"
                                            maxlength="9"
                                            placeholder="0000-0000"
                                        >

                                        @error('telefono')
                                            <div class="cc-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="cc-field cc-col-span-2">
                                        <label for="correo">
                                            Correo de alerta
                                        </label>

                                        <input
                                            id="correo"
                                            type="email"
                                            name="correo"
                                            value="{{ old('correo', $gasolinera->correo) }}"
                                            class="cc-input"
                                            maxlength="150"
                                            placeholder="encargado@empresa.com"
                                        >

                                        @error('correo')
                                            <div class="cc-error">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="cc-actions cc-actions-compact">
                                    <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                        Guardar cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Tanques asociados</h5>
                            <p>Capacidad instalada, inventario actual y estado operativo por tanque.</p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            @if ($tanques->isEmpty())
                                <div class="cc-empty-panel cc-empty-panel-compact">
                                    <h5>Sin tanques registrados</h5>
                                    <p>Esta gasolinera no tiene tanques asociados.</p>
                                </div>
                            @else
                                <div class="cc-results-list">
                                    @foreach ($tanques as $tanque)
                                        @php
                                            $porcentajeTanque = $tanque->porcentajeDisponible();
                                            $bajoAlerta = $tanque->estaBajoAlerta();
                                        @endphp

                                        <article class="cc-result-card cc-result-card-compact">
                                            <div style="display: grid; grid-template-columns: minmax(0, 1.25fr) minmax(0, 0.85fr) minmax(0, 0.85fr) minmax(0, 0.85fr); gap: 1rem; align-items: center;">
                                                <div>
                                                    <div style="display: flex; flex-wrap: wrap; align-items: center; gap: 0.5rem;">
                                                        <h5 class="cc-result-title cc-cell-truncate">
                                                            {{ $tanque->nombre }}
                                                        </h5>

                                                        <span class="cc-badge {{ $tanque->estado === 'activo' ? 'cc-badge-active' : 'cc-badge-inactive' }}">
                                                            {{ ucfirst($tanque->estado) }}
                                                        </span>

                                                        @if ($bajoAlerta)
                                                            <span class="cc-badge cc-badge-warning">
                                                                Bajo mínimo
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="cc-result-value-muted">
                                                        {{ number_format((float) $tanque->volumen_actual, 2) }} gal disponibles de {{ number_format((float) $tanque->capacidad_total, 2) }} gal.
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Mínimo alerta
                                                    </div>

                                                    <div class="cc-result-value">
                                                        {{ number_format((float) $tanque->volumen_minimo_alerta, 2) }} gal
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Disponibilidad
                                                    </div>

                                                    <div class="cc-result-value">
                                                        {{ number_format($porcentajeTanque, 2) }}%
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Estado operativo
                                                    </div>

                                                    <div class="cc-result-value">
                                                        {{ $tanque->estado === 'activo' ? 'Disponible' : 'No disponible' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Agregar tanque</h5>
                            <p>Registre un tanque adicional para esta gasolinera activa.</p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            @if ($gasolinera->estado === 'activa')
                                <form method="POST" action="{{ route('gasolineras.tanques.store', $gasolinera) }}" novalidate>
                                    @csrf

                                    <div class="cc-grid cc-grid-compact">
                                        <div class="cc-field">
                                            <label for="nombre_tanque">
                                                Nombre del tanque <span class="cc-required">*</span>
                                            </label>

                                            <input
                                                id="nombre_tanque"
                                                type="text"
                                                name="nombre"
                                                class="cc-input"
                                                maxlength="100"
                                                required
                                                placeholder="Ej. Tanque 2"
                                            >
                                        </div>

                                        <div class="cc-field">
                                            <label for="capacidad_total">
                                                Capacidad total (galones) <span class="cc-required">*</span>
                                            </label>

                                            <input
                                                id="capacidad_total"
                                                type="number"
                                                name="capacidad_total"
                                                class="cc-input"
                                                min="0.01"
                                                step="0.01"
                                                required
                                                placeholder="Ej. 10000.00"
                                            >
                                        </div>

                                        <div class="cc-field">
                                            <label for="volumen_actual">
                                                Volumen actual (galones) <span class="cc-required">*</span>
                                            </label>

                                            <input
                                                id="volumen_actual"
                                                type="number"
                                                name="volumen_actual"
                                                class="cc-input"
                                                min="0"
                                                step="0.01"
                                                required
                                                placeholder="Ej. 8000.00"
                                            >
                                        </div>

                                        <div class="cc-field">
                                            <label for="volumen_minimo_alerta">
                                                Volumen mínimo de alerta (galones) <span class="cc-required">*</span>
                                            </label>

                                            <input
                                                id="volumen_minimo_alerta"
                                                type="number"
                                                name="volumen_minimo_alerta"
                                                class="cc-input"
                                                min="0"
                                                step="0.01"
                                                required
                                                placeholder="Ej. 1000.00"
                                            >
                                        </div>
                                    </div>

                                    <div class="cc-actions cc-actions-compact">
                                        <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                            Agregar tanque
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="cc-empty-panel cc-empty-panel-compact">
                                    <h5>Gasolinera inactiva</h5>
                                    <p>No se pueden agregar tanques a una gasolinera inactiva.</p>
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Estado de gasolinera</h5>
                            <p>Administre únicamente la activación o inactivación del registro principal.</p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            @if ($gasolinera->estado === 'activa')
                                <form method="POST" action="{{ route('gasolineras.inactivar', $gasolinera) }}" class="cc-inline-action-form">
                                    @csrf
                                    @method('PATCH')

                                    <div class="cc-inline-action-field">
                                        <label for="motivo_inactivacion">
                                            Motivo de inactivación
                                        </label>

                                        <select id="motivo_inactivacion" name="motivo_inactivacion" class="cc-input" required>
                                            <option value="">Seleccione un motivo</option>
                                            <option value="Mantenimiento operativo">Mantenimiento operativo</option>
                                            <option value="Cierre de gasolinera">Cierre de gasolinera</option>
                                            <option value="No continúa en operación">No continúa en operación</option>
                                            <option value="Datos incorrectos en registro">Datos incorrectos en registro</option>
                                            <option value="Suspensión administrativa">Suspensión administrativa</option>
                                            <option value="Solicitud del cliente">Solicitud del cliente</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="cc-btn-danger cc-btn-form-action">
                                        Inactivar gasolinera
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('gasolineras.reactivar', $gasolinera) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit" class="cc-btn-success cc-btn-form-action">
                                        Reactivar gasolinera
                                    </button>
                                </form>
                            @endif
                        </div>
                    </section>

                </div>

            </div>
        </div>
    </div>

    <script>
        function formatPhone(value) {
            const digits = value.replace(/\D/g, '').slice(0, 8);

            if (digits.length <= 4) {
                return digits;
            }

            return `${digits.slice(0, 4)}-${digits.slice(4)}`;
        }

        const telefonoInput = document.getElementById('telefono');

        if (telefonoInput) {
            telefonoInput.addEventListener('input', function () {
                this.value = formatPhone(this.value);
            });
        }
    </script>
</x-app-layout>