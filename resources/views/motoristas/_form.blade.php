<div class="cc-grid cc-grid-compact">

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Identificación del motorista
        </div>
    </div>

    <div class="cc-field">
        <label for="empresa_id">
            Empresa <span class="cc-required">*</span>
        </label>

        @if ($esUsuarioDieselCop)
            <select id="empresa_id" name="empresa_id" class="cc-input" required>
                <option value="">Seleccione una empresa</option>

                @foreach ($empresasSelector as $empresaOpcion)
                    <option value="{{ $empresaOpcion->id }}"
                        @selected((string) old('empresa_id', $motorista->empresa_id ?? '') === (string) $empresaOpcion->id)>
                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                    </option>
                @endforeach
            </select>
        @else
            <select id="empresa_id" name="empresa_id" class="cc-input" disabled>
                @foreach ($empresasSelector as $empresaOpcion)
                    <option value="{{ $empresaOpcion->id }}" selected>
                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                    </option>
                @endforeach
            </select>
        @endif

        @error('empresa_id')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="licencia">
            Licencia <span class="cc-required">*</span>
        </label>

        <input
            id="licencia"
            name="licencia"
            type="text"
            class="cc-input"
            value="{{ old('licencia', $motorista->licencia ?? '') }}"
            maxlength="14"
            inputmode="numeric"
            placeholder="Solo números, sin guiones"
            required
        >

        @error('licencia')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="nombres">
            Nombres <span class="cc-required">*</span>
        </label>

        <input
            id="nombres"
            name="nombres"
            type="text"
            class="cc-input"
            value="{{ old('nombres', $motorista->nombres ?? '') }}"
            maxlength="100"
            required
        >

        @error('nombres')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="apellidos">
            Apellidos <span class="cc-required">*</span>
        </label>

        <input
            id="apellidos"
            name="apellidos"
            type="text"
            class="cc-input"
            value="{{ old('apellidos', $motorista->apellidos ?? '') }}"
            maxlength="100"
            required
        >

        @error('apellidos')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="telefono">
            Teléfono <span class="cc-required">*</span>
        </label>

        <input
            id="telefono"
            name="telefono"
            type="text"
            class="cc-input"
            value="{{ old('telefono', $motorista->telefono ?? '') }}"
            maxlength="9"
            placeholder="0000-0000"
            required
        >

        @error('telefono')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="cc-actions cc-actions-compact">
    <button type="submit" class="cc-btn-primary cc-btn-form-action">
        {{ $submitLabel }}
    </button>

    @if ($motorista)
        <a href="{{ ($modoVentana ?? false) ? route('motoristas.show.ventana', $motorista) : route('motoristas.show', $motorista) }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @else
        <a href="{{ ($modoVentana ?? false) ? route('motoristas.consulta.ventana') : route('motoristas.index') }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @endif
</div>

<script>
    function formatPhone(value) {
        const digits = value.replace(/\D/g, '').slice(0, 8);

        if (digits.length <= 4) {
            return digits;
        }

        return `${digits.slice(0, 4)}-${digits.slice(4)}`;
    }

    function onlyNumbers(value, maxLength) {
        return value.replace(/\D/g, '').slice(0, maxLength);
    }

    const telefonoInput = document.getElementById('telefono');
    const licenciaInput = document.getElementById('licencia');

    if (telefonoInput) {
        telefonoInput.addEventListener('input', function () {
            this.value = formatPhone(this.value);
        });
    }

    if (licenciaInput) {
        licenciaInput.addEventListener('input', function () {
            this.value = onlyNumbers(this.value, 14);
        });
    }
</script>