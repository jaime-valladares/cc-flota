<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar gasolinera
                        </h3>

                        <p class="cc-subtitle cc-subtitle-compact">
                            Actualice los datos generales, ubicación y contacto operativo de la gasolinera.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('gasolineras.edit.ventana', $gasolinera) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('gasolineras.show', $gasolinera) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('gasolineras.update', $gasolinera) }}" novalidate>
                    @csrf
                    @method('PUT')

                    @include('gasolineras._form', [
                        'gasolinera' => $gasolinera,
                        'modoVentana' => false,
                        'submitLabel' => 'Guardar cambios',
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>