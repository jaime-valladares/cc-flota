<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar ruta
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Actualice los puntos que conforman la ruta y sus valores estimados de recorrido y consumo.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('rutas.show', $ruta) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
                        </a>

                        <a href="{{ route('rutas.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('rutas.update', $ruta) }}">
                    @csrf
                    @method('PUT')

                    @include('rutas._form', [
                        'ruta' => $ruta,
                        'submitLabel' => 'Actualizar ruta',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>