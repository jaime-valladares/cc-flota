<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-form-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Editar usuario
                        </h3>
                        <p class="cc-subtitle">
                            Actualice los datos de acceso, empresa, rol y contacto del usuario.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('usuarios.show', $usuario) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('usuarios.update', $usuario) }}">
                    @csrf
                    @method('PUT')

                    @include('usuarios._form', [
                        'usuario' => $usuario,
                        'empresas' => $empresas,
                        'roles' => $roles,
                        'esUsuarioDieselCop' => $esUsuarioDieselCop,
                        'submitLabel' => 'Actualizar usuario',
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>