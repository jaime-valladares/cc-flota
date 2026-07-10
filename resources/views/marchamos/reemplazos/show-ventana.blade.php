<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Reemplazo de marchamos | CC-Flota</title>

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
                    <div class="cc-card">

                        @php
                            $totalPuntos = $puntos->count();
                            $totalMarchamosActuales = $puntos->filter(fn ($punto) => ! is_null($punto->marchamo_actual_id))->count();

                            $rutaVolver = route('marchamos.reemplazos.index.ventana', [
                                'empresa_id' => $unidad->empresa_id,
                                'unidad_id' => $unidad->id,
                                'consultar' => 1,
                            ]);

                            $rutaSistema = route('marchamos.reemplazos.show', $unidad);
                        @endphp

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Reemplazo de marchamos
                                </h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ $rutaVolver }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver
                                </a>

                                <a href="{{ $rutaSistema }}" class="cc-btn-secondary cc-btn-wide">
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
                                <span class="cc-badge cc-badge-active">
                                    Activa
                                </span>
                            </div>
                        </div>

                        <section class="cc-detail-section mt-5">
                            <div class="cc-detail-section-header">
                                <h5>
                                    Cobertura actual
                                </h5>
                                <p>
                                    La unidad cuenta con marchamos activos. El reemplazo registra historial y conserva trazabilidad.
                                </p>
                            </div>

                            <div class="cc-summary-strip">
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
                                        Marchamos actuales
                                    </span>
                                    <span class="cc-summary-strip-value cc-summary-strip-value-success">
                                        {{ $totalMarchamosActuales }}
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">
                                        Cobertura
                                    </span>
                                    <span class="cc-summary-strip-value cc-summary-strip-value-success">
                                        Completa
                                    </span>
                                </div>
                            </div>
                        </section>

                        <form method="POST" action="{{ route('marchamos.reemplazos.store', $unidad) }}" class="mt-6">
                            @csrf
                            <input type="hidden" name="return_to" value="ventana">

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Puntos de seguridad
                                    </h5>
                                    <p>
                                        Marque los puntos que desea reemplazar. Cada punto seleccionado requiere un nuevo código de 7 dígitos y un motivo.
                                    </p>
                                </div>

                                <div class="cc-table-adaptive-wrapper">
                                    <table class="cc-table-adaptive" style="min-width: 96rem;">
                                        <thead>
                                            <tr>
                                                <th class="cc-table-adaptive-nowrap" style="width: 5rem;">
                                                    Sel.
                                                </th>

                                                <th class="cc-table-adaptive-nowrap" style="width: 8rem;">
                                                    Orden
                                                </th>

                                                <th class="cc-table-adaptive-nowrap" style="width: 32rem;">
                                                    Punto de seguridad
                                                </th>

                                                <th class="cc-table-adaptive-nowrap" style="width: 14rem;">
                                                    Marchamo actual
                                                </th>

                                                <th class="cc-table-adaptive-nowrap" style="width: 16rem;">
                                                    Nuevo marchamo
                                                </th>

                                                <th class="cc-table-adaptive-nowrap" style="width: 21rem;">
                                                    Motivo
                                                </th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @forelse ($puntos as $index => $punto)
                                                <tr>
                                                    <td class="cc-table-adaptive-nowrap">
                                                        <input
                                                            type="hidden"
                                                            name="reemplazos[{{ $index }}][punto_seguridad_id]"
                                                            value="{{ $punto->id }}">

                                                        <input
                                                            type="checkbox"
                                                            name="reemplazos[{{ $index }}][seleccionado]"
                                                            value="1"
                                                            class="h-5 w-5 rounded border-[var(--cc-border)] text-[var(--cc-primary)] focus:ring-[var(--cc-primary)]"
                                                            @checked(old("reemplazos.{$index}.seleccionado"))>
                                                    </td>

                                                    <td class="cc-table-adaptive-nowrap">
                                                        <div class="cc-table-adaptive-strong">
                                                            {{ $punto->orden }}
                                                        </div>

                                                        @if ($punto->codigo_punto)
                                                            <div class="cc-table-adaptive-muted">
                                                                {{ $punto->codigo_punto }}
                                                            </div>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        <div class="cc-table-adaptive-strong">
                                                            {{ $punto->nombre_punto }}
                                                        </div>

                                                        <div class="cc-table-adaptive-muted">
                                                            {{ $punto->grupo ?: 'Sin grupo' }}

                                                            @if ($punto->subgrupo)
                                                                · {{ $punto->subgrupo }}
                                                            @endif

                                                            @if ($punto->posicion_tanque)
                                                                · {{ $punto->posicion_tanque }}
                                                            @endif
                                                        </div>
                                                    </td>

                                                    <td class="cc-table-adaptive-nowrap">
                                                        @if ($punto->marchamoActual)
                                                            <div class="cc-table-adaptive-strong">
                                                                {{ $punto->marchamoActual->codigo_marchamo }}
                                                            </div>

                                                            <div class="cc-table-adaptive-muted text-[var(--cc-success)]">
                                                                Activo
                                                            </div>
                                                        @else
                                                            <span class="cc-table-adaptive-muted">
                                                                Sin marchamo
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td class="cc-table-adaptive-nowrap">
                                                        <input
                                                            type="text"
                                                            name="reemplazos[{{ $index }}][nuevo_codigo_marchamo]"
                                                            value="{{ old("reemplazos.{$index}.nuevo_codigo_marchamo") }}"
                                                            class="cc-input"
                                                            placeholder="0000000"
                                                            maxlength="7"
                                                            inputmode="numeric"
                                                            pattern="\d{7}">
                                                    </td>

                                                    <td class="cc-table-adaptive-nowrap">
                                                        <select
                                                            name="reemplazos[{{ $index }}][motivo_reemplazo]"
                                                            class="cc-input">
                                                            <option value="">Seleccione motivo</option>

                                                            @foreach ($motivosReemplazo as $codigo => $texto)
                                                                <option value="{{ $codigo }}" @selected(old("reemplazos.{$index}.motivo_reemplazo") === $codigo)>
                                                                    {{ $texto }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-[var(--cc-text-muted)] py-8">
                                                        No hay puntos de seguridad disponibles para esta unidad.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="cc-form-actions mt-6">
                                    <a href="{{ $rutaVolver }}" class="cc-btn-secondary cc-btn-form-action">
                                        Cancelar
                                    </a>

                                    <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                        Confirmar reemplazos
                                    </button>
                                </div>
                            </section>
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>