@php
    $gasolinera = $gasolinera ?? null;
    $esEdicion = ! is_null($gasolinera);
    $modoVentana = $modoVentana ?? false;
    $submitLabel = $submitLabel ?? 'Guardar gasolinera';

    $empresas = $empresas ?? $empresasSelector ?? collect();

    $empresaActual = old('empresa_id', $gasolinera->empresa_id ?? ($empresaUsuario->id ?? ''));

    $tanquesOld = old('tanques', [
        [
            'nombre' => 'Tanque 1',
            'capacidad_total' => '',
            'volumen_actual' => '',
            'volumen_minimo_alerta' => '',
        ],
    ]);
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
            Identificación de la gasolinera
        </div>
    </div>

    <div class="cc-field">
        <label for="empresa_id">
            Empresa <span class="cc-required">*</span>
        </label>

        @if ($esUsuarioDieselCop)
            <select id="empresa_id" name="empresa_id" class="cc-input" required>
                <option value="">Seleccione una empresa</option>

                @foreach ($empresas as $empresa)
                    <option value="{{ $empresa->id }}"
                            @selected((string) $empresaActual === (string) $empresa->id)>
                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                    </option>
                @endforeach
            </select>
        @else
            <select id="empresa_id_visible" class="cc-input" disabled>
                @foreach ($empresas as $empresa)
                    <option value="{{ $empresa->id }}" selected>
                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
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
        <label for="nombre">
            Nombre de gasolinera <span class="cc-required">*</span>
        </label>

        <input
            id="nombre"
            name="nombre"
            type="text"
            class="cc-input"
            value="{{ old('nombre', $gasolinera->nombre ?? '') }}"
            maxlength="150"
            placeholder="Ej. Gasolinera central"
            required
        >

        @error('nombre')
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
            value="{{ old('direccion', $gasolinera->direccion ?? '') }}"
            maxlength="255"
            placeholder="Dirección física de la gasolinera"
            required
        >

        @error('direccion')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Contacto operativo
        </div>
    </div>

    <div class="cc-field">
        <label for="encargado">
            Encargado
        </label>

        <input
            id="encargado"
            name="encargado"
            type="text"
            class="cc-input"
            value="{{ old('encargado', $gasolinera->encargado ?? '') }}"
            maxlength="150"
            placeholder="Nombre del encargado"
        >

        @error('encargado')
            <div class="cc-error">
                {{ $message }}
            </div>
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
            value="{{ old('telefono', $gasolinera->telefono ?? '') }}"
            maxlength="9"
            placeholder="0000-0000"
        >

        @error('telefono')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="correo">
            Correo de alerta
        </label>

        <input
            id="correo"
            name="correo"
            type="email"
            class="cc-input"
            value="{{ old('correo', $gasolinera->correo ?? '') }}"
            maxlength="150"
            placeholder="encargado@empresa.com"
        >

        @error('correo')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    @if (! $esEdicion)
        <div class="cc-form-section-slim">
            <div class="cc-form-section-title">
                Tanques e inventario inicial
            </div>
        </div>

        <div class="cc-field cc-col-span-2">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm leading-relaxed text-[var(--cc-text-muted)]">
                    Registre al menos un tanque para que la gasolinera pueda operar. El volumen actual generará una carga inicial de inventario.
                </p>

                <button
                    type="button"
                    id="agregarTanqueBtn"
                    class="cc-btn-secondary cc-btn-form-action w-full sm:w-auto"
                >
                    Agregar tanque
                </button>
            </div>
        </div>

        <div id="tanquesContainer" class="cc-col-span-2">
            @foreach ($tanquesOld as $index => $tanqueOld)
                <div class="tanque-item" data-index="{{ $index }}">
                    <div class="cc-admin-result-card" style="margin-bottom: 1rem;">
                        <div class="cc-grid cc-grid-compact">

                            <div class="cc-form-section-slim">
                                <div class="cc-form-section-title">
                                    Tanque <span class="tanque-numero">{{ $index + 1 }}</span>
                                </div>
                            </div>

                            <div class="cc-field">
                                <label>
                                    Nombre del tanque <span class="cc-required">*</span>
                                </label>

                                <input
                                    type="text"
                                    name="tanques[{{ $index }}][nombre]"
                                    value="{{ $tanqueOld['nombre'] ?? '' }}"
                                    class="cc-input"
                                    maxlength="100"
                                    required
                                    placeholder="Ej. Tanque {{ $index + 1 }}"
                                >

                                @error("tanques.$index.nombre")
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-field">
                                <label>
                                    Capacidad total (galones) <span class="cc-required">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="tanques[{{ $index }}][capacidad_total]"
                                    value="{{ $tanqueOld['capacidad_total'] ?? '' }}"
                                    class="cc-input"
                                    min="0.01"
                                    step="0.01"
                                    required
                                    placeholder="Ej. 10000.00"
                                >

                                @error("tanques.$index.capacidad_total")
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-field">
                                <label>
                                    Volumen actual (galones) <span class="cc-required">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="tanques[{{ $index }}][volumen_actual]"
                                    value="{{ $tanqueOld['volumen_actual'] ?? '' }}"
                                    class="cc-input"
                                    min="0"
                                    step="0.01"
                                    required
                                    placeholder="Ej. 8000.00"
                                >

                                @error("tanques.$index.volumen_actual")
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-field">
                                <label>
                                    Volumen mínimo de alerta (galones) <span class="cc-required">*</span>
                                </label>

                                <input
                                    type="number"
                                    name="tanques[{{ $index }}][volumen_minimo_alerta]"
                                    value="{{ $tanqueOld['volumen_minimo_alerta'] ?? '' }}"
                                    class="cc-input"
                                    min="0"
                                    step="0.01"
                                    required
                                    placeholder="Ej. 1000.00"
                                >

                                @error("tanques.$index.volumen_minimo_alerta")
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-field cc-col-span-2">
                                <button
                                    type="button"
                                    class="cc-btn-secondary cc-btn-form-action eliminar-tanque-btn"
                                >
                                    Eliminar tanque
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

<div class="cc-actions cc-actions-compact">
    <button type="submit" class="cc-btn-primary cc-btn-form-action">
        {{ $submitLabel }}
    </button>

    @if ($esEdicion)
        <a href="{{ $modoVentana ? route('gasolineras.show.ventana', $gasolinera) : route('gasolineras.show', $gasolinera) }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @else
        <a href="{{ $modoVentana ? route('gasolineras.consulta.ventana') : route('gasolineras.index') }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @endif
</div>

<script>
    const tanquesContainer = document.getElementById('tanquesContainer');
    const agregarTanqueBtn = document.getElementById('agregarTanqueBtn');
    const telefonoInput = document.getElementById('telefono');

    function formatPhone(value) {
        const digits = value.replace(/\D/g, '').slice(0, 8);

        if (digits.length <= 4) {
            return digits;
        }

        return `${digits.slice(0, 4)}-${digits.slice(4)}`;
    }

    function normalizarTelefono() {
        if (! telefonoInput) {
            return;
        }

        telefonoInput.value = formatPhone(telefonoInput.value);
    }

    function actualizarTanques() {
        if (! tanquesContainer) {
            return;
        }

        const items = tanquesContainer.querySelectorAll('.tanque-item');

        items.forEach((item, index) => {
            item.dataset.index = index;

            const numero = item.querySelector('.tanque-numero');

            if (numero) {
                numero.textContent = index + 1;
            }

            item.querySelectorAll('input').forEach((input) => {
                const field = input.name.match(/\]\[(.+)\]/)?.[1];

                if (field) {
                    input.name = `tanques[${index}][${field}]`;
                }
            });

            const eliminarBtn = item.querySelector('.eliminar-tanque-btn');

            if (eliminarBtn) {
                eliminarBtn.disabled = items.length === 1;
                eliminarBtn.style.opacity = items.length === 1 ? '0.45' : '1';
                eliminarBtn.style.cursor = items.length === 1 ? 'not-allowed' : 'pointer';
            }
        });
    }

    function crearTanque() {
        if (! tanquesContainer) {
            return;
        }

        const index = tanquesContainer.querySelectorAll('.tanque-item').length;

        const wrapper = document.createElement('div');
        wrapper.className = 'tanque-item';
        wrapper.dataset.index = index;

        wrapper.innerHTML = `
            <div class="cc-admin-result-card" style="margin-bottom: 1rem;">
                <div class="cc-grid cc-grid-compact">

                    <div class="cc-form-section-slim">
                        <div class="cc-form-section-title">
                            Tanque <span class="tanque-numero">${index + 1}</span>
                        </div>
                    </div>

                    <div class="cc-field">
                        <label>
                            Nombre del tanque <span class="cc-required">*</span>
                        </label>

                        <input
                            type="text"
                            name="tanques[${index}][nombre]"
                            value="Tanque ${index + 1}"
                            class="cc-input"
                            maxlength="100"
                            required
                            placeholder="Ej. Tanque ${index + 1}"
                        >
                    </div>

                    <div class="cc-field">
                        <label>
                            Capacidad total (galones) <span class="cc-required">*</span>
                        </label>

                        <input
                            type="number"
                            name="tanques[${index}][capacidad_total]"
                            class="cc-input"
                            min="0.01"
                            step="0.01"
                            required
                            placeholder="Ej. 10000.00"
                        >
                    </div>

                    <div class="cc-field">
                        <label>
                            Volumen actual (galones) <span class="cc-required">*</span>
                        </label>

                        <input
                            type="number"
                            name="tanques[${index}][volumen_actual]"
                            class="cc-input"
                            min="0"
                            step="0.01"
                            required
                            placeholder="Ej. 8000.00"
                        >
                    </div>

                    <div class="cc-field">
                        <label>
                            Volumen mínimo de alerta (galones) <span class="cc-required">*</span>
                        </label>

                        <input
                            type="number"
                            name="tanques[${index}][volumen_minimo_alerta]"
                            class="cc-input"
                            min="0"
                            step="0.01"
                            required
                            placeholder="Ej. 1000.00"
                        >
                    </div>

                    <div class="cc-field cc-col-span-2">
                        <button
                            type="button"
                            class="cc-btn-secondary cc-btn-form-action eliminar-tanque-btn"
                        >
                            Eliminar tanque
                        </button>
                    </div>

                </div>
            </div>
        `;

        tanquesContainer.appendChild(wrapper);
        actualizarTanques();
    }

    function validarTanques() {
        if (! tanquesContainer) {
            return;
        }

        const items = tanquesContainer.querySelectorAll('.tanque-item');

        items.forEach((item) => {
            const capacidadInput = item.querySelector('input[name*="[capacidad_total]"]');
            const volumenActualInput = item.querySelector('input[name*="[volumen_actual]"]');
            const minimoInput = item.querySelector('input[name*="[volumen_minimo_alerta]"]');

            if (! capacidadInput || ! volumenActualInput || ! minimoInput) {
                return;
            }

            const capacidad = Number(capacidadInput.value);
            const volumenActual = Number(volumenActualInput.value);
            const minimo = Number(minimoInput.value);

            if (capacidad > 0 && volumenActual > capacidad) {
                volumenActualInput.setCustomValidity('El volumen actual no puede superar la capacidad total.');
            } else {
                volumenActualInput.setCustomValidity('');
            }

            if (capacidad > 0 && minimo >= capacidad) {
                minimoInput.setCustomValidity('El volumen mínimo debe ser menor que la capacidad total.');
            } else {
                minimoInput.setCustomValidity('');
            }
        });
    }

    if (telefonoInput) {
        telefonoInput.addEventListener('input', normalizarTelefono);
    }

    if (agregarTanqueBtn) {
        agregarTanqueBtn.addEventListener('click', crearTanque);
    }

    if (tanquesContainer) {
        tanquesContainer.addEventListener('click', (event) => {
            const btn = event.target.closest('.eliminar-tanque-btn');

            if (! btn) {
                return;
            }

            const items = tanquesContainer.querySelectorAll('.tanque-item');

            if (items.length <= 1) {
                return;
            }

            btn.closest('.tanque-item')?.remove();
            actualizarTanques();
            validarTanques();
        });

        tanquesContainer.addEventListener('input', validarTanques);
    }

    actualizarTanques();
    validarTanques();
</script>