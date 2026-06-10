<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>CC-Flota</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased">
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
                            Diesel Cop Admin
                        </div>
                    </div>
                </div>

                <nav class="cc-sidebar-nav">
                    <div class="cc-sidebar-section">
                        Administración
                    </div>

                    <a href="{{ route('empresas.index') }}"
                       class="cc-sidebar-link {{ request()->routeIs('empresas.*') ? 'cc-sidebar-link-active' : '' }}">
                        Empresas
                    </a>

                    <span class="cc-sidebar-link cc-sidebar-link-disabled">
                        Usuarios
                    </span>

                    <span class="cc-sidebar-link cc-sidebar-link-disabled">
                        Roles y permisos
                    </span>

                    <div class="cc-sidebar-section">
                        Operación
                    </div>

                    <span class="cc-sidebar-link cc-sidebar-link-disabled">
                        Unidades
                    </span>

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
                        <div class="cc-topbar-title">
                            Consola administrativa
                        </div>
                        <div class="cc-topbar-subtitle">
                            Gestión interna de Diesel Cop
                        </div>
                    </div>

                    <div class="cc-topbar-user">
                        <div class="cc-user-info">
                            <div class="cc-user-name">
                                {{ Auth::user()->name }}
                            </div>
                            <div class="cc-user-role">
                                Administrador
                            </div>
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