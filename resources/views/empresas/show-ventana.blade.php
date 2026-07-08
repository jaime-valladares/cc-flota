<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Ficha administrativa de empresa | CC-Flota</title>

        
        @include('layouts.partials.favicon')
<!-- Fonts -->
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
                                    Ficha administrativa de empresa
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
                                    Consulta consolidada de la empresa cliente y sus datos administrativos principales.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('empresas.administrar.ventana') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver a administrar
                                </a>

                                <a href="{{ route('empresas.administrar') }}" class="cc-btn-secondary cc-btn-wide">
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
                                    Empresa cliente
                                </div>

                                <h4 class="cc-profile-title">
                                    {{ $empresa->nombre_legal }}
                                </h4>

                                <div class="cc-profile-meta">
                                    <span>NIT: {{ $empresa->nit }}</span>

                                    @if ($empresa->nombre_comercial)
                                        <span>Nombre comercial: {{ $empresa->nombre_comercial }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="cc-profile-status">
                                @if ($empresa->estado === 'activa')
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
                                    <h5>Identificación</h5>
                                    <p>Datos legales y comerciales registrados para la empresa cliente.</p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Nombre legal</div>
                                        <div class="cc-detail-value">{{ $empresa->nombre_legal }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Nombre comercial</div>
                                        <div class="cc-detail-value">{{ $empresa->nombre_comercial ?? '—' }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">NIT</div>
                                        <div class="cc-detail-value">{{ $empresa->nit }}</div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>Contacto institucional</h5>
                                    <p>Canales principales de comunicación y ubicación de la empresa.</p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Correo empresa</div>
                                        <div class="cc-detail-value">{{ $empresa->correo_empresa }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Teléfono empresa</div>
                                        <div class="cc-detail-value">{{ $empresa->telefono_empresa ?? '—' }}</div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">Dirección</div>
                                        <div class="cc-detail-value">{{ $empresa->direccion ?? '—' }}</div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>Punto de contacto operativo</h5>
                                    <p>Persona principal de referencia para coordinación administrativa y operativa.</p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Nombre del POC</div>
                                        <div class="cc-detail-value">{{ $empresa->poc_nombre }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Correo del POC</div>
                                        <div class="cc-detail-value">{{ $empresa->poc_email }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Teléfono del POC</div>
                                        <div class="cc-detail-value">{{ $empresa->poc_telefono ?? '—' }}</div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>Control administrativo</h5>
                                    <p>Información de estado, activación, inactivación y actualización del registro.</p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Estado actual</div>
                                        <div class="cc-detail-value">
                                            @if ($empresa->estado === 'activa')
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

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Fecha de creación</div>
                                        <div class="cc-detail-value">{{ $empresa->fecha_creacion ?? '—' }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Fecha de actualización</div>
                                        <div class="cc-detail-value">{{ $empresa->fecha_actualizacion ?? '—' }}</div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">Fecha de inactivación</div>
                                        <div class="cc-detail-value">{{ $empresa->fecha_inactivacion ?? '—' }}</div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">Motivo de inactivación</div>
                                        <div class="cc-detail-value">{{ $empresa->motivo_inactivacion ?? '—' }}</div>
                                    </div>
                                </div>
                            </section>

                        </div>

                        <div class="cc-actions cc-actions-split">
                            <div class="cc-actions-normal">
                                <a href="{{ route('empresas.edit.ventana', $empresa) }}" class="cc-btn-primary cc-btn-form-action">
                                    Editar empresa
                                </a>

                                <a href="{{ route('empresas.administrar.ventana') }}" class="cc-btn-secondary cc-btn-form-action">
                                    Volver a administrar
                                </a>
                            </div>
                        </div>

                        @if ($empresa->estado === 'activa')
                            <section class="cc-danger-zone">
                                <div class="cc-danger-zone-header">
                                    <div>
                                        <h5>Zona de riesgo</h5>
                                        <p>
                                            Inactive la empresa únicamente cuando exista una razón administrativa válida.
                                        </p>
                                    </div>
                                </div>

                                <form method="POST"
                                      action="{{ route('empresas.inactivar', $empresa) }}"
                                      class="cc-danger-zone-form"
                                      onsubmit="return confirmarInactivacionEmpresa();">
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
                                            <option value="Falta de pago">Falta de pago</option>
                                            <option value="No continúa como cliente">No continúa como cliente</option>
                                            <option value="Contrato finalizado">Contrato finalizado</option>
                                            <option value="Empresa duplicada">Empresa duplicada</option>
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
                                        Inactivar empresa
                                    </button>
                                </form>
                            </section>
                        @else
                            <div class="cc-actions">
                                <form method="POST"
                                      action="{{ route('empresas.reactivar', $empresa) }}"
                                      onsubmit="return confirm('¿Seguro que deseas reactivar esta empresa?');">
                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden" name="return_to" value="ventana">

                                    <button type="submit" class="cc-btn-success cc-btn-form-action">
                                        Reactivar empresa
                                    </button>
                                </form>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <script>
            function confirmarInactivacionEmpresa() {
                const motivo = document.getElementById('motivo_inactivacion').value;

                if (!motivo) {
                    alert('Debe seleccionar un motivo de inactivación.');
                    return false;
                }

                return confirm(`¿Seguro que deseas inactivar esta empresa por el motivo "${motivo}"?`);
            }
        </script>
    </body>
</html>