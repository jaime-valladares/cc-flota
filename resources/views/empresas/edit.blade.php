<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-form-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Editar empresa cliente
                        </h3>
                        <p class="cc-subtitle">
                            Actualice los datos legales, fiscales y de contacto de la empresa cliente.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('empresas.show', $empresa) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('empresas.update', $empresa) }}">
                    @csrf
                    @method('PUT')

                    @include('empresas._form', [
                        'empresa' => $empresa,
                        'submitLabel' => 'Actualizar empresa',
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>