<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Registro de usuario
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Complete los datos de acceso, empresa, rol y contacto del usuario.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('usuarios.create.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('usuarios.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a consulta
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('usuarios.store') }}" novalidate>
                    @csrf

                    @include('usuarios._form', [
                        'usuario' => null,
                        'empresas' => $empresas,
                        'roles' => $roles,
                        'esUsuarioDieselCop' => $esUsuarioDieselCop,
                        'submitLabel' => 'Guardar usuario',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>