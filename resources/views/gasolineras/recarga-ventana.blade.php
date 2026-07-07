<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Recargar tanque | CC-Flota</title>

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
            <div class="cc-window-container" style="max-width: 79rem;">
                <div class="cc-card">

                    <div class="cc-card-header cc-card-header-compact">
                        <div>
                            <h3 class="cc-title cc-title-compact">
                                Recargar tanque
                            </h3>

                            <p class="cc-subtitle cc-subtitle-compact">
                                Registre una entrada de combustible y valide el volumen resultante antes de guardar.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('gasolineras.show.ventana', $gasolinera) }}" class="cc-btn-secondary cc-btn-wide">
                                Volver a ficha
                            </a>
                        </div>
                    </div>

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
                                Tanque seleccionado
                            </div>

                            <div class="cc-profile-title">
                                {{ $tanque->nombre }}
                            </div>

                            <div class="cc-profile-meta">
                                <span>{{ $gasolinera->nombre }}</span>
                                <span>{{ $gasolinera->empresa?->nombre_comercial ?: $gasolinera->empresa?->nombre_legal }}</span>
                            </div>
                        </div>

                        <div class="cc-profile-status">
                            <span class="cc-badge {{ $tanque->estado === 'activo' ? 'cc-badge-active' : 'cc-badge-inactive' }}">
                                {{ ucfirst($tanque->estado) }}
                            </span>
                        </div>
                    </div>

                    <div class="cc-summary-strip">
                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">Capacidad</span>
                            <span class="cc-summary-strip-value">
                                {{ number_format((float) $tanque->capacidad_total, 2) }} gal
                            </span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">Actual</span>
                            <span class="cc-summary-strip-value">
                                {{ number_format((float) $tanque->volumen_actual, 2) }} gal
                            </span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">Disponible para recarga</span>
                            <span class="cc-summary-strip-value">
                                {{ number_format((float) $tanque->capacidad_total - (float) $tanque->volumen_actual, 2) }} gal
                            </span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">Mínimo alerta</span>
                            <span class="cc-summary-strip-value">
                                {{ number_format((float) $tanque->volumen_minimo_alerta, 2) }} gal
                            </span>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('gasolineras.tanques.recarga.store', [$gasolinera, $tanque]) }}" novalidate>
                        @csrf

                        <input type="hidden" name="return_to" value="ventana">

                        <div class="cc-grid cc-grid-compact">

                            <div class="cc-form-section-slim">
                                <div class="cc-form-section-title">
                                    Datos de recarga
                                </div>
                            </div>

                            <div class="cc-field">
                                <label for="volumen_movimiento">
                                    Cantidad a recargar (galones) <span class="cc-required">*</span>
                                </label>

                                <input
                                    id="volumen_movimiento"
                                    name="volumen_movimiento"
                                    type="number"
                                    class="cc-input"
                                    value="{{ old('volumen_movimiento') }}"
                                    min="0.01"
                                    step="0.01"
                                    required
                                    placeholder="Ej. 500.00"
                                >

                                @error('volumen_movimiento')
                                    <div class="cc-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="cc-field">
                                <label for="volumen_resultante_preview">
                                    Volumen resultante
                                </label>

                                <input
                                    id="volumen_resultante_preview"
                                    type="text"
                                    class="cc-input"
                                    value="{{ number_format((float) $tanque->volumen_actual, 2) }} gal"
                                    disabled
                                >
                            </div>

                            <div class="cc-field cc-col-span-2">
                                <label for="observaciones">
                                    Observaciones
                                </label>

                                <input
                                    id="observaciones"
                                    name="observaciones"
                                    type="text"
                                    class="cc-input"
                                    value="{{ old('observaciones') }}"
                                    maxlength="255"
                                    placeholder="Detalle opcional de la recarga"
                                >

                                @error('observaciones')
                                    <div class="cc-error">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="cc-field cc-col-span-2">
                                <div id="recargaMensaje" class="cc-empty-panel cc-empty-panel-compact">
                                    <h5>Validación de capacidad</h5>
                                    <p>
                                        Ingrese la cantidad a recargar. El sistema bloqueará el registro si el volumen resultante supera la capacidad total del tanque.
                                    </p>
                                </div>
                            </div>

                        </div>

                        <div class="cc-actions cc-actions-compact">
                            <button id="guardarRecargaBtn" type="submit" class="cc-btn-primary cc-btn-form-action">
                                Guardar recarga
                            </button>

                            <a href="{{ route('gasolineras.show.ventana', $gasolinera) }}" class="cc-btn-secondary cc-btn-form-action">
                                Cancelar
                            </a>
                        </div>
                    </form>

                </div>
            </div>
        </div>

        <script>
            const volumenMovimientoInput = document.getElementById('volumen_movimiento');
            const volumenResultantePreview = document.getElementById('volumen_resultante_preview');
            const recargaMensaje = document.getElementById('recargaMensaje');
            const guardarRecargaBtn = document.getElementById('guardarRecargaBtn');

            const volumenActual = Number(@json((float) $tanque->volumen_actual));
            const capacidadTotal = Number(@json((float) $tanque->capacidad_total));

            function formatNumber(value) {
                return new Intl.NumberFormat('es-SV', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }).format(value);
            }

            function validarRecarga() {
                if (! volumenMovimientoInput || ! volumenResultantePreview || ! recargaMensaje || ! guardarRecargaBtn) {
                    return;
                }

                const movimiento = Number(volumenMovimientoInput.value || 0);
                const resultante = volumenActual + movimiento;
                const disponible = capacidadTotal - volumenActual;

                volumenResultantePreview.value = `${formatNumber(resultante)} gal`;

                if (movimiento <= 0) {
                    volumenMovimientoInput.setCustomValidity('');
                    guardarRecargaBtn.disabled = false;

                    recargaMensaje.className = 'cc-empty-panel cc-empty-panel-compact';
                    recargaMensaje.innerHTML = `
                        <h5>Validación de capacidad</h5>
                        <p>Ingrese la cantidad a recargar. Disponible máximo: ${formatNumber(disponible)} gal.</p>
                    `;

                    return;
                }

                if (resultante > capacidadTotal) {
                    volumenMovimientoInput.setCustomValidity('La recarga supera la capacidad total del tanque.');
                    guardarRecargaBtn.disabled = true;

                    recargaMensaje.className = 'cc-empty-panel cc-empty-panel-compact';
                    recargaMensaje.innerHTML = `
                        <h5>Capacidad excedida</h5>
                        <p>La recarga ingresada supera la capacidad total. Disponible máximo: ${formatNumber(disponible)} gal.</p>
                    `;

                    return;
                }

                volumenMovimientoInput.setCustomValidity('');
                guardarRecargaBtn.disabled = false;

                recargaMensaje.className = 'cc-empty-panel cc-empty-panel-compact';
                recargaMensaje.innerHTML = `
                    <h5>Recarga válida</h5>
                    <p>El volumen resultante será ${formatNumber(resultante)} gal de ${formatNumber(capacidadTotal)} gal.</p>
                `;
            }

            if (volumenMovimientoInput) {
                volumenMovimientoInput.addEventListener('input', validarRecarga);
            }

            validarRecarga();
        </script>
    </body>
</html>