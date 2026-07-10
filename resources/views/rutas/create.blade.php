<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Registro de ruta
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Construya una ruta entre dos puntos registrados y defina sus valores estimados de recorrido y consumo.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('rutas.create.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('rutas.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Ir a consulta
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('rutas.store') }}">
                    @csrf

                    @include('rutas._form', [
                        'ruta' => null,
                        'submitLabel' => 'Guardar ruta',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>