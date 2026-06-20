<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Administrar usuario
                        </h3>
                        <p class="cc-subtitle">
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

                <form method="GET" action="{{ route('usuarios.administrar') }}" class="mb-6">
                    <input type="hidden" name="consultar" value="1">

                    <div class="cc-filter-panel">

                        <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
                            <div class="cc-form-section-title">
                                Búsqueda administrativa
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">

                            <div class="lg:col-span-3 cc-field">
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

                            <div class="lg:col-span-4 cc-field">
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

                            <div class="lg:col-span-5 cc-field">
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

                        </div>

                        <div class="mt-5 border-t border-[var(--cc-card-border)] pt-5">
                            <div class="flex items-center justify-end gap-3">
                                <button type="submit" class="cc-btn-primary">
                                    Buscar
                                </button>

                                <a href="{{ route('usuarios.administrar') }}" class="cc-btn-secondary">
                                    Resetear
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
                    <div class="cc-empty-panel">
                        <h5>
                            Búsqueda pendiente
                        </h5>
                        <p>
                            Los resultados permanecerán vacíos hasta que localice un usuario por tipo, empresa o rol.
                        </p>
                    </div>
                @elseif ($usuarios->isEmpty())
                    <div class="cc-empty-panel">
                        <h5>
                            Sin resultados
                        </h5>
                        <p>
                            No hay usuarios que coincidan con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="space-y-4">
                        @foreach ($usuarios as $usuario)
                            <article class="cc-result-card">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">

                                    <div class="lg:col-span-4 min-w-0">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <h5 class="font-[var(--cc-font-heading)] text-xl font-extrabold text-[var(--cc-text-heading)] tracking-[-0.03em] cc-cell-truncate">
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

                                        <div class="mt-1 text-sm font-medium text-[var(--cc-text-muted)] cc-cell-truncate">
                                            {{ $usuario->email }}
                                        </div>
                                    </div>

                                    <div class="lg:col-span-2 min-w-0">
                                        <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                            Empresa
                                        </div>

                                        @if ($usuario->empresa)
                                            <div class="mt-1 font-bold text-[var(--cc-text-main)] cc-cell-truncate">
                                                {{ $usuario->empresa->nombre_comercial ?: $usuario->empresa->nombre_legal }}
                                            </div>
                                        @else
                                            <div class="mt-1 font-bold text-[var(--cc-text-main)]">
                                                Diesel Cop
                                            </div>
                                        @endif
                                    </div>

                                    <div class="lg:col-span-3 min-w-0">
                                        <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                            Rol
                                        </div>

                                        @if ($usuario->role)
                                            <div class="mt-1 font-bold text-[var(--cc-text-main)] cc-cell-truncate">
                                                {{ $usuario->role->nombre }}
                                            </div>
                                        @else
                                            <div class="mt-1 text-sm text-[var(--cc-text-muted)]">
                                                Sin rol
                                            </div>
                                        @endif
                                    </div>

                                    <div class="lg:col-span-3">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('usuarios.show', $usuario) }}" class="cc-btn-primary">
                                                Ver ficha
                                            </a>

                                            <a href="{{ route('usuarios.edit', $usuario) }}" class="cc-btn-secondary">
                                                Editar
                                            </a>
                                        </div>
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