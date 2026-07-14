@php
    $queryParams = request()->query();

    $esEdicion = isset($motorista) && $motorista;

    $empresaActual = $esEdicion
        ? $motorista->empresa
        : null;
@endphp

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

        @if ($esEdicion)
            <select
                id="empresa_id"
                class="cc-input"
                disabled
            >
                <option selected>
                    {{ $empresaActual?->nombre_comercial ?: $empresaActual?->nombre_legal }}
                </option>
            </select>

            <div class="cc-field-help">
                La empresa no puede modificarse después del registro.
            </div>
        @elseif ($esUsuarioDieselCop)
            <select
                id="empresa_id"
                name="empresa_id"
                class="cc-input"
                required
            >
                <option value="">
                    Seleccione una empresa
                </option>

                @foreach ($empresasSelector as $empresaOpcion)
                    <option
                        value="{{ $empresaOpcion->id }}"
                        @selected(
                            (string) old('empresa_id')
                            === (string) $empresaOpcion->id
                        )
                    >
                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                    </option>
                @endforeach
            </select>
        @else
            <select
                id="empresa_id"
                class="cc-input"
                disabled
            >
                @foreach ($empresasSelector as $empresaOpcion)
                    <option selected>
                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                    </option>
                @endforeach
            </select>

            <div class="cc-field-help">
                El motorista será registrado para su empresa.
            </div>
        @endif

        @error('empresa_id')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="licencia">
            Licencia <span class="cc-required">*</span>
        </label>

        <input
            id="licencia"
            @unless ($esEdicion)
                name="licencia"
            @endunless
            type="text"
            class="cc-input"
            value="{{ old('licencia', $motorista->licencia ?? '') }}"
            maxlength="14"
            inputmode="numeric"
            placeholder="Solo números, sin guiones"
            @disabled($esEdicion)
            required
        >

        @if ($esEdicion)
            <div class="cc-field-help">
                La licencia no puede modificarse después del registro.
            </div>
        @endif

        @error('licencia')
            <div class="cc-error">
                {{ $message }}
            </div>
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
            autocomplete="off"
            required
        >

        @error('nombres')
            <div class="cc-error">
                {{ $message }}
            </div>
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
            autocomplete="off"
            required
        >

        @error('apellidos')
            <div class="cc-error">
                {{ $message }}
            </div>
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
            inputmode="numeric"
            placeholder="0000-0000"
            autocomplete="off"
            required
        >

        @error('telefono')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

</div>

<div class="cc-actions cc-actions-compact">
    <button
        type="submit"
        class="cc-btn-primary cc-btn-form-action"
    >
        {{ $submitLabel }}
    </button>

    @if ($esEdicion)
        <a
            href="{{ ($modoVentana ?? false)
                ? route(
                    'motoristas.show.ventana',
                    array_merge(
                        $queryParams,
                        ['motorista' => $motorista]
                    )
                )
                : route(
                    'motoristas.show',
                    array_merge(
                        $queryParams,
                        ['motorista' => $motorista]
                    )
                ) }}"
            class="cc-btn-secondary cc-btn-form-action"
        >
            Cancelar
        </a>
    @else
        <a
            href="{{ ($modoVentana ?? false)
                ? route(
                    'motoristas.consulta.ventana',
                    $queryParams
                )
                : route(
                    'motoristas.index',
                    $queryParams
                ) }}"
            class="cc-btn-secondary cc-btn-form-action"
        >
            Cancelar
        </a>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const telefonoInput = document.getElementById('telefono');
        const licenciaInput = document.getElementById('licencia');

        function formatearTelefono(value) {
            const digits = value
                .replace(/\D/g, '')
                .slice(0, 8);

            if (digits.length <= 4) {
                return digits;
            }

            return digits.slice(0, 4)
                + '-'
                + digits.slice(4);
        }

        function conservarSoloNumeros(value, maxLength) {
            return value
                .replace(/\D/g, '')
                .slice(0, maxLength);
        }

        if (telefonoInput) {
            telefonoInput.addEventListener('input', function () {
                this.value = formatearTelefono(this.value);
            });
        }

        if (
            licenciaInput
            && ! licenciaInput.disabled
        ) {
            licenciaInput.addEventListener('input', function () {
                this.value = conservarSoloNumeros(
                    this.value,
                    14
                );
            });
        }
    });
</script>