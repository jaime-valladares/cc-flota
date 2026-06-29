<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar usuario
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Actualice los datos de acceso, empresa, rol y contacto del usuario.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('usuarios.show', $usuario) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('usuarios.update', $usuario) }}" novalidate>
                    @csrf
                    @method('PUT')

                    @include('usuarios._form', [
                        'usuario' => $usuario,
                        'empresas' => $empresas,
                        'roles' => $roles,
                        'esUsuarioDieselCop' => $esUsuarioDieselCop,
                        'submitLabel' => 'Actualizar usuario',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>