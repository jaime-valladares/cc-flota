<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Registro de punto de ruta
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Complete la identificación y dirección del punto operativo utilizado como origen o destino en abastecimientos.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('puntos-ruta.create.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('puntos-ruta.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Ir a consulta
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('puntos-ruta.store') }}" novalidate>
                    @csrf

                    @include('puntos-ruta._form', [
                        'puntoRuta' => null,
                        'submitLabel' => 'Guardar punto',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>