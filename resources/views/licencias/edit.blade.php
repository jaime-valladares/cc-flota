<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-form-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Editar licencia
                        </h3>
                        <p class="cc-subtitle">
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
                        'periodosVigencia' => $periodosVigencia,
                        'submitLabel' => 'Actualizar licencia',
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>