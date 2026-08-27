<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>CC-Flota</title>

        @include('layouts.partials.favicon')

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

            /* Menú lateral colapsable: comportamiento visual para todos los módulos */
            .cc-sidebar-collapsible-parent {
                cursor: pointer;
                user-select: none;
            }

            .cc-sidebar-collapsible-parent:hover {
                transform: none;
            }

            .cc-sidebar-collapsible-parent .cc-sidebar-collapse-icon {
                margin-left: auto;
                font-size: 0.8rem;
                line-height: 1;
                transition: transform 0.18s ease;
            }

            .cc-sidebar-collapsible-parent[aria-expanded="false"] .cc-sidebar-collapse-icon {
                transform: rotate(-90deg);
            }

            .cc-sidebar-subnav-collapsed {
                display: none;
            }

            /* Control visual del menú lateral completo */
            .cc-sidebar-menu-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2.75rem;
                height: 2.75rem;
                flex: 0 0 auto;
                border: 1px solid rgba(148, 163, 184, 0.35);
                border-radius: 0.8rem;
                background: rgba(255, 255, 255, 0.9);
                color: inherit;
                cursor: pointer;
                transition: background-color 0.18s ease, border-color 0.18s ease, transform 0.18s ease;
            }

            .cc-sidebar-menu-toggle:hover {
                background: #ffffff;
                border-color: rgba(100, 116, 139, 0.55);
            }

            .cc-sidebar-menu-toggle:active {
                transform: scale(0.97);
            }

            .cc-sidebar-menu-toggle svg {
                width: 1.35rem;
                height: 1.35rem;
            }

            .cc-sidebar-controls {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                flex: 0 0 auto;
            }

            .cc-sidebar-reset-button {
                width: 2.5rem;
                height: 2.5rem;
            }

            .cc-topbar-left {
                display: flex;
                align-items: center;
                gap: 0.9rem;
                min-width: 0;
            }

            .cc-sidebar-overlay {
                display: none;
            }

            @media (min-width: 1024px) {
                /*
                 * Escritorio: al ocultar el sidebar se elimina también su columna.
                 * Esto evita que el área principal quede atrapada en el ancho que
                 * anteriormente correspondía al menú lateral.
                 */
                body.cc-sidebar-desktop-hidden #ccSidebar {
                    display: none !important;
                }

                body.cc-sidebar-desktop-hidden .cc-admin-shell {
                    display: grid !important;
                    grid-template-columns: minmax(0, 1fr) !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    min-width: 0 !important;
                }

                body.cc-sidebar-desktop-hidden .cc-main-area {
                    grid-column: 1 / -1 !important;
                    width: 100% !important;
                    max-width: none !important;
                    min-width: 0 !important;
                    margin-left: 0 !important;
                }

                body.cc-sidebar-desktop-hidden .cc-topbar,
                body.cc-sidebar-desktop-hidden .cc-main-content,
                body.cc-sidebar-desktop-hidden .cc-page-heading {
                    width: 100% !important;
                    max-width: 100% !important;
                    min-width: 0 !important;
                    box-sizing: border-box !important;
                }

                body.cc-sidebar-desktop-hidden .cc-main-content .cc-content,
                body.cc-sidebar-desktop-hidden .cc-main-content .cc-form,
                body.cc-sidebar-desktop-hidden .cc-main-content .cc-window {
                    width: 100% !important;
                    min-width: 0 !important;
                    margin-left: auto !important;
                    margin-right: auto !important;
                    box-sizing: border-box !important;
                }
            }

            @media (max-width: 1023px) {
                #ccSidebar {
                    position: fixed !important;
                    top: 0 !important;
                    bottom: 0 !important;
                    left: 0 !important;
                    z-index: 70 !important;
                    width: min(86vw, 20rem) !important;
                    max-width: 20rem !important;
                    height: 100vh !important;
                    transform: translateX(-105%);
                    transition: transform 0.22s ease;
                    box-shadow: 0 1.5rem 3rem rgba(15, 23, 42, 0.24);
                }

                body.cc-sidebar-mobile-open #ccSidebar {
                    transform: translateX(0);
                }

                .cc-main-area {
                    width: 100% !important;
                    max-width: none !important;
                    margin-left: 0 !important;
                }

                .cc-sidebar-overlay {
                    position: fixed;
                    inset: 0;
                    z-index: 60;
                    border: 0;
                    background: rgba(15, 23, 42, 0.48);
                    cursor: pointer;
                }

                body.cc-sidebar-mobile-open .cc-sidebar-overlay {
                    display: block;
                }

                body.cc-sidebar-mobile-open {
                    overflow: hidden;
                }

                /* La barra lateral flota sobre el contenido; no reduce el ancho de la página. */
                .cc-admin-shell {
                    display: block !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    min-width: 0 !important;
                }

                .cc-main-area {
                    min-width: 0 !important;
                    overflow-x: hidden !important;
                }

                /* Topbar adaptable para tablet y teléfono. */
                .cc-topbar {
                    display: flex !important;
                    align-items: center !important;
                    justify-content: space-between !important;
                    flex-wrap: wrap !important;
                    gap: 0.85rem !important;
                    width: 100% !important;
                    min-width: 0 !important;
                    padding: 0.9rem 1rem !important;
                    box-sizing: border-box !important;
                }

                .cc-topbar-left {
                    flex: 1 1 18rem !important;
                    min-width: 0 !important;
                }

                .cc-topbar-left > div:last-child {
                    min-width: 0 !important;
                }

                .cc-topbar-kicker,
                .cc-topbar-title {
                    max-width: 100% !important;
                    white-space: normal !important;
                    overflow-wrap: anywhere !important;
                }

                .cc-topbar-user {
                    display: flex !important;
                    align-items: center !important;
                    justify-content: flex-end !important;
                    flex: 0 1 auto !important;
                    min-width: 0 !important;
                    gap: 0.65rem !important;
                    flex-wrap: wrap !important;
                }

                .cc-user-info {
                    min-width: 0 !important;
                    max-width: min(19rem, 52vw) !important;
                }

                .cc-user-name,
                .cc-user-role {
                    white-space: normal !important;
                    overflow-wrap: anywhere !important;
                }

                .cc-logout-button {
                    white-space: nowrap !important;
                }

                /* El contenido y las ventanas dejan de conservar anchos de escritorio. */
                .cc-main-content,
                .cc-page-heading {
                    width: 100% !important;
                    max-width: 100% !important;
                    min-width: 0 !important;
                    box-sizing: border-box !important;
                    padding-left: 1rem !important;
                    padding-right: 1rem !important;
                    overflow-x: hidden !important;
                }

                .cc-main-content .cc-content,
                .cc-main-content .cc-form,
                .cc-main-content .cc-window {
                    width: 100% !important;
                    max-width: 100% !important;
                    min-width: 0 !important;
                    margin-left: auto !important;
                    margin-right: auto !important;
                    box-sizing: border-box !important;
                }

                .cc-main-content img,
                .cc-main-content svg,
                .cc-main-content video,
                .cc-main-content canvas {
                    max-width: 100%;
                }

                .cc-main-content input,
                .cc-main-content select,
                .cc-main-content textarea {
                    max-width: 100% !important;
                    min-width: 0 !important;
                    box-sizing: border-box !important;
                }

                .cc-main-content table {
                    max-width: 100%;
                }
            }

            @media (max-width: 639px) {
                .cc-topbar {
                    align-items: stretch !important;
                    padding: 0.75rem !important;
                }

                .cc-topbar-left {
                    flex: 1 1 100% !important;
                    gap: 0.7rem !important;
                }

                .cc-topbar-kicker {
                    font-size: 0.68rem !important;
                    line-height: 1.25 !important;
                }

                .cc-topbar-title {
                    font-size: 1rem !important;
                    line-height: 1.25 !important;
                }

                .cc-topbar-user {
                    width: 100% !important;
                    justify-content: space-between !important;
                    flex-wrap: nowrap !important;
                }

                .cc-user-info {
                    flex: 1 1 auto !important;
                    max-width: none !important;
                }

                .cc-user-avatar {
                    flex: 0 0 auto !important;
                }

                .cc-logout-button {
                    padding: 0.65rem 0.8rem !important;
                    font-size: 0.72rem !important;
                }

                .cc-main-content,
                .cc-page-heading {
                    padding-left: 0.75rem !important;
                    padding-right: 0.75rem !important;
                }

                /* Formularios en una sola columna en teléfono. */
                .cc-main-content .cc-form-grid,
                .cc-main-content .cc-grid-2,
                .cc-main-content .cc-grid-3,
                .cc-main-content .cc-grid-4 {
                    grid-template-columns: minmax(0, 1fr) !important;
                }

                .cc-main-content .cc-actions,
                .cc-main-content .cc-form-actions {
                    display: flex !important;
                    flex-wrap: wrap !important;
                    gap: 0.65rem !important;
                }

                .cc-main-content .cc-actions > *,
                .cc-main-content .cc-form-actions > * {
                    max-width: 100% !important;
                }
            }
        </style>
    </head>

    <body class="antialiased">
        @php
            $usuarioAutenticado = Auth::user();
            $nombre = trim((string) ($usuarioAutenticado->name ?? ''));
            $apellido = trim((string) ($usuarioAutenticado->apellido ?? ''));
            $userName = trim($nombre.' '.$apellido);
            $userEmail = $usuarioAutenticado->email ?? null;

            if ($userName === '') {
                $userName = 'Usuario';
            }

            $initials = '';

            if ($nombre !== '') {
                $initials .= mb_substr($nombre, 0, 1);
            }

            if ($apellido !== '') {
                $initials .= mb_substr($apellido, 0, 1);
            }

            if ($initials === '') {
                $initials = 'U';
            }

            $empresasActivo = request()->routeIs('empresas.*');
            $usuariosActivo = request()->routeIs('usuarios.*');
            $licenciasActivo = request()->routeIs('licencias.*');
            $unidadesActivo = request()->routeIs('unidades.*');
            $marchamosActivo = request()->routeIs('marchamos.*');
            $gasolinerasActivo = request()->routeIs('gasolineras.*');
            $gasolinerasExternasActivo = request()->routeIs('gasolineras-externas.*');
            $puntosRutaActivo = request()->routeIs('puntos-ruta.*');
            $rutasActivo = request()->routeIs('rutas.*');
            $motoristasActivo = request()->routeIs('motoristas.*');
            $abastecimientosActivo = request()->routeIs('abastecimientos.*');
            $auditoriaActivo = request()->routeIs(
                'analisis.panel-operativo'
            )
                || request()->routeIs(
                    'auditoria.abastecimientos.*'
                )
                || request()->routeIs(
                    'auditoria.marchamos.*'
                );

            $analisisActivo = request()->routeIs(
                'analisis.rendimientos.*'
            )
                || request()->routeIs(
                    'analisis.consumo-unidades.*'
                )
                || request()->routeIs(
                    'analisis.rutas.*'
                );
        @endphp

        <div class="cc-admin-shell">

            <!-- Sidebar -->
            <aside id="ccSidebar" class="cc-sidebar">
                <div class="cc-sidebar-brand">
                    <div class="cc-sidebar-logo cc-sidebar-logo-image">
                        <img
                            src="{{ asset('images/cc-flota/favicon.png') }}"
                            alt="CC-Flota"
                            class="cc-sidebar-logo-img"
                        >
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
                    @if ($usuarioAutenticado->tieneAlgunPermiso([
                        'empresas.consultar',
                        'empresas.administrar',
                        'empresas.crear',
                    ]))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccEmpresasToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $empresasActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccEmpresasSubnav"
                            >
                                <span>Empresas</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccEmpresasSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                @if ($usuarioAutenticado->tienePermiso('empresas.consultar'))
                                    <a href="{{ route('empresas.index') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('empresas.index') || request()->routeIs('empresas.show') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Consulta empresas
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('empresas.administrar'))
                                    <a href="{{ route('empresas.administrar') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('empresas.administrar') || request()->routeIs('empresas.show') || request()->routeIs('empresas.edit') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Administrar empresa
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('empresas.crear'))
                                    <a href="{{ route('empresas.create') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('empresas.create') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Nueva empresa
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Usuarios -->
                    @if ($usuarioAutenticado->tieneAlgunPermiso([
                        'usuarios.consultar',
                        'usuarios.administrar',
                        'usuarios.crear',
                    ]))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccUsuariosToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $usuariosActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccUsuariosSubnav"
                            >
                                <span>Usuarios</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccUsuariosSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                @if ($usuarioAutenticado->tienePermiso('usuarios.consultar'))
                                    <a href="{{ route('usuarios.index') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('usuarios.index') || request()->routeIs('usuarios.show') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Consulta usuarios
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('usuarios.administrar'))
                                    <a href="{{ route('usuarios.administrar') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('usuarios.administrar') || request()->routeIs('usuarios.show') || request()->routeIs('usuarios.edit') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Administrar usuario
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('usuarios.crear'))
                                    <a href="{{ route('usuarios.create') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('usuarios.create') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Nuevo usuario
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="cc-sidebar-section">
                        Configuración operativa
                    </div>

                    <!-- Unidades -->
                    @if ($usuarioAutenticado->tieneAlgunPermiso([
                        'unidades.consultar',
                        'unidades.administrar',
                        'unidades.crear',
                    ]))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccUnidadesToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $unidadesActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccUnidadesSubnav"
                            >
                                <span>Unidades</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccUnidadesSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                @if ($usuarioAutenticado->tienePermiso('unidades.consultar'))
                                    <a href="{{ route('unidades.index') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('unidades.index') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Consulta unidades
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('unidades.administrar'))
                                    <a href="{{ route('unidades.administrar') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('unidades.administrar') || request()->routeIs('unidades.show') || request()->routeIs('unidades.edit') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Administrar unidad
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('unidades.crear'))
                                    <a href="{{ route('unidades.create') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('unidades.create') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Nueva unidad
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Licencias -->
                    @if ($usuarioAutenticado->tieneAlgunPermiso([
                        'licencias.consultar',
                        'licencias.administrar',
                        'licencias.crear',
                    ]))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccLicenciasToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $licenciasActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccLicenciasSubnav"
                            >
                                <span>Licencias</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccLicenciasSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                @if ($usuarioAutenticado->tienePermiso('licencias.consultar'))
                                    <a href="{{ route('licencias.index') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('licencias.index') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Consulta licencias
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('licencias.administrar'))
                                    <a href="{{ route('licencias.administrar') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('licencias.administrar') || request()->routeIs('licencias.show') || request()->routeIs('licencias.edit') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Administrar licencia
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('licencias.crear'))
                                    <a href="{{ route('licencias.create') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('licencias.create') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Nueva licencia
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Marchamos -->
                    @if ($usuarioAutenticado->tieneAlgunPermiso([
                        'marchamos.consultar',
                        'marchamos.administrar',
                        'marchamos.asignar_inicial',
                    ]))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccMarchamosToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $marchamosActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccMarchamosSubnav"
                            >
                                <span>Marchamos</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccMarchamosSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                @if ($usuarioAutenticado->tienePermiso('marchamos.consultar'))
                                    <a href="{{ route('marchamos.index') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('marchamos.index') || request()->routeIs('marchamos.detalle-unidad') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Consulta de marchamos
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('marchamos.administrar'))
                                    <a href="{{ route('marchamos.reemplazos.index') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('marchamos.reemplazos.*') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Administración de marchamos
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('marchamos.asignar_inicial'))
                                    <a href="{{ route('marchamos.asignacion-inicial.index') }}"
                                       class="cc-sidebar-sublink {{ request()->routeIs('marchamos.asignacion-inicial.*') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Asignación inicial
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="cc-sidebar-section">
                        Operación cliente
                    </div>

                    <!-- Gasolineras internas -->
                    @if ($usuarioAutenticado->tieneAlgunPermiso([
                        'gasolineras.consultar',
                        'gasolineras.administrar',
                        'gasolineras.crear',
                        'tanques.administrar',
                        'recargas_tanques.registrar',
                    ]))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccGasolinerasToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $gasolinerasActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccGasolinerasSubnav"
                            >
                                <span>Gasolineras Internas</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccGasolinerasSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                @if ($usuarioAutenticado->tienePermiso('gasolineras.consultar'))
                                    <a
                                        href="{{ route('gasolineras.index') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('gasolineras.index') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Consulta gasolineras
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('gasolineras.administrar'))
                                    <a
                                        href="{{ route('gasolineras.administrar') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('gasolineras.administrar') || request()->routeIs('gasolineras.show') || request()->routeIs('gasolineras.edit') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Administrar gasolineras
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('tanques.administrar'))
                                    <a
                                        href="{{ route('gasolineras.tanques.index') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('gasolineras.tanques.index') || request()->routeIs('gasolineras.tanques.show') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Gestión de tanques
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('recargas_tanques.registrar'))
                                    <a
                                        href="{{ route('gasolineras.tanques.recargas.index') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('gasolineras.tanques.recargas.*') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Recarga de tanques
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('gasolineras.crear'))
                                    <a
                                        href="{{ route('gasolineras.create') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('gasolineras.create') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Nueva gasolinera
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Gasolineras externas -->
                    @if ($usuarioAutenticado->tieneAlgunPermiso([
                        'gasolineras_externas.consultar',
                        'gasolineras_externas.administrar',
                        'gasolineras_externas.crear',
                    ]))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccGasolinerasExternasToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $gasolinerasExternasActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccGasolinerasExternasSubnav"
                            >
                                <span>Gasolineras Externas</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccGasolinerasExternasSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                @if ($usuarioAutenticado->tienePermiso('gasolineras_externas.consultar'))
                                    <a
                                        href="{{ route('gasolineras-externas.index') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('gasolineras-externas.index') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Consulta gasolineras
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('gasolineras_externas.administrar'))
                                    <a
                                        href="{{ route('gasolineras-externas.administrar') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('gasolineras-externas.administrar') || request()->routeIs('gasolineras-externas.show') || request()->routeIs('gasolineras-externas.edit') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Administrar gasolineras
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('gasolineras_externas.crear'))
                                    <a
                                        href="{{ route('gasolineras-externas.create') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('gasolineras-externas.create') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Nueva gasolinera
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Puntos de ruta -->
                    @if ($usuarioAutenticado->tieneAlgunPermiso([
                        'puntos_ruta.consultar',
                        'puntos_ruta.administrar',
                        'puntos_ruta.crear',
                    ]))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccPuntosRutaToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $puntosRutaActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccPuntosRutaSubnav"
                            >
                                <span>Puntos de Ruta</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccPuntosRutaSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                @if ($usuarioAutenticado->tienePermiso('puntos_ruta.consultar'))
                                    <a
                                        href="{{ route('puntos-ruta.index') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('puntos-ruta.index') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Consulta puntos
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('puntos_ruta.administrar'))
                                    <a
                                        href="{{ route('puntos-ruta.administrar') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('puntos-ruta.administrar') || request()->routeIs('puntos-ruta.show') || request()->routeIs('puntos-ruta.edit') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Administrar puntos
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('puntos_ruta.crear'))
                                    <a
                                        href="{{ route('puntos-ruta.create') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('puntos-ruta.create') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Nuevo punto
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Rutas -->
                    @if ($usuarioAutenticado->tieneAlgunPermiso([
                        'rutas.consultar',
                        'rutas.administrar',
                        'rutas.crear',
                    ]))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccRutasToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $rutasActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccRutasSubnav"
                            >
                                <span>Rutas</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccRutasSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                @if ($usuarioAutenticado->tienePermiso('rutas.consultar'))
                                    <a
                                        href="{{ route('rutas.index') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('rutas.index') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Consulta rutas
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('rutas.administrar'))
                                    <a
                                        href="{{ route('rutas.administrar') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('rutas.administrar') || request()->routeIs('rutas.show') || request()->routeIs('rutas.edit') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Administrar rutas
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('rutas.crear'))
                                    <a
                                        href="{{ route('rutas.create') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('rutas.create') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Nueva ruta
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Motoristas -->
                    @if ($usuarioAutenticado->tieneAlgunPermiso([
                        'motoristas.consultar',
                        'motoristas.administrar',
                        'motoristas.crear',
                    ]))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccMotoristasToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $motoristasActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccMotoristasSubnav"
                            >
                                <span>Motoristas</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccMotoristasSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                @if ($usuarioAutenticado->tienePermiso('motoristas.consultar'))
                                    <a
                                        href="{{ route('motoristas.index') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('motoristas.index') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Consulta motoristas
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('motoristas.administrar'))
                                    <a
                                        href="{{ route('motoristas.administrar') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('motoristas.administrar') || request()->routeIs('motoristas.show') || request()->routeIs('motoristas.edit') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Administrar motoristas
                                    </a>
                                @endif

                                @if ($usuarioAutenticado->tienePermiso('motoristas.crear'))
                                    <a
                                        href="{{ route('motoristas.create') }}"
                                        class="cc-sidebar-sublink {{ request()->routeIs('motoristas.create') ? 'cc-sidebar-sublink-active' : '' }}"
                                    >
                                        Nuevo motorista
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- Abastecimientos -->
                    @if ($usuarioAutenticado->tieneAlgunPermiso([
                        'abastecimientos.consultar',
                        'abastecimientos.registrar',
                    ]))
                        <div class="cc-sidebar-group">
                            <div id="ccAbastecimientosToggle" class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $abastecimientosActivo ? 'cc-sidebar-parent-active' : '' }}" role="button" tabindex="0" aria-expanded="false" aria-controls="ccAbastecimientosSubnav">
                                <span>Abastecimientos</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>
                            <div id="ccAbastecimientosSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                @if ($usuarioAutenticado->tienePermiso('abastecimientos.consultar'))
                                    <a href="{{ route('abastecimientos.consulta') }}" class="cc-sidebar-sublink {{ request()->routeIs('abastecimientos.consulta*') || request()->routeIs('abastecimientos.show*') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Consultar abastecimientos
                                    </a>
                                @endif
                                @if ($usuarioAutenticado->tienePermiso('abastecimientos.consultar'))
                                    <a href="{{ route('abastecimientos.ciclos.index') }}" class="cc-sidebar-sublink {{ request()->routeIs('abastecimientos.ciclos.*') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Consultar ciclos
                                    </a>
                                @endif
                                @if ($usuarioAutenticado->tienePermiso('abastecimientos.registrar'))
                                    <a href="{{ route('abastecimientos.index') }}" class="cc-sidebar-sublink {{ request()->routeIs('abastecimientos.index*') || request()->routeIs('abastecimientos.create*') ? 'cc-sidebar-sublink-active' : '' }}">
                                        Registrar abastecimiento
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif

                    <div class="cc-sidebar-section">
                        Control
                    </div>

                    <!-- Auditoría -->
                    @if ($usuarioAutenticado->tienePermiso('auditoria.consultar'))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccAuditoriaToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $auditoriaActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccAuditoriaSubnav"
                            >
                                <span>Auditoría</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccAuditoriaSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                <a href="{{ route('analisis.panel-operativo') }}"
                                   class="cc-sidebar-sublink {{ request()->routeIs('analisis.panel-operativo*') ? 'cc-sidebar-sublink-active' : '' }}">
                                    Control operativo de flota
                                </a>

                                <a href="{{ route('auditoria.abastecimientos.index') }}"
                                   class="cc-sidebar-sublink {{ request()->routeIs('auditoria.abastecimientos.*') ? 'cc-sidebar-sublink-active' : '' }}">
                                    Auditoría de Abastecimientos
                                </a>

                                <a href="{{ route('auditoria.marchamos.index') }}"
                                   class="cc-sidebar-sublink {{ request()->routeIs('auditoria.marchamos.*') ? 'cc-sidebar-sublink-active' : '' }}">
                                    Auditoría de Marchamos
                                </a>
                            </div>
                        </div>
                    @endif

                    <!-- Análisis -->
                    @if ($usuarioAutenticado->tienePermiso('analisis.consultar'))
                        <div class="cc-sidebar-group">
                            <div
                                id="ccAnalisisToggle"
                                class="cc-sidebar-parent cc-sidebar-collapsible-parent {{ $analisisActivo ? 'cc-sidebar-parent-active' : '' }}"
                                role="button"
                                tabindex="0"
                                aria-expanded="false"
                                aria-controls="ccAnalisisSubnav"
                            >
                                <span>Análisis</span>
                                <span class="cc-sidebar-collapse-icon" aria-hidden="true">▼</span>
                            </div>

                            <div id="ccAnalisisSubnav" class="cc-sidebar-subnav cc-sidebar-subnav-collapsed">
                                <a href="{{ route('analisis.rendimientos.index') }}"
                                   class="cc-sidebar-sublink {{ request()->routeIs('analisis.rendimientos.*') ? 'cc-sidebar-sublink-active' : '' }}">
                                    Análisis de Kilometraje
                                </a>

                                <a href="{{ route('analisis.consumo-unidades.index') }}"
                                   class="cc-sidebar-sublink {{ request()->routeIs('analisis.consumo-unidades.*') ? 'cc-sidebar-sublink-active' : '' }}">
                                    Consumo por Unidad
                                </a>

                                <a href="{{ route('analisis.rutas.index') }}"
                                   class="cc-sidebar-sublink {{ request()->routeIs('analisis.rutas.*') ? 'cc-sidebar-sublink-active' : '' }}">
                                    Análisis de Rutas
                                </a>
                            </div>
                        </div>
                    @endif
                </nav>
            </aside>

            <button
                id="ccSidebarOverlay"
                type="button"
                class="cc-sidebar-overlay"
                aria-label="Cerrar menú lateral"
                tabindex="-1"
            ></button>

            <!-- Main area -->
            <div class="cc-main-area">

                <!-- Topbar -->
                <header class="cc-topbar">
                    <div class="cc-topbar-left">
                        <div class="cc-sidebar-controls">
                            <button
                                id="ccSidebarMenuToggle"
                                type="button"
                                class="cc-sidebar-menu-toggle"
                                aria-label="Mostrar u ocultar menú lateral"
                                aria-controls="ccSidebar"
                                aria-expanded="true"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                                    <path d="M4 6h16"></path>
                                    <path d="M4 12h16"></path>
                                    <path d="M4 18h16"></path>
                                </svg>
                            </button>

                            <button
                                id="ccSidebarReset"
                                type="button"
                                class="cc-sidebar-menu-toggle cc-sidebar-reset-button"
                                aria-label="Restablecer menú"
                                title="Restablecer menú"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 12a9 9 0 1 0 3-6.7"></path>
                                    <path d="M3 4v6h6"></path>
                                </svg>
                            </button>
                        </div>

                        <div>
                            <div class="cc-topbar-kicker">
                                Plataforma operativa
                            </div>

                            <div class="cc-topbar-title">
                                Consola administrativa CC-Flota
                            </div>
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
                            {{ mb_strtoupper($initials) }}
                        </div>

                        <form id="ccLogoutForm" method="POST" action="{{ route('logout') }}">
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
                const sidebar = document.getElementById('ccSidebar');

                if (!sidebar) {
                    return;
                }

                const savedScroll = localStorage.getItem('ccSidebarScrollTop');

                if (savedScroll !== null) {
                    sidebar.scrollTop = parseInt(savedScroll, 10);
                }

                sidebar.addEventListener('scroll', function () {
                    localStorage.setItem('ccSidebarScrollTop', sidebar.scrollTop);
                });

                /*
                 * Botón hamburguesa: únicamente muestra u oculta el menú lateral.
                 * En escritorio el menú inicia visible; en tablet/teléfono inicia oculto.
                 */
                const sidebarMenuToggle = document.getElementById('ccSidebarMenuToggle');
                const sidebarReset = document.getElementById('ccSidebarReset');
                const sidebarOverlay = document.getElementById('ccSidebarOverlay');
                const desktopMediaQuery = window.matchMedia('(min-width: 1024px)');

                const actualizarEstadoHamburguesa = function () {
                    if (!sidebarMenuToggle) {
                        return;
                    }

                    const abierto = desktopMediaQuery.matches
                        ? !document.body.classList.contains('cc-sidebar-desktop-hidden')
                        : document.body.classList.contains('cc-sidebar-mobile-open');

                    sidebarMenuToggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
                    sidebarMenuToggle.setAttribute(
                        'aria-label',
                        abierto ? 'Ocultar menú lateral' : 'Mostrar menú lateral'
                    );
                };

                const cerrarMenuMovil = function () {
                    document.body.classList.remove('cc-sidebar-mobile-open');
                    actualizarEstadoHamburguesa();
                };

                const alternarMenuLateral = function () {
                    if (desktopMediaQuery.matches) {
                        document.body.classList.toggle('cc-sidebar-desktop-hidden');
                    } else {
                        document.body.classList.toggle('cc-sidebar-mobile-open');
                    }

                    actualizarEstadoHamburguesa();
                };

                if (sidebarMenuToggle) {
                    sidebarMenuToggle.addEventListener('click', alternarMenuLateral);
                }

                if (sidebarOverlay) {
                    sidebarOverlay.addEventListener('click', cerrarMenuMovil);
                }

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && document.body.classList.contains('cc-sidebar-mobile-open')) {
                        cerrarMenuMovil();
                        sidebarMenuToggle?.focus();
                    }
                });

                sidebar.querySelectorAll('a').forEach(function (link) {
                    link.addEventListener('click', function () {
                        if (!desktopMediaQuery.matches) {
                            cerrarMenuMovil();
                        }
                    });
                });

                const manejarCambioDeVista = function () {
                    document.body.classList.remove('cc-sidebar-mobile-open');
                    document.body.classList.remove('cc-sidebar-desktop-hidden');
                    actualizarEstadoHamburguesa();
                };

                if (typeof desktopMediaQuery.addEventListener === 'function') {
                    desktopMediaQuery.addEventListener('change', manejarCambioDeVista);
                } else if (typeof desktopMediaQuery.addListener === 'function') {
                    desktopMediaQuery.addListener(manejarCambioDeVista);
                }

                actualizarEstadoHamburguesa();

                /*
                 * Estado exclusivamente visual del menú lateral.
                 * sessionStorage conserva los módulos abiertos mientras esta pestaña
                 * permanezca activa y se limpia al cerrar sesión.
                 */
                const sidebarStateKey = 'ccSidebarExpandedGroups';
                const gruposColapsables = [
                    ['empresas', 'ccEmpresasToggle', 'ccEmpresasSubnav'],
                    ['usuarios', 'ccUsuariosToggle', 'ccUsuariosSubnav'],
                    ['unidades', 'ccUnidadesToggle', 'ccUnidadesSubnav'],
                    ['licencias', 'ccLicenciasToggle', 'ccLicenciasSubnav'],
                    ['marchamos', 'ccMarchamosToggle', 'ccMarchamosSubnav'],
                    ['gasolineras', 'ccGasolinerasToggle', 'ccGasolinerasSubnav'],
                    ['gasolineras_externas', 'ccGasolinerasExternasToggle', 'ccGasolinerasExternasSubnav'],
                    ['puntos_ruta', 'ccPuntosRutaToggle', 'ccPuntosRutaSubnav'],
                    ['rutas', 'ccRutasToggle', 'ccRutasSubnav'],
                    ['motoristas', 'ccMotoristasToggle', 'ccMotoristasSubnav'],
                    ['abastecimientos', 'ccAbastecimientosToggle', 'ccAbastecimientosSubnav'],
                    ['auditoria', 'ccAuditoriaToggle', 'ccAuditoriaSubnav'],
                    ['analisis', 'ccAnalisisToggle', 'ccAnalisisSubnav'],
                ];

                let gruposAbiertos = [];
                const gruposDisponibles = [];

                try {
                    const estadoGuardado = JSON.parse(sessionStorage.getItem(sidebarStateKey) || '[]');
                    gruposAbiertos = Array.isArray(estadoGuardado) ? estadoGuardado : [];
                } catch (error) {
                    gruposAbiertos = [];
                    sessionStorage.removeItem(sidebarStateKey);
                }

                const guardarEstadoVisual = function () {
                    sessionStorage.setItem(sidebarStateKey, JSON.stringify(gruposAbiertos));
                };

                gruposColapsables.forEach(function ([groupKey, toggleId, subnavId]) {
                    const toggle = document.getElementById(toggleId);
                    const subnav = document.getElementById(subnavId);

                    if (!toggle || !subnav) {
                        return;
                    }

                    const aplicarEstado = function (abierto) {
                        toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
                        subnav.classList.toggle('cc-sidebar-subnav-collapsed', !abierto);
                    };

                    gruposDisponibles.push({ aplicarEstado });

                    aplicarEstado(gruposAbiertos.includes(groupKey));

                    const alternarGrupo = function () {
                        const estaAbierto = toggle.getAttribute('aria-expanded') === 'true';
                        const debeAbrirse = !estaAbierto;

                        aplicarEstado(debeAbrirse);

                        if (debeAbrirse) {
                            if (!gruposAbiertos.includes(groupKey)) {
                                gruposAbiertos.push(groupKey);
                            }
                        } else {
                            gruposAbiertos = gruposAbiertos.filter(function (key) {
                                return key !== groupKey;
                            });
                        }

                        guardarEstadoVisual();
                    };

                    toggle.addEventListener('click', alternarGrupo);

                    toggle.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            alternarGrupo();
                        }
                    });
                });

                if (sidebarReset) {
                    sidebarReset.addEventListener('click', function () {
                        gruposAbiertos = [];

                        gruposDisponibles.forEach(function ({ aplicarEstado }) {
                            aplicarEstado(false);
                        });

                        sessionStorage.removeItem(sidebarStateKey);
                        localStorage.removeItem('ccSidebarScrollTop');
                        sidebar.scrollTop = 0;

                        document.body.classList.remove('cc-sidebar-desktop-hidden');
                        document.body.classList.remove('cc-sidebar-mobile-open');
                        actualizarEstadoHamburguesa();
                    });
                }

                const logoutForm = document.getElementById('ccLogoutForm');

                if (logoutForm) {
                    logoutForm.addEventListener('submit', function () {
                        sessionStorage.removeItem(sidebarStateKey);
                    });
                }
            });
        </script>

    </body>
</html>
