<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar motorista
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Actualice la información del motorista disponible para solicitudes de abastecimiento.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('motoristas.show', $motorista) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
                        </a>

                        <a href="{{ route('motoristas.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('motoristas.update', $motorista) }}" novalidate>
                    @csrf
                    @method('PUT')

                    @include('motoristas._form', [
                        'motorista' => $motorista,
                        'submitLabel' => 'Actualizar motorista',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>