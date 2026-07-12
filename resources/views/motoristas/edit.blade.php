@php
    $queryParams = request()->query();

    $empresaActualSelector = collect([$motorista->empresa])
        ->filter()
        ->values();
@endphp

<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar motorista
                        </h3>

                        <p class="cc-subtitle cc-subtitle-compact">
                            Actualice la información del motorista disponible para solicitudes de abastecimiento.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('motoristas.show', array_merge(['motorista' => $motorista], $queryParams)) }}"
                           class="cc-btn-secondary cc-btn-wide">
                            Volver a ficha
                        </a>

                        <a href="{{ route('motoristas.administrar', $queryParams) }}"
                           class="cc-btn-secondary cc-btn-wide">
                            Volver a administrar
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form method="POST"
                      action="{{ route('motoristas.update', array_merge(['motorista' => $motorista], $queryParams)) }}"
                      novalidate>
                    @csrf
                    @method('PUT')

                    @include('motoristas._form', [
                        'motorista' => $motorista,
                        'submitLabel' => 'Actualizar motorista',
                        'modoVentana' => false,

                        /*
                         * En edición la empresa no debe modificarse.
                         * Se fuerza el formulario a mostrar solo la empresa actual
                         * como campo bloqueado, aunque el usuario sea Diesel Cop.
                         */
                        'esUsuarioDieselCop' => false,
                        'empresasSelector' => $empresaActualSelector,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>