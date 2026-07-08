@php
    $unidad = $unidad ?? null;
    $esEdicion = ! is_null($unidad);
    $modoVentana = $modoVentana ?? false;
    $submitLabel = $submitLabel ?? 'Guardar unidad';

    $empresas = $empresas ?? collect();

    $empresaActual = old('empresa_id', $unidad->empresa_id ?? ($empresaUsuario->id ?? ''));
@endphp

@if ($modoVentana)
    <input type="hidden" name="return_to" value="ventana">
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

<div class="cc-grid cc-grid-compact">

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Identificación de la unidad
        </div>
    </div>

    <div class="cc-field">
        <label for="empresa_id">
            Empresa <span class="cc-required">*</span>
        </label>

        @if ($esEdicion)
            <select id="empresa_id_visible" class="cc-input" disabled>
                @foreach ($empresas as $empresa)
                    <option value="{{ $empresa->id }}" selected>
                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="empresa_id" value="{{ $empresaActual }}">
        @elseif ($esUsuarioDieselCop)
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

            <input type="hidden" name="empresa_id" value="{{ $empresaActual }}">
        @endif

        @error('empresa_id')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="placa">
            Placa <span class="cc-required">*</span>
        </label>

        <input
            id="placa"
            name="placa"
            type="text"
            class="cc-input"
            value="{{ old('placa', $unidad->placa ?? '') }}"
            maxlength="30"
            required
            placeholder="Ej. C-123456"
        >

        @error('placa')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="marca">
            Marca
        </label>

        <input
            id="marca"
            name="marca"
            type="text"
            class="cc-input"
            value="{{ old('marca', $unidad->marca ?? '') }}"
            maxlength="100"
            placeholder="Ej. Freightliner, International, Volvo"
        >

        @error('marca')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Configuración de tanques y cobertura
        </div>
    </div>

    <div class="cc-field">
        <label for="total_tanques">
            Total de tanques de la unidad <span class="cc-required">*</span>
        </label>

        <select id="total_tanques" name="total_tanques" class="cc-input" required>
            <option value="">Seleccione</option>
            <option value="1" @selected((string) old('total_tanques', $unidad->total_tanques ?? '') === '1')>1 tanque</option>
            <option value="2" @selected((string) old('total_tanques', $unidad->total_tanques ?? '') === '2')>2 tanques</option>
            <option value="3" @selected((string) old('total_tanques', $unidad->total_tanques ?? '') === '3')>3 tanques</option>
        </select>

        @error('total_tanques')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="cantidad_tanques_con_licencia">
            Tanques cubiertos por licencia <span class="cc-required">*</span>
        </label>

        <select id="cantidad_tanques_con_licencia" name="cantidad_tanques_con_licencia" class="cc-input" required>
            <option value="">Seleccione</option>
            <option value="1" @selected((string) old('cantidad_tanques_con_licencia', $unidad->cantidad_tanques_con_licencia ?? '') === '1')>1 tanque</option>
            <option value="2" @selected((string) old('cantidad_tanques_con_licencia', $unidad->cantidad_tanques_con_licencia ?? '') === '2')>2 tanques</option>
            <option value="3" @selected((string) old('cantidad_tanques_con_licencia', $unidad->cantidad_tanques_con_licencia ?? '') === '3')>3 tanques</option>
        </select>

        @error('cantidad_tanques_con_licencia')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="capacidad_total">
            Capacidad total de la unidad <span class="cc-required">*</span>
        </label>

        <input
            id="capacidad_total"
            name="capacidad_total"
            type="number"
            class="cc-input"
            value="{{ old('capacidad_total', $unidad->capacidad_total ?? '') }}"
            min="0.01"
            step="0.01"
            required
            placeholder="Ej. 250.00"
        >

        @error('capacidad_total')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="capacidad_cubierta">
            Capacidad cubierta por licencia <span class="cc-required">*</span>
        </label>

        <input
            id="capacidad_cubierta"
            name="capacidad_cubierta"
            type="number"
            class="cc-input"
            value="{{ old('capacidad_cubierta', $unidad->capacidad_cubierta ?? '') }}"
            min="0.01"
            step="0.01"
            required
            placeholder="Ej. 250.00"
        >

        @error('capacidad_cubierta')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Modelo de medición
        </div>
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="modelo_medicion">
            Modelo de medición <span class="cc-required">*</span>
        </label>

        <select id="modelo_medicion" name="modelo_medicion" class="cc-input" required>
            <option value="">Seleccione un modelo</option>

            @foreach ($modelosMedicion as $valor => $etiqueta)
                <option value="{{ $valor }}"
                        @selected(old('modelo_medicion', $unidad->modelo_medicion ?? '') === $valor)>
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>

        @error('modelo_medicion')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="cc-actions cc-actions-compact">
    <button type="submit" class="cc-btn-primary cc-btn-form-action">
        {{ $submitLabel }}
    </button>

    @if ($esEdicion)
        <a href="{{ $modoVentana ? route('unidades.show.ventana', $unidad) : route('unidades.show', $unidad) }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @else
        <a href="{{ $modoVentana ? route('unidades.consulta.ventana') : route('unidades.index') }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @endif
</div>

<script>
    const placaInput = document.getElementById('placa');
    const totalTanquesInput = document.getElementById('total_tanques');
    const tanquesLicenciaInput = document.getElementById('cantidad_tanques_con_licencia');
    const capacidadTotalInput = document.getElementById('capacidad_total');
    const capacidadCubiertaInput = document.getElementById('capacidad_cubierta');

    if (placaInput) {
        placaInput.addEventListener('input', function () {
            this.value = this.value.toUpperCase();
        });
    }

    function validarTanquesLicencia() {
        if (!totalTanquesInput || !tanquesLicenciaInput) {
            return;
        }

        const totalTanques = Number(totalTanquesInput.value);
        const tanquesLicencia = Number(tanquesLicenciaInput.value);

        if (totalTanques > 0 && tanquesLicencia > totalTanques) {
            tanquesLicenciaInput.setCustomValidity('Los tanques cubiertos por licencia no pueden superar el total de tanques.');
        } else {
            tanquesLicenciaInput.setCustomValidity('');
        }
    }

    function validarCapacidadCubierta() {
        if (!capacidadTotalInput || !capacidadCubiertaInput) {
            return;
        }

        const capacidadTotal = Number(capacidadTotalInput.value);
        const capacidadCubierta = Number(capacidadCubiertaInput.value);

        if (capacidadTotal > 0 && capacidadCubierta > capacidadTotal) {
            capacidadCubiertaInput.setCustomValidity('La capacidad cubierta no puede superar la capacidad total.');
        } else {
            capacidadCubiertaInput.setCustomValidity('');
        }
    }

    if (totalTanquesInput) {
        totalTanquesInput.addEventListener('change', validarTanquesLicencia);
    }

    if (tanquesLicenciaInput) {
        tanquesLicenciaInput.addEventListener('change', validarTanquesLicencia);
    }

    if (capacidadTotalInput) {
        capacidadTotalInput.addEventListener('input', validarCapacidadCubierta);
    }

    if (capacidadCubiertaInput) {
        capacidadCubiertaInput.addEventListener('input', validarCapacidadCubierta);
    }

    validarTanquesLicencia();
    validarCapacidadCubierta();
</script>