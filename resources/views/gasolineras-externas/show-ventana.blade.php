@php
    $queryParams = request()->query();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Ficha administrativa de gasolinera externa | CC-Flota</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
            rel="stylesheet"
        >

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        <div
            class="min-h-screen"
            style="background: var(--cc-bg-main);"
        >
            <div class="cc-page-wrapper">
                <div
                    class="cc-window-container"
                    style="max-width: 80rem;"
                >
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Ficha administrativa de gasolinera externa
                                </h3>

                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    href="{{ route(
                                        'gasolineras-externas.administrar.ventana',
                                        $queryParams
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver a administrar
                                </a>

                                <a
                                    href="{{ route(
                                        'gasolineras-externas.administrar',
                                        $queryParams
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="cc-profile-summary">
                            <div>
                                <div class="cc-profile-eyebrow">
                                    Gasolinera externa
                                </div>

                                <h4 class="cc-profile-title">
                                    {{ $gasolineraExterna->compania }}
                                </h4>

                                <div class="cc-profile-meta">
                                    <span>
                                        Empresa:
                                        {{ $gasolineraExterna->empresa->nombre_comercial
                                            ?: $gasolineraExterna->empresa->nombre_legal }}
                                    </span>

                                    <span>
                                        ID:
                                        {{ $gasolineraExterna->id }}
                                    </span>
                                </div>
                            </div>

                            <div class="cc-profile-status">
                                @if ($gasolineraExterna->estado === 'activa')
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
                                        Identificación
                                    </h5>

                                    <p>
                                        Datos principales de la gasolinera externa y empresa a la que pertenece.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            ID
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $gasolineraExterna->id }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Empresa
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $gasolineraExterna->empresa->nombre_comercial
                                                ?: $gasolineraExterna->empresa->nombre_legal }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Compañía
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $gasolineraExterna->compania }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">
                                            Dirección
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $gasolineraExterna->direccion }}
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Control administrativo
                                    </h5>

                                    <p>
                                        Estado actual y trazabilidad de las operaciones realizadas.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Estado actual
                                        </div>

                                        <div class="cc-detail-value">
                                            @if ($gasolineraExterna->estado === 'activa')
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
                                        <div class="cc-detail-label">
                                            Fecha de creación
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $gasolineraExterna->fecha_creacion
                                                ? $gasolineraExterna->fecha_creacion->format('d/m/Y H:i')
                                                : '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Creado por
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $gasolineraExterna->creadoPor ? trim($gasolineraExterna->creadoPor->name . ' ' . ($gasolineraExterna->creadoPor->apellido ?? '')) : '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Fecha de actualización
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $gasolineraExterna->fecha_actualizacion
                                                ? $gasolineraExterna->fecha_actualizacion->format('d/m/Y H:i')
                                                : '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Actualizado por
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $gasolineraExterna->actualizadoPor ? trim($gasolineraExterna->actualizadoPor->name . ' ' . ($gasolineraExterna->actualizadoPor->apellido ?? '')) : '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Fecha de inactivación
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $gasolineraExterna->fecha_inactivacion
                                                ? $gasolineraExterna->fecha_inactivacion->format('d/m/Y H:i')
                                                : '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Inactivado por
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $gasolineraExterna->inactivadoPor ? trim($gasolineraExterna->inactivadoPor->name . ' ' . ($gasolineraExterna->inactivadoPor->apellido ?? '')) : '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">
                                            Motivo de inactivación
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $gasolineraExterna->motivo_inactivacion ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </section>

                        </div>

                        <div class="cc-actions cc-actions-split">
                            <div class="cc-actions-normal">
                                @if ($gasolineraExterna->estado === 'activa')
                                    <a
                                        href="{{ route(
                                            'gasolineras-externas.edit.ventana',
                                            array_merge(
                                                [
                                                    'gasolineraExterna' =>
                                                        $gasolineraExterna,
                                                ],
                                                $queryParams
                                            )
                                        ) }}"
                                        class="cc-btn-primary cc-btn-form-action"
                                    >
                                        Editar gasolinera
                                    </a>
                                @endif

                                <a
                                    href="{{ route(
                                        'gasolineras-externas.administrar.ventana',
                                        $queryParams
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-form-action"
                                >
                                    Volver a administrar
                                </a>
                            </div>
                        </div>

                        @if ($gasolineraExterna->estado === 'activa')
                            <section class="cc-danger-zone">
                                <div class="cc-danger-zone-header">
                                    <div>
                                        <h5>
                                            Zona de riesgo
                                        </h5>

                                        <p>
                                            Inactive la gasolinera externa cuando ya no deba estar disponible para nuevas operaciones.
                                        </p>
                                    </div>
                                </div>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'gasolineras-externas.inactivar',
                                        array_merge(
                                            [
                                                'gasolineraExterna' =>
                                                    $gasolineraExterna,
                                            ],
                                            $queryParams
                                        )
                                    ) }}"
                                    class="cc-danger-zone-form"
                                    onsubmit="return confirmarInactivacionGasolineraExterna();"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <input
                                        type="hidden"
                                        name="return_to"
                                        value="ventana"
                                    >

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
                                            <option value="">
                                                Seleccione un motivo
                                            </option>

                                            <option
                                                value="No continúa en uso"
                                                @selected(
                                                    old('motivo_inactivacion')
                                                        === 'No continúa en uso'
                                                )
                                            >
                                                No continúa en uso
                                            </option>

                                            <option
                                                value="Cambio de proveedor"
                                                @selected(
                                                    old('motivo_inactivacion')
                                                        === 'Cambio de proveedor'
                                                )
                                            >
                                                Cambio de proveedor
                                            </option>

                                            <option
                                                value="Cierre de estación"
                                                @selected(
                                                    old('motivo_inactivacion')
                                                        === 'Cierre de estación'
                                                )
                                            >
                                                Cierre de estación
                                            </option>

                                            <option
                                                value="Datos incorrectos en registro"
                                                @selected(
                                                    old('motivo_inactivacion')
                                                        === 'Datos incorrectos en registro'
                                                )
                                            >
                                                Datos incorrectos en registro
                                            </option>

                                            <option
                                                value="Solicitud del cliente"
                                                @selected(
                                                    old('motivo_inactivacion')
                                                        === 'Solicitud del cliente'
                                                )
                                            >
                                                Solicitud del cliente
                                            </option>

                                            <option
                                                value="Suspensión administrativa"
                                                @selected(
                                                    old('motivo_inactivacion')
                                                        === 'Suspensión administrativa'
                                                )
                                            >
                                                Suspensión administrativa
                                            </option>

                                            <option
                                                value="Otro"
                                                @selected(
                                                    old('motivo_inactivacion')
                                                        === 'Otro'
                                                )
                                            >
                                                Otro
                                            </option>
                                        </select>

                                        @error('motivo_inactivacion')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <button
                                        type="submit"
                                        class="cc-btn-danger cc-btn-form-action"
                                    >
                                        Inactivar gasolinera
                                    </button>
                                </form>
                            </section>
                        @else
                            <div class="cc-actions">
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'gasolineras-externas.reactivar',
                                        array_merge(
                                            [
                                                'gasolineraExterna' =>
                                                    $gasolineraExterna,
                                            ],
                                            $queryParams
                                        )
                                    ) }}"
                                    onsubmit="return confirm('¿Seguro que deseas reactivar esta gasolinera externa?');"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <input
                                        type="hidden"
                                        name="return_to"
                                        value="ventana"
                                    >

                                    <button
                                        type="submit"
                                        class="cc-btn-success cc-btn-form-action"
                                    >
                                        Reactivar gasolinera
                                    </button>
                                </form>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <script>
            function confirmarInactivacionGasolineraExterna() {
                const motivo = document
                    .getElementById('motivo_inactivacion')
                    ?.value;

                if (! motivo) {
                    alert('Debe seleccionar un motivo de inactivación.');

                    return false;
                }

                return confirm(
                    `¿Seguro que deseas inactivar esta gasolinera externa por el motivo "${motivo}"?`
                );
            }
        </script>
    </body>
</html>
