<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Nueva gasolinera
                        </h3>

                        <p class="cc-subtitle cc-subtitle-compact">
                            Registre una gasolinera interna con sus tanques iniciales y capacidad operativa.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras.create.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('gasolineras.index') }}" class="cc-btn-secondary">
                            Volver a Consulta
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('gasolineras.store') }}" novalidate>
                    @csrf

                    @include('gasolineras._form', [
                        'modoVentana' => false,
                        'submitLabel' => 'Guardar gasolinera',
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>