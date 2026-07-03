<x-app-layout>
    @php
        $userName = Auth::user()->name ?? 'Usuario';
    @endphp

    <style>
        .cc-dashboard-compact .cc-home-hero {
            min-height: 340px !important;
        }

        .cc-dashboard-compact .cc-home-hero-content {
            padding-top: 2.75rem !important;
            padding-bottom: 2.75rem !important;
        }

        .cc-dashboard-compact .cc-home-title {
            font-size: clamp(1.75rem, 2.2vw, 2.35rem) !important;
            line-height: 1.12 !important;
            letter-spacing: -0.035em !important;
            max-width: 22rem !important;
        }

        .cc-dashboard-compact .cc-home-lead {
            max-width: 30rem !important;
            margin-top: 1.15rem !important;
            font-size: 1.05rem !important;
            line-height: 1.55 !important;
        }

        .cc-dashboard-compact .cc-home-divider {
            max-width: 22rem !important;
            margin-top: 1.55rem !important;
            margin-bottom: 1.25rem !important;
        }

        .cc-dashboard-compact .cc-home-footnote {
            max-width: 29rem !important;
            font-size: 0.92rem !important;
            line-height: 1.55 !important;
        }

        .cc-dashboard-compact .cc-home-visual {
            min-height: 470px !important;
        }

        .cc-dashboard-compact .cc-home-mark {
            width: 7rem !important;
            height: 7rem !important;
            font-size: 2rem !important;
            border-radius: 1.65rem !important;
        }

        .cc-dashboard-compact .cc-home-orbit-one {
            width: 20rem !important;
            height: 20rem !important;
        }

        .cc-dashboard-compact .cc-home-orbit-two {
            width: 28rem !important;
            height: 28rem !important;
        }

        @media (max-width: 1024px) {
            .cc-dashboard-compact .cc-home-title {
                font-size: clamp(2rem, 7vw, 2.85rem) !important;
            }

            .cc-dashboard-compact .cc-home-visual {
                min-height: 260px !important;
            }
        }
    </style>

    <div class="cc-dashboard-compact">
        <div class="cc-page-wrapper">
            <div class="cc-content-container">

                <section class="cc-page-intro mb-6">
                <div class="cc-page-intro-icon cc-page-intro-icon-image">
                    <img
                        src="{{ asset('images/cc-flota/favicon.png') }}"
                        alt="CC-Flota"
                        class="cc-page-intro-icon-img"
                    >
                </div>

                    <div>
                        <div class="cc-page-intro-kicker">
                            Consola operativa CC-Flota
                        </div>

                        <h1 class="cc-page-intro-title">
                            Bienvenido, {{ $userName }}
                        </h1>

                        <p class="cc-page-intro-text">
                            Seleccione un módulo del menú lateral para comenzar.
                        </p>
                    </div>
                </section>

                <section class="cc-home-hero">
                    <div class="cc-home-hero-content">
                        
                        <h2 class="cc-home-title">
                            Inicio de operación
                        </h2>

                        <p class="cc-home-lead">
                            Acceda a las secciones habilitadas para consultar, registrar o administrar información operativa.
                        </p>

                        <div class="cc-home-divider"></div>

                        <p class="cc-home-footnote">
                            El menú lateral centraliza los módulos disponibles para su perfil.
                        </p>
                    </div>

                    <div class="cc-home-visual" aria-hidden="true">
                        <div class="cc-home-mark cc-home-mark-image">
                            <img
                                src="{{ asset('images/cc-flota/favicon.png') }}"
                                alt="CC-Flota"
                                class="cc-home-mark-img"
                            >
                        </div>

                        <div class="cc-home-orbit cc-home-orbit-one"></div>
                        <div class="cc-home-orbit cc-home-orbit-two"></div>

                        <div class="cc-home-node cc-home-node-one"></div>
                        <div class="cc-home-node cc-home-node-two"></div>
                        <div class="cc-home-node cc-home-node-three"></div>
                    </div>
                </section>

                <div class="mt-5 grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <div class="cc-callout cc-callout-info">
                        <div class="cc-callout-marker"></div>
                        <div>
                            <div class="cc-callout-title">
                                Acceso por rol
                            </div>
                            <div class="cc-callout-text">
                                Las acciones visibles dependen de los permisos asignados.
                            </div>
                        </div>
                    </div>

                    <div class="cc-status-strip cc-status-strip-active">
                        <div>
                            <strong>Sesión activa</strong>
                            <span>Entorno administrativo disponible.</span>
                        </div>
                        <span>CC-Flota</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>