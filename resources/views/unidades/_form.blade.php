@php
    $unidad = $unidad ?? null;
    $esEdicion = ! is_null($unidad);
    $modoVentana = $modoVentana ?? false;
    $submitLabel = $submitLabel ?? 'Guardar unidad';

    $empresas = $empresas ?? collect();

    $queryParams = request()->query();

    $empresaActual = old(
        'empresa_id',
        $unidad->empresa_id
            ?? ($empresaUsuario->id ?? '')
    );

    $tanquesPersistidos = $unidad?->tanquesUnidad ?? collect();
    $tanquesFormulario = collect(old('tanques', []));
    $cantidadTanquesActual = (int) old(
        'cantidad_tanques',
        $tanquesPersistidos->count() ?: ($unidad->total_tanques ?? 1)
    );
@endphp

@if ($errors->any())
    <div class="cc-alert cc-alert-danger">
        <ul class="cc-alert-list">
            @foreach ($errors->all() as $error)
                <li>
                    {{ $error }}
                </li>
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
            <select
                id="empresa_id_visible"
                class="cc-input"
                disabled
            >
                @foreach ($empresas as $empresa)
                    <option
                        value="{{ $empresa->id }}"
                        @selected(
                            (string) $empresaActual === (string) $empresa->id
                        )
                    >
                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                    </option>
                @endforeach
            </select>

            <input
                type="hidden"
                name="empresa_id"
                value="{{ $empresaActual }}"
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

                @foreach ($empresas as $empresa)
                    <option
                        value="{{ $empresa->id }}"
                        @selected(
                            (string) $empresaActual === (string) $empresa->id
                        )
                    >
                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                    </option>
                @endforeach
            </select>
        @else
            <select
                id="empresa_id_visible"
                class="cc-input"
                disabled
            >
                @foreach ($empresas as $empresa)
                    <option
                        value="{{ $empresa->id }}"
                        @selected(
                            (string) $empresaActual === (string) $empresa->id
                        )
                    >
                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                    </option>
                @endforeach
            </select>

            <input
                type="hidden"
                name="empresa_id"
                value="{{ $empresaActual }}"
            >
        @endif

        @error('empresa_id')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="placa">
            Nombre / Placa <span class="cc-required">*</span>
        </label>

        <input
            id="placa"
            name="placa"
            type="text"
            class="cc-input"
            value="{{ old('placa', $unidad->placa ?? '') }}"
            maxlength="30"
            required
            placeholder="Ej. Camión 01 o P123-456"
            autocomplete="off"
        >

        @error('placa')
            <div class="cc-error">
                {{ $message }}
            </div>
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
            autocomplete="off"
        >

        @error('marca')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Configuración de tanques y cobertura
        </div>
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="cantidad_tanques">
            Total de tanques de la unidad
            <span class="cc-required">*</span>
        </label>

        <select
            id="cantidad_tanques"
            name="cantidad_tanques"
            class="cc-input"
            required
        >
            <option value="">
                Seleccione
            </option>

            <option
                value="1"
                @selected(
                    (string) $cantidadTanquesActual === '1'
                )
            >
                1 tanque
            </option>

            <option
                value="2"
                @selected(
                    (string) $cantidadTanquesActual === '2'
                )
            >
                2 tanques
            </option>

            <option
                value="3"
                @selected(
                    (string) $cantidadTanquesActual === '3'
                )
            >
                3 tanques
            </option>
        </select>

        @error('cantidad_tanques')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div id="unidad-tanques" class="cc-col-span-2 cc-grid cc-grid-compact">
        @for ($indice = 0; $indice < 3; $indice++)
            @php
                $persistido = $tanquesPersistidos->get($indice);
                $enviado = $tanquesFormulario->get($indice, []);
                $capacidad = $enviado['capacidad'] ?? $persistido?->capacidad;
                $cubierto = array_key_exists('cubierto_por_licencia', $enviado)
                    ? (bool) $enviado['cubierto_por_licencia']
                    : (bool) ($persistido?->cubierto_por_licencia ?? ($indice === 0));
            @endphp
            <div class="cc-field" data-unidad-tanque="{{ $indice + 1 }}">
                <label for="tanque_{{ $indice }}_capacidad">
                    Tanque {{ $indice + 1 }} — Capacidad (gal)
                    <span class="cc-required">*</span>
                </label>
                <input
                    id="tanque_{{ $indice }}_capacidad"
                    name="tanques[{{ $indice }}][capacidad]"
                    type="number"
                    class="cc-input"
                    value="{{ $capacidad }}"
                    min="0.01"
                    max="99999999.99"
                    step="0.01"
                    data-capacidad-tanque
                >
                <input type="hidden" name="tanques[{{ $indice }}][cubierto_por_licencia]" value="0">
                <label class="cc-checkbox-option">
                    <input
                        type="checkbox"
                        name="tanques[{{ $indice }}][cubierto_por_licencia]"
                        value="1"
                        data-cobertura-tanque
                        @checked($cubierto)
                    >
                    Cubierto por licencia
                </label>
                @error("tanques.$indice.capacidad")
                    <div class="cc-error">{{ $message }}</div>
                @enderror
            </div>
        @endfor
    </div>

    @error('tanques')
        <div class="cc-error cc-col-span-2">{{ $message }}</div>
    @enderror

    <div class="cc-field">
        <label>Capacidad total de la unidad</label>
        <input id="capacidad_total_calculada" type="text" class="cc-input" readonly>
    </div>
    <div class="cc-field">
        <label>Tanques cubiertos por licencia</label>
        <input id="tanques_cubiertos_calculados" type="text" class="cc-input" readonly>
    </div>
    <div class="cc-field">
        <label>Capacidad cubierta por licencia</label>
        <input id="capacidad_cubierta_calculada" type="text" class="cc-input" readonly>
    </div>

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Modelo de medición
        </div>
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="modelo_medicion">
            Modelo de medición
            <span class="cc-required">*</span>
        </label>

        <select
            id="modelo_medicion"
            name="modelo_medicion"
            class="cc-input"
            required
        >
            <option value="">
                Seleccione un modelo
            </option>

            @foreach ($modelosMedicion as $valor => $etiqueta)
                <option
                    value="{{ $valor }}"
                    @selected(
                        old(
                            'modelo_medicion',
                            $unidad->modelo_medicion ?? ''
                        ) === $valor
                    )
                >
                    {{ $etiqueta }}
                </option>
            @endforeach
        </select>

        @error('modelo_medicion')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="rendimiento_teorico_km_galon">
            Km/Gal teórico <span class="cc-required">*</span>
        </label>
        <input
            id="rendimiento_teorico_km_galon"
            name="rendimiento_teorico_km_galon"
            type="number"
            class="cc-input"
            value="{{ old('rendimiento_teorico_km_galon', $unidad->rendimiento_teorico_km_galon ?? '') }}"
            min="0.0001"
            step="0.0001"
            required
        >
        @error('rendimiento_teorico_km_galon')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field" id="campo-rendimiento-gal-hora">
        <label for="rendimiento_teorico_gal_hora">
            Gal/Hora teórico <span class="cc-required">*</span>
        </label>
        <input
            id="rendimiento_teorico_gal_hora"
            name="rendimiento_teorico_gal_hora"
            type="number"
            class="cc-input"
            value="{{ old('rendimiento_teorico_gal_hora', $unidad->rendimiento_teorico_gal_hora ?? '') }}"
            min="0.0001"
            step="0.0001"
        >
        @error('rendimiento_teorico_gal_hora')
            <div class="cc-error">{{ $message }}</div>
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
            href="{{ $modoVentana
                ? route(
                    'unidades.show.ventana',
                    array_merge(
                        $queryParams,
                        ['unidad' => $unidad]
                    )
                )
                : route(
                    'unidades.show',
                    array_merge(
                        $queryParams,
                        ['unidad' => $unidad]
                    )
                ) }}"
            class="cc-btn-secondary cc-btn-form-action"
        >
            Cancelar
        </a>
    @else
        <a
            href="{{ $modoVentana
                ? route(
                    'unidades.consulta.ventana',
                    $queryParams
                )
                : route(
                    'unidades.index',
                    $queryParams
                ) }}"
            class="cc-btn-secondary cc-btn-form-action"
        >
            Cancelar
        </a>
    @endif
</div>

<script>
    const placaInput = document.getElementById('placa');
    const cantidadTanquesInput = document.getElementById('cantidad_tanques');
    const modeloMedicionInput = document.getElementById('modelo_medicion');
    const campoGalHora = document.getElementById('campo-rendimiento-gal-hora');
    const rendimientoGalHora = document.getElementById('rendimiento_teorico_gal_hora');
    const filasTanques = Array.from(document.querySelectorAll('[data-unidad-tanque]'));

    function actualizarEstructura() {
        const cantidad = Number(cantidadTanquesInput?.value || 0);
        let capacidadTotal = 0;
        let capacidadCubierta = 0;
        let tanquesCubiertos = 0;

        filasTanques.forEach(function (fila, indice) {
            const visible = indice < cantidad;
            const capacidad = fila.querySelector('[data-capacidad-tanque]');
            const cobertura = fila.querySelector('[data-cobertura-tanque]');

            fila.hidden = !visible;
            fila.querySelectorAll('input[name]').forEach(function (input) {
                input.disabled = !visible;
            });
            capacidad.required = visible;

            if (!visible) {
                return;
            }

            const galones = Number(capacidad.value || 0);
            capacidadTotal += galones;

            if (cobertura.checked) {
                tanquesCubiertos++;
                capacidadCubierta += galones;
            }
        });

        document.getElementById('capacidad_total_calculada').value =
            capacidadTotal.toFixed(2) + ' gal';
        document.getElementById('tanques_cubiertos_calculados').value =
            String(tanquesCubiertos);
        document.getElementById('capacidad_cubierta_calculada').value =
            capacidadCubierta.toFixed(2) + ' gal';
    }

    function actualizarGalHora() {
        const aplica = modeloMedicionInput?.value === 'galones_hora';
        campoGalHora.hidden = !aplica;
        rendimientoGalHora.required = aplica;
        rendimientoGalHora.disabled = !aplica;
    }

    cantidadTanquesInput?.addEventListener('change', actualizarEstructura);
    modeloMedicionInput?.addEventListener('change', actualizarGalHora);
    filasTanques.forEach(function (fila) {
        fila.addEventListener('input', actualizarEstructura);
        fila.addEventListener('change', actualizarEstructura);
    });

    actualizarEstructura();
    actualizarGalHora();
</script>
