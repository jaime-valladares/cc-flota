<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Ficha de usuario | CC-Flota</title>
    @include('layouts.partials.favicon')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased">
<div class="min-h-screen" style="background: var(--cc-bg-main);">
<div class="cc-page-wrapper">
<div class="cc-window-container" style="max-width: 80rem;">
@php
    $queryParams = request()->query();
@endphp

<div class="cc-card">
    <div class="cc-card-header cc-card-header-compact">
        <div>
            <h3 class="cc-title cc-title-compact">
                Ficha de usuario
            </h3>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('usuarios.administrar.ventana', $queryParams) }}"
               class="cc-btn-secondary cc-btn-wide">
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
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="cc-profile-summary">
        <div>
            <div class="cc-profile-eyebrow">Usuario</div>
            <h4 class="cc-profile-title">
                {{ trim($usuario->name . ' ' . ($usuario->apellido ?? '')) }}
            </h4>
            <div class="cc-profile-meta">
                <span>Correo: {{ $usuario->email }}</span>
                <span>
                    Empresa:
                    {{ $usuario->empresa
                        ? ($usuario->empresa->nombre_comercial ?: $usuario->empresa->nombre_legal)
                        : 'Diesel Cop' }}
                </span>
                <span>Rol: {{ $usuario->role->nombre ?? '—' }}</span>
            </div>
        </div>

        <div class="cc-profile-status">
            @if ($usuario->estado === 'activo')
                <span class="cc-badge cc-badge-active">Activo</span>
            @else
                <span class="cc-badge cc-badge-inactive">Inactivo</span>
            @endif
        </div>
    </div>

    <div class="cc-detail-layout">
        <section class="cc-detail-section">
            <div class="cc-detail-section-header">
                <h5>Identificación</h5>
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
            </div>

            <div class="cc-detail-grid">
                <div class="cc-detail-item">
                    <div class="cc-detail-label">Tipo de usuario</div>
                    <div class="cc-detail-value">
                        {{ $usuario->tipo_usuario === 'diesel_cop' ? 'Diesel Cop' : 'Empresa' }}
                    </div>
                </div>
                <div class="cc-detail-item">
                    <div class="cc-detail-label">Empresa</div>
                    <div class="cc-detail-value">
                        {{ $usuario->empresa
                            ? ($usuario->empresa->nombre_comercial ?: $usuario->empresa->nombre_legal)
                            : 'Diesel Cop' }}
                    </div>
                </div>
                <div class="cc-detail-item">
                    <div class="cc-detail-label">Rol</div>
                    <div class="cc-detail-value">{{ $usuario->role->nombre ?? '—' }}</div>
                </div>
                <div class="cc-detail-item">
                    <div class="cc-detail-label">Código de rol</div>
                    <div class="cc-detail-value">{{ $usuario->role->codigo ?? '—' }}</div>
                </div>
            </div>
        </section>

        <section class="cc-detail-section">
            <div class="cc-detail-section-header">
                <h5>Control administrativo</h5>
            </div>

            <div class="cc-detail-grid">
                <div class="cc-detail-item">
                    <div class="cc-detail-label">Estado actual</div>
                    <div class="cc-detail-value">{{ ucfirst($usuario->estado) }}</div>
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
                        {{ $usuario->inactivadoPor
                            ? trim($usuario->inactivadoPor->name . ' ' . ($usuario->inactivadoPor->apellido ?? ''))
                            : '—' }}
                    </div>
                </div>
                <div class="cc-detail-item cc-detail-item-wide">
                    <div class="cc-detail-label">Motivo de inactivación</div>
                    <div class="cc-detail-value">{{ $usuario->motivo_inactivacion ?? '—' }}</div>
                </div>
                <div class="cc-detail-item">
                    <div class="cc-detail-label">Creado por</div>
                    <div class="cc-detail-value">
                        {{ $usuario->creadoPor
                            ? trim($usuario->creadoPor->name . ' ' . ($usuario->creadoPor->apellido ?? ''))
                            : '—' }}
                    </div>
                </div>
                <div class="cc-detail-item">
                    <div class="cc-detail-label">Actualizado por</div>
                    <div class="cc-detail-value">
                        {{ $usuario->actualizadoPor
                            ? trim($usuario->actualizadoPor->name . ' ' . ($usuario->actualizadoPor->apellido ?? ''))
                            : '—' }}
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="cc-actions cc-actions-split">
        <div class="cc-actions-normal">
            @if (
                Auth::user()->tienePermiso('usuarios.actualizar')
                && $usuario->estado === 'activo'
            )
                <a
                    href="{{ route('usuarios.edit.ventana', array_merge($queryParams, ['usuario' => $usuario])) }}"
                    class="cc-btn-primary cc-btn-form-action"
                >
                    Editar usuario
                </a>
            @endif

            <a
                href="{{ route('usuarios.administrar.ventana', $queryParams) }}"
                class="cc-btn-secondary cc-btn-form-action"
            >
                Volver a administrar
            </a>
        </div>
    </div>

    @if (
        Auth::user()->tienePermiso('usuarios.inactivar')
        && $usuario->estado === 'activo'
    )
        <section class="cc-danger-zone">
            <div class="cc-danger-zone-header">
                <div>
                    <h5>Zona de riesgo</h5>
                </div>
            </div>

            <form
                method="POST"
                action="{{ route('usuarios.inactivar', array_merge($queryParams, ['usuario' => $usuario])) }}"
                class="cc-danger-zone-form"
                onsubmit="return confirmarInactivacionUsuario();"
            >
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
                        <option value="Falta de uso">Falta de uso</option>
                        <option value="Cambio de puesto">Cambio de puesto</option>
                        <option value="Salida de la empresa">Salida de la empresa</option>
                        <option value="Acceso duplicado">Acceso duplicado</option>
                        <option value="Datos incorrectos en registro">Datos incorrectos en registro</option>
                        <option value="Solicitud administrativa">Solicitud administrativa</option>
                        <option value="Suspensión temporal">Suspensión temporal</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <button type="submit" class="cc-btn-danger cc-btn-form-action">
                    Inactivar usuario
                </button>
            </form>
        </section>
    @elseif (
        Auth::user()->tienePermiso('usuarios.reactivar')
        && $usuario->estado === 'inactivo'
    )
        <div class="cc-actions">
            <form
                method="POST"
                action="{{ route('usuarios.reactivar', array_merge($queryParams, ['usuario' => $usuario])) }}"
                onsubmit="return confirm('¿Seguro que deseas reactivar este usuario?');"
            >
                @csrf
                @method('PATCH')
                <input type="hidden" name="return_to" value="ventana">

                <button type="submit" class="cc-btn-success cc-btn-form-action">
                    Reactivar usuario
                </button>
            </form>
        </div>
    @endif
</div>

<script>
    function confirmarInactivacionUsuario() {
        const motivo = document.getElementById('motivo_inactivacion').value;

        if (! motivo) {
            alert('Debe seleccionar un motivo de inactivación.');
            return false;
        }

        return confirm(
            `¿Seguro que deseas inactivar este usuario por el motivo "${motivo}"?`
        );
    }
</script>
</div>
</div>
</div>
</body>
</html>