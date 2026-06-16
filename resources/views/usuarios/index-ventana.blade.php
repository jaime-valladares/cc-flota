<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Consulta de usuarios | CC-Flota</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
            <div class="cc-page-wrapper">
                <div class="cc-window-container">
                    <div class="cc-card">

                        <div class="cc-card-header">
                            <div>
                                <h3 class="cc-title">
                                    Consulta de usuarios
                                </h3>
                                <p class="cc-subtitle">
                                    Consulte información general de los usuarios registrados en CC-Flota, incluyendo tipo de
                                    usuario, empresa asociada y rol asignado.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('usuarios.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="cc-metric-grid">
                            <div class="cc-metric-card">
                                <div class="cc-metric-label">
                                    Total usuarios
                                </div>
                                <div class="cc-metric-value">
                                    {{ $totalUsuarios }}
                                </div>
                            </div>

                            <div class="cc-metric-card">
                                <div class="cc-metric-label">
                                    Activos
                                </div>
                                <div class="cc-metric-value cc-metric-value-success">
                                    {{ $usuariosActivos }}
                                </div>
                            </div>

                            <div class="cc-metric-card">
                                <div class="cc-metric-label">
                                    Inactivos
                                </div>
                                <div class="cc-metric-value cc-metric-value-danger">
                                    {{ $usuariosInactivos }}
                                </div>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('usuarios.consulta.ventana') }}" class="mb-6">
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel">

                                <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
                                    <div class="cc-form-section-title">
                                        Filtros de consulta
                                    </div>
                                    <div class="cc-form-section-note">
                                        Utilice los filtros para localizar usuarios por tipo, empresa o rol asignado.
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
                                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                        <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                            La consulta permite visualizar usuarios, sin modificar información.
                                        </p>

                                        <div class="flex items-center gap-3">
                                            <button type="submit" class="cc-btn-primary">
                                                Buscar
                                            </button>

                                            <a href="{{ route('usuarios.consulta.ventana') }}" class="cc-btn-secondary">
                                                Resetear
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="cc-section-heading">
                            <div>
                                <h4 class="cc-section-title">
                                    Resultados
                                </h4>

                                <p class="cc-section-note">
                                    @if (! $hayFiltros)
                                        Seleccione tipo de usuario, empresa o rol para consultar usuarios.
                                    @elseif ($usuarios->total() === 0)
                                        No se encontraron usuarios con los criterios seleccionados.
                                    @elseif ($usuarios->total() === 1)
                                        Se encontró 1 usuario.
                                    @else
                                        Se encontraron {{ $usuarios->total() }} usuarios.
                                    @endif
                                </p>
                            </div>

                            @if ($hayFiltros && $usuarios->total() > 0)
                                <div class="text-sm text-[var(--cc-text-muted)]">
                                    Mostrando
                                    <span class="font-bold text-[var(--cc-text-main)]">{{ $usuarios->firstItem() }}</span>
                                    -
                                    <span class="font-bold text-[var(--cc-text-main)]">{{ $usuarios->lastItem() }}</span>
                                    de
                                    <span class="font-bold text-[var(--cc-text-main)]">{{ $usuarios->total() }}</span>
                                </div>
                            @endif
                        </div>

                        @if (! $hayFiltros)
                            <div class="cc-empty-panel">
                                <h5>
                                    Consulta pendiente
                                </h5>
                                <p>
                                    Los resultados permanecerán vacíos hasta que seleccione tipo de usuario, empresa o rol.
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
                            <div class="cc-table-wrapper">
                                <table class="cc-table">
                                    <colgroup>
                                        <col style="width: 28%;">
                                        <col style="width: 28%;">
                                        <col style="width: 28%;">
                                        <col style="width: 16%;">
                                    </colgroup>

                                    <thead>
                                        <tr>
                                            <th class="cc-text-left">Usuario</th>
                                            <th class="cc-text-left">Empresa</th>
                                            <th class="cc-text-left">Rol</th>
                                            <th class="cc-text-left">Tipo</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($usuarios as $usuario)
                                            <tr>
                                                <td class="cc-text-left">
                                                    <div class="font-bold text-[var(--cc-text-main)]">
                                                        {{ $usuario->name }} {{ $usuario->apellido }}
                                                    </div>
                                                    <div class="text-sm text-[var(--cc-text-muted)]">
                                                        {{ $usuario->email }}
                                                    </div>
                                                </td>

                                                <td class="cc-text-left">
                                                    @if ($usuario->empresa)
                                                        <div class="font-bold text-[var(--cc-text-main)] cc-cell-truncate">
                                                            {{ $usuario->empresa->nombre_comercial ?: $usuario->empresa->nombre_legal }}
                                                        </div>
                                                        <div class="text-sm text-[var(--cc-text-muted)]">
                                                            {{ $usuario->empresa->nit }}
                                                        </div>
                                                    @else
                                                        <div class="font-bold text-[var(--cc-text-main)]">
                                                            Diesel Cop
                                                        </div>
                                                    @endif
                                                </td>

                                                <td class="cc-text-left">
                                                    @if ($usuario->role)
                                                        <div class="font-bold text-[var(--cc-text-main)]">
                                                            {{ $usuario->role->nombre }}
                                                        </div>
                                                        <div class="text-sm text-[var(--cc-text-muted)]">
                                                            {{ $usuario->role->codigo }}
                                                        </div>
                                                    @else
                                                        <span class="text-sm text-[var(--cc-text-muted)]">
                                                            Sin rol
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="cc-text-left">
                                                    @if ($usuario->tipo_usuario === 'diesel_cop')
                                                        <span class="cc-badge cc-badge-info">
                                                            Diesel Cop
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-active">
                                                            Empresa
                                                        </span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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