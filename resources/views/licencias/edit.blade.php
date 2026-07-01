<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar licencia
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Actualice la vigencia y fecha de activación de la licencia.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('licencias.show', $licencia) }}" class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
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

                <form method="POST" action="{{ route('licencias.update', $licencia) }}" novalidate>
                    @csrf
                    @method('PUT')

                    @include('licencias._form', [
                        'licencia' => $licencia,
                        'empresas' => $empresas ?? collect(),
                        'unidades' => $unidades ?? collect(),
                        'periodosVigencia' => $periodosVigencia,
                        'esUsuarioDieselCop' => $esUsuarioDieselCop ?? true,
                        'submitLabel' => 'Actualizar licencia',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>