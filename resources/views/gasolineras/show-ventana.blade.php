<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar gasolinera | CC-Flota</title>

        
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
        <div class="cc-window-wrapper" style="padding-top: 2.1rem;">
            <div class="cc-window-container" style="max-width: 80rem;">
                <div class="cc-card">

                    <div class="cc-card-header cc-card-header-compact">
                        <div>
                            <h3 class="cc-title cc-title-compact" style="font-size: 1.7rem; line-height: 1.2;">
                                Administrar gasolinera
                            </h3>

                            <p class="cc-subtitle cc-subtitle-compact">
                                Consulte la ficha operativa, tanques asociados e inventario disponible.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('gasolineras.administrar.ventana') }}" class="cc-btn-secondary cc-btn-wide">
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

                    <div class="cc-profile-summary">
                        <div>
                            <div class="cc-profile-eyebrow">
                                Gasolinera interna
                            </div>

                            <div class="cc-profile-title">
                                {{ $gasolinera->nombre }}
                            </div>

                            <div class="cc-profile-meta">
                                <span>
                                    {{ $gasolinera->empresa?->nombre_comercial ?: $gasolinera->empresa?->nombre_legal }}
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
                                <h5>Datos generales</h5>
                                <p>Información principal de ubicación, contacto y estado operativo.</p>
                            </div>

                            <div class="cc-detail-grid">
                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Empresa</div>
                                    <div class="cc-detail-value">
                                        {{ $gasolinera->empresa?->nombre_comercial ?: $gasolinera->empresa?->nombre_legal }}
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Estado</div>
                                    <div class="cc-detail-value">
                                        {{ ucfirst($gasolinera->estado) }}
                                    </div>
                                </div>

                                <div class="cc-detail-item cc-detail-item-wide">
                                    <div class="cc-detail-label">Dirección</div>
                                    <div class="cc-detail-value">
                                        {{ $gasolinera->direccion }}
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Encargado</div>
                                    <div class="cc-detail-value">
                                        {{ $gasolinera->encargado ?: 'No registrado' }}
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">Teléfono</div>
                                    <div class="cc-detail-value">
                                        {{ $gasolinera->telefono ?: 'No registrado' }}
                                    </div>
                                </div>

                                <div class="cc-detail-item cc-detail-item-wide">
                                    <div class="cc-detail-label">Correo de alerta</div>
                                    <div class="cc-detail-value">
                                        {{ $gasolinera->correo ?: 'No registrado' }}
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="cc-detail-section">
                            <div class="cc-detail-section-header">
                                <h5>Tanques asociados</h5>
                                <p>Capacidad instalada, inventario actual y acciones operativas por tanque.</p>
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
                                                <div class="cc-result-grid">
                                                    <div class="cc-result-main">
                                                        <div class="cc-result-title-row">
                                                            <div class="cc-result-title">
                                                                {{ $tanque->nombre }}
                                                            </div>

                                                            <span class="cc-badge {{ $tanque->estado === 'activo' ? 'cc-badge-active' : 'cc-badge-inactive' }}">
                                                                {{ ucfirst($tanque->estado) }}
                                                            </span>

                                                            @if ($bajoAlerta)
                                                                <span class="cc-badge cc-badge-warning">
                                                                    Bajo mínimo
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <div class="cc-result-subtitle">
                                                            Inventario actual: {{ number_format((float) $tanque->volumen_actual, 2) }} gal de {{ number_format((float) $tanque->capacidad_total, 2) }} gal.
                                                        </div>
                                                    </div>

                                                    <div class="cc-result-meta">
                                                        <div class="cc-result-label">Mínimo alerta</div>
                                                        <div class="cc-result-value">
                                                            {{ number_format((float) $tanque->volumen_minimo_alerta, 2) }} gal
                                                        </div>
                                                    </div>

                                                    <div class="cc-result-meta">
                                                        <div class="cc-result-label">Disponibilidad</div>
                                                        <div class="cc-result-value">
                                                            {{ number_format($porcentajeTanque, 2) }}%
                                                        </div>
                                                    </div>

                                                    <div class="cc-result-actions">
                                                        @if ($gasolinera->estado === 'activa' && $tanque->estado === 'activo')
                                                            <a href="{{ route('gasolineras.tanques.recarga.ventana', [$gasolinera, $tanque]) }}" class="cc-btn-result">
                                                                Recargar
                                                            </a>
                                                        @endif

                                                        @if ($tanque->estado === 'activo')
                                                            <form method="POST" action="{{ route('gasolineras.tanques.inactivar', [$gasolinera, $tanque]) }}">
                                                                @csrf
                                                                @method('PATCH')

                                                                <input type="hidden" name="return_to" value="ventana">
                                                                <input type="hidden" name="motivo_inactivacion" value="Mantenimiento">

                                                                <button type="submit" class="cc-btn-result">
                                                                    Inactivar
                                                                </button>
                                                            </form>
                                                        @elseif ($gasolinera->estado === 'activa')
                                                            <form method="POST" action="{{ route('gasolineras.tanques.reactivar', [$gasolinera, $tanque]) }}">
                                                                @csrf
                                                                @method('PATCH')

                                                                <input type="hidden" name="return_to" value="ventana">

                                                                <button type="submit" class="cc-btn-result">
                                                                    Reactivar
                                                                </button>
                                                            </form>
                                                        @endif
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

                                        <input type="hidden" name="return_to" value="ventana">

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
                                <h5>Acciones de gasolinera</h5>
                                <p>Administración del registro principal y estado operativo.</p>
                            </div>

                            <div style="padding: 1rem 1.2rem;">
                                <div class="cc-actions-normal">
                                    <a href="{{ route('gasolineras.edit.ventana', $gasolinera) }}" class="cc-btn-secondary cc-btn-form-action">
                                        Editar
                                    </a>

                                    @if ($gasolinera->estado === 'activa')
                                        <form method="POST" action="{{ route('gasolineras.inactivar', $gasolinera) }}" class="cc-inline-action-form">
                                            @csrf
                                            @method('PATCH')

                                            <input type="hidden" name="return_to" value="ventana">

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
                                                Inactivar
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('gasolineras.reactivar', $gasolinera) }}">
                                            @csrf
                                            @method('PATCH')

                                            <input type="hidden" name="return_to" value="ventana">

                                            <button type="submit" class="cc-btn-success cc-btn-form-action">
                                                Reactivar gasolinera
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </section>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>