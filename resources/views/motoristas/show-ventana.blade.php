<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Ficha administrativa de motorista | CC-Flota</title>

        
        @include('layouts.partials.favicon')
<link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
            <div class="cc-page-wrapper">
                <div class="cc-window-container" style="max-width: 79rem;">
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Ficha administrativa de motorista
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
                                    Consulta consolidada del motorista disponible para solicitudes de abastecimiento.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('motoristas.administrar.ventana') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver a administrar
                                </a>

                                <a href="{{ route('motoristas.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
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
                                    Motorista
                                </div>

                                <h4 class="cc-profile-title">
                                    {{ $motorista->nombre_completo }}
                                </h4>

                                <div class="cc-profile-meta">
                                    <span>
                                        Empresa: {{ $motorista->empresa->nombre_comercial ?: $motorista->empresa->nombre_legal }}
                                    </span>

                                    <span>
                                        Licencia: {{ $motorista->licencia }}
                                    </span>
                                </div>
                            </div>

                            <div class="cc-profile-status">
                                @if ($motorista->estado === 'activo')
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
                                    <p>Datos principales del motorista registrado para la empresa.</p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Empresa</div>
                                        <div class="cc-detail-value">
                                            {{ $motorista->empresa->nombre_comercial ?: $motorista->empresa->nombre_legal }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Nombres</div>
                                        <div class="cc-detail-value">{{ $motorista->nombres }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Apellidos</div>
                                        <div class="cc-detail-value">{{ $motorista->apellidos }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Licencia</div>
                                        <div class="cc-detail-value">{{ $motorista->licencia }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Teléfono</div>
                                        <div class="cc-detail-value">{{ $motorista->telefono }}</div>
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
                                        <div class="cc-detail-label">Estado actual</div>
                                        <div class="cc-detail-value">
                                            @if ($motorista->estado === 'activo')
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
                                        <div class="cc-detail-label">Fecha de creación</div>
                                        <div class="cc-detail-value">{{ $motorista->fecha_creacion ?? '—' }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Fecha de actualización</div>
                                        <div class="cc-detail-value">{{ $motorista->fecha_actualizacion ?? '—' }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Fecha de inactivación</div>
                                        <div class="cc-detail-value">{{ $motorista->fecha_inactivacion ?? '—' }}</div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">Motivo de inactivación</div>
                                        <div class="cc-detail-value">{{ $motorista->motivo_inactivacion ?? '—' }}</div>
                                    </div>
                                </div>
                            </section>

                        </div>

                        <div class="cc-actions cc-actions-split">
                            <div class="cc-actions-normal">
                                <a href="{{ route('motoristas.edit.ventana', $motorista) }}" class="cc-btn-primary cc-btn-form-action">
                                    Editar motorista
                                </a>

                                <a href="{{ route('motoristas.administrar.ventana') }}" class="cc-btn-secondary cc-btn-form-action">
                                    Volver a administrar
                                </a>
                            </div>
                        </div>

                        @if ($motorista->estado === 'activo')
                            <section class="cc-danger-zone">
                                <div class="cc-danger-zone-header">
                                    <div>
                                        <h5>Zona de riesgo</h5>
                                        <p>
                                            Inactive el motorista únicamente cuando ya no deba estar disponible para nuevas solicitudes de abastecimiento.
                                        </p>
                                    </div>
                                </div>

                                <form method="POST"
                                      action="{{ route('motoristas.inactivar', $motorista) }}"
                                      class="cc-danger-zone-form"
                                      onsubmit="return confirmarInactivacionMotorista();">
                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden" name="return_to" value="ventana">

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
                                            <option value="No continúa en servicio">No continúa en servicio</option>
                                            <option value="Cambio operativo">Cambio operativo</option>
                                            <option value="Licencia vencida">Licencia vencida</option>
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
                                        Inactivar motorista
                                    </button>
                                </form>
                            </section>
                        @else
                            <div class="cc-actions">
                                <form method="POST"
                                      action="{{ route('motoristas.reactivar', $motorista) }}"
                                      onsubmit="return confirm('¿Seguro que deseas reactivar este motorista?');">
                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden" name="return_to" value="ventana">

                                    <button type="submit" class="cc-btn-success cc-btn-form-action">
                                        Reactivar motorista
                                    </button>
                                </form>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <script>
            function confirmarInactivacionMotorista() {
                const motivo = document.getElementById('motivo_inactivacion').value;

                if (!motivo) {
                    alert('Debe seleccionar un motivo de inactivación.');
                    return false;
                }

                return confirm(`¿Seguro que deseas inactivar este motorista por el motivo "${motivo}"?`);
            }
        </script>
    </body>
</html>