<x-app-layout>
    @php
        $userName = Auth::user()->name ?? 'Usuario';
    @endphp

    <div class="cc-page-wrapper">
        <div class="cc-content-container">

            <section class="cc-page-intro mb-6">
                <div class="cc-page-intro-icon">
                    CC
                </div>

                <div>
                    <div class="cc-page-intro-kicker">
                        Consola operativa CC-Flota
                    </div>

                    <h1 class="cc-page-intro-title">
                        Bienvenido, {{ $userName }}
                    </h1>

                    <p class="cc-page-intro-text">
                        Seleccione una sección del menú lateral para iniciar su operación. Las opciones disponibles pueden variar según su rol, permisos asignados y alcance operativo dentro del sistema.
                    </p>
                </div>
            </section>

            <section class="cc-home-hero">
                <div class="cc-home-hero-content">
                    <div class="cc-home-kicker">
                        Inicio de trabajo
                    </div>

                    <h1 class="cc-home-title">
                        Navegación según permisos
                    </h1>

                    <p class="cc-home-lead">
                        CC-Flota organiza la operación por módulos. Utilice el menú lateral para acceder a las secciones habilitadas para su usuario.
                    </p>

                    <div class="cc-home-divider"></div>

                    <p class="cc-home-footnote">
                        El sistema mostrará únicamente las secciones y acciones disponibles según el perfil operativo asignado.
                    </p>
                </div>

                <div class="cc-home-visual" aria-hidden="true">
                    <div class="cc-home-mark">
                        CC
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
                            Las secciones visibles y acciones disponibles dependerán de la configuración de seguridad asignada a su usuario.
                        </div>
                    </div>
                </div>

                <div class="cc-status-strip cc-status-strip-active">
                    <div>
                        <strong>Sesión activa</strong>
                        <span>Entorno administrativo disponible para operación.</span>
                    </div>
                    <span>CC-Flota</span>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>