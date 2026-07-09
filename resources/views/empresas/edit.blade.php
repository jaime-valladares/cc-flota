<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar empresa cliente
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('empresas.show', $empresa) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
                        </a>

                        <a href="{{ route('empresas.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('empresas.update', $empresa) }}" novalidate>
                    @csrf
                    @method('PUT')

                    @include('empresas._form', [
                        'empresa' => $empresa,
                        'submitLabel' => 'Actualizar empresa',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>