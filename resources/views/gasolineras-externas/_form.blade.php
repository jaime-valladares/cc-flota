<div class="cc-grid cc-grid-compact">

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Identificación de la gasolinera externa
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
                        @selected((string) old('empresa_id', $gasolineraExterna->empresa_id ?? '') === (string) $empresaOpcion->id)>
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
        <label for="nombre">
            Nombre <span class="cc-required">*</span>
        </label>
        <input
            id="nombre"
            name="nombre"
            type="text"
            class="cc-input"
            value="{{ old('nombre', $gasolineraExterna->nombre ?? '') }}"
            maxlength="150"
            required
        >
        @error('nombre')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="direccion">
            Dirección <span class="cc-required">*</span>
        </label>
        <input
            id="direccion"
            name="direccion"
            type="text"
            class="cc-input"
            value="{{ old('direccion', $gasolineraExterna->direccion ?? '') }}"
            maxlength="255"
            required
        >
        @error('direccion')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Datos complementarios
        </div>
    </div>

    <div class="cc-field">
        <label for="compania">
            Compañía
        </label>
        <input
            id="compania"
            name="compania"
            type="text"
            class="cc-input"
            value="{{ old('compania', $gasolineraExterna->compania ?? '') }}"
            maxlength="150"
            placeholder="UNO, Puma, Texaco, Shell..."
        >
        @error('compania')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="ciudad">
            Ciudad
        </label>
        <input
            id="ciudad"
            name="ciudad"
            type="text"
            class="cc-input"
            value="{{ old('ciudad', $gasolineraExterna->ciudad ?? '') }}"
            maxlength="100"
        >
        @error('ciudad')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="departamento">
            Departamento
        </label>
        <input
            id="departamento"
            name="departamento"
            type="text"
            class="cc-input"
            value="{{ old('departamento', $gasolineraExterna->departamento ?? '') }}"
            maxlength="100"
        >
        @error('departamento')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="telefono">
            Teléfono
        </label>
        <input
            id="telefono"
            name="telefono"
            type="text"
            class="cc-input"
            value="{{ old('telefono', $gasolineraExterna->telefono ?? '') }}"
            maxlength="9"
            placeholder="0000-0000"
        >
        @error('telefono')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="correo">
            Correo
        </label>
        <input
            id="correo"
            name="correo"
            type="email"
            class="cc-input"
            value="{{ old('correo', $gasolineraExterna->correo ?? '') }}"
            maxlength="150"
        >
        @error('correo')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="cc-actions cc-actions-compact">
    <button type="submit" class="cc-btn-primary cc-btn-form-action">
        {{ $submitLabel }}
    </button>

    @if ($gasolineraExterna)
        <a href="{{ ($modoVentana ?? false) ? route('gasolineras-externas.show.ventana', $gasolineraExterna) : route('gasolineras-externas.show', $gasolineraExterna) }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @else
        <a href="{{ ($modoVentana ?? false) ? route('gasolineras-externas.consulta.ventana') : route('gasolineras-externas.index') }}"
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

    const telefonoInput = document.getElementById('telefono');

    if (telefonoInput) {
        telefonoInput.addEventListener('input', function () {
            this.value = formatPhone(this.value);
        });
    }
</script>