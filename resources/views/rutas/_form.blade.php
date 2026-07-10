<div class="cc-grid cc-grid-compact">

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Construcción de la ruta
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
                        @selected((string) old('empresa_id', $ruta->empresa_id ?? '') === (string) $empresaOpcion->id)>
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
        <label for="punto_origen_id">
            Punto de origen <span class="cc-required">*</span>
        </label>

        <select id="punto_origen_id" name="punto_origen_id" class="cc-input" required>
            <option value="">Seleccione punto de origen</option>

            @foreach ($puntosRutaSelector as $puntoRutaOpcion)
                <option
                    value="{{ $puntoRutaOpcion->id }}"
                    data-empresa-id="{{ $puntoRutaOpcion->empresa_id }}"
                    @selected((string) old('punto_origen_id', $ruta->punto_origen_id ?? '') === (string) $puntoRutaOpcion->id)
                >
                    {{ $puntoRutaOpcion->nombre }}
                </option>
            @endforeach
        </select>

        @error('punto_origen_id')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="punto_destino_id">
            Punto de destino <span class="cc-required">*</span>
        </label>

        <select id="punto_destino_id" name="punto_destino_id" class="cc-input" required>
            <option value="">Seleccione punto de destino</option>

            @foreach ($puntosRutaSelector as $puntoRutaOpcion)
                <option
                    value="{{ $puntoRutaOpcion->id }}"
                    data-empresa-id="{{ $puntoRutaOpcion->empresa_id }}"
                    @selected((string) old('punto_destino_id', $ruta->punto_destino_id ?? '') === (string) $puntoRutaOpcion->id)
                >
                    {{ $puntoRutaOpcion->nombre }}
                </option>
            @endforeach
        </select>

        @error('punto_destino_id')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="ruta_preview">
            Ruta generada
        </label>

        <input
            id="ruta_preview"
            type="text"
            class="cc-input"
            value="{{ old('ruta', $ruta->ruta ?? '') }}"
            placeholder="Se generará al seleccionar origen y destino"
            disabled
        >
    </div>

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Valores estimados
        </div>
    </div>

    <div class="cc-field">
        <label for="kilometros_estimados">
            Kilómetros estimados <span class="cc-required">*</span>
        </label>

        <input
            id="kilometros_estimados"
            name="kilometros_estimados"
            type="number"
            class="cc-input"
            value="{{ old('kilometros_estimados', $ruta->kilometros_estimados ?? '') }}"
            min="0.01"
            max="99999999.99"
            step="0.01"
            placeholder="Ej. 98.50"
            required
        >

        @error('kilometros_estimados')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="galones_estimados">
            Galones estimados <span class="cc-required">*</span>
        </label>

        <input
            id="galones_estimados"
            name="galones_estimados"
            type="number"
            class="cc-input"
            value="{{ old('galones_estimados', $ruta->galones_estimados ?? '') }}"
            min="0.01"
            max="99999999.99"
            step="0.01"
            placeholder="Ej. 14.50"
            required
        >

        @error('galones_estimados')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="cc-actions cc-actions-compact">
    <button type="submit" class="cc-btn-primary cc-btn-form-action">
        {{ $submitLabel }}
    </button>

    @if ($ruta)
        <a href="{{ ($modoVentana ?? false) ? route('rutas.show.ventana', $ruta) : route('rutas.show', $ruta) }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @else
        <a href="{{ ($modoVentana ?? false) ? route('rutas.consulta.ventana') : route('rutas.index') }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @endif
</div>

<script>
    const empresaSelectRuta = document.getElementById('empresa_id');
    const puntoOrigenSelect = document.getElementById('punto_origen_id');
    const puntoDestinoSelect = document.getElementById('punto_destino_id');
    const rutaPreviewInput = document.getElementById('ruta_preview');

    const puntosRutaOriginales = Array.from(puntoOrigenSelect.options)
        .filter(function (option) {
            return option.value;
        })
        .map(function (option) {
            return {
                value: option.value,
                text: option.text.trim(),
                empresaId: option.dataset.empresaId,
            };
        });

    const puntoOrigenInicial = "{{ old('punto_origen_id', $ruta->punto_origen_id ?? '') }}";
    const puntoDestinoInicial = "{{ old('punto_destino_id', $ruta->punto_destino_id ?? '') }}";

    let formularioInicializado = false;

    function obtenerEmpresaSeleccionadaRuta() {
        if (!empresaSelectRuta) {
            return '';
        }

        return empresaSelectRuta.value;
    }

    function reconstruirSelectPunto(select, placeholder, valorSeleccionado) {
        if (!select) {
            return;
        }

        const empresaId = obtenerEmpresaSeleccionadaRuta();

        select.innerHTML = '';

        const optionBase = document.createElement('option');
        optionBase.value = '';
        optionBase.textContent = placeholder;
        select.appendChild(optionBase);

        if (!empresaId) {
            select.value = '';
            return;
        }

        puntosRutaOriginales
            .filter(function (punto) {
                return punto.empresaId === empresaId;
            })
            .forEach(function (punto) {
                const option = document.createElement('option');
                option.value = punto.value;
                option.textContent = punto.text;
                option.dataset.empresaId = punto.empresaId;

                if ((valorSeleccionado || '') === punto.value) {
                    option.selected = true;
                }

                select.appendChild(option);
            });
    }

    function filtrarPuntosPorEmpresaRuta() {
        const mantenerOrigen = formularioInicializado ? puntoOrigenSelect.value : puntoOrigenInicial;
        const mantenerDestino = formularioInicializado ? puntoDestinoSelect.value : puntoDestinoInicial;

        reconstruirSelectPunto(
            puntoOrigenSelect,
            'Seleccione punto de origen',
            mantenerOrigen
        );

        reconstruirSelectPunto(
            puntoDestinoSelect,
            'Seleccione punto de destino',
            mantenerDestino
        );

        formularioInicializado = true;
        actualizarVistaRuta();
    }

    function actualizarVistaRuta() {
        if (!puntoOrigenSelect || !puntoDestinoSelect || !rutaPreviewInput) {
            return;
        }

        const origenTexto = puntoOrigenSelect.options[puntoOrigenSelect.selectedIndex]?.text?.trim() || '';
        const destinoTexto = puntoDestinoSelect.options[puntoDestinoSelect.selectedIndex]?.text?.trim() || '';

        if (puntoOrigenSelect.value && puntoDestinoSelect.value) {
            if (puntoOrigenSelect.value === puntoDestinoSelect.value) {
                rutaPreviewInput.value = 'El origen y destino no pueden ser iguales';
                return;
            }

            rutaPreviewInput.value = origenTexto + ' - ' + destinoTexto;
            return;
        }

        rutaPreviewInput.value = '';
    }

    if (empresaSelectRuta && puntoOrigenSelect && puntoDestinoSelect) {
        empresaSelectRuta.addEventListener('change', function () {
            puntoOrigenSelect.value = '';
            puntoDestinoSelect.value = '';
            filtrarPuntosPorEmpresaRuta();
        });

        puntoOrigenSelect.addEventListener('change', actualizarVistaRuta);
        puntoDestinoSelect.addEventListener('change', actualizarVistaRuta);

        filtrarPuntosPorEmpresaRuta();
    }
</script>