<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar tanque | CC-Flota</title>

        
        @include('layouts.partials.favicon')
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/cc-flota/favicon.png') }}?v=3">
        <link rel="shortcut icon" type="image/png" href="{{ asset('images/cc-flota/favicon.png') }}?v=3">
        <link rel="apple-touch-icon" href="{{ asset('images/cc-flota/favicon.png') }}?v=3">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        @php
            $empresaNombre = $gasolinera->empresa?->nombre_comercial ?: $gasolinera->empresa?->nombre_legal;
        @endphp

        <div class="cc-window-wrapper" style="padding-top: 2.1rem;">
            <div class="cc-window-container" style="max-width: 79rem;">
                <div class="cc-card">

                    <div class="cc-card-header cc-card-header-compact">
                        <div>
                            <h3 class="cc-title cc-title-compact">
                                Administrar tanque
                            </h3>

                            <p class="cc-subtitle cc-subtitle-compact">
                                Actualice los datos controlados del tanque y gestione su estado operativo.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('gasolineras.tanques.index.ventana') }}" class="cc-btn-secondary cc-btn-wide">
                                Volver a gestión
                            </a>

                            <a href="{{ route('gasolineras.tanques.show', [$gasolinera, $tanque]) }}" class="cc-btn-secondary cc-btn-wide">
                                Volver al sistema
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

                        <div class="cc-profile-status" style="display: flex; gap: 0.5rem; align-items: center;">
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
                            <span class="cc-summary-strip-label">Capacidad</span>
                            <span class="cc-summary-strip-value">{{ number_format($capacidadTotal, 2) }} gal</span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">Volumen actual</span>
                            <span class="cc-summary-strip-value">{{ number_format($volumenActual, 2) }} gal</span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">Mínimo alerta</span>
                            <span class="cc-summary-strip-value">{{ number_format($volumenMinimoAlerta, 2) }} gal</span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">Disponibilidad</span>
                            <span class="cc-summary-strip-value {{ $bajoAlerta ? 'cc-summary-strip-value-danger' : 'cc-summary-strip-value-success' }}">
                                {{ number_format($porcentajeDisponible, 2) }}%
                            </span>
                        </div>
                    </div>

                    <div class="cc-detail-layout">

                        <section class="cc-detail-section">
                            <div class="cc-detail-section-header">
                                <h5>Datos del tanque</h5>
                                <p>Modifique únicamente los datos administrativos permitidos. El volumen actual no se edita manualmente.</p>
                            </div>

                            <div style="padding: 1rem 1.2rem;">
                                <form method="POST" action="{{ route('gasolineras.tanques.update', [$gasolinera, $tanque]) }}" novalidate>
                                    @csrf
                                    @method('PUT')

                                    <input type="hidden" name="return_to" value="ventana">

                                    <div class="cc-grid cc-grid-compact">

                                        <div class="cc-form-section-slim">
                                            <div class="cc-form-section-title">
                                                Ubicación operativa
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

                                        <div class="cc-form-section-slim">
                                            <div class="cc-form-section-title">
                                                Configuración del tanque
                                            </div>
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
                                            >

                                            @error('nombre')
                                                <div class="cc-error">{{ $message }}</div>
                                            @enderror
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
                                            >

                                            @error('capacidad_total')
                                                <div class="cc-error">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <div class="cc-field">
                                            <label for="volumen_actual_visible">
                                                Volumen actual
                                            </label>

                                            <input
                                                id="volumen_actual_visible"
                                                type="text"
                                                class="cc-input"
                                                value="{{ number_format($volumenActual, 2) }} gal"
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
                                            >

                                            @error('volumen_minimo_alerta')
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
                                <h5>Estado del tanque</h5>
                                <p>Administre la disponibilidad operativa del tanque sin eliminar su historial.</p>
                            </div>

                            <div style="padding: 1rem 1.2rem;">
                                @if ($tanque->estado === 'activo')
                                    <form method="POST" action="{{ route('gasolineras.tanques.inactivar', [$gasolinera, $tanque]) }}" class="cc-inline-action-form">
                                        @csrf
                                        @method('PATCH')

                                        <input type="hidden" name="return_to" value="ventana">

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
                                @elseif ($gasolinera->estado === 'activa')
                                    <form method="POST" action="{{ route('gasolineras.tanques.reactivar', [$gasolinera, $tanque]) }}">
                                        @csrf
                                        @method('PATCH')

                                        <input type="hidden" name="return_to" value="ventana">

                                        <button type="submit" class="cc-btn-success cc-btn-form-action">
                                            Reactivar tanque
                                        </button>
                                    </form>
                                @else
                                    <div class="cc-empty-panel cc-empty-panel-compact">
                                        <h5>Gasolinera inactiva</h5>
                                        <p>No se puede reactivar este tanque mientras la gasolinera esté inactiva.</p>
                                    </div>
                                @endif
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
    </body>
</html>