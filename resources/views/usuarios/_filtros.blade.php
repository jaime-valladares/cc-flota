<form method="GET" action="{{ $accionFiltro }}" class="mb-5">
    <input type="hidden" name="consultar" value="1">

    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">
        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
            <div class="cc-form-section-title">
                Filtros de consulta
            </div>
        </div>

        <div class="cc-users-filter-grid">
            <div class="cc-field cc-users-filter-search">
                <label for="busqueda_usuario">
                    Búsqueda de usuario
                </label>

                <input
                    id="busqueda_usuario"
                    name="busqueda_usuario"
                    type="text"
                    class="cc-input"
                    value="{{ $busquedaUsuario }}"
                    maxlength="150"
                    placeholder="Nombre, apellido o correo"
                >

                @error('busqueda_usuario')
                    <div class="cc-error">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="cc-field">
                <label for="tipo_usuario">
                    Tipo de usuario
                </label>

                @if ($esUsuarioDieselCop)
                    <select id="tipo_usuario" name="tipo_usuario" class="cc-input">
                        <option value="">Todos</option>
                        <option value="diesel_cop" @selected($tipoUsuario === 'diesel_cop')>
                            Diesel Cop
                        </option>
                        <option value="empresa" @selected($tipoUsuario === 'empresa')>
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
            </div>

            <div class="cc-field">
                <label>
                    Empresa
                </label>

                @if ($esUsuarioDieselCop)
                    <div class="cc-filter-multiselect" data-cc-filter-multiselect>
                        <button
                            type="button"
                            class="cc-filter-multiselect-toggle"
                            data-cc-filter-toggle
                        >
                            <span data-cc-filter-label data-default-label="Todas">
                                @if (! empty($empresaIds))
                                    {{ count($empresaIds) }} seleccionadas
                                @else
                                    Todas
                                @endif
                            </span>

                            <span class="cc-filter-multiselect-arrow">
                                ⌄
                            </span>
                        </button>

                        <div class="cc-filter-multiselect-menu" data-cc-filter-menu>
                            <div class="cc-filter-multiselect-list">
                                <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                    <input type="checkbox" data-cc-filter-master>
                                    <span>Seleccionar todo</span>
                                </label>

                                @foreach ($empresasSelector as $empresa)
                                    <label class="cc-filter-multiselect-option" data-cc-filter-option>
                                        <input
                                            type="checkbox"
                                            name="empresa_ids[]"
                                            value="{{ $empresa->id }}"
                                            @checked(
                                                in_array(
                                                    (string) $empresa->id,
                                                    array_map('strval', $empresaIds),
                                                    true
                                                )
                                            )
                                            data-cc-filter-checkbox
                                        >

                                        <span data-cc-filter-option-label>
                                            {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <select class="cc-input" disabled>
                        @foreach ($empresasSelector as $empresa)
                            <option value="{{ $empresa->id }}" selected>
                                {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                            </option>
                        @endforeach
                    </select>

                    @foreach ($empresaIds as $empresaId)
                        <input type="hidden" name="empresa_ids[]" value="{{ $empresaId }}">
                    @endforeach
                @endif
            </div>

            <div class="cc-field">
                <label>
                    Rol
                </label>

                <div class="cc-filter-multiselect" data-cc-filter-multiselect>
                    <button
                        type="button"
                        class="cc-filter-multiselect-toggle"
                        data-cc-filter-toggle
                    >
                        <span data-cc-filter-label data-default-label="Todos">
                            @if (! empty($rolIds))
                                {{ count($rolIds) }} seleccionados
                            @else
                                Todos
                            @endif
                        </span>

                        <span class="cc-filter-multiselect-arrow">
                            ⌄
                        </span>
                    </button>

                    <div class="cc-filter-multiselect-menu" data-cc-filter-menu>
                        <div class="cc-filter-multiselect-list">
                            <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                <input type="checkbox" data-cc-filter-master>
                                <span>Seleccionar todo</span>
                            </label>

                            @foreach ($rolesSelector as $rol)
                                <label class="cc-filter-multiselect-option" data-cc-filter-option>
                                    <input
                                        type="checkbox"
                                        name="rol_ids[]"
                                        value="{{ $rol->id }}"
                                        @checked(
                                            in_array(
                                                (string) $rol->id,
                                                array_map('strval', $rolIds),
                                                true
                                            )
                                        )
                                        data-cc-filter-checkbox
                                    >

                                    <span data-cc-filter-option-label>
                                        {{ $rol->nombre }}
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="cc-field">
                <label for="estado">
                    Estado
                </label>

                <select id="estado" name="estado" class="cc-input">
                    <option value="">Todos</option>
                    <option value="activo" @selected($estado === 'activo')>
                        Activos
                    </option>
                    <option value="inactivo" @selected($estado === 'inactivo')>
                        Inactivos
                    </option>
                </select>
            </div>

            <div class="cc-standard-filter-actions cc-users-filter-actions">
                <button type="submit" class="cc-btn-primary">
                    Consultar
                </button>

                <a href="{{ $rutaLimpiar }}" class="cc-btn-secondary">
                    Limpiar
                </a>
            </div>
        </div>
    </div>
</form>
