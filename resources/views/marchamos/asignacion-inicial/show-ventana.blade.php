<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Asignación inicial de marchamos | CC-Flota</title>

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

                        @php
                            $rutaVolverAsignacion = route(
                                'marchamos.asignacion-inicial.index.ventana',
                                [
                                    'empresa_ids' => [
                                        $unidad->empresa_id,
                                    ],
                                    'placas' => [
                                        $unidad->placa,
                                    ],
                                    'consultar' => 1,
                                ]
                            );

                            $asignacionCompleta =
                                $totalPuntos > 0
                                && $puntosPendientes === 0;
                        @endphp

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Asignación inicial de marchamos
                                </h3>

                                <p class="cc-subtitle cc-subtitle-compact">
                                    Registre o corrija los códigos instalados antes de finalizar oficialmente la asignación.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    href="{{ $rutaVolverAsignacion }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver a asignación
                                </a>

                                <a
                                    href="{{ route(
                                        'marchamos.asignacion-inicial.show',
                                        $unidad
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="cc-alert-danger">
                                <div class="font-bold">
                                    Revise la información ingresada.
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
                                    Unidad
                                </div>

                                <div class="cc-profile-title">
                                    {{ $unidad->placa }}
                                </div>

                                <div class="cc-profile-meta flex flex-wrap gap-x-5 gap-y-2">
                                    <span>
                                        <strong>Marca:</strong>
                                        {{ $unidad->marca ?: 'Sin marca registrada' }}
                                    </span>

                                    <span>
                                        <strong>Empresa:</strong>

                                        @if ($unidad->empresa)
                                            {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                        @else
                                            Sin empresa
                                        @endif
                                    </span>

                                    <span>
                                        <strong>Licencia:</strong>

                                        @if ($unidad->licencia)
                                            {{ $unidad->licencia->periodo_vigencia_texto }}
                                        @else
                                            Sin licencia
                                        @endif
                                    </span>

                                    <span>
                                        <strong>Plantilla:</strong>

                                        @if ($unidad->licencia)
                                            {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                        @else
                                            Sin plantilla
                                        @endif
                                    </span>
                                </div>
                            </div>

                            <div class="cc-profile-status">
                                <span class="cc-badge cc-badge-warning">
                                    Registrada
                                </span>
                            </div>
                        </div>

                        <section class="cc-detail-section mt-5">
                            <div class="cc-detail-section-header">
                                <h5>
                                    Avance de asignación
                                </h5>

                                <p>
                                    Los códigos guardados permanecen como avances provisionales hasta que la asignación inicial sea finalizada.
                                </p>
                            </div>

                            <div class="cc-summary-strip">
                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">
                                        Avance
                                    </span>

                                    <span class="cc-summary-strip-value">
                                        {{ $porcentajeAvance }}%
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">
                                        Total puntos
                                    </span>

                                    <span class="cc-summary-strip-value">
                                        {{ $totalPuntos }}
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">
                                        Asignados
                                    </span>

                                    <span class="cc-summary-strip-value">
                                        {{ $puntosAsignados }}
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">
                                        Pendientes
                                    </span>

                                    <span class="cc-summary-strip-value">
                                        {{ $puntosPendientes }}
                                    </span>
                                </div>
                            </div>
                        </section>

                        <section class="cc-detail-section mt-6">
                            <div class="cc-detail-section-header">
                                <h5>
                                    Registro de marchamos
                                </h5>

                                <p>
                                    Ingrese códigos de exactamente 7 dígitos. Puede guardar avances parciales y corregirlos antes de finalizar.
                                </p>
                            </div>

                            <form
                                method="POST"
                                action="{{ route(
                                    'marchamos.asignacion-inicial.guardar-avance',
                                    $unidad
                                ) }}"
                            >
                                @csrf

                                <input
                                    type="hidden"
                                    name="return_to"
                                    value="ventana"
                                >

                                <div class="cc-table-adaptive-wrapper">
                                    <table
                                        class="cc-table-adaptive"
                                        style="min-width: 73rem;"
                                    >
                                        <thead>
                                            <tr>
                                                <th
                                                    class="cc-table-adaptive-nowrap"
                                                    style="width: 7rem;"
                                                >
                                                    Orden
                                                </th>

                                                <th
                                                    class="cc-table-adaptive-nowrap"
                                                    style="width: 27rem;"
                                                >
                                                    Punto de seguridad
                                                </th>

                                                <th
                                                    class="cc-table-adaptive-nowrap"
                                                    style="width: 11rem;"
                                                >
                                                    Código punto
                                                </th>

                                                <th
                                                    class="cc-table-adaptive-nowrap"
                                                    style="width: 11rem;"
                                                >
                                                    Posición
                                                </th>

                                                <th
                                                    class="cc-table-adaptive-nowrap"
                                                    style="width: 11rem;"
                                                >
                                                    Estado
                                                </th>

                                                <th
                                                    class="cc-table-adaptive-nowrap"
                                                    style="width: 14rem;"
                                                >
                                                    Marchamo
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @forelse ($unidad->puntosSeguridad as $punto)
                                                @php
                                                    $codigoActual =
                                                        $punto->marchamoActual
                                                            ?->codigo_marchamo;

                                                    $tieneCodigo =
                                                        filled($codigoActual);
                                                @endphp

                                                <tr>
                                                    <td class="cc-table-adaptive-nowrap">
                                                        <div class="cc-table-adaptive-strong">
                                                            {{ $punto->orden }}
                                                        </div>
                                                    </td>

                                                    <td>
                                                        <div class="cc-table-adaptive-strong">
                                                            {{ $punto->nombre_punto }}
                                                        </div>

                                                        @if ($punto->grupo || $punto->subgrupo)
                                                            <div class="cc-table-adaptive-muted">
                                                                {{ $punto->grupo ?: 'Sin grupo' }}

                                                                @if ($punto->subgrupo)
                                                                    · {{ $punto->subgrupo }}
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td class="cc-table-adaptive-nowrap">
                                                        {{ $punto->codigo_punto ?: 'No definido' }}
                                                    </td>

                                                    <td class="cc-table-adaptive-nowrap">
                                                        {{ $punto->posicion_tanque ?: 'No definida' }}
                                                    </td>

                                                    <td class="cc-table-adaptive-nowrap">
                                                        @if ($tieneCodigo)
                                                            <span class="cc-badge cc-badge-active">
                                                                Asignado
                                                            </span>
                                                        @else
                                                            <span class="cc-badge cc-badge-warning">
                                                                Pendiente
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="cc-table-adaptive-nowrap">
                                                        <input
                                                            type="text"
                                                            name="marchamos[{{ $punto->id }}]"
                                                            value="{{ old(
                                                                'marchamos.' . $punto->id,
                                                                $codigoActual
                                                            ) }}"
                                                            class="cc-input"
                                                            placeholder="0006387"
                                                            maxlength="7"
                                                            inputmode="numeric"
                                                            pattern="\d{7}"
                                                            autocomplete="off"
                                                        >

                                                        <div class="cc-table-adaptive-muted mt-1">
                                                            @if ($tieneCodigo)
                                                                Código provisional editable
                                                            @else
                                                                Pendiente de registro
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td
                                                        colspan="6"
                                                        class="text-center text-[var(--cc-text-muted)] py-8"
                                                    >
                                                        La unidad no tiene puntos de seguridad disponibles para asignación.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="cc-actions cc-actions-compact mt-6 mb-6">
                                    <button
                                        type="submit"
                                        class="cc-btn-primary cc-btn-form-action"
                                    >
                                        Guardar avance
                                    </button>
                                </div>
                            </form>
                        </section>

                        <section class="cc-detail-section mt-6">
                            <div class="cc-detail-section-header">
                                <h5>
                                    Finalización de asignación inicial
                                </h5>

                                @if ($asignacionCompleta)
                                    <p>
                                        Todos los puntos tienen un marchamo provisional asignado. Puede finalizar para hacer oficial la cobertura y activar la unidad.
                                    </p>
                                @else
                                    <p>
                                        La asignación todavía no puede finalizarse. Todos los puntos activos que requieren marchamo deben estar completos.
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                                <div>
                                    <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                        Al finalizar, los marchamos pasan a formar parte oficial de la unidad y esta cambia de Registrada a Activa.
                                    </p>

                                    @if (! $asignacionCompleta)
                                        <p class="mt-2 text-sm font-semibold text-[var(--cc-danger)]">
                                            Faltan {{ $puntosPendientes }} puntos por completar.
                                        </p>
                                    @endif
                                </div>

                                <form
                                    method="POST"
                                    action="{{ route(
                                        'marchamos.asignacion-inicial.finalizar',
                                        $unidad
                                    ) }}"
                                    onsubmit="return confirm(
                                        '¿Está seguro de finalizar la asignación inicial? La unidad pasará a estado activa y los marchamos quedarán registrados oficialmente.'
                                    );"
                                >
                                    @csrf

                                    <input
                                        type="hidden"
                                        name="return_to"
                                        value="ventana"
                                    >

                                    <button
                                        type="submit"
                                        class="{{ $asignacionCompleta
                                            ? 'cc-btn-success'
                                            : 'cc-btn-secondary opacity-60 cursor-not-allowed'
                                        }} cc-btn-form-action"
                                        @disabled(! $asignacionCompleta)
                                    >
                                        Finalizar asignación inicial
                                    </button>
                                </form>
                            </div>
                        </section>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>