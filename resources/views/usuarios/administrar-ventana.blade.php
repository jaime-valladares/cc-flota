<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar usuario | CC-Flota</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
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
                                <a href="{{ route('usuarios.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="GET" action="{{ route('usuarios.administrar.ventana') }}" class="mb-6">
                            <input type="hidden" name="consultar" value="1">

                            <div class="border border-gray-200 rounded-lg p-5 bg-gray-50">

                                <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
                                    <div class="cc-form-section-title">
                                        Búsqueda administrativa
                                    </div>
                                    <div class="cc-form-section-note">
                                        Ingrese criterios para localizar el usuario que desea administrar.
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

                                    <div class="lg:col-span-3 cc-field">
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

                                    <div class="lg:col-span-3 cc-field">
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

                                    <div class="lg:col-span-3 flex items-center gap-3">
                                        <button type="submit" class="cc-btn-primary">
                                            Buscar
                                        </button>

                                        <a href="{{ route('usuarios.administrar.ventana') }}" class="cc-btn-secondary">
                                            Resetear
                                        </a>
                                    </div>

                                </div>

                                <div class="mt-4 border-t border-gray-200 pt-4">
                                    <p class="text-sm text-gray-500 italic">
                                        Seleccione tipo de usuario, empresa o rol para localizar usuarios administrables.
                                    </p>
                                </div>
                            </div>
                        </form>

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h4 class="text-base font-black text-gray-900">
                                    Resultado administrativo
                                </h4>

                                <p class="text-sm text-gray-500 italic">
                                    @if (! $hayFiltros)
                                        Seleccione tipo de usuario, empresa o rol para buscar usuarios.
                                    @elseif ($usuarios->total() === 0)
                                        No se encontraron usuarios con los criterios seleccionados.
                                    @elseif ($usuarios->total() === 1)
                                        Se encontró 1 usuario para administrar.
                                    @else
                                        Se encontraron {{ $usuarios->total() }} usuarios para administrar.
                                    @endif
                                </p>
                            </div>

                            @if ($hayFiltros && $usuarios->total() > 0)
                                <div class="text-sm text-gray-500">
                                    Mostrando
                                    <span class="font-bold text-gray-700">{{ $usuarios->firstItem() }}</span>
                                    -
                                    <span class="font-bold text-gray-700">{{ $usuarios->lastItem() }}</span>
                                    de
                                    <span class="font-bold text-gray-700">{{ $usuarios->total() }}</span>
                                </div>
                            @endif
                        </div>

                        @if (! $hayFiltros)
                            <div class="border border-dashed border-gray-300 rounded-lg p-6 bg-gray-50">
                                <h5 class="text-base font-black text-gray-900">
                                    Búsqueda pendiente
                                </h5>
                                <p class="mt-1 text-sm text-gray-500 italic">
                                    Los resultados permanecerán vacíos hasta que localice un usuario por tipo, empresa o rol.
                                </p>
                            </div>
                        @elseif ($usuarios->isEmpty())
                            <div class="border border-dashed border-gray-300 rounded-lg p-6 bg-gray-50">
                                <h5 class="text-base font-black text-gray-900">
                                    Sin resultados
                                </h5>
                                <p class="mt-1 text-sm text-gray-500 italic">
                                    No hay usuarios que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($usuarios as $usuario)
                                    <article class="border border-gray-200 rounded-xl bg-white p-5 shadow-sm">
                                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">

                                            <div class="lg:col-span-4 min-w-0">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <h5 class="text-xl font-black text-gray-900 cc-cell-truncate">
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

                                                <div class="mt-1 text-sm text-gray-500 cc-cell-truncate">
                                                    {{ $usuario->email }}
                                                </div>
                                            </div>

                                            <div class="lg:col-span-2 min-w-0">
                                                <div class="text-xs font-black text-gray-500 uppercase tracking-wider">
                                                    Empresa
                                                </div>

                                                @if ($usuario->empresa)
                                                    <div class="mt-1 font-bold text-gray-900 cc-cell-truncate">
                                                        {{ $usuario->empresa->nombre_comercial ?: $usuario->empresa->nombre_legal }}
                                                    </div>
                                                @else
                                                    <div class="mt-1 font-bold text-gray-900">
                                                        Diesel Cop
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="lg:col-span-3 min-w-0">
                                                <div class="text-xs font-black text-gray-500 uppercase tracking-wider">
                                                    Rol
                                                </div>

                                                @if ($usuario->role)
                                                    <div class="mt-1 font-bold text-gray-900 cc-cell-truncate">
                                                        {{ $usuario->role->nombre }}
                                                    </div>
                                                @else
                                                    <div class="mt-1 text-sm text-gray-500 italic">
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
    </body>
</html>