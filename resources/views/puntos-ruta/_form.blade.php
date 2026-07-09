<div class="cc-grid cc-grid-compact">

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Identificación del punto de ruta
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
                        @selected((string) old('empresa_id', $puntoRuta->empresa_id ?? '') === (string) $empresaOpcion->id)>
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
            Nombre del punto <span class="cc-required">*</span>
        </label>

        <input
            id="nombre"
            name="nombre"
            type="text"
            class="cc-input"
            value="{{ old('nombre', $puntoRuta->nombre ?? '') }}"
            maxlength="150"
            placeholder="Ej. Planta central, Terminal Santa Ana, Cliente San Miguel"
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
            value="{{ old('direccion', $puntoRuta->direccion ?? '') }}"
            maxlength="255"
            placeholder="Ingrese la dirección o referencia física del punto de ruta"
            required
        >

        @error('direccion')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="cc-actions cc-actions-compact">
    <button type="submit" class="cc-btn-primary cc-btn-form-action">
        {{ $submitLabel }}
    </button>

    @if ($puntoRuta)
        <a href="{{ ($modoVentana ?? false) ? route('puntos-ruta.show.ventana', $puntoRuta) : route('puntos-ruta.show', $puntoRuta) }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @else
        <a href="{{ ($modoVentana ?? false) ? route('puntos-ruta.consulta.ventana') : route('puntos-ruta.index') }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @endif
</div>