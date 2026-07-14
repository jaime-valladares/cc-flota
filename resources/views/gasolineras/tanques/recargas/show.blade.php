<x-app-layout>
    @php
        $empresaNombre = $gasolinera->empresa?->nombre_comercial ?: $gasolinera->empresa?->nombre_legal;

        $totalCapacidad = $resumenTanques->sum('capacidad_total');
        $totalInventario = $resumenTanques->sum('volumen_actual');
        $totalDisponible = $resumenTanques->sum('capacidad_disponible');
        $tanquesBajoAlerta = $resumenTanques->where('bajo_alerta', true)->count();
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
                        <a href="{{ route('gasolineras.tanques.recargas.create.ventana', ['gasolinera' => $gasolinera, 'tanque_id' => $tanquePreseleccionadoId]) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('gasolineras.tanques.recargas.index', ['consultar' => 1, 'gasolinera_id' => $gasolinera->id]) }}"
                           class="cc-btn-secondary cc-btn-wide">
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
                            Gasolinera
                        </div>

                        <div class="cc-profile-title">
                            {{ $gasolinera->nombre }}
                        </div>

                        <div class="cc-profile-meta">
                            <span>
                                {{ $empresaNombre }}
                            </span>

                            <span>
                                {{ $tanques->count() }} tanque{{ $tanques->count() === 1 ? '' : 's' }} activo{{ $tanques->count() === 1 ? '' : 's' }}
                            </span>
                        </div>
                    </div>

                    <div class="cc-profile-status">
                        <span class="cc-badge cc-badge-active">
                            {{ ucfirst($gasolinera->estado) }}
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
                        <span class="cc-summary-strip-label">
                            Capacidad total
                        </span>
                        <span class="cc-summary-strip-value">
                            {{ number_format($totalCapacidad, 2) }} gal
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Inventario actual
                        </span>
                        <span class="cc-summary-strip-value">
                            {{ number_format($totalInventario, 2) }} gal
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Espacio disponible
                        </span>
                        <span class="cc-summary-strip-value">
                            {{ number_format($totalDisponible, 2) }} gal
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Bajo mínimo
                        </span>
                        <span class="cc-summary-strip-value {{ $tanquesBajoAlerta > 0 ? 'cc-summary-strip-value-danger' : 'cc-summary-strip-value-success' }}">
                            {{ $tanquesBajoAlerta }}
                        </span>
                    </div>
                </div>

                <div class="cc-detail-layout">

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Datos de la recarga
                            </h5>

                            <p>
                                Ingrese el precio por galón y el volumen a recargar únicamente en los tanques que recibirán combustible.
                            </p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            @if ($tanques->isEmpty())
                                <div class="cc-empty-panel cc-empty-panel-compact">
                                    <h5>
                                        Sin tanques disponibles
                                    </h5>

                                    <p>
                                        Esta gasolinera no tiene tanques activos disponibles para recarga.
                                    </p>
                                </div>
                            @else
                                <form method="POST"
                                      action="{{ route('gasolineras.tanques.recargas.store', $gasolinera) }}"
                                      novalidate
                                      data-recarga-form>
                                    @csrf

                                    <div class="cc-grid cc-grid-compact">

                                        <div class="cc-form-section-slim">
                                            <div class="cc-form-section-title">
                                                Compra de combustible
                                            </div>
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
                                                data-precio-galon
                                            >

                                            @error('precio_galon')
                                                <div class="cc-error">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        </div>

                                        <div class="cc-field">
                                            <label for="total_galones_visible">
                                                Total galones
                                            </label>

                                            <input
                                                id="total_galones_visible"
                                                type="text"
                                                class="cc-input"
                                                value="0.00 gal"
                                                disabled
                                                data-total-galones
                                            >
                                        </div>

                                        <div class="cc-field">
                                            <label for="total_compra_visible">
                                                Total compra
                                            </label>

                                            <input
                                                id="total_compra_visible"
                                                type="text"
                                                class="cc-input"
                                                value="$0.00"
                                                disabled
                                                data-total-compra
                                            >
                                        </div>

                                        <div class="cc-form-section-slim">
                                            <div class="cc-form-section-title">
                                                Distribución por tanque
                                            </div>
                                        </div>

                                    </div>

                                    @error('volumenes')
                                        <div class="cc-error" style="margin-bottom: 1rem;">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                    <div class="cc-admin-result-list">
                                        @foreach ($resumenTanques as $resumen)
                                            @php
                                                $tanque = $resumen['tanque'];
                                                $capacidadTotal = (float) $resumen['capacidad_total'];
                                                $volumenActual = (float) $resumen['volumen_actual'];
                                                $capacidadDisponible = (float) $resumen['capacidad_disponible'];
                                                $porcentajeDisponible = (float) $resumen['porcentaje_disponible'];
                                                $bajoAlerta = (bool) $resumen['bajo_alerta'];

                                                $valorAnterior = old("volumenes.{$tanque->id}", '');
                                                $esPreseleccionado = (int) $tanquePreseleccionadoId === (int) $tanque->id;
                                            @endphp

                                            <article class="cc-admin-result-card">
                                                <div class="grid gap-5 xl:grid-cols-12 xl:items-start">

                                                    <div class="min-w-0 xl:col-span-3">
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <h5 class="cc-admin-result-title">
                                                                {{ $tanque->nombre }}
                                                            </h5>

                                                            <span class="cc-badge cc-badge-active">
                                                                Activo
                                                            </span>

                                                            @if ($bajoAlerta)
                                                                <span class="cc-badge cc-badge-warning">
                                                                    Bajo mínimo
                                                                </span>
                                                            @endif

                                                            @if ($esPreseleccionado)
                                                                <span class="cc-badge">
                                                                    Seleccionado
                                                                </span>
                                                            @endif
                                                        </div>

                                                        <div class="cc-admin-result-subtitle">
                                                            Tanque recargable
                                                        </div>
                                                    </div>

                                                    <div class="min-w-0 sm:grid sm:grid-cols-3 sm:gap-5 xl:col-span-5 xl:grid-cols-3">
                                                        <div class="min-w-0">
                                                            <div class="cc-admin-result-label">
                                                                Inventario
                                                            </div>

                                                            <div class="cc-admin-result-value">
                                                                {{ number_format($volumenActual, 2) }} gal
                                                            </div>

                                                            <div class="cc-admin-result-value-muted">
                                                                Actual
                                                            </div>
                                                        </div>

                                                        <div class="min-w-0">
                                                            <div class="cc-admin-result-label">
                                                                Espacio libre
                                                            </div>

                                                            <div class="cc-admin-result-value">
                                                                {{ number_format($capacidadDisponible, 2) }} gal
                                                            </div>

                                                            <div class="cc-admin-result-value-muted">
                                                                {{ number_format($porcentajeDisponible, 2) }}% disponible
                                                            </div>
                                                        </div>

                                                        <div class="min-w-0">
                                                            <div class="cc-admin-result-label">
                                                                Capacidad
                                                            </div>

                                                            <div class="cc-admin-result-value">
                                                                {{ number_format($capacidadTotal, 2) }} gal
                                                            </div>

                                                            <div class="cc-admin-result-value-muted">
                                                                Máximo instalado
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="min-w-0 xl:col-span-4">
                                                        <div class="grid gap-3 sm:grid-cols-2">
                                                            <div class="cc-field" style="margin-bottom: 0;">
                                                                <label for="volumen_{{ $tanque->id }}">
                                                                    Volumen a recargar
                                                                </label>

                                                                <input
                                                                    id="volumen_{{ $tanque->id }}"
                                                                    type="number"
                                                                    name="volumenes[{{ $tanque->id }}]"
                                                                    value="{{ $valorAnterior }}"
                                                                    class="cc-input"
                                                                    min="0"
                                                                    max="{{ $capacidadDisponible }}"
                                                                    step="0.01"
                                                                    placeholder="0.00"
                                                                    data-volumen-input
                                                                    data-volumen-actual="{{ $volumenActual }}"
                                                                    data-capacidad-total="{{ $capacidadTotal }}"
                                                                    data-capacidad-disponible="{{ $capacidadDisponible }}"
                                                                    @if ($esPreseleccionado) autofocus @endif
                                                                >

                                                                @error("volumenes.{$tanque->id}")
                                                                    <div class="cc-error">
                                                                        {{ $message }}
                                                                    </div>
                                                                @enderror
                                                            </div>

                                                            <div class="cc-field" style="margin-bottom: 0;">
                                                                <label for="resultado_{{ $tanque->id }}">
                                                                    Resultado
                                                                </label>

                                                                <input
                                                                    id="resultado_{{ $tanque->id }}"
                                                                    type="text"
                                                                    class="cc-input"
                                                                    value="{{ number_format($volumenActual, 2) }} gal"
                                                                    disabled
                                                                    data-volumen-resultante
                                                                >
                                                            </div>
                                                        </div>

                                                        <div class="cc-admin-result-value-muted" style="margin-top: .65rem;">
                                                            Subtotal estimado:
                                                            <strong data-subtotal-tanque>$0.00</strong>
                                                        </div>
                                                    </div>

                                                </div>
                                            </article>
                                        @endforeach
                                    </div>

                                    <div class="cc-actions cc-actions-compact">
                                        <button type="submit" class="cc-btn-primary cc-btn-form-action">
                                            Registrar recarga
                                        </button>

                                        <a href="{{ route('gasolineras.tanques.recargas.index', ['consultar' => 1, 'gasolinera_id' => $gasolinera->id]) }}"
                                           class="cc-btn-secondary cc-btn-form-action">
                                            Cancelar
                                        </a>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Recargas recientes
                            </h5>

                            <p>
                                Historial reciente de recargas registradas y anuladas para esta gasolinera.
                            </p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            @if ($recargasRecientes->isEmpty())
                                <div class="cc-empty-panel cc-empty-panel-compact">
                                    <h5>
                                        Sin recargas recientes
                                    </h5>

                                    <p>
                                        Todavía no hay recargas registradas para esta gasolinera.
                                    </p>
                                </div>
                            @else
                                <div class="cc-table-adaptive-wrapper">
                                    <table class="cc-table-adaptive" style="min-width: 86rem;">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Tanques</th>
                                                <th>Galones</th>
                                                <th>Precio/galón</th>
                                                <th>Total</th>
                                                <th>Registró</th>
                                                <th>Estado</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($recargasRecientes as $recarga)
                                                @php
                                                    $tanquesRecargados = $recarga->movimientosInventario
                                                        ->where('tipo_movimiento', 'entrada_recarga')
                                                        ->pluck('tanque.nombre')
                                                        ->filter()
                                                        ->unique()
                                                        ->values();

                                                    $estaRegistrada = $recarga->estado === 'registrado';
                                                @endphp

                                                <tr>
                                                    <td class="cc-table-adaptive-nowrap">
                                                        {{ optional($recarga->fecha_hora_recarga)->format('d/m/Y H:i') }}
                                                    </td>

                                                    <td>
                                                        {{ $tanquesRecargados->isNotEmpty() ? $tanquesRecargados->join(', ') : 'Sin detalle' }}
                                                    </td>

                                                    <td>
                                                        {{ number_format((float) $recarga->total_galones, 2) }} gal
                                                    </td>

                                                    <td>
                                                        ${{ number_format((float) $recarga->precio_galon, 4) }}
                                                    </td>

                                                    <td>
                                                        ${{ number_format((float) $recarga->total_compra, 2) }}
                                                    </td>

                                                    <td>
                                                        {{ $recarga->usuarioRegistra?->name ?: 'Sistema' }}
                                                    </td>

                                                    <td>
                                                        @if ($estaRegistrada)
                                                            <span class="cc-badge cc-badge-active">
                                                                Registrada
                                                            </span>
                                                        @else
                                                            <span class="cc-badge cc-badge-inactive">
                                                                Anulada
                                                            </span>

                                                            <div class="cc-table-adaptive-muted">
                                                                {{ optional($recarga->fecha_anulacion)->format('d/m/Y H:i') }}
                                                            </div>

                                                            <div class="cc-table-adaptive-muted">
                                                                Por: {{ $recarga->anuladoPor?->name ?: 'Sistema' }}
                                                            </div>

                                                            @if ($recarga->motivo_anulacion)
                                                                <div class="cc-table-adaptive-muted">
                                                                    Motivo: {{ $recarga->motivo_anulacion }}
                                                                </div>
                                                            @endif
                                                        @endif
                                                    </td>

                                                    <td>
                                                        @if ($estaRegistrada)
                                                            <form method="POST"
                                                                  action="{{ route('gasolineras.tanques.recargas.anular', [$gasolinera, $recarga]) }}"
                                                                  onsubmit="return confirm('Esta acción anulará la recarga completa y revertirá el inventario de todos los tanques involucrados. La anulación es irreversible. ¿Desea continuar?');">
                                                                @csrf
                                                                @method('PATCH')

                                                                <div class="cc-field" style="min-width: 17rem; margin-bottom: .65rem;">
                                                                    <label for="motivo_anulacion_{{ $recarga->id }}">
                                                                        Motivo de anulación
                                                                    </label>

                                                                    <input
                                                                        id="motivo_anulacion_{{ $recarga->id }}"
                                                                        type="text"
                                                                        name="motivo_anulacion"
                                                                        class="cc-input"
                                                                        maxlength="255"
                                                                        required
                                                                        placeholder="Describa el motivo"
                                                                    >
                                                                </div>

                                                                <button type="submit" class="cc-btn-danger cc-btn-result">
                                                                    Anular recarga
                                                                </button>
                                                            </form>
                                                        @else
                                                            <span class="cc-admin-result-value-muted">
                                                                Sin acciones disponibles
                                                            </span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </section>

                </div>

            </div>
        </div>
    </div>

    <script>
        const recargaForm = document.querySelector('[data-recarga-form]');

        if (recargaForm) {
            const precioInput = recargaForm.querySelector('[data-precio-galon]');
            const volumenInputs = Array.from(recargaForm.querySelectorAll('[data-volumen-input]'));
            const totalGalonesInput = recargaForm.querySelector('[data-total-galones]');
            const totalCompraInput = recargaForm.querySelector('[data-total-compra]');

            function formatNumber(value, decimals = 2) {
                return Number(value || 0).toLocaleString('es-SV', {
                    minimumFractionDigits: decimals,
                    maximumFractionDigits: decimals
                });
            }

            function formatMoney(value, decimals = 2) {
                return '$' + formatNumber(value, decimals);
            }

            function updateTotals() {
                const precioGalon = Number(precioInput.value || 0);
                let totalGalones = 0;

                volumenInputs.forEach((input) => {
                    const volumenMovimiento = Number(input.value || 0);
                    const volumenActual = Number(input.dataset.volumenActual || 0);
                    const capacidadTotal = Number(input.dataset.capacidadTotal || 0);
                    const volumenResultante = volumenActual + volumenMovimiento;
                    const subtotal = volumenMovimiento * precioGalon;

                    const card = input.closest('.cc-admin-result-card');
                    const resultanteInput = card ? card.querySelector('[data-volumen-resultante]') : null;
                    const subtotalLabel = card ? card.querySelector('[data-subtotal-tanque]') : null;

                    if (resultanteInput) {
                        resultanteInput.value = formatNumber(volumenResultante) + ' gal';
                    }

                    if (subtotalLabel) {
                        subtotalLabel.textContent = formatMoney(subtotal);
                    }

                    totalGalones += volumenMovimiento;

                    if (capacidadTotal > 0 && volumenResultante > capacidadTotal) {
                        input.setCustomValidity('La recarga excede la capacidad total del tanque.');
                    } else {
                        input.setCustomValidity('');
                    }
                });

                totalGalonesInput.value = formatNumber(totalGalones) + ' gal';
                totalCompraInput.value = formatMoney(totalGalones * precioGalon);
            }

            precioInput.addEventListener('input', updateTotals);

            volumenInputs.forEach((input) => {
                input.addEventListener('input', updateTotals);
            });

            updateTotals();
        }
    </script>
</x-app-layout>