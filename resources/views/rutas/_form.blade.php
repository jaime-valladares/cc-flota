@php
    $queryParams = collect(request()->query())
        ->except([
            'ruta',
            'return_to',
            'return_query',
        ])
        ->all();

    $esEdicion = isset($ruta) && $ruta;

    $empresaSeleccionada = old(
        'empresa_id',
        $ruta->empresa_id
            ?? $empresaUsuario?->id
            ?? ''
    );

    $puntoUnoSeleccionado = old(
        'punto_origen_id',
        $ruta->punto_origen_id ?? ''
    );

    $puntoDosSeleccionado = old(
        'punto_destino_id',
        $ruta->punto_destino_id ?? ''
    );
@endphp

<div class="cc-grid cc-grid-compact">

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Asignación empresarial
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
                @foreach ($empresasSelector as $empresaOpcion)
                    @if (
                        (int) $empresaOpcion->id
                            === (int) $ruta->empresa_id
                    )
                        <option
                            value="{{ $empresaOpcion->id }}"
                            selected
                        >
                            {{ $empresaOpcion->nombre_comercial
                                ?: $empresaOpcion->nombre_legal }}
                        </option>
                    @endif
                @endforeach
            </select>

            <input
                type="hidden"
                name="empresa_id"
                value="{{ $ruta->empresa_id }}"
            >
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
                            (string) $empresaSeleccionada
                                === (string) $empresaOpcion->id
                        )
                    >
                        {{ $empresaOpcion->nombre_comercial
                            ?: $empresaOpcion->nombre_legal }}
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
                    <option
                        value="{{ $empresaOpcion->id }}"
                        selected
                    >
                        {{ $empresaOpcion->nombre_comercial
                            ?: $empresaOpcion->nombre_legal }}
                    </option>
                @endforeach
            </select>

            <input
                type="hidden"
                name="empresa_id"
                value="{{ $empresaUsuario?->id }}"
            >
        @endif

        @error('empresa_id')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Construcción de la ruta
        </div>
    </div>

    <div class="cc-field">
        <label for="punto_origen_id">
            Punto 1 <span class="cc-required">*</span>
        </label>

        <select
            id="punto_origen_id"
            name="punto_origen_id"
            class="cc-input"
            required
        >
            <option value="">
                Seleccione el primer punto
            </option>

            @foreach ($puntosRutaSelector as $puntoRutaOpcion)
                <option
                    value="{{ $puntoRutaOpcion->id }}"
                    data-empresa-id="{{ $puntoRutaOpcion->empresa_id }}"
                    @selected(
                        (string) $puntoUnoSeleccionado
                            === (string) $puntoRutaOpcion->id
                    )
                >
                    {{ $puntoRutaOpcion->nombre }}
                </option>
            @endforeach
        </select>

        @error('punto_origen_id')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="punto_destino_id">
            Punto 2 <span class="cc-required">*</span>
        </label>

        <select
            id="punto_destino_id"
            name="punto_destino_id"
            class="cc-input"
            required
        >
            <option value="">
                Seleccione el segundo punto
            </option>

            @foreach ($puntosRutaSelector as $puntoRutaOpcion)
                <option
                    value="{{ $puntoRutaOpcion->id }}"
                    data-empresa-id="{{ $puntoRutaOpcion->empresa_id }}"
                    @selected(
                        (string) $puntoDosSeleccionado
                            === (string) $puntoRutaOpcion->id
                    )
                >
                    {{ $puntoRutaOpcion->nombre }}
                </option>
            @endforeach
        </select>

        @error('punto_destino_id')
            <div class="cc-error">
                {{ $message }}
            </div>
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
            value="{{ $ruta->ruta ?? '' }}"
            placeholder="Se generará al seleccionar ambos puntos"
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
            Kilómetros estimados
            <span class="cc-required">*</span>
        </label>

        <input
            id="kilometros_estimados"
            name="kilometros_estimados"
            type="number"
            class="cc-input"
            value="{{ old(
                'kilometros_estimados',
                isset($ruta)
                    ? number_format(
                        (float) $ruta->kilometros_estimados,
                        1,
                        '.',
                        ''
                    )
                    : ''
            ) }}"
            min="0.1"
            max="99999999.9"
            step="0.1"
            inputmode="decimal"
            placeholder="Ej. 98.5"
            required
        >

        @error('kilometros_estimados')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="galones_estimados">
            Galones estimados
            <span class="cc-required">*</span>
        </label>

        <input
            id="galones_estimados"
            name="galones_estimados"
            type="number"
            class="cc-input"
            value="{{ old(
                'galones_estimados',
                isset($ruta)
                    ? number_format(
                        (float) $ruta->galones_estimados,
                        1,
                        '.',
                        ''
                    )
                    : ''
            ) }}"
            min="0.1"
            max="99999999.9"
            step="0.1"
            inputmode="decimal"
            placeholder="Ej. 14.5"
            required
        >

        @error('galones_estimados')
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
                    'rutas.show.ventana',
                    array_merge(
                        $queryParams,
                        ['ruta' => $ruta]
                    )
                )
                : route(
                    'rutas.show',
                    array_merge(
                        $queryParams,
                        ['ruta' => $ruta]
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
                    'rutas.consulta.ventana',
                    $queryParams
                )
                : route(
                    'rutas.index',
                    $queryParams
                ) }}"
            class="cc-btn-secondary cc-btn-form-action"
        >
            Cancelar
        </a>
    @endif
</div>

<script>
    const empresaSelectRuta =
        document.getElementById('empresa_id');

    const puntoUnoSelect =
        document.getElementById('punto_origen_id');

    const puntoDosSelect =
        document.getElementById('punto_destino_id');

    const rutaPreviewInput =
        document.getElementById('ruta_preview');

    const empresaInicialRuta =
        @json((string) $empresaSeleccionada);

    const puntoUnoInicial =
        @json((string) $puntoUnoSeleccionado);

    const puntoDosInicial =
        @json((string) $puntoDosSeleccionado);

    const puntosRutaOriginales = puntoUnoSelect
        ? Array.from(puntoUnoSelect.options)
            .filter(function (option) {
                return option.value;
            })
            .map(function (option) {
                return {
                    value: option.value,
                    text: option.text.trim(),
                    empresaId:
                        option.dataset.empresaId || '',
                };
            })
        : [];

    let formularioRutaInicializado = false;

    function obtenerEmpresaSeleccionadaRuta() {
        if (! empresaSelectRuta) {
            return empresaInicialRuta;
        }

        return empresaSelectRuta.value
            || empresaInicialRuta;
    }

    function reconstruirSelectPuntoRuta(
        select,
        placeholder,
        valorSeleccionado
    ) {
        if (! select) {
            return;
        }

        const empresaId =
            obtenerEmpresaSeleccionadaRuta();

        select.innerHTML = '';

        const optionBase =
            document.createElement('option');

        optionBase.value = '';
        optionBase.textContent = placeholder;

        select.appendChild(optionBase);

        if (! empresaId) {
            select.value = '';
            return;
        }

        puntosRutaOriginales
            .filter(function (punto) {
                return punto.empresaId === empresaId;
            })
            .forEach(function (punto) {
                const option =
                    document.createElement('option');

                option.value = punto.value;
                option.textContent = punto.text;
                option.dataset.empresaId =
                    punto.empresaId;

                if (
                    String(valorSeleccionado || '')
                        === String(punto.value)
                ) {
                    option.selected = true;
                }

                select.appendChild(option);
            });
    }

    function actualizarRutaGenerada() {
        if (
            ! puntoUnoSelect
            || ! puntoDosSelect
            || ! rutaPreviewInput
        ) {
            return;
        }

        const puntoUnoTexto =
            puntoUnoSelect.options[
                puntoUnoSelect.selectedIndex
            ]?.text?.trim() || '';

        const puntoDosTexto =
            puntoDosSelect.options[
                puntoDosSelect.selectedIndex
            ]?.text?.trim() || '';

        if (
            puntoUnoSelect.value
            && puntoDosSelect.value
        ) {
            if (
                puntoUnoSelect.value
                === puntoDosSelect.value
            ) {
                rutaPreviewInput.value =
                    'Los dos puntos deben ser diferentes';

                return;
            }

            rutaPreviewInput.value =
                puntoUnoTexto
                + ' - '
                + puntoDosTexto;

            return;
        }

        rutaPreviewInput.value = '';
    }

    function filtrarPuntosRutaPorEmpresa() {
        const conservarPuntoUno =
            formularioRutaInicializado
                ? puntoUnoSelect?.value
                : puntoUnoInicial;

        const conservarPuntoDos =
            formularioRutaInicializado
                ? puntoDosSelect?.value
                : puntoDosInicial;

        reconstruirSelectPuntoRuta(
            puntoUnoSelect,
            'Seleccione el primer punto',
            conservarPuntoUno
        );

        reconstruirSelectPuntoRuta(
            puntoDosSelect,
            'Seleccione el segundo punto',
            conservarPuntoDos
        );

        formularioRutaInicializado = true;

        actualizarRutaGenerada();
    }

    if (
        empresaSelectRuta
        && puntoUnoSelect
        && puntoDosSelect
    ) {
        empresaSelectRuta.addEventListener(
            'change',
            function () {
                puntoUnoSelect.value = '';
                puntoDosSelect.value = '';

                filtrarPuntosRutaPorEmpresa();
            }
        );

        puntoUnoSelect.addEventListener(
            'change',
            actualizarRutaGenerada
        );

        puntoDosSelect.addEventListener(
            'change',
            actualizarRutaGenerada
        );

        filtrarPuntosRutaPorEmpresa();
    }
</script>