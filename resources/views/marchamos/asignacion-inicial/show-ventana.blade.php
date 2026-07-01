<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Asignación inicial de marchamos | CC-Flota</title>

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
                                    Asignación inicial de marchamos
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
                                    Registre los marchamos físicos instalados en cada punto de seguridad de la unidad.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('marchamos.asignacion-inicial.index.ventana', [
                                    'empresa_id' => $unidad->empresa_id,
                                    'placa' => $unidad->placa,
                                    'consultar' => 1,
                                ]) }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver a asignación
                                </a>

                                <a href="{{ route('marchamos.asignacion-inicial.show', $unidad) }}" class="cc-btn-secondary cc-btn-wide">
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
                                        <li>{{ $error }}</li>
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

                                    @if ($unidad->empresa)
                                        <span>
                                            <strong>Empresa:</strong>
                                            {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                        </span>
                                    @else
                                        <span>
                                            <strong>Empresa:</strong>
                                            Sin empresa
                                        </span>
                                    @endif

                                    @if ($unidad->licencia)
                                        <span>
                                            <strong>Licencia:</strong>
                                            {{ $unidad->licencia->periodo_vigencia_texto }}
                                        </span>

                                        <span>
                                            <strong>Plantilla:</strong>
                                            {{ $unidad->licencia->plantilla_puntos_seguridad_texto }}
                                        </span>
                                    @else
                                        <span>
                                            <strong>Licencia:</strong>
                                            Sin licencia
                                        </span>

                                        <span>
                                            <strong>Plantilla:</strong>
                                            Sin plantilla
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="cc-profile-status">
                                @if ($unidad->estado === 'registrada')
                                    <span class="cc-badge cc-badge-warning">
                                        Registrada
                                    </span>
                                @elseif ($unidad->estado === 'activa')
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

                        <section class="cc-detail-section mt-5">
                            <div class="cc-detail-section-header">
                                <h5>
                                    Avance de asignación
                                </h5>
                                <p>
                                    Estado actual de la instalación física de marchamos en los puntos de seguridad.
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
                                    Puede guardar avances parciales. Los códigos deben tener exactamente 7 dígitos, conservando ceros a la izquierda.
                                </p>
                            </div>

                            <form method="POST" action="{{ route('marchamos.asignacion-inicial.guardar-avance', $unidad) }}">
                                @csrf

                                <input type="hidden" name="return_to" value="ventana">

                                <div class="overflow-x-auto">
                                    <table class="cc-table">
                                        <thead>
                                            <tr>
                                                <th style="width: 80px;">Orden</th>
                                                <th>Punto de seguridad</th>
                                                <th style="width: 180px;">Código punto</th>
                                                <th style="width: 180px;">Posición</th>
                                                <th style="width: 180px;">Estado</th>
                                                <th style="width: 220px;">Marchamo</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($unidad->puntosSeguridad as $punto)
                                                <tr>
                                                    <td>
                                                        <span class="font-bold">
                                                            {{ $punto->orden }}
                                                        </span>
                                                    </td>

                                                    <td>
                                                        <div class="font-bold text-[var(--cc-text-main)]">
                                                            {{ $punto->nombre_punto }}
                                                        </div>

                                                        @if ($punto->grupo || $punto->subgrupo)
                                                            <div class="text-sm text-[var(--cc-text-muted)]">
                                                                {{ $punto->grupo }}

                                                                @if ($punto->subgrupo)
                                                                    · {{ $punto->subgrupo }}
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        {{ $punto->codigo_punto ?: 'No definido' }}
                                                    </td>

                                                    <td>
                                                        {{ $punto->posicion_tanque ?: 'No definida' }}
                                                    </td>

                                                    <td>
                                                        @if ($punto->marchamo_actual_id)
                                                            <span class="cc-badge cc-badge-active">
                                                                Asignado
                                                            </span>
                                                        @else
                                                            <span class="cc-badge cc-badge-warning">
                                                                Pendiente
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        @if ($punto->marchamo_actual_id && $punto->marchamoActual)
                                                            <div class="font-bold text-[var(--cc-text-main)]">
                                                                {{ $punto->marchamoActual->codigo_marchamo }}
                                                            </div>

                                                            <div class="text-xs text-[var(--cc-text-muted)]">
                                                                Activo
                                                            </div>
                                                        @else
                                                            <input
                                                                type="text"
                                                                name="marchamos[{{ $punto->id }}]"
                                                                value="{{ old('marchamos.' . $punto->id) }}"
                                                                class="cc-input"
                                                                placeholder="Ej. 0006387"
                                                                maxlength="7"
                                                                inputmode="numeric"
                                                                pattern="\d{7}">
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="cc-actions cc-actions-compact mt-6 mb-6">
                                    <button type="submit" class="cc-btn-primary cc-btn-form-action">
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

                                @if ($puntosPendientes > 0)
                                    <p>
                                        La unidad aún tiene puntos pendientes. Para finalizar, todos los puntos activos deben tener un marchamo asignado.
                                    </p>
                                @else
                                    <p>
                                        Todos los puntos de seguridad tienen marchamo asignado. Puede finalizar la asignación inicial para activar la unidad.
                                    </p>
                                @endif
                            </div>

                            <div class="flex flex-col gap-4 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                                <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                    Esta acción cambia la unidad de Registrada a Activa. Debe usarse únicamente cuando la instalación física esté completa.
                                </p>

                                <form method="POST"
                                      action="{{ route('marchamos.asignacion-inicial.finalizar', $unidad) }}"
                                      onsubmit="return confirm('¿Está seguro de finalizar la asignación inicial? La unidad pasará a estado activa.');">
                                    @csrf

                                    <input type="hidden" name="return_to" value="ventana">

                                    <button type="submit"
                                            class="cc-btn-success cc-btn-form-action"
                                            @disabled($puntosPendientes > 0)>
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