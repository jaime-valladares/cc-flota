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
            .cc-sidebar-subnav.is-collapsed {
                display: none;
            }

            .cc-sidebar-chevron {
                transition: transform 180ms ease;
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

                    <div class="cc-sidebar-group">
                        <button type="button"
                                class="cc-sidebar-parent {{ $empresasActivo ? 'cc-sidebar-parent-active' : '' }}"
                                data-sidebar-toggle="empresas"
                                aria-expanded="{{ $empresasActivo ? 'true' : 'false' }}">
                            <span>Empresas</span>
                            <span class="cc-sidebar-chevron">▾</span>
                        </button>

                        <div class="cc-sidebar-subnav {{ $empresasActivo ? '' : 'is-collapsed' }}"
                             data-sidebar-panel="empresas">
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

                    <div class="cc-sidebar-group">
                        <button type="button"
                                class="cc-sidebar-parent {{ $usuariosActivo ? 'cc-sidebar-parent-active' : '' }}"
                                data-sidebar-toggle="usuarios"
                                aria-expanded="{{ $usuariosActivo ? 'true' : 'false' }}">
                            <span>Usuarios</span>
                            <span class="cc-sidebar-chevron">▾</span>
                        </button>

                        <div class="cc-sidebar-subnav {{ $usuariosActivo ? '' : 'is-collapsed' }}"
                             data-sidebar-panel="usuarios">
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

                    <span class="cc-sidebar-link cc-sidebar-link-disabled">
                        Roles y permisos
                    </span>

                    <div class="cc-sidebar-section">
                        Operación
                    </div>

                    <div class="cc-sidebar-group">
                        <button type="button"
                                class="cc-sidebar-parent {{ $unidadesActivo ? 'cc-sidebar-parent-active' : '' }}"
                                data-sidebar-toggle="unidades"
                                aria-expanded="{{ $unidadesActivo ? 'true' : 'false' }}">
                            <span>Unidades</span>
                            <span class="cc-sidebar-chevron">▾</span>
                        </button>

                        <div class="cc-sidebar-subnav {{ $unidadesActivo ? '' : 'is-collapsed' }}"
                             data-sidebar-panel="unidades">
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

                    <div class="cc-sidebar-group">
                        <button type="button"
                                class="cc-sidebar-parent {{ $licenciasActivo ? 'cc-sidebar-parent-active' : '' }}"
                                data-sidebar-toggle="licencias"
                                aria-expanded="{{ $licenciasActivo ? 'true' : 'false' }}">
                            <span>Licencias</span>
                            <span class="cc-sidebar-chevron">▾</span>
                        </button>

                        <div class="cc-sidebar-subnav {{ $licenciasActivo ? '' : 'is-collapsed' }}"
                             data-sidebar-panel="licencias">
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

                    <span class="cc-sidebar-link cc-sidebar-link-disabled">
                        Marchamos
                    </span>

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

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
                    const chevron = button.querySelector('.cc-sidebar-chevron');

                    if (button.getAttribute('aria-expanded') === 'true' && chevron) {
                        chevron.style.transform = 'rotate(180deg)';
                    }

                    button.addEventListener('click', function () {
                        const key = this.dataset.sidebarToggle;
                        const panel = document.querySelector(`[data-sidebar-panel="${key}"]`);
                        const icon = this.querySelector('.cc-sidebar-chevron');

                        if (!panel) {
                            return;
                        }

                        const isOpen = !panel.classList.contains('is-collapsed');

                        if (isOpen) {
                            panel.classList.add('is-collapsed');
                            this.setAttribute('aria-expanded', 'false');

                            if (icon) {
                                icon.style.transform = 'rotate(0deg)';
                            }
                        } else {
                            panel.classList.remove('is-collapsed');
                            this.setAttribute('aria-expanded', 'true');

                            if (icon) {
                                icon.style.transform = 'rotate(180deg)';
                            }
                        }
                    });
                });
            });
        </script>
    </body>
</html>