<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Registro de unidad
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('unidades.create.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('unidades.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Ir a Consulta
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
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>