@php
    $gasolineraExterna = $gasolineraExterna ?? null;
    $modoVentana = $modoVentana ?? false;
    $submitLabel = $submitLabel ?? 'Guardar gasolinera';

    $esEdicion = ! is_null($gasolineraExterna);

    $queryParams = request()->query();

    $empresaActualId = old(
        'empresa_id',
        $gasolineraExterna?->empresa_id
            ?? $empresaUsuario?->id
            ?? ''
    );

    $empresaActual = $esEdicion
        ? $gasolineraExterna?->empresa
        : $empresasSelector->firstWhere(
            'id',
            (int) $empresaActualId
        );
@endphp

@if ($modoVentana)
    <input
        type="hidden"
        name="return_to"
        value="ventana"
    >
@endif

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
            Identificación de la gasolinera externa
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
                <option selected>
                    {{ $empresaActual?->nombre_comercial
                        ?: $empresaActual?->nombre_legal }}
                </option>
            </select>

            <input
                type="hidden"
                name="empresa_id"
                value="{{ $gasolineraExterna->empresa_id }}"
            >

            <p class="cc-field-help">
                La empresa asignada no puede modificarse después del registro.
            </p>
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
                            (string) $empresaActualId
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
                id="empresa_id_visible"
                class="cc-input"
                disabled
            >
                @foreach ($empresasSelector as $empresaOpcion)
                    <option selected>
                        {{ $empresaOpcion->nombre_comercial
                            ?: $empresaOpcion->nombre_legal }}
                    </option>
                @endforeach
            </select>

            <input
                type="hidden"
                name="empresa_id"
                value="{{ $empresaActualId }}"
            >
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
            value="{{ old(
                'compania',
                $gasolineraExterna?->compania ?? ''
            ) }}"
            maxlength="150"
            required
            autocomplete="organization"
            placeholder="Ejemplo: UNO, Puma, Texaco o Shell"
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
            value="{{ old(
                'direccion',
                $gasolineraExterna?->direccion ?? ''
            ) }}"
            maxlength="255"
            required
            autocomplete="street-address"
            placeholder="Ingrese la ubicación o referencia de la gasolinera externa"
        >

        @error('direccion')
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
            href="{{ $modoVentana
                ? route(
                    'gasolineras-externas.show.ventana',
                    array_merge(
                        ['gasolineraExterna' => $gasolineraExterna],
                        $queryParams
                    )
                )
                : route(
                    'gasolineras-externas.show',
                    array_merge(
                        ['gasolineraExterna' => $gasolineraExterna],
                        $queryParams
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
                    'gasolineras-externas.consulta.ventana',
                    $queryParams
                )
                : route(
                    'gasolineras-externas.index',
                    $queryParams
                ) }}"
            class="cc-btn-secondary cc-btn-form-action"
        >
            Cancelar
        </a>
    @endif
</div>