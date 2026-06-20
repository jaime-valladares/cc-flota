@php
    $unidad = $unidad ?? null;
    $esEdicion = ! is_null($unidad);

    $empresaActual = old('empresa_id', $unidad->empresa_id ?? '');
    $modeloMedicionActual = old('modelo_medicion', $unidad->modelo_medicion ?? '');
@endphp

<div class="cc-grid">

    <div class="cc-form-section">
        <div class="cc-form-section-title">
            Identificación de la unidad
        </div>
    </div>

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
        <input id="placa"
               type="text"
               name="placa"
               value="{{ old('placa', $unidad->placa ?? '') }}"
               class="cc-input"
               maxlength="30"
               required
               placeholder="Ej. C123ABC">

        @error('placa')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="marca">
            Marca
        </label>
        <input id="marca"
               type="text"
               name="marca"
               value="{{ old('marca', $unidad->marca ?? '') }}"
               class="cc-input"
               maxlength="100"
               placeholder="Ej. Freightliner, International, Isuzu">

        @error('marca')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-form-section">
        <div class="cc-form-section-title">
            Tanques y cobertura Diesel Cop
        </div>
    </div>

    <div class="cc-field">
        <label for="total_tanques">
            Total de tanques <span class="cc-required">*</span>
        </label>
        <input id="total_tanques"
               type="number"
               name="total_tanques"
               value="{{ old('total_tanques', $unidad->total_tanques ?? '') }}"
               class="cc-input"
               min="1"
               max="10"
               required>

        @error('total_tanques')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="cantidad_tanques_con_licencia">
            Tanques protegidos <span class="cc-required">*</span>
        </label>
        <input id="cantidad_tanques_con_licencia"
               type="number"
               name="cantidad_tanques_con_licencia"
               value="{{ old('cantidad_tanques_con_licencia', $unidad->cantidad_tanques_con_licencia ?? '') }}"
               class="cc-input"
               min="1"
               max="3"
               required>

        @error('cantidad_tanques_con_licencia')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="capacidad_total">
            Capacidad total (galones) <span class="cc-required">*</span>
        </label>
        <input id="capacidad_total"
               type="number"
               name="capacidad_total"
               value="{{ old('capacidad_total', $unidad->capacidad_total ?? '') }}"
               class="cc-input"
               min="0.01"
               step="0.01"
               required
               placeholder="Ej. 300.00">

        @error('capacidad_total')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="capacidad_cubierta">
            Capacidad cubierta (galones) <span class="cc-required">*</span>
        </label>
        <input id="capacidad_cubierta"
               type="number"
               name="capacidad_cubierta"
               value="{{ old('capacidad_cubierta', $unidad->capacidad_cubierta ?? '') }}"
               class="cc-input"
               min="0.01"
               step="0.01"
               required
               placeholder="Ej. 200.00">

        @error('capacidad_cubierta')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-form-section">
        <div class="cc-form-section-title">
            Modelo de medición
        </div>
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="modelo_medicion">
            Modelo de medición <span class="cc-required">*</span>
        </label>
        <select id="modelo_medicion" name="modelo_medicion" class="cc-input" required>
            <option value="">Seleccione un modelo de medición</option>

            @foreach ($modelosMedicion as $valor => $texto)
                <option value="{{ $valor }}" @selected($modeloMedicionActual === $valor)>
                    {{ $texto }}
                </option>
            @endforeach
        </select>

        @error('modelo_medicion')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="cc-actions">
    <button type="submit" class="cc-btn-primary cc-btn-form-action">
        {{ $submitLabel }}
    </button>

    @if ($esEdicion)
        <a href="{{ route('unidades.show', $unidad) }}" class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @else
        <a href="{{ route('unidades.index') }}" class="cc-btn-secondary cc-btn-form-action">
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

    function normalizarPlaca() {
        if (!placaInput) {
            return;
        }

        placaInput.value = placaInput.value.toUpperCase().trim();
    }

    function validarTanques() {
        if (!totalTanquesInput || !tanquesLicenciaInput) {
            return;
        }

        const totalTanques = Number(totalTanquesInput.value);
        const tanquesLicencia = Number(tanquesLicenciaInput.value);

        if (totalTanques > 0) {
            tanquesLicenciaInput.max = Math.min(totalTanques, 3);
        }

        if (tanquesLicencia > totalTanques && totalTanques > 0) {
            tanquesLicenciaInput.setCustomValidity('Los tanques protegidos no pueden ser mayores al total de tanques.');
        } else {
            tanquesLicenciaInput.setCustomValidity('');
        }
    }

    function validarCapacidades() {
        if (!capacidadTotalInput || !capacidadCubiertaInput) {
            return;
        }

        const capacidadTotal = Number(capacidadTotalInput.value);
        const capacidadCubierta = Number(capacidadCubiertaInput.value);

        if (capacidadCubierta > capacidadTotal && capacidadTotal > 0) {
            capacidadCubiertaInput.setCustomValidity('La capacidad cubierta no puede ser mayor que la capacidad total.');
        } else {
            capacidadCubiertaInput.setCustomValidity('');
        }
    }

    if (placaInput) {
        placaInput.addEventListener('blur', normalizarPlaca);
    }

    if (totalTanquesInput) {
        totalTanquesInput.addEventListener('input', validarTanques);
    }

    if (tanquesLicenciaInput) {
        tanquesLicenciaInput.addEventListener('input', validarTanques);
    }

    if (capacidadTotalInput) {
        capacidadTotalInput.addEventListener('input', validarCapacidades);
    }

    if (capacidadCubiertaInput) {
        capacidadCubiertaInput.addEventListener('input', validarCapacidades);
    }

    validarTanques();
    validarCapacidades();
</script>