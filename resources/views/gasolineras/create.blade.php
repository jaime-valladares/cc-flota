<x-app-layout>
    @php
        $filtrosAdministracion = request()->query();
    @endphp

    <div class="cc-page-wrapper cc-va-scope">
        <div class="cc-content-container cc-operational-container">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Nueva gasolinera
                        </h3>

                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route(
                                'gasolineras.create.ventana',
                                $filtrosAdministracion
                            ) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Abrir en nueva pestaña
                        </a>

                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('gasolineras.store') }}"
                    novalidate
                >
                    @csrf

                    @foreach ($filtrosAdministracion as $nombreFiltro => $valorFiltro)
                        @if (is_array($valorFiltro))
                            @foreach ($valorFiltro as $valorItem)
                                <input
                                    type="hidden"
                                    name="filtros_retorno[{{ $nombreFiltro }}][]"
                                    value="{{ $valorItem }}"
                                >
                            @endforeach
                        @else
                            <input
                                type="hidden"
                                name="filtros_retorno[{{ $nombreFiltro }}]"
                                value="{{ $valorFiltro }}"
                            >
                        @endif
                    @endforeach

                    @include('gasolineras._form', [
                        'gasolinera' => null,
                        'modoVentana' => false,
                        'submitLabel' => 'Crear gasolinera',
                        'filtrosAdministracion' => $filtrosAdministracion,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>