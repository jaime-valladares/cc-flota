<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar punto de ruta
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Actualice el nombre del punto operativo utilizado como origen o destino en abastecimientos.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('puntos-ruta.show', $puntoRuta) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
                        </a>

                        <a href="{{ route('puntos-ruta.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('puntos-ruta.update', $puntoRuta) }}" novalidate>
                    @csrf
                    @method('PUT')

                    @include('puntos-ruta._form', [
                        'puntoRuta' => $puntoRuta,
                        'submitLabel' => 'Actualizar punto',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>