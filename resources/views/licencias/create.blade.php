<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Registro de licencia
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Asigne cobertura Diesel Cop a una unidad existente.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('licencias.create.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>

                        <a href="{{ route('licencias.index') }}" class="cc-btn-secondary cc-btn-wide">
                            Ir a Consulta
                        </a>
                    </div>
                </div>

                @if ($errors->any())
                    <div class="cc-alert-danger">
                        <div class="font-bold">
                            Revise la información ingresada.
                        </div>

                        <ul class="mt-2 list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('licencias.store') }}" novalidate>
                    @csrf

                    @include('licencias._form', [
                        'licencia' => null,
                        'empresas' => $empresas,
                        'unidades' => $unidades,
                        'periodosVigencia' => $periodosVigencia,
                        'esUsuarioDieselCop' => $esUsuarioDieselCop,
                        'submitLabel' => 'Guardar licencia',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>