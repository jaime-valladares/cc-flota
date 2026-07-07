<x-app-layout>
    @php
        $empresaNombre = $gasolinera->empresa?->nombre_comercial ?: $gasolinera->empresa?->nombre_legal;
    @endphp

    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Recargar tanque
                        </h3>

                        <p class="cc-subtitle cc-subtitle-compact">
                            Registre una entrada de combustible controlada para el tanque seleccionado.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras.tanques.recargas.show.ventana', [$gasolinera, $tanque]) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('gasolineras.tanques.recargas.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a recargas
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
                            Tanque recargable
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

                    <div class="cc-profile-status" style="display: flex; gap: 0.5rem; align-items: center;">
                        <span class="cc-badge cc-badge-active">
                            Recargable
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
                        <span class="cc-summary-strip-label">Capacidad</span>
                        <span class="cc-summary-strip-value">{{ number_format($capacidadTotal, 2) }} gal</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Volumen actual</span>
                        <span class="cc-summary-strip-value">{{ number_format($volumenActual, 2) }} gal</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Disponible para recarga</span>
                        <span class="cc-summary-strip-value">{{ number_format($capacidadDisponible, 2) }} gal</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Ocupación</span>
                        <span class="cc-summary-strip-value {{ $bajoAlerta ? 'cc-summary-strip-value-danger' : 'cc-summary-strip-value-success' }}">
                            {{ number_format($porcentajeDisponible, 2) }}%
                        </span>
                    </div>
                </div>

                <div class="cc-detail-layout">

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Registrar recarga</h5>
                            <p>Ingrese el volumen recibido. El sistema validará que no exceda la capacidad total del tanque.</p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            <form method="POST" action="{{ route('gasolineras.tanques.recargas.store', [$gasolinera, $tanque]) }}" novalidate>
                                @csrf

                                <div class="cc-grid cc-grid-compact">

                                    <div class="cc-form-section-slim">
                                        <div class="cc-form-section-title">
                                            Información operativa
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
                                        <label for="tanque_visible">
                                            Tanque
                                        </label>

                                        <input
                                            id="tanque_visible"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $tanque->nombre }}"
                                            disabled
                                        >
                                    </div>

                                    <div class="cc-field">
                                        <label for="capacidad_disponible_visible">
                                            Disponible para recarga
                                        </label>

                                        <input
                                            id="capacidad_disponible_visible"
                                            type="text"
                                            class="cc-input"
                                            value="{{ number_format($capacidadDisponible, 2) }} gal"
                                            disabled
                                        >
                                    </div>

                                    <div class="cc-form-section-slim">
                                        <div class="cc-form-section-title">
                                            Movimiento de entrada
                                        </div>
                                    </div>

                                    <div class="cc-field">
                                        <label for="volumen_movimiento">
                                            Volumen a recargar (galones) <span class="cc-required">*</span>
                                        </label>

                                        <input
                                            id="volumen_movimiento"
                                            type="number"
                                            name="volumen_movimiento"
                                            value="{{ old('volumen_movimiento') }}"
                                            class="cc-input"
                                            min="0.01"
                                            max="{{ $capacidadDisponible }}"
                                            step="0.01"
                                            required
                                            placeholder="Ej. 500.00"
                                        >

                                        @error('volumen_movimiento')
                                            <div class="cc-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="volumen_resultante_estimado">
                                            Volumen resultante estimado
                                        </label>

                                        <input
                                            id="volumen_resultante_estimado"
                                            type="text"
                                            class="cc-input"
                                            value="Se calculará al guardar"
                                            disabled
                                        >
                                    </div>

                                </div>

                                <div class="cc-actions cc-actions-compact">
                                    <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                        Registrar recarga
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Movimientos recientes</h5>
                            <p>Últimos movimientos de inventario registrados para este tanque.</p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            @if ($movimientosRecientes->isEmpty())
                                <div class="cc-empty-panel cc-empty-panel-compact">
                                    <h5>Sin movimientos</h5>
                                    <p>Este tanque todavía no tiene movimientos de inventario registrados.</p>
                                </div>
                            @else
                                <div class="cc-results-list">
                                    @foreach ($movimientosRecientes as $movimiento)
                                        <article class="cc-result-card cc-result-card-compact">
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-center">
                                                <div>
                                                    <div class="cc-result-label">
                                                        Tipo
                                                    </div>

                                                    <div class="cc-result-value">
                                                        {{ str_replace('_', ' ', ucfirst($movimiento->tipo_movimiento)) }}
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Movimiento
                                                    </div>

                                                    <div class="cc-result-value">
                                                        {{ ucfirst($movimiento->sentido_movimiento) }} {{ number_format((float) $movimiento->volumen_movimiento, 2) }} gal
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Resultante
                                                    </div>

                                                    <div class="cc-result-value">
                                                        {{ number_format((float) $movimiento->volumen_resultante, 2) }} gal
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Fecha
                                                    </div>

                                                    <div class="cc-result-value">
                                                        {{ optional($movimiento->fecha_hora_movimiento)->format('d/m/Y H:i') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>

                </div>

            </div>
        </div>
    </div>
</x-app-layout>