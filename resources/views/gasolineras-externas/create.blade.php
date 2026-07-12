<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Registro de gasolinera externa
                        </h3>

                        <p class="cc-subtitle cc-subtitle-compact">
                            Registre una gasolinera comercial autorizada o frecuente para abastecimientos externos.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras-externas.create.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('gasolineras-externas.index') }}"
                           class="cc-btn-secondary cc-btn-wide">
                            Volver a Consulta
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('gasolineras-externas.store') }}" novalidate>
                    @csrf

                    @include('gasolineras-externas._form', [
                        'gasolineraExterna' => null,
                        'submitLabel' => 'Guardar gasolinera',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>