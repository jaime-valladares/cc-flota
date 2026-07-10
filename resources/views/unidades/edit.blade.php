<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar unidad
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('unidades.show', $unidad) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('unidades.update', $unidad) }}" novalidate>
                    @csrf
                    @method('PUT')

                    @include('unidades._form', [
                        'unidad' => $unidad,
                        'empresas' => $empresas,
                        'modelosMedicion' => $modelosMedicion,
                        'esUsuarioDieselCop' => $esUsuarioDieselCop,
                        'submitLabel' => 'Actualizar unidad',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>