<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Registro de empresa cliente
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Complete los datos legales, fiscales y de contacto de la empresa cliente.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('empresas.create.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('empresas.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a consulta
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('empresas.store') }}" novalidate>
                    @csrf

                    @include('empresas._form', [
                        'empresa' => null,
                        'submitLabel' => 'Guardar empresa',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>