<div class="cc-grid">
    <div class="cc-field">
        <label for="nombre_legal">
            Nombre legal <span class="cc-required">*</span>
        </label>
        <input type="text"
               name="nombre_legal"
               id="nombre_legal"
               value="{{ old('nombre_legal', $empresa->nombre_legal ?? '') }}"
               required
               maxlength="150"
               class="cc-input">
        @error('nombre_legal')
            <p class="cc-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="cc-field">
        <label for="nombre_comercial">
            Nombre comercial
        </label>
        <input type="text"
               name="nombre_comercial"
               id="nombre_comercial"
               value="{{ old('nombre_comercial', $empresa->nombre_comercial ?? '') }}"
               maxlength="150"
               class="cc-input">
        @error('nombre_comercial')
            <p class="cc-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="cc-field">
        <label for="nit">
            NIT <span class="cc-required">*</span>
        </label>
        <input type="text"
               name="nit"
               id="nit"
               value="{{ old('nit', $empresa->nit ?? '') }}"
               required
               maxlength="17"
               inputmode="numeric"
               autocomplete="off"
               class="cc-input">
        @error('nit')
            <p class="cc-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="cc-field">
        <label for="telefono_empresa">
            Teléfono empresa
        </label>
        <input type="text"
               name="telefono_empresa"
               id="telefono_empresa"
               value="{{ old('telefono_empresa', $empresa->telefono_empresa ?? '') }}"
               maxlength="9"
               inputmode="numeric"
               autocomplete="off"
               class="cc-input">
        @error('telefono_empresa')
            <p class="cc-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="direccion">
            Dirección
        </label>
        <input type="text"
               name="direccion"
               id="direccion"
               value="{{ old('direccion', $empresa->direccion ?? '') }}"
               maxlength="255"
               class="cc-input">
        @error('direccion')
            <p class="cc-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="cc-field">
        <label for="correo_empresa">
            Correo empresa <span class="cc-required">*</span>
        </label>
        <input type="email"
               name="correo_empresa"
               id="correo_empresa"
               value="{{ old('correo_empresa', $empresa->correo_empresa ?? '') }}"
               required
               maxlength="150"
               class="cc-input">
        @error('correo_empresa')
            <p class="cc-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="cc-field">
        <label for="poc_nombre">
            Nombre del POC <span class="cc-required">*</span>
        </label>
        <input type="text"
               name="poc_nombre"
               id="poc_nombre"
               value="{{ old('poc_nombre', $empresa->poc_nombre ?? '') }}"
               required
               maxlength="150"
               class="cc-input">
        @error('poc_nombre')
            <p class="cc-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="cc-field">
        <label for="poc_email">
            Correo del POC <span class="cc-required">*</span>
        </label>
        <input type="email"
               name="poc_email"
               id="poc_email"
               value="{{ old('poc_email', $empresa->poc_email ?? '') }}"
               required
               maxlength="150"
               class="cc-input">
        @error('poc_email')
            <p class="cc-error">{{ $message }}</p>
        @enderror
    </div>

    <div class="cc-field">
        <label for="poc_telefono">
            Teléfono del POC
        </label>
        <input type="text"
               name="poc_telefono"
               id="poc_telefono"
               value="{{ old('poc_telefono', $empresa->poc_telefono ?? '') }}"
               maxlength="9"
               inputmode="numeric"
               autocomplete="off"
               class="cc-input">
        @error('poc_telefono')
            <p class="cc-error">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="cc-actions">
    <button type="submit" class="cc-btn-primary">
        {{ $submitLabel }}
    </button>

    <a href="{{ route('empresas.index') }}" class="cc-btn-secondary">
        Cancelar
    </a>
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

    document.addEventListener('DOMContentLoaded', function () {
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
    });
</script>