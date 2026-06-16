<x-app-layout>
    @php
        $userName = Auth::user()->name ?? 'Usuario';
    @endphp

    <div class="cc-page-wrapper">
        <div class="cc-content-container">

            <section class="cc-home-hero">
                <div class="cc-home-hero-content">
                    <div class="cc-home-kicker">
                        Consola operativa CC-Flota
                    </div>

                    <h1 class="cc-home-title">
                        Bienvenido, {{ $userName }}
                    </h1>

                    <p class="cc-home-lead">
                        Seleccione una sección del menú lateral para iniciar su operación.
                        Las opciones disponibles pueden variar según su rol y permisos asignados.
                    </p>

                    <div class="cc-home-divider"></div>

                    <div class="cc-home-principles">
                        <div>
                            <span>01</span>
                            <strong>Control</strong>
                            <p>Gestión estructurada de empresas, usuarios y unidades.</p>
                        </div>

                        <div>
                            <span>02</span>
                            <strong>Trazabilidad</strong>
                            <p>Operaciones administradas bajo registros y estados controlados.</p>
                        </div>

                        <div>
                            <span>03</span>
                            <strong>Operación</strong>
                            <p>Base funcional para administrar el ciclo operativo de flotas.</p>
                        </div>
                    </div>
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

            <section class="cc-home-note">
                <div>
                    <h2>
                        Inicio de trabajo
                    </h2>

                    <p>
                        Utilice el menú lateral para navegar entre los módulos habilitados. A medida que el sistema evolucione,
                        esta pantalla podrá incorporar indicadores, alertas operativas o accesos rápidos según el perfil del usuario.
                    </p>
                </div>

                <div class="cc-home-note-badge">
                    Acceso según permisos
                </div>
            </section>

        </div>
    </div>
</x-app-layout>