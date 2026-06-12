@php
    $usuario = $usuario ?? null;
    $esEdicion = ! is_null($usuario);
    $tipoUsuarioActual = old('tipo_usuario', $usuario->tipo_usuario ?? '');
    $empresaActual = old('empresa_id', $usuario->empresa_id ?? '');
    $rolActual = old('rol_id', $usuario->rol_id ?? '');
@endphp

<div class="cc-grid">

    <div class="cc-form-section">
        <div class="cc-form-section-title">
            Clasificación y acceso
        </div>
        <div class="cc-form-section-note">
            Defina si el usuario pertenece a Diesel Cop o a una empresa cliente, y asigne el rol correspondiente.
        </div>
    </div>

    <div class="cc-field">
        <label for="tipo_usuario">
            Tipo de usuario <span class="cc-required">*</span>
        </label>

        @if ($esUsuarioDieselCop)
            <select id="tipo_usuario" name="tipo_usuario" class="cc-input" required>
                <option value="">Seleccione</option>
                <option value="diesel_cop" @selected($tipoUsuarioActual === 'diesel_cop')>
                    Diesel Cop
                </option>
                <option value="empresa" @selected($tipoUsuarioActual === 'empresa')>
                    Empresa
                </option>
            </select>
        @else
            <input type="hidden" name="tipo_usuario" value="empresa">

            <select id="tipo_usuario" class="cc-input" disabled>
                <option value="empresa" selected>
                    Empresa
                </option>
            </select>
        @endif

        @error('tipo_usuario')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="empresa_id">
            Empresa <span id="empresa_required_marker" class="cc-required">*</span>
        </label>

        @if ($esUsuarioDieselCop)
            <select id="empresa_id" name="empresa_id" class="cc-input">
                <option value="">Seleccione</option>

                @foreach ($empresas as $empresa)
                    <option value="{{ $empresa->id }}"
                            @selected((string) $empresaActual === (string) $empresa->id)>
                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                    </option>
                @endforeach
            </select>
        @else
            <input type="hidden" name="empresa_id" value="{{ $empresaActual }}">

            <select id="empresa_id" class="cc-input" disabled>
                @foreach ($empresas as $empresa)
                    <option value="{{ $empresa->id }}" selected>
                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                    </option>
                @endforeach
            </select>
        @endif

        @error('empresa_id')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field cc-col-span-2">
        <label for="rol_id">
            Rol <span class="cc-required">*</span>
        </label>

        <select id="rol_id" name="rol_id" class="cc-input" required>
            <option value="">Seleccione</option>

            @foreach ($roles as $rol)
                <option value="{{ $rol->id }}"
                        data-alcance="{{ $rol->alcance }}"
                        @selected((string) $rolActual === (string) $rol->id)>
                    {{ $rol->nombre }}
                </option>
            @endforeach
        </select>

        @error('rol_id')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-form-section">
        <div class="cc-form-section-title">
            Datos personales
        </div>
        <div class="cc-form-section-note">
            Información básica de identificación y contacto del usuario.
        </div>
    </div>

    <div class="cc-field">
        <label for="name">
            Nombre <span class="cc-required">*</span>
        </label>

        <input
            id="name"
            name="name"
            type="text"
            class="cc-input"
            value="{{ old('name', $usuario->name ?? '') }}"
            required
        >

        @error('name')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="apellido">
            Apellido
        </label>

        <input
            id="apellido"
            name="apellido"
            type="text"
            class="cc-input"
            value="{{ old('apellido', $usuario->apellido ?? '') }}"
        >

        @error('apellido')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="email">
            Correo electrónico <span class="cc-required">*</span>
        </label>

        <input
            id="email"
            name="email"
            type="email"
            class="cc-input"
            value="{{ old('email', $usuario->email ?? '') }}"
            required
        >

        @error('email')
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
            value="{{ old('telefono', $usuario->telefono ?? '') }}"
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
        <label for="cargo">
            Cargo
        </label>

        <input
            id="cargo"
            name="cargo"
            type="text"
            class="cc-input"
            value="{{ old('cargo', $usuario->cargo ?? '') }}"
            placeholder="Ej. Administrador, Supervisor de Flota, Operador"
        >

        @error('cargo')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-form-section">
        <div class="cc-form-section-title">
            Credenciales
        </div>
        <div class="cc-form-section-note">
            @if ($esEdicion)
                Deje la contraseña en blanco si no desea modificarla.
            @else
                Defina la contraseña inicial del usuario.
            @endif
        </div>
    </div>

    <div class="cc-field">
        <label for="password">
            Contraseña
            @if (! $esEdicion)
                <span class="cc-required">*</span>
            @endif
        </label>

        <input
            id="password"
            name="password"
            type="password"
            class="cc-input"
            @if (! $esEdicion) required @endif
        >

        @error('password')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="password_confirmation">
            Confirmar contraseña
            @if (! $esEdicion)
                <span class="cc-required">*</span>
            @endif
        </label>

        <input
            id="password_confirmation"
            name="password_confirmation"
            type="password"
            class="cc-input"
            @if (! $esEdicion) required @endif
        >
    </div>

</div>

<div class="cc-actions">
    <button type="submit" class="cc-btn-primary cc-btn-form-action">
        {{ $submitLabel }}
    </button>

    @if ($esEdicion)
        <a href="{{ route('usuarios.show', $usuario) }}" class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @else
        <a href="{{ route('usuarios.index') }}" class="cc-btn-secondary cc-btn-form-action">
            Cancelar
        </a>
    @endif
</div>

<script>
    function formatPhone(value) {
        const digits = value.replace(/\D/g, '').slice(0, 8);

        if (digits.length <= 4) {
            return digits;
        }

        return `${digits.slice(0, 4)}-${digits.slice(4)}`;
    }

    const tipoUsuarioSelect = document.getElementById('tipo_usuario');
    const empresaSelect = document.getElementById('empresa_id');
    const empresaRequiredMarker = document.getElementById('empresa_required_marker');
    const rolSelect = document.getElementById('rol_id');
    const telefonoInput = document.getElementById('telefono');

    function actualizarFormularioUsuario() {
        const tipoUsuario = tipoUsuarioSelect.value;

        if (tipoUsuario === 'diesel_cop') {
            empresaSelect.value = '';
            empresaSelect.disabled = true;
            empresaRequiredMarker.style.display = 'none';
        } else {
            empresaSelect.disabled = false;
            empresaRequiredMarker.style.display = 'inline';
        }

        Array.from(rolSelect.options).forEach((option) => {
            const alcance = option.dataset.alcance;

            if (!alcance) {
                option.hidden = false;
                return;
            }

            if (!tipoUsuario) {
                option.hidden = false;
                return;
            }

            option.hidden = alcance !== tipoUsuario;

            if (option.selected && option.hidden) {
                rolSelect.value = '';
            }
        });
    }

    if (tipoUsuarioSelect && empresaSelect && rolSelect) {
        tipoUsuarioSelect.addEventListener('change', actualizarFormularioUsuario);
        actualizarFormularioUsuario();
    }

    if (telefonoInput) {
        telefonoInput.addEventListener('input', function () {
            this.value = formatPhone(this.value);
        });
    }
</script>