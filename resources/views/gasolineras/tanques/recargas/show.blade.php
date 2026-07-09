<x-app-layout>
    @php
        $empresaNombre = $gasolinera->empresa?->nombre_comercial ?: $gasolinera->empresa?->nombre_legal;

        $totalCapacidad = $resumenTanques->sum('capacidad_total');
        $totalActual = $resumenTanques->sum('volumen_actual');
        $totalDisponible = $resumenTanques->sum('capacidad_disponible');

        $tanquesBajoAlerta = $resumenTanques
            ->filter(fn ($item) => $item['bajo_alerta'])
            ->count();
    @endphp

    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Registrar recarga
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras.tanques.recargas.create.ventana', $gasolinera) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('gasolineras.tanques.recargas.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a recargas
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
                        <ul class="cc-alert-list">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="cc-profile-summary" style="margin-bottom: 1.1rem;">
                    <div style="min-width: 0;">
                        <div class="cc-profile-eyebrow">
                            Gasolinera interna
                        </div>

                        <div class="cc-profile-title">
                            {{ $gasolinera->nombre }}
                        </div>

                        <div class="cc-profile-meta">
                            <span>
                                {{ $empresaNombre }}
                            </span>

                            <span>
                                {{ $gasolinera->direccion ?: 'Sin dirección registrada' }}
                            </span>
                        </div>
                    </div>

                    <div class="cc-profile-status" style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
                        <span class="cc-badge cc-badge-active">
                            Recargable
                        </span>

                        @if ($tanquesBajoAlerta > 0)
                            <span class="cc-badge cc-badge-warning">
                                {{ $tanquesBajoAlerta }} bajo mínimo
                            </span>
                        @endif
                    </div>
                </div>

                <div class="cc-summary-strip">
                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Tanques activos</span>
                        <span class="cc-summary-strip-value">{{ $resumenTanques->count() }}</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Capacidad total</span>
                        <span class="cc-summary-strip-value">{{ number_format($totalCapacidad, 2) }} gal</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Inventario actual</span>
                        <span class="cc-summary-strip-value">{{ number_format($totalActual, 2) }} gal</span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">Disponible</span>
                        <span class="cc-summary-strip-value">{{ number_format($totalDisponible, 2) }} gal</span>
                    </div>
                </div>

                <div class="cc-detail-layout">

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Datos de la compra</h5>
                            <p>El precio por galón se aplicará a todos los tanques incluidos en esta recarga.</p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            <form method="POST" action="{{ route('gasolineras.tanques.recargas.store', $gasolinera) }}" novalidate>
                                @csrf

                                <div class="cc-grid cc-grid-compact">

                                    <div class="cc-form-section-slim">
                                        <div class="cc-form-section-title">
                                            Información operativa
                                        </div>
                                    </div>

                                    <div class="cc-field">
                                        <label for="empresa_visible">
                                            Empresa
                                        </label>

                                        <input
                                            id="empresa_visible"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $empresaNombre }}"
                                            disabled
                                        >
                                    </div>

                                    <div class="cc-field">
                                        <label for="gasolinera_visible">
                                            Gasolinera
                                        </label>

                                        <input
                                            id="gasolinera_visible"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $gasolinera->nombre }}"
                                            disabled
                                        >
                                    </div>

                                    <div class="cc-field">
                                        <label for="precio_galon">
                                            Precio por galón <span class="cc-required">*</span>
                                        </label>

                                        <input
                                            id="precio_galon"
                                            type="number"
                                            name="precio_galon"
                                            value="{{ old('precio_galon') }}"
                                            class="cc-input"
                                            min="0.0001"
                                            step="0.0001"
                                            required
                                            placeholder="Ej. 4.2500"
                                        >

                                        @error('precio_galon')
                                            <div class="cc-error">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="total_compra_estimado">
                                            Total estimado
                                        </label>

                                        <input
                                            id="total_compra_estimado"
                                            type="text"
                                            class="cc-input"
                                            value="$0.00"
                                            disabled
                                        >
                                    </div>

                                    <div class="cc-form-section-slim">
                                        <div class="cc-form-section-title">
                                            Distribución por tanque
                                        </div>
                                    </div>

                                    <div style="grid-column: 1 / -1;">
                                        @error('volumenes')
                                            <div class="cc-error" style="margin-bottom: 0.75rem;">{{ $message }}</div>
                                        @enderror

                                        @if ($resumenTanques->isEmpty())
                                            <div class="cc-empty-panel cc-empty-panel-compact">
                                                <h5>Sin tanques disponibles</h5>
                                                <p>Esta gasolinera no tiene tanques activos disponibles para recarga.</p>
                                            </div>
                                        @else
                                            <div class="space-y-3">
                                                @foreach ($resumenTanques as $item)
                                                    @php
                                                        $tanqueItem = $item['tanque'];
                                                        $oldVolumen = old('volumenes.' . $tanqueItem->id);
                                                        $estaPreseleccionado = (int) $tanquePreseleccionadoId === (int) $tanqueItem->id;
                                                    @endphp

                                                    <article
                                                        class="cc-result-card cc-result-card-compact"
                                                        data-tanque-row
                                                        data-actual="{{ $item['volumen_actual'] }}"
                                                        data-disponible="{{ $item['capacidad_disponible'] }}"
                                                    >
                                                        <div class="grid grid-cols-1 xl:grid-cols-6 gap-4 items-center">

                                                            <div class="xl:col-span-2">
                                                                <div class="cc-result-label">
                                                                    Tanque
                                                                </div>

                                                                <div class="cc-result-value cc-cell-truncate">
                                                                    {{ $tanqueItem->nombre }}
                                                                </div>

                                                                <div class="cc-result-value-muted">
                                                                    @if ($estaPreseleccionado)
                                                                        Seleccionado desde búsqueda
                                                                    @else
                                                                        Disponible para esta operación
                                                                    @endif
                                                                </div>
                                                            </div>

                                                            <div>
                                                                <div class="cc-result-label">
                                                                    Actual
                                                                </div>

                                                                <div class="cc-result-value">
                                                                    {{ number_format($item['volumen_actual'], 2) }} gal
                                                                </div>

                                                                <div class="cc-result-value-muted">
                                                                    {{ number_format($item['porcentaje_disponible'], 2) }}% ocupado
                                                                </div>
                                                            </div>

                                                            <div>
                                                                <div class="cc-result-label">
                                                                    Disponible
                                                                </div>

                                                                <div class="cc-result-value">
                                                                    {{ number_format($item['capacidad_disponible'], 2) }} gal
                                                                </div>

                                                                <div class="cc-result-value-muted">
                                                                    Capacidad: {{ number_format($item['capacidad_total'], 2) }} gal
                                                                </div>
                                                            </div>

                                                            <div class="cc-field" style="margin-bottom: 0;">
                                                                <label for="volumen_{{ $tanqueItem->id }}">
                                                                    Galones a recargar
                                                                </label>

                                                                <input
                                                                    id="volumen_{{ $tanqueItem->id }}"
                                                                    type="number"
                                                                    name="volumenes[{{ $tanqueItem->id }}]"
                                                                    value="{{ $oldVolumen }}"
                                                                    class="cc-input"
                                                                    min="0"
                                                                    max="{{ $item['capacidad_disponible'] }}"
                                                                    step="0.01"
                                                                    placeholder="0.00"
                                                                    data-volumen-input
                                                                >

                                                                @error('volumenes.' . $tanqueItem->id)
                                                                    <div class="cc-error">{{ $message }}</div>
                                                                @enderror
                                                            </div>

                                                            <div>
                                                                <div class="cc-result-label">
                                                                    Subtotal
                                                                </div>

                                                                <div class="cc-result-value" data-subtotal-display>
                                                                    $0.00
                                                                </div>

                                                                <div class="cc-result-value-muted" data-resultante-display>
                                                                    Resultante: {{ number_format($item['volumen_actual'], 2) }} gal
                                                                </div>
                                                            </div>

                                                        </div>
                                                    </article>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <div class="cc-form-section-slim">
                                        <div class="cc-form-section-title">
                                            Resumen de la recarga
                                        </div>
                                    </div>

                                    <div class="cc-field">
                                        <label for="total_galones_estimado">
                                            Total galones
                                        </label>

                                        <input
                                            id="total_galones_estimado"
                                            type="text"
                                            class="cc-input"
                                            value="0.00 gal"
                                            disabled
                                        >
                                    </div>

                                </div>

                                <div class="cc-actions cc-actions-compact">
                                    <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                        Registrar recarga
                                    </button>
                                </div>
                            </form>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>Recargas recientes</h5>
                            <p>Últimas compras de combustible registradas para esta gasolinera.</p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            @if ($recargasRecientes->isEmpty())
                                <div class="cc-empty-panel cc-empty-panel-compact">
                                    <h5>Sin recargas</h5>
                                    <p>Esta gasolinera todavía no tiene recargas registradas.</p>
                                </div>
                            @else
                                <div class="cc-results-list">
                                    @foreach ($recargasRecientes as $recarga)
                                        <article class="cc-result-card cc-result-card-compact">
                                            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center">
                                                <div>
                                                    <div class="cc-result-label">
                                                        Fecha
                                                    </div>

                                                    <div class="cc-result-value">
                                                        {{ optional($recarga->fecha_hora_recarga)->format('d/m/Y H:i') }}
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Galones
                                                    </div>

                                                    <div class="cc-result-value">
                                                        {{ number_format((float) $recarga->total_galones, 2) }} gal
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Precio
                                                    </div>

                                                    <div class="cc-result-value">
                                                        ${{ number_format((float) $recarga->precio_galon, 4) }}
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Total
                                                    </div>

                                                    <div class="cc-result-value">
                                                        ${{ number_format((float) $recarga->total_compra, 2) }}
                                                    </div>
                                                </div>

                                                <div>
                                                    <div class="cc-result-label">
                                                        Registrado por
                                                    </div>

                                                    <div class="cc-result-value cc-cell-truncate">
                                                        {{ $recarga->usuarioRegistra?->name ?: 'No registrado' }}
                                                    </div>

                                                    <div class="cc-result-value-muted">
                                                        {{ $recarga->movimientosInventario->count() }} tanque(s)
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </section>

                </div>

            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const precioInput = document.getElementById('precio_galon');
            const totalCompraInput = document.getElementById('total_compra_estimado');
            const totalGalonesInput = document.getElementById('total_galones_estimado');
            const rows = document.querySelectorAll('[data-tanque-row]');

            function formatNumber(value, decimals = 2) {
                return Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            }

            function recalcularTotales() {
                const precio = parseFloat(precioInput.value || 0);
                let totalGalones = 0;
                let totalCompra = 0;

                rows.forEach(function (row) {
                    const actual = parseFloat(row.dataset.actual || 0);
                    const disponible = parseFloat(row.dataset.disponible || 0);
                    const volumenInput = row.querySelector('[data-volumen-input]');
                    const subtotalDisplay = row.querySelector('[data-subtotal-display]');
                    const resultanteDisplay = row.querySelector('[data-resultante-display]');

                    let volumen = parseFloat(volumenInput.value || 0);

                    if (volumen < 0) {
                        volumen = 0;
                        volumenInput.value = '0';
                    }

                    if (volumen > disponible) {
                        volumen = disponible;
                        volumenInput.value = disponible.toFixed(2);
                    }

                    const subtotal = volumen * precio;
                    const resultante = actual + volumen;

                    totalGalones += volumen;
                    totalCompra += subtotal;

                    subtotalDisplay.textContent = '$' + formatNumber(subtotal, 2);
                    resultanteDisplay.textContent = 'Resultante: ' + formatNumber(resultante, 2) + ' gal';
                });

                totalGalonesInput.value = formatNumber(totalGalones, 2) + ' gal';
                totalCompraInput.value = '$' + formatNumber(totalCompra, 2);
            }

            precioInput.addEventListener('input', recalcularTotales);

            rows.forEach(function (row) {
                const input = row.querySelector('[data-volumen-input]');
                input.addEventListener('input', recalcularTotales);
            });

            recalcularTotales();
        });
    </script>
</x-app-layout>