<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Registro de motorista
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Registre un motorista disponible para seleccionar en solicitudes de abastecimiento.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('motoristas.create.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('motoristas.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Ir a consulta
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('motoristas.store') }}" novalidate>
                    @csrf

                    @include('motoristas._form', [
                        'motorista' => null,
                        'submitLabel' => 'Guardar motorista',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>