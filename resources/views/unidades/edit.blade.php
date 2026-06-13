<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-form-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Editar unidad
                        </h3>
                        <p class="cc-subtitle">
                            Actualice la información de la unidad, su cobertura Diesel Cop y su modelo de medición.
                        </p>
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
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>