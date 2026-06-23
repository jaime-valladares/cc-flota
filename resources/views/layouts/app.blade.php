<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>CC-Flota</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .cc-sidebar-static-parent {
                cursor: default;
            }

            .cc-sidebar-static-parent:hover {
                transform: none;
            }

            .cc-sidebar-subnav {
                display: flex;
            }
        </style>
    </head>

    <body class="antialiased">
        @php
            $userName = Auth::user()->name ?? 'Usuario';
            $userEmail = Auth::user()->email ?? null;

            $initials = collect(explode(' ', trim($userName)))
                ->filter()
                ->map(fn ($part) => mb_substr($part, 0, 1))
                ->take(2)
                ->implode('');

            if ($initials === '') {
                $initials = 'U';
            }

            $empresasActivo = request()->routeIs('empresas.*');
            $usuariosActivo = request()->routeIs('usuarios.*');
            $unidadesActivo = request()->routeIs('unidades.*');
            $licenciasActivo = request()->routeIs('licencias.*');
            $marchamosActivo = request()->routeIs('marchamos.*');
        @endphp

        <div class="cc-admin-shell">

            <!-- Sidebar -->
            <aside class="cc-sidebar">
                <div class="cc-sidebar-brand">
                    <div class="cc-sidebar-logo">
                        CC
                    </div>

                    <div>
                        <div class="cc-sidebar-title">
                            CC-Flota
                        </div>
                        <div class="cc-sidebar-subtitle">
                            Diesel Cop Operations
                        </div>
                    </div>
                </div>

                <nav class="cc-sidebar-nav">
                    <div class="cc-sidebar-section">
                        Administración
                    </div>

                    <!-- Empresas -->
                    <div class="cc-sidebar-group">
                        <div class="cc-sidebar-parent cc-sidebar-static-parent {{ $empresasActivo ? 'cc-sidebar-parent-active' : '' }}">
                            <span>Empresas</span>
                        </div>

                        <div class="cc-sidebar-subnav">
                            <a href="{{ route('empresas.index') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('empresas.index') ? 'cc-sidebar-sublink-active' : '' }}">
                                Consulta empresas
                            </a>

                            <a href="{{ route('empresas.administrar') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('empresas.administrar') || request()->routeIs('empresas.show') || request()->routeIs('empresas.edit') ? 'cc-sidebar-sublink-active' : '' }}">
                                Administrar empresa
                            </a>

                            <a href="{{ route('empresas.create') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('empresas.create') ? 'cc-sidebar-sublink-active' : '' }}">
                                Nueva empresa
                            </a>
                        </div>
                    </div>

                    <!-- Usuarios -->
                    <div class="cc-sidebar-group">
                        <div class="cc-sidebar-parent cc-sidebar-static-parent {{ $usuariosActivo ? 'cc-sidebar-parent-active' : '' }}">
                            <span>Usuarios</span>
                        </div>

                        <div class="cc-sidebar-subnav">
                            <a href="{{ route('usuarios.index') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('usuarios.index') ? 'cc-sidebar-sublink-active' : '' }}">
                                Consulta usuarios
                            </a>

                            <a href="{{ route('usuarios.administrar') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('usuarios.administrar') || request()->routeIs('usuarios.show') || request()->routeIs('usuarios.edit') ? 'cc-sidebar-sublink-active' : '' }}">
                                Administrar usuario
                            </a>

                            <a href="{{ route('usuarios.create') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('usuarios.create') ? 'cc-sidebar-sublink-active' : '' }}">
                                Nuevo usuario
                            </a>
                        </div>
                    </div>

                    <div class="cc-sidebar-section">
                        Operación
                    </div>

                    <!-- Unidades -->
                    <div class="cc-sidebar-group">
                        <div class="cc-sidebar-parent cc-sidebar-static-parent {{ $unidadesActivo ? 'cc-sidebar-parent-active' : '' }}">
                            <span>Unidades</span>
                        </div>

                        <div class="cc-sidebar-subnav">
                            <a href="{{ route('unidades.index') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('unidades.index') ? 'cc-sidebar-sublink-active' : '' }}">
                                Consulta unidades
                            </a>

                            <a href="{{ route('unidades.administrar') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('unidades.administrar') || request()->routeIs('unidades.show') || request()->routeIs('unidades.edit') ? 'cc-sidebar-sublink-active' : '' }}">
                                Administrar unidad
                            </a>

                            <a href="{{ route('unidades.create') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('unidades.create') ? 'cc-sidebar-sublink-active' : '' }}">
                                Nueva unidad
                            </a>
                        </div>
                    </div>

                    <!-- Licencias -->
                    <div class="cc-sidebar-group">
                        <div class="cc-sidebar-parent cc-sidebar-static-parent {{ $licenciasActivo ? 'cc-sidebar-parent-active' : '' }}">
                            <span>Licencias</span>
                        </div>

                        <div class="cc-sidebar-subnav">
                            <a href="{{ route('licencias.index') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('licencias.index') ? 'cc-sidebar-sublink-active' : '' }}">
                                Consulta licencias
                            </a>

                            <a href="{{ route('licencias.administrar') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('licencias.administrar') || request()->routeIs('licencias.show') || request()->routeIs('licencias.edit') ? 'cc-sidebar-sublink-active' : '' }}">
                                Administrar licencia
                            </a>

                            <a href="{{ route('licencias.create') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('licencias.create') ? 'cc-sidebar-sublink-active' : '' }}">
                                Nueva licencia
                            </a>
                        </div>
                    </div>

                    <!-- Marchamos -->
                    <div class="cc-sidebar-group">
                        <div class="cc-sidebar-parent cc-sidebar-static-parent {{ $marchamosActivo ? 'cc-sidebar-parent-active' : '' }}">
                            <span>Marchamos</span>
                        </div>

                        <div class="cc-sidebar-subnav">
                            <a href="{{ route('marchamos.index') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('marchamos.index') || request()->routeIs('marchamos.detalle-unidad') ? 'cc-sidebar-sublink-active' : '' }}">
                                Consulta de marchamos
                            </a>

                            <a href="{{ route('marchamos.reemplazos.index') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('marchamos.reemplazos.*') ? 'cc-sidebar-sublink-active' : '' }}">
                                Administración de marchamos
                            </a>

                            <a href="{{ route('marchamos.asignacion-inicial.index') }}"
                               class="cc-sidebar-sublink {{ request()->routeIs('marchamos.asignacion-inicial.*') ? 'cc-sidebar-sublink-active' : '' }}">
                                Asignación inicial
                            </a>
                        </div>
                    </div>

                    <span class="cc-sidebar-link cc-sidebar-link-disabled">
                        Gasolineras
                    </span>

                    <span class="cc-sidebar-link cc-sidebar-link-disabled">
                        Tanques
                    </span>

                    <span class="cc-sidebar-link cc-sidebar-link-disabled">
                        Motoristas
                    </span>

                    <div class="cc-sidebar-section">
                        Transacciones
                    </div>

                    <span class="cc-sidebar-link cc-sidebar-link-disabled">
                        Abastecimientos
                    </span>

                    <span class="cc-sidebar-link cc-sidebar-link-disabled">
                        Reemplazos
                    </span>

                    <div class="cc-sidebar-section">
                        Control
                    </div>

                    <span class="cc-sidebar-link cc-sidebar-link-disabled">
                        Auditoría
                    </span>
                </nav>
            </aside>

            <!-- Main area -->
            <div class="cc-main-area">

                <!-- Topbar -->
                <header class="cc-topbar">
                    <div>
                        <div class="cc-topbar-kicker">
                            Plataforma operativa
                        </div>

                        <div class="cc-topbar-title">
                            Consola administrativa CC-Flota
                        </div>
                    </div>

                    <div class="cc-topbar-user">
                        <div class="cc-user-info">
                            <div class="cc-user-name">
                                {{ $userName }}
                            </div>

                            @if ($userEmail)
                                <div class="cc-user-role">
                                    {{ $userEmail }}
                                </div>
                            @endif
                        </div>

                        <div class="cc-user-avatar">
                            {{ strtoupper($initials) }}
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <button type="submit" class="cc-logout-button">
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </header>

                <!-- Optional page heading, kept for future compatibility -->
                @isset($header)
                    <section class="cc-page-heading">
                        {{ $header }}
                    </section>
                @endisset

                <!-- Page content -->
                <main class="cc-main-content">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>