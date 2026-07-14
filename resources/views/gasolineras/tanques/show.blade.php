<x-app-layout>
    @php
        $empresaNombre = $gasolinera->empresa?->nombre_comercial ?: $gasolinera->empresa?->nombre_legal;
        $bajoAlerta = $bajoAlerta ?? $tanque->estaBajoAlerta();
        $tanqueActivo = $tanque->estado === 'activo';
    @endphp

    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Administrar tanque
                        </h3>

                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras.tanques.show.ventana', [$gasolinera, $tanque]) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('gasolineras.tanques.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a gestión
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
                            Tanque interno
                        </div>

                        <div class="cc-profile-title">
                            {{ $tanque->nombre }}
                        </div>

                        <div class="cc-profile-meta">
                            <span>
                                {{ $gasolinera->nombre }}
                            </span>

                            <span>
                                {{ $empresaNombre }}
                            </span>
                        </div>
                    </div>

                    <div class="cc-profile-status">
                        <span class="cc-badge {{ $tanque->estado === 'activo' ? 'cc-badge-active' : 'cc-badge-inactive' }}">
                            {{ ucfirst($tanque->estado) }}
                        </span>

                        @if ($bajoAlerta)
                            <span class="cc-badge cc-badge-warning">
                                Bajo mínimo
                            </span>
                        @endif
                    </div>
                </div>

                <div class="cc-summary-strip">
                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Capacidad
                        </span>
                        <span class="cc-summary-strip-value">
                            {{ number_format($capacidadTotal, 2) }} gal
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Disponible
                        </span>
                        <span class="cc-summary-strip-value">
                            {{ number_format($volumenActual, 2) }} gal
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Disponibilidad
                        </span>
                        <span class="cc-summary-strip-value">
                            {{ number_format($porcentajeDisponible, 2) }}%
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Mínimo alerta
                        </span>
                        <span class="cc-summary-strip-value {{ $bajoAlerta ? 'cc-summary-strip-value-danger' : 'cc-summary-strip-value-success' }}">
                            {{ number_format($volumenMinimoAlerta, 2) }} gal
                        </span>
                    </div>
                </div>

                <div class="cc-detail-layout">

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Datos del tanque
                            </h5>

                            <p>
                                @if ($tanqueActivo)
                                    Actualice únicamente los datos controlados del tanque. El volumen actual se modifica mediante movimientos de inventario.
                                @else
                                    El tanque permanece disponible para consulta, pero no puede editarse ni utilizarse mientras esté inactivo.
                                @endif
                            </p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            <form method="POST" action="{{ route('gasolineras.tanques.update', [$gasolinera, $tanque]) }}" novalidate>
                                @csrf
                                @method('PUT')

                                <div class="cc-grid cc-grid-compact">

                                    <div class="cc-form-section-slim">
                                        <div class="cc-form-section-title">
                                            Identificación
                                        </div>
                                    </div>

                                    <div class="cc-field">
                                        <label for="empresa_visible">
                                            Empresa
                                        </label>

                                        <input
                                            id="empresa_visible"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $empresaNombre }}"
                                            disabled
                                        >
                                    </div>

                                    <div class="cc-field">
                                        <label for="gasolinera_visible">
                                            Gasolinera
                                        </label>

                                        <input
                                            id="gasolinera_visible"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $gasolinera->nombre }}"
                                            disabled
                                        >
                                    </div>

                                    <div class="cc-field">
                                        <label for="nombre">
                                            Nombre del tanque <span class="cc-required">*</span>
                                        </label>

                                        <input
                                            id="nombre"
                                            type="text"
                                            name="nombre"
                                            value="{{ old('nombre', $tanque->nombre) }}"
                                            class="cc-input"
                                            maxlength="100"
                                            required
                                            placeholder="Ej. Tanque 1"
                                            @disabled(! $tanqueActivo)
                                        >

                                        @error('nombre')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-form-section-slim">
                                        <div class="cc-form-section-title">
                                            Capacidad y alerta
                                        </div>
                                    </div>

                                    <div class="cc-field">
                                        <label for="capacidad_total">
                                            Capacidad total (galones) <span class="cc-required">*</span>
                                        </label>

                                        <input
                                            id="capacidad_total"
                                            type="number"
                                            name="capacidad_total"
                                            value="{{ old('capacidad_total', $tanque->capacidad_total) }}"
                                            class="cc-input"
                                            min="0.01"
                                            step="0.01"
                                            required
                                            placeholder="Ej. 10000.00"
                                            @disabled(! $tanqueActivo)
                                        >

                                        @error('capacidad_total')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="volumen_actual_visible">
                                            Volumen actual (galones)
                                        </label>

                                        <input
                                            id="volumen_actual_visible"
                                            type="text"
                                            class="cc-input"
                                            value="{{ number_format($volumenActual, 2) }}"
                                            disabled
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
                                            value="{{ old('volumen_minimo_alerta', $tanque->volumen_minimo_alerta) }}"
                                            class="cc-input"
                                            min="0"
                                            step="0.01"
                                            required
                                            placeholder="Ej. 1000.00"
                                            @disabled(! $tanqueActivo)
                                        >

                                        @error('volumen_minimo_alerta')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                </div>

                                @if ($tanqueActivo)
                                    <div class="cc-actions cc-actions-compact">
                                        <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                            Guardar cambios
                                        </button>

                                        <a href="{{ route('gasolineras.tanques.recargas.create', ['gasolinera' => $gasolinera, 'tanque_id' => $tanque->id]) }}"
                                           class="cc-btn-secondary cc-btn-form-action">
                                            Recargar tanque
                                        </a>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Estado operativo
                            </h5>

                            <p>
                                Administre la disponibilidad del tanque dentro de la gasolinera seleccionada.
                            </p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            @if ($tanqueActivo)
                                <form method="POST"
                                      action="{{ route('gasolineras.tanques.inactivar', [$gasolinera, $tanque]) }}"
                                      class="cc-inline-action-form">
                                    @csrf
                                    @method('PATCH')

                                    <div class="cc-inline-action-field">
                                        <label for="motivo_inactivacion">
                                            Motivo de inactivación
                                        </label>

                                        <select id="motivo_inactivacion" name="motivo_inactivacion" class="cc-input" required>
                                            <option value="">Seleccione un motivo</option>
                                            <option value="Mantenimiento">Mantenimiento</option>
                                            <option value="Daño operativo">Daño operativo</option>
                                            <option value="Fuera de servicio">Fuera de servicio</option>
                                            <option value="Datos incorrectos en registro">Datos incorrectos en registro</option>
                                            <option value="Solicitud del cliente">Solicitud del cliente</option>
                                            <option value="Otro">Otro</option>
                                        </select>
                                    </div>

                                    <button type="submit" class="cc-btn-danger cc-btn-form-action">
                                        Inactivar tanque
                                    </button>
                                </form>
                            @else
                                <div class="cc-callout cc-callout-warning" style="margin-bottom: 1rem;">
                                    <span class="cc-callout-marker"></span>

                                    <div>
                                        <div class="cc-callout-title">
                                            Tanque inactivo
                                        </div>

                                        <div class="cc-callout-text">
                                            Mientras permanezca inactivo, el tanque solo puede consultarse. No admite edición, recargas ni otras operaciones.
                                        </div>
                                    </div>
                                </div>

                                <form method="POST" action="{{ route('gasolineras.tanques.reactivar', [$gasolinera, $tanque]) }}">
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit" class="cc-btn-success cc-btn-form-action">
                                        Reactivar tanque
                                    </button>
                                </form>
                            @endif
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Movimientos recientes
                            </h5>

                            <p>
                                Últimos movimientos registrados para el tanque.
                            </p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            @if ($movimientosRecientes->isEmpty())
                                <div class="cc-empty-panel cc-empty-panel-compact">
                                    <h5>
                                        Sin movimientos
                                    </h5>

                                    <p>
                                        Este tanque todavía no tiene movimientos de inventario registrados.
                                    </p>
                                </div>
                            @else
                                <div class="cc-table-adaptive-wrapper">
                                    <table class="cc-table-adaptive" style="min-width: 58rem;">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Tipo</th>
                                                <th>Movimiento</th>
                                                <th>Anterior</th>
                                                <th>Resultante</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($movimientosRecientes as $movimiento)
                                                <tr>
                                                    <td class="cc-table-adaptive-nowrap">
                                                        {{ optional($movimiento->fecha_hora_movimiento)->format('d/m/Y H:i') }}
                                                    </td>

                                                    <td>
                                                        {{ str_replace('_', ' ', ucfirst($movimiento->tipo_movimiento)) }}
                                                    </td>

                                                    <td>
                                                        <span class="{{ $movimiento->sentido_movimiento === 'entrada' ? 'text-[var(--cc-success)]' : 'text-[var(--cc-danger)]' }}">
                                                            {{ ucfirst($movimiento->sentido_movimiento) }}
                                                        </span>

                                                        <div class="cc-table-adaptive-muted">
                                                            {{ number_format((float) $movimiento->volumen_movimiento, 2) }} gal
                                                        </div>
                                                    </td>

                                                    <td>
                                                        {{ number_format((float) $movimiento->volumen_anterior, 2) }} gal
                                                    </td>

                                                    <td>
                                                        {{ number_format((float) $movimiento->volumen_resultante, 2) }} gal
                                                    </td>

                                                    <td>
                                                        @if ($movimiento->estado === 'registrado')
                                                            <span class="cc-badge cc-badge-active">
                                                                Registrado
                                                            </span>
                                                        @else
                                                            <span class="cc-badge cc-badge-inactive">
                                                                {{ ucfirst($movimiento->estado) }}
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </section>

                </div>

            </div>
        </div>
    </div>
</x-app-layout>