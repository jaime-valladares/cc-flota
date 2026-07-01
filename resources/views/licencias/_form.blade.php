@php
    $licencia = $licencia ?? null;
    $esEdicion = ! is_null($licencia);
    $modoVentana = $modoVentana ?? false;

    $empresaActual = old(
        'empresa_id',
        $licencia->empresa_id ?? ($empresaSeleccionadaId ?? '')
    );

    $unidadActual = old('unidad_id', $licencia->unidad_id ?? '');
    $periodoActual = old('periodo_vigencia_meses', $licencia->periodo_vigencia_meses ?? '');

    $fechaActivacionActual = old(
        'fecha_activacion',
        $licencia?->fecha_activacion?->format('Y-m-d') ?? now()->format('Y-m-d')
    );
@endphp

<div class="cc-grid cc-grid-compact">

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Unidad a asignar licencia
        </div>
    </div>

    @if ($esEdicion)
        <div class="cc-field">
            <label>
                Empresa
            </label>

            <input type="text"
                   class="cc-input"
                   value="{{ $licencia->empresa?->nombre_comercial ?: $licencia->empresa?->nombre_legal }}"
                   disabled>
        </div>

        <div class="cc-field">
            <label>
                Unidad / Placa
            </label>

            <input type="text"
                   class="cc-input"
                   value="{{ $licencia->unidad?->placa ?? 'Sin unidad' }}"
                   disabled>
        </div>
    @else
        <div class="cc-field">
            <label for="empresa_id">
                Empresa <span class="cc-required">*</span>
            </label>

            @if ($esUsuarioDieselCop)
                <select id="empresa_id" name="empresa_id" class="cc-input" required>
                    <option value="">Seleccione una empresa</option>

                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}"
                                @selected((string) $empresaActual === (string) $empresa->id)>
                            {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                        </option>
                    @endforeach
                </select>
            @else
                <select id="empresa_id_visible" class="cc-input" disabled>
                    @foreach ($empresas as $empresa)
                        <option value="{{ $empresa->id }}" selected>
                            {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                        </option>
                    @endforeach
                </select>

                <input id="empresa_id"
                       type="hidden"
                       name="empresa_id"
                       value="{{ $empresaActual ?: ($empresas->first()->id ?? '') }}">
            @endif

            @error('empresa_id')
                <div class="cc-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="cc-field">
            <label for="unidad_id">
                Unidad / placa <span class="cc-required">*</span>
            </label>

            <select id="unidad_id"
                    name="unidad_id"
                    class="cc-input"
                    required
                    @disabled(blank($empresaActual))>
                @if (blank($empresaActual))
                    <option value="">Seleccione una empresa primero</option>
                @elseif ($unidades->isEmpty())
                    <option value="">No hay unidades disponibles</option>
                @else
                    <option value="">Seleccione una unidad</option>

                    @foreach ($unidades as $unidad)
                        <option value="{{ $unidad->id }}"
                                data-placa="{{ $unidad->placa }}"
                                data-marca="{{ $unidad->marca ?: 'Sin marca registrada' }}"
                                data-total-tanques="{{ $unidad->total_tanques }}"
                                data-tanques-protegidos="{{ $unidad->cantidad_tanques_con_licencia }}"
                                data-capacidad-cubierta="{{ number_format((float) $unidad->capacidad_cubierta, 2, '.', '') }}"
                                @selected((string) $unidadActual === (string) $unidad->id)>
                            {{ $unidad->placa }} — {{ $unidad->empresa?->nombre_comercial ?: $unidad->empresa?->nombre_legal }}
                        </option>
                    @endforeach
                @endif
            </select>

            @error('unidad_id')
                <div class="cc-error">{{ $message }}</div>
            @enderror
        </div>

        <div id="unidad_resumen_panel" class="cc-col-span-2 hidden">
            <div class="cc-callout cc-callout-info">
                <div class="cc-callout-marker"></div>
                <div>
                    <div class="cc-callout-title">
                        Unidad seleccionada
                    </div>
                    <div id="unidad_resumen" class="cc-callout-text"></div>
                </div>
            </div>
        </div>
    @endif

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Vigencia de licencia
        </div>
    </div>

    <div class="cc-field">
        <label for="periodo_vigencia_meses">
            Período de vigencia <span class="cc-required">*</span>
        </label>

        <select id="periodo_vigencia_meses" name="periodo_vigencia_meses" class="cc-input" required>
            <option value="">Seleccione un período</option>

            @foreach ($periodosVigencia as $valor => $texto)
                <option value="{{ $valor }}" @selected((string) $periodoActual === (string) $valor)>
                    {{ $texto }}
                </option>
            @endforeach
        </select>

        @error('periodo_vigencia_meses')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="fecha_activacion">
            Fecha de activación <span class="cc-required">*</span>
        </label>

        <input id="fecha_activacion"
               type="date"
               name="fecha_activacion"
               value="{{ $fechaActivacionActual }}"
               class="cc-input"
               required>

        @error('fecha_activacion')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-col-span-2">
        <div class="cc-status-strip cc-status-strip-active">
            <div>
                <strong>Fecha de vencimiento estimada</strong>
                <span id="fecha_vencimiento_preview">
                    Seleccione fecha de activación y período de vigencia.
                </span>
            </div>
        </div>
    </div>

</div>

<div class="cc-actions cc-actions-compact">
    <button type="submit" class="cc-btn-primary cc-btn-form-action">
        {{ $submitLabel }}
    </button>

    @if ($esEdicion)
        <a href="{{ $modoVentana ? route('licencias.show.ventana', $licencia) : route('licencias.show', $licencia) }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @else
        <a href="{{ $modoVentana ? route('licencias.consulta.ventana') : route('licencias.index') }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @endif
</div>

<script>
    const empresaSelect = document.getElementById('empresa_id');
    const unidadSelect = document.getElementById('unidad_id');
    const unidadResumenPanel = document.getElementById('unidad_resumen_panel');
    const unidadResumen = document.getElementById('unidad_resumen');
    const periodoInput = document.getElementById('periodo_vigencia_meses');
    const fechaActivacionInput = document.getElementById('fecha_activacion');
    const fechaVencimientoPreview = document.getElementById('fecha_vencimiento_preview');

    function plantillaTexto(tanquesProtegidos) {
        if (tanquesProtegidos === 1) {
            return 'Plantilla 1 tanque · 29 puntos esperados';
        }

        if (tanquesProtegidos === 2) {
            return 'Plantilla 2 tanques · 38 puntos esperados';
        }

        if (tanquesProtegidos === 3) {
            return 'Plantilla 3 tanques · 49 puntos esperados';
        }

        return 'Plantilla no definida';
    }

    function actualizarResumenUnidad() {
        if (!unidadSelect || !unidadResumen || !unidadResumenPanel) {
            return;
        }

        const option = unidadSelect.options[unidadSelect.selectedIndex];

        if (!option || !option.value) {
            unidadResumen.textContent = '';
            unidadResumenPanel.classList.add('hidden');
            return;
        }

        const placa = option.dataset.placa || 'Sin placa';
        const marca = option.dataset.marca || 'Sin marca registrada';
        const totalTanques = Number(option.dataset.totalTanques || 0);
        const tanquesProtegidos = Number(option.dataset.tanquesProtegidos || 0);
        const capacidadCubierta = option.dataset.capacidadCubierta || '0.00';

        unidadResumen.textContent = `${placa} · ${marca} · ${tanquesProtegidos} de ${totalTanques} tanques protegidos · ${capacidadCubierta} galones cubiertos · ${plantillaTexto(tanquesProtegidos)}.`;
        unidadResumenPanel.classList.remove('hidden');
    }

    function formatearFecha(date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();

        return `${day}/${month}/${year}`;
    }

    function calcularFechaVencimientoPreview() {
        if (!periodoInput || !fechaActivacionInput || !fechaVencimientoPreview) {
            return;
        }

        const periodo = Number(periodoInput.value);
        const fechaActivacion = fechaActivacionInput.value;

        if (!periodo || !fechaActivacion) {
            fechaVencimientoPreview.textContent = 'Seleccione fecha de activación y período de vigencia.';
            return;
        }

        const partes = fechaActivacion.split('-');
        const fecha = new Date(Number(partes[0]), Number(partes[1]) - 1, Number(partes[2]));

        fecha.setMonth(fecha.getMonth() + periodo);

        fechaVencimientoPreview.textContent = `Vence el ${formatearFecha(fecha)}.`;
    }

    if (empresaSelect) {
        empresaSelect.addEventListener('change', function () {
            const empresaId = empresaSelect.value;

            const url = new URL(window.location.href);

            if (empresaId) {
                url.searchParams.set('empresa_id', empresaId);
            } else {
                url.searchParams.delete('empresa_id');
            }

            window.location.href = url.toString();
        });
    }

    if (unidadSelect) {
        unidadSelect.addEventListener('change', actualizarResumenUnidad);
    }

    if (periodoInput) {
        periodoInput.addEventListener('change', calcularFechaVencimientoPreview);
    }

    if (fechaActivacionInput) {
        fechaActivacionInput.addEventListener('change', calcularFechaVencimientoPreview);
    }

    actualizarResumenUnidad();
    calcularFechaVencimientoPreview();
</script>