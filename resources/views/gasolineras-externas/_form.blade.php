@php
    $gasolineraExterna = $gasolineraExterna ?? null;
    $modoVentana = $modoVentana ?? false;
    $submitLabel = $submitLabel ?? 'Guardar gasolinera';

    $empresaActual = old('empresa_id', $gasolineraExterna->empresa_id ?? ($empresaUsuario->id ?? ''));
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
                            @selected((string) $empresaActual === (string) $empresaOpcion->id)>
                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                    </option>
                @endforeach
            </select>
        @else
            <select id="empresa_id_visible" class="cc-input" disabled>
                @foreach ($empresasSelector as $empresaOpcion)
                    <option value="{{ $empresaOpcion->id }}" selected>
                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                    </option>
                @endforeach
            </select>

            <input type="hidden" name="empresa_id" value="{{ $empresaActual }}">
        @endif

        @error('empresa_id')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="compania">
            Compañía <span class="cc-required">*</span>
        </label>

        <input
            id="compania"
            name="compania"
            type="text"
            class="cc-input"
            value="{{ old('compania', $gasolineraExterna->compania ?? '') }}"
            maxlength="150"
            required
            placeholder="UNO, Puma, Texaco, Shell..."
        >

        @error('compania')
            <div class="cc-error">
                {{ $message }}
            </div>
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
            placeholder="Ubicación o referencia de la gasolinera externa"
        >

        @error('direccion')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

</div>

<div class="cc-actions cc-actions-compact">
    <button type="submit" class="cc-btn-primary cc-btn-form-action">
        {{ $submitLabel }}
    </button>

    @if ($gasolineraExterna)
        <a href="{{ $modoVentana ? route('gasolineras-externas.show.ventana', $gasolineraExterna) : route('gasolineras-externas.show', $gasolineraExterna) }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @else
        <a href="{{ $modoVentana ? route('gasolineras-externas.consulta.ventana') : route('gasolineras-externas.index') }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @endif
</div>