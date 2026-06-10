<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Editar empresa cliente
                        </h3>
                        <p class="cc-subtitle">
                            Actualice los datos generales, fiscales y de contacto de la empresa.
                        </p>
                    </div>

                    <a href="{{ route('empresas.index') }}" class="cc-btn-secondary cc-btn-wide">
                        Volver al listado
                    </a>
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