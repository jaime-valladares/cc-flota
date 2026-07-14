@php
    $queryParams = collect(request()->query())
        ->except([
            'ruta',
            'return_to',
            'return_query',
        ])
        ->all();

    $returnQuery = http_build_query($queryParams);

    $nombreEmpresa = $ruta->empresa?->nombre_comercial
        ?: $ruta->empresa?->nombre_legal
        ?: 'Empresa no disponible';

    $rutaActiva = $ruta->estado === 'activo';

    $empresaActiva = $ruta->empresa
        && $ruta->empresa->estado === 'activa';

    $puntoUnoActivo = $ruta->puntoOrigen
        && $ruta->puntoOrigen->estado === 'activo';

    $puntoDosActivo = $ruta->puntoDestino
        && $ruta->puntoDestino->estado === 'activo';

    $puedeReactivarse = ! $rutaActiva
        && $empresaActiva
        && $puntoUnoActivo
        && $puntoDosActivo;
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Ficha administrativa de ruta | CC-Flota</title>

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
                                    Ficha administrativa de ruta
                                </h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    href="{{ route(
                                        'rutas.administrar.ventana',
                                        $queryParams
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver a administrar
                                </a>

                                <a
                                    href="{{ route(
                                        'rutas.show',
                                        array_merge(
                                            $queryParams,
                                            ['ruta' => $ruta]
                                        )
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

                        @if ($errors->any())
                            <div class="cc-alert cc-alert-danger">
                                <div class="font-bold">
                                    No fue posible completar la operación.
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
                                    Ruta
                                </div>

                                <h4 class="cc-profile-title">
                                    {{ $ruta->ruta }}
                                </h4>

                                <div class="cc-profile-meta">
                                    <span>
                                        Empresa: {{ $nombreEmpresa }}
                                    </span>

                                    <span>
                                        {{ $ruta->puntoOrigen?->nombre
                                            ?: 'Punto no disponible' }}
                                        ↔
                                        {{ $ruta->puntoDestino?->nombre
                                            ?: 'Punto no disponible' }}
                                    </span>
                                </div>
                            </div>

                            <div class="cc-profile-status">
                                @if ($rutaActiva)
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
                                        Identificación de la ruta
                                    </h5>

                                    <p>
                                        Puntos que conforman la combinación registrada para la empresa.
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
                                            Ruta
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $ruta->ruta }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Punto 1
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $ruta->puntoOrigen?->nombre
                                                ?: 'Punto no disponible' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Punto 2
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $ruta->puntoDestino?->nombre
                                                ?: 'Punto no disponible' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">
                                            Alcance
                                        </div>

                                        <div class="cc-detail-value">
                                            La combinación representa una sola ruta,
                                            independientemente de la dirección del recorrido.
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Valores estimados
                                    </h5>

                                    <p>
                                        Referencias operativas registradas para la ruta.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Kilómetros estimados
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ number_format(
                                                (float) $ruta->kilometros_estimados,
                                                1
                                            ) }} km
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Galones estimados
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ number_format(
                                                (float) $ruta->galones_estimados,
                                                1
                                            ) }} gal
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Disponibilidad administrativa
                                    </h5>

                                    <p>
                                        Condiciones actuales para editar, utilizar o reactivar la ruta.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Estado de la ruta
                                        </div>

                                        <div class="cc-detail-value">
                                            @if ($rutaActiva)
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
                                            Empresa
                                        </div>

                                        <div class="cc-detail-value">
                                            @if ($empresaActiva)
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
                                            Punto 1
                                        </div>

                                        <div class="cc-detail-value">
                                            @if ($puntoUnoActivo)
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
                                            Punto 2
                                        </div>

                                        <div class="cc-detail-value">
                                            @if ($puntoDosActivo)
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

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">
                                            Condición actual
                                        </div>

                                        <div class="cc-detail-value">
                                            @if ($rutaActiva)
                                                La ruta se encuentra disponible para
                                                operaciones futuras y puede editarse.
                                            @elseif ($puedeReactivarse)
                                                La ruta está disponible para reactivación.
                                            @else
                                                La ruta no puede reactivarse hasta que la
                                                empresa y ambos puntos se encuentren activos.
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
                                                $ruta->fecha_creacion
                                            )->format('d/m/Y H:i') ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Creado por
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $ruta->creadoPor?->name ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Fecha de actualización
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ optional(
                                                $ruta->fecha_actualizacion
                                            )->format('d/m/Y H:i') ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Actualizado por
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $ruta->actualizadoPor?->name ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Fecha de inactivación
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ optional(
                                                $ruta->fecha_inactivacion
                                            )->format('d/m/Y H:i') ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Inactivado por
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $ruta->inactivadoPor?->name ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">
                                            Motivo de inactivación
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $ruta->motivo_inactivacion ?? '—' }}
                                        </div>
                                    </div>
                                </div>
                            </section>

                        </div>

                        <div class="cc-actions cc-actions-split">
                            <div class="cc-actions-normal">

                                @if ($rutaActiva && $empresaActiva)
                                    <a
                                        href="{{ route(
                                            'rutas.edit.ventana',
                                            array_merge(
                                                $queryParams,
                                                ['ruta' => $ruta]
                                            )
                                        ) }}"
                                        class="cc-btn-primary cc-btn-form-action"
                                    >
                                        Editar ruta
                                    </a>
                                @endif

                                <a
                                    href="{{ route(
                                        'rutas.administrar.ventana',
                                        $queryParams
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-form-action"
                                >
                                    Volver a administrar
                                </a>

                            </div>
                        </div>

                        @if ($rutaActiva)
                            <section class="cc-danger-zone">
                                <div class="cc-danger-zone-header">
                                    <div>
                                        <h5>
                                            Zona de riesgo
                                        </h5>

                                        <p>
                                            Inactive la ruta únicamente cuando ya no deba
                                            estar disponible para operaciones futuras.
                                            Los registros históricos permanecerán intactos.
                                        </p>
                                    </div>
                                </div>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'rutas.inactivar',
                                        array_merge(
                                            $queryParams,
                                            ['ruta' => $ruta]
                                        )
                                    ) }}"
                                    class="cc-danger-zone-form"
                                    onsubmit="return confirmarInactivacionRuta();"
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
                                        Inactivar ruta
                                    </button>
                                </form>
                            </section>
                        @else
                            <div class="cc-actions">
                                @if ($puedeReactivarse)
                                    <form
                                        method="POST"
                                        action="{{ route(
                                            'rutas.reactivar',
                                            array_merge(
                                                $queryParams,
                                                ['ruta' => $ruta]
                                            )
                                        ) }}"
                                        onsubmit="return confirm(
                                            '¿Seguro que deseas reactivar esta ruta?'
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
                                            Reactivar ruta
                                        </button>
                                    </form>
                                @else
                                    <div class="cc-alert cc-alert-danger">
                                        La reactivación no está disponible porque la
                                        empresa o alguno de los puntos de la ruta se
                                        encuentra inactivo.
                                    </div>
                                @endif
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <script>
            function confirmarInactivacionRuta() {
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
                    `¿Seguro que deseas inactivar esta ruta por el motivo "${motivo}"?`
                );
            }
        </script>
    </body>
</html>