<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-form-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Registro de usuario
                        </h3>
                        <p class="cc-subtitle">
                            Complete los datos de acceso, empresa, rol y contacto del usuario.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('usuarios.create') }}"
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

                <form method="POST" action="{{ route('usuarios.store') }}">
                    @csrf

                    @include('usuarios._form', [
                        'usuario' => null,
                        'empresas' => $empresas,
                        'roles' => $roles,
                        'esUsuarioDieselCop' => $esUsuarioDieselCop,
                        'submitLabel' => 'Guardar usuario',
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>