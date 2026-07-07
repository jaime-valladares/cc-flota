<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar gasolinera externa
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Actualice la información registrada para esta gasolinera comercial externa.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras-externas.show', $gasolineraExterna) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
                        </a>

                        <a href="{{ route('gasolineras-externas.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('gasolineras-externas.update', $gasolineraExterna) }}" novalidate>
                    @csrf
                    @method('PUT')

                    @include('gasolineras-externas._form', [
                        'gasolineraExterna' => $gasolineraExterna,
                        'submitLabel' => 'Actualizar gasolinera',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>