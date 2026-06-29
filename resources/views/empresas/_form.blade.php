<div class="cc-grid cc-grid-compact">

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Identificación de la empresa
        </div>
    </div>

    <div class="cc-field">
        <label for="nombre_legal">
            Nombre legal <span class="cc-required">*</span>
        </label>
        <input
            id="nombre_legal"
            name="nombre_legal"
            type="text"
            class="cc-input"
            value="{{ old('nombre_legal', $empresa->nombre_legal ?? '') }}"
            required
        >
        @error('nombre_legal')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="nombre_comercial">
            Nombre comercial
        </label>
        <input
            id="nombre_comercial"
            name="nombre_comercial"
            type="text"
            class="cc-input"
            value="{{ old('nombre_comercial', $empresa->nombre_comercial ?? '') }}"
        >
        @error('nombre_comercial')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="nit">
            NIT <span class="cc-required">*</span>
        </label>
        <input
            id="nit"
            name="nit"
            type="text"
            class="cc-input"
            value="{{ old('nit', $empresa->nit ?? '') }}"
            maxlength="17"
            placeholder="0000-000000-000-0"
            required
        >
        @error('nit')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Contacto institucional
        </div>
    </div>

    <div class="cc-field">
        <label for="correo_empresa">
            Correo empresa <span class="cc-required">*</span>
        </label>
        <input
            id="correo_empresa"
            name="correo_empresa"
            type="email"
            class="cc-input"
            value="{{ old('correo_empresa', $empresa->correo_empresa ?? '') }}"
            required
        >
        @error('correo_empresa')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="telefono_empresa">
            Teléfono empresa
        </label>
        <input
            id="telefono_empresa"
            name="telefono_empresa"
            type="text"
            class="cc-input"
            value="{{ old('telefono_empresa', $empresa->telefono_empresa ?? '') }}"
            maxlength="9"
            placeholder="0000-0000"
        >
        @error('telefono_empresa')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="direccion">
            Dirección
        </label>
        <input
            id="direccion"
            name="direccion"
            type="text"
            class="cc-input"
            value="{{ old('direccion', $empresa->direccion ?? '') }}"
        >
        @error('direccion')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Punto de contacto operativo
        </div>
    </div>

    <div class="cc-field">
        <label for="poc_nombre">
            Nombre del POC <span class="cc-required">*</span>
        </label>
        <input
            id="poc_nombre"
            name="poc_nombre"
            type="text"
            class="cc-input"
            value="{{ old('poc_nombre', $empresa->poc_nombre ?? '') }}"
            required
        >
        @error('poc_nombre')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="poc_email">
            Correo del POC <span class="cc-required">*</span>
        </label>
        <input
            id="poc_email"
            name="poc_email"
            type="email"
            class="cc-input"
            value="{{ old('poc_email', $empresa->poc_email ?? '') }}"
            required
        >
        @error('poc_email')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="poc_telefono">
            Teléfono del POC
        </label>
        <input
            id="poc_telefono"
            name="poc_telefono"
            type="text"
            class="cc-input"
            value="{{ old('poc_telefono', $empresa->poc_telefono ?? '') }}"
            maxlength="9"
            placeholder="0000-0000"
        >
        @error('poc_telefono')
            <div class="cc-error">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="cc-actions cc-actions-compact">
    <button type="submit" class="cc-btn-primary cc-btn-form-action">
        {{ $submitLabel }}
    </button>

    @if ($empresa)
        <a href="{{ ($modoVentana ?? false) ? route('empresas.show.ventana', $empresa) : route('empresas.show', $empresa) }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @else
        <a href="{{ ($modoVentana ?? false) ? route('empresas.consulta.ventana') : route('empresas.index') }}"
           class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @endif
</div>

<script>
    function formatNit(value) {
        const digits = value.replace(/\D/g, '').slice(0, 14);

        if (digits.length <= 4) {
            return digits;
        }

        if (digits.length <= 10) {
            return `${digits.slice(0, 4)}-${digits.slice(4)}`;
        }

        if (digits.length <= 13) {
            return `${digits.slice(0, 4)}-${digits.slice(4, 10)}-${digits.slice(10)}`;
        }

        return `${digits.slice(0, 4)}-${digits.slice(4, 10)}-${digits.slice(10, 13)}-${digits.slice(13)}`;
    }

    function formatPhone(value) {
        const digits = value.replace(/\D/g, '').slice(0, 8);

        if (digits.length <= 4) {
            return digits;
        }

        return `${digits.slice(0, 4)}-${digits.slice(4)}`;
    }

    const nitInput = document.getElementById('nit');
    const telefonoEmpresaInput = document.getElementById('telefono_empresa');
    const pocTelefonoInput = document.getElementById('poc_telefono');

    if (nitInput) {
        nitInput.addEventListener('input', function () {
            this.value = formatNit(this.value);
        });
    }

    if (telefonoEmpresaInput) {
        telefonoEmpresaInput.addEventListener('input', function () {
            this.value = formatPhone(this.value);
        });
    }

    if (pocTelefonoInput) {
        pocTelefonoInput.addEventListener('input', function () {
            this.value = formatPhone(this.value);
        });
    }
</script>