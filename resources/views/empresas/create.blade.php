<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-form-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Registro de empresa cliente
                        </h3>
                        <p class="cc-subtitle">
                            Complete los datos generales, fiscales y de contacto de la empresa.
                        </p>
                    </div>

                    <a href="{{ route('empresas.index') }}" class="cc-btn-secondary cc-btn-wide">
                        Volver al listado
                    </a>
                </div>

                <form method="POST" action="{{ route('empresas.store') }}">
                    @csrf

                    @include('empresas._form', [
                        'empresa' => null,
                        'submitLabel' => 'Guardar empresa',
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>