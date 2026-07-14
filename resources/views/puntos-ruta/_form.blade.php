@php
    $queryParams = request()->query();

    $esEdicion = ! is_null($puntoRuta);

    $empresaPunto = $puntoRuta?->empresa;

    $empresaSeleccionadaId = old(
        'empresa_id',
        $puntoRuta?->empresa_id
            ?? $empresaUsuario?->id
            ?? ''
    );

    $nombreEmpresaFija = $empresaPunto?->nombre_comercial
        ?: $empresaPunto?->nombre_legal
        ?: $empresaUsuario?->nombre_comercial
        ?: $empresaUsuario?->nombre_legal
        ?: 'Empresa no disponible';
@endphp

<div class="cc-grid cc-grid-compact">

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Asignación empresarial
        </div>
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="empresa_id">
            Empresa <span class="cc-required">*</span>
        </label>

        @if ($esEdicion)
            <input
                id="empresa_visual"
                type="text"
                class="cc-input"
                value="{{ $nombreEmpresaFija }}"
                disabled
            >

            <input
                type="hidden"
                name="empresa_id"
                value="{{ $puntoRuta->empresa_id }}"
            >

            <div class="mt-2 text-sm text-[var(--cc-text-muted)]">
                La empresa propietaria no puede modificarse después de registrar el punto.
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
                            (string) $empresaSeleccionadaId
                            === (string) $empresaOpcion->id
                        )
                    >
                        {{ $empresaOpcion->nombre_comercial
                            ?: $empresaOpcion->nombre_legal }}
                    </option>
                @endforeach
            </select>
        @else
            <input
                id="empresa_visual"
                type="text"
                class="cc-input"
                value="{{ $nombreEmpresaFija }}"
                disabled
            >

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
            Información del punto de ruta
        </div>
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
            value="{{ old('nombre', $puntoRuta?->nombre ?? '') }}"
            maxlength="150"
            autocomplete="off"
            required
        >

        @error('nombre')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="direccion">
            Dirección <span class="cc-required">*</span>
        </label>

        <input
            id="direccion"
            name="direccion"
            type="text"
            class="cc-input"
            value="{{ old('direccion', $puntoRuta?->direccion ?? '') }}"
            maxlength="255"
            autocomplete="off"
            required
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
            href="{{ ($modoVentana ?? false)
                ? route(
                    'puntos-ruta.show.ventana',
                    array_merge(
                        $queryParams,
                        ['puntoRuta' => $puntoRuta]
                    )
                )
                : route(
                    'puntos-ruta.show',
                    array_merge(
                        $queryParams,
                        ['puntoRuta' => $puntoRuta]
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
                    'puntos-ruta.consulta.ventana',
                    $queryParams
                )
                : route(
                    'puntos-ruta.index',
                    $queryParams
                ) }}"
            class="cc-btn-secondary cc-btn-form-action"
        >
            Cancelar
        </a>
    @endif
</div>