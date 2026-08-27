@php
    $queryParams = collect(request()->query())
        ->except([
            'puntoRuta',
            'return_to',
            'return_query',
        ])
        ->all();

    $returnQuery = http_build_query($queryParams);

    $nombreEmpresa = $puntoRuta->empresa?->nombre_comercial
        ?: $puntoRuta->empresa?->nombre_legal
        ?: 'Empresa no disponible';

    $puntoActivo = $puntoRuta->estado === 'activo';

    $tieneRutasActivas = $tieneRutasActivas
        ?? false;

    $cantidadRutasActivas = $cantidadRutasActivas
        ?? 0;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>
            Ficha administrativa de punto de ruta | CC-Flota
        </title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
            rel="stylesheet"
        >

        @vite([
            'resources/css/app.css',
            'resources/js/app.js',
        ])
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
                                    Ficha administrativa de punto de ruta
                                </h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    href="{{ route(
                                        'puntos-ruta.administrar.ventana',
                                        $queryParams
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
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
                                <div class="font-bold">
                                    Revise la información proporcionada.
                                </div>

                                <ul class="mt-2 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>
                                            {{ $error }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="cc-profile-summary">
                            <div>
                                <div class="cc-profile-eyebrow">
                                    Punto de ruta
                                </div>

                                <h4 class="cc-profile-title">
                                    {{ $puntoRuta->nombre }}
                                </h4>

                                <div class="cc-profile-meta">
                                    <span>
                                        Empresa: {{ $nombreEmpresa }}
                                    </span>

                                    <span>
                                        Uso: origen o destino de ruta
                                    </span>
                                </div>
                            </div>

                            <div class="cc-profile-status">
                                @if ($puntoActivo)
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
                                    <h5>
                                        Identificación
                                    </h5>

                                    <p>
                                        Datos principales registrados para el punto de ruta.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Empresa
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $nombreEmpresa }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Nombre del punto
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $puntoRuta->nombre }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">
                                            Dirección
                                        </div>

                                        <div
                                            class="cc-detail-value"
                                            style="
                                                white-space: normal;
                                                overflow-wrap: anywhere;
                                                line-height: 1.5;
                                            "
                                        >
                                            {{ $puntoRuta->direccion ?: '—' }}
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Disponibilidad operativa
                                    </h5>

                                    <p>
                                        Condición actual del punto para utilizarse en rutas.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Estado actual
                                        </div>

                                        <div class="cc-detail-value">
                                            @if ($puntoActivo)
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
                                        <div class="cc-detail-label">
                                            Disponibilidad
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $puntoActivo
                                                ? 'Disponible para nuevas rutas'
                                                : 'Solo consulta histórica' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Rutas activas relacionadas
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $cantidadRutasActivas }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">
                                            Condición administrativa
                                        </div>

                                        <div class="cc-detail-value">
                                            @if (! $puntoActivo)
                                                El punto debe reactivarse antes de volver
                                                a utilizarse como origen o destino.
                                            @elseif ($tieneRutasActivas)
                                                El punto participa en rutas activas y no
                                                puede inactivarse hasta que dichas rutas
                                                sean inactivadas.
                                            @else
                                                El punto se encuentra disponible para
                                                operaciones administrativas.
                                            @endif
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
                                        Información de creación, actualización e inactivación del registro.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Fecha de creación
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ optional(
                                                $puntoRuta->fecha_creacion
                                            )->format('d/m/Y H:i') ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Creado por
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $puntoRuta->creadoPor ? trim($puntoRuta->creadoPor->name . ' ' . ($puntoRuta->creadoPor->apellido ?? '')) : '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Fecha de actualización
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ optional(
                                                $puntoRuta->fecha_actualizacion
                                            )->format('d/m/Y H:i') ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Actualizado por
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $puntoRuta->actualizadoPor ? trim($puntoRuta->actualizadoPor->name . ' ' . ($puntoRuta->actualizadoPor->apellido ?? '')) : '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Fecha de inactivación
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ optional(
                                                $puntoRuta->fecha_inactivacion
                                            )->format('d/m/Y H:i') ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Inactivado por
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $puntoRuta->inactivadoPor ? trim($puntoRuta->inactivadoPor->name . ' ' . ($puntoRuta->inactivadoPor->apellido ?? '')) : '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">
                                            Motivo de inactivación
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $puntoRuta->motivo_inactivacion ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </section>

                        </div>

                        <div class="cc-actions cc-actions-split">
                            <div class="cc-actions-normal">

                                @if ($puntoActivo)
                                    <a
                                        href="{{ route(
                                            'puntos-ruta.edit.ventana',
                                            array_merge(
                                                $queryParams,
                                                ['puntoRuta' => $puntoRuta]
                                            )
                                        ) }}"
                                        class="cc-btn-primary cc-btn-form-action"
                                    >
                                        Editar punto
                                    </a>
                                @endif

                                <a
                                    href="{{ route(
                                        'puntos-ruta.administrar.ventana',
                                        $queryParams
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-form-action"
                                >
                                    Volver a administrar
                                </a>

                            </div>
                        </div>

                        @if ($puntoActivo)
                            <section class="cc-danger-zone">
                                <div class="cc-danger-zone-header">
                                    <div>
                                        <h5>
                                            Zona de riesgo
                                        </h5>

                                        @if ($tieneRutasActivas)
                                            <p>
                                                Este punto participa en
                                                {{ $cantidadRutasActivas }}
                                                {{ $cantidadRutasActivas === 1
                                                    ? 'ruta activa'
                                                    : 'rutas activas' }}.
                                                Debe inactivarlas antes de poder
                                                inactivar el punto.
                                            </p>
                                        @else
                                            <p>
                                                Inactive el punto únicamente cuando
                                                ya no deba estar disponible como
                                                origen o destino de nuevas rutas.
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                @if ($tieneRutasActivas)
                                    <div class="cc-alert cc-alert-danger">
                                        La inactivación no está disponible mientras
                                        el punto se encuentre relacionado con rutas
                                        activas.
                                    </div>
                                @else
                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'puntos-ruta.inactivar',
                                            array_merge(
                                                $queryParams,
                                                ['puntoRuta' => $puntoRuta]
                                            )
                                        ) }}"
                                        class="cc-danger-zone-form"
                                        onsubmit="return confirmarInactivacionPuntoRuta();"
                                    >
                                        @csrf
                                        @method('PATCH')

                                        <input
                                            type="hidden"
                                            name="return_to"
                                            value="ventana"
                                        >

                                        <input
                                            type="hidden"
                                            name="return_query"
                                            value="{{ $returnQuery }}"
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
                                                    value="Cambio operativo"
                                                    @selected(
                                                        old('motivo_inactivacion')
                                                        === 'Cambio operativo'
                                                    )
                                                >
                                                    Cambio operativo
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
                                            Inactivar punto
                                        </button>
                                    </form>
                                @endif
                            </section>
                        @else
                            <div class="cc-actions">
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'puntos-ruta.reactivar',
                                        array_merge(
                                            $queryParams,
                                            ['puntoRuta' => $puntoRuta]
                                        )
                                    ) }}"
                                    onsubmit="return confirm(
                                        '¿Seguro que deseas reactivar este punto de ruta?'
                                    );"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <input
                                        type="hidden"
                                        name="return_to"
                                        value="ventana"
                                    >

                                    <input
                                        type="hidden"
                                        name="return_query"
                                        value="{{ $returnQuery }}"
                                    >

                                    <button
                                        type="submit"
                                        class="cc-btn-success cc-btn-form-action"
                                    >
                                        Reactivar punto
                                    </button>
                                </form>
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <script>
            function confirmarInactivacionPuntoRuta() {
                const motivo = document.getElementById(
                    'motivo_inactivacion'
                )?.value;

                if (! motivo) {
                    alert(
                        'Debe seleccionar un motivo de inactivación.'
                    );

                    return false;
                }

                return confirm(
                    `¿Seguro que deseas inactivar este punto de ruta por el motivo "${motivo}"?`
                );
            }
        </script>
    </body>
</html>
