<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-form-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Registro de unidad
                        </h3>
                        <p class="cc-subtitle">
                            Complete la información de la unidad, sus tanques, capacidad cubierta y modelo de medición.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('unidades.create.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('unidades.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a consulta
                        </a>
                    </div>
                </div>

                <form method="POST" action="{{ route('unidades.store') }}" novalidate>
                    @csrf

                    @include('unidades._form', [
                        'unidad' => null,
                        'empresas' => $empresas,
                        'modelosMedicion' => $modelosMedicion,
                        'esUsuarioDieselCop' => $esUsuarioDieselCop,
                        'submitLabel' => 'Guardar unidad',
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>