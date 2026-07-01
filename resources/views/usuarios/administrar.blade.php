<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Administrar usuario
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Localice un usuario para consultar su ficha, editar sus datos o gestionar su estado administrativo.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('usuarios.administrar.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="GET" action="{{ route('usuarios.administrar') }}" class="mb-5">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                        <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                            <div class="cc-form-section-title">
                                Búsqueda administrativa
                            </div>
                        </div>

                        <div class="cc-filter-inline-grid">

                            <div class="cc-field">
                                <label for="tipo_usuario">
                                    Tipo de usuario
                                </label>
                                <select id="tipo_usuario" name="tipo_usuario" class="cc-input">
                                    <option value="">Todos</option>
                                    <option value="diesel_cop" @selected($tipoUsuario === 'diesel_cop')>
                                        Diesel Cop
                                    </option>
                                    <option value="empresa" @selected($tipoUsuario === 'empresa')>
                                        Empresa
                                    </option>
                                </select>
                            </div>

                            <div class="cc-field">
                                <label for="empresa_id">
                                    Empresa
                                </label>
                                <select id="empresa_id" name="empresa_id" class="cc-input">
                                    <option value="">Todas</option>

                                    @foreach ($empresas as $empresa)
                                        <option value="{{ $empresa->id }}"
                                                @selected((string) $empresaId === (string) $empresa->id)>
                                            {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="cc-field">
                                <label for="rol_id">
                                    Rol
                                </label>
                                <select id="rol_id" name="rol_id" class="cc-input">
                                    <option value="">Todos</option>

                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol->id }}"
                                                data-alcance="{{ $rol->alcance }}"
                                                @selected((string) $rolId === (string) $rol->id)>
                                            {{ $rol->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="cc-filter-inline-actions">
                                <button type="submit" class="cc-btn-primary">
                                    Consultar
                                </button>

                                <a href="{{ route('usuarios.administrar') }}" class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $usuarios->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $usuarios->firstItem() }}</span>
                        -
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $usuarios->lastItem() }}</span>
                        de
                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $usuarios->total() }}</span>
                    </div>
                @endif

                @if (! $hayFiltros)
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Búsqueda pendiente
                        </h5>
                        <p>
                            Los resultados permanecerán vacíos hasta que localice un usuario por tipo, empresa o rol.
                        </p>
                    </div>
                @elseif ($usuarios->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>
                        <p>
                            No hay usuarios que coincidan con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($usuarios as $usuario)
                            <article class="cc-result-card cc-result-card-compact">
                                <div class="cc-result-grid">

                                    <div class="cc-result-main">
                                        <div class="cc-result-title-row">
                                            <h5 class="cc-result-title cc-cell-truncate">
                                                {{ $usuario->name }} {{ $usuario->apellido }}
                                            </h5>

                                            @if ($usuario->estado === 'activo')
                                                <span class="cc-badge cc-badge-active">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </div>

                                        <div class="cc-result-subtitle cc-cell-truncate">
                                            {{ $usuario->email }}
                                        </div>
                                    </div>

                                    <div class="cc-result-meta">
                                        <div class="cc-result-label">
                                            Empresa
                                        </div>

                                        @if ($usuario->empresa)
                                            <div class="cc-result-value cc-cell-truncate">
                                                {{ $usuario->empresa->nombre_comercial ?: $usuario->empresa->nombre_legal }}
                                            </div>
                                        @else
                                            <div class="cc-result-value">
                                                Diesel Cop
                                            </div>
                                        @endif
                                    </div>

                                    <div class="cc-result-meta">
                                        <div class="cc-result-label">
                                            Rol
                                        </div>

                                        @if ($usuario->role)
                                            <div class="cc-result-value cc-cell-truncate">
                                                {{ $usuario->role->nombre }}
                                            </div>

                                            <div class="cc-result-value-muted cc-cell-truncate">
                                                {{ $usuario->role->codigo }}
                                            </div>
                                        @else
                                            <div class="cc-result-value-muted">
                                                Sin rol
                                            </div>
                                        @endif
                                    </div>

                                    <div class="cc-result-actions">
                                        <a href="{{ route('usuarios.show', $usuario) }}" class="cc-btn-primary cc-btn-result">
                                            Ver ficha
                                        </a>

                                        <a href="{{ route('usuarios.edit', $usuario) }}" class="cc-btn-secondary cc-btn-result">
                                            Editar
                                        </a>
                                    </div>

                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $usuarios->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>

    <script>
        const tipoUsuarioSelect = document.getElementById('tipo_usuario');
        const empresaSelect = document.getElementById('empresa_id');
        const rolSelect = document.getElementById('rol_id');

        function actualizarFiltrosUsuarios() {
            const tipoUsuario = tipoUsuarioSelect.value;

            if (tipoUsuario === 'diesel_cop') {
                empresaSelect.value = '';
                empresaSelect.disabled = true;
            } else {
                empresaSelect.disabled = false;
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

        tipoUsuarioSelect.addEventListener('change', actualizarFiltrosUsuarios);

        actualizarFiltrosUsuarios();
    </script>
</x-app-layout>