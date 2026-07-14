<x-app-layout>
    @php
        $filtrosAdministracion = request()->query();
    @endphp

    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 79rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar gasolinera
                        </h3>

                        <p class="cc-subtitle cc-subtitle-compact">
                            Actualice los datos generales, ubicación y contacto operativo de la gasolinera.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route(
                                'gasolineras.edit.ventana',
                                array_merge(
                                    ['gasolinera' => $gasolinera],
                                    $filtrosAdministracion
                                )
                            ) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Abrir en nueva pestaña
                        </a>

                        <a
                            href="{{ route(
                                'gasolineras.show',
                                array_merge(
                                    ['gasolinera' => $gasolinera],
                                    $filtrosAdministracion
                                )
                            ) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver a ficha
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="cc-alert cc-alert-danger">
                        <ul class="cc-alert-list">
                            @foreach ($errors->all() as $error)
                                <li>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route(
                        'gasolineras.update',
                        array_merge(
                            ['gasolinera' => $gasolinera],
                            $filtrosAdministracion
                        )
                    ) }}"
                    novalidate
                >
                    @csrf
                    @method('PUT')

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
                        'gasolinera' => $gasolinera,
                        'modoVentana' => false,
                        'submitLabel' => 'Guardar cambios',
                        'filtrosAdministracion' => $filtrosAdministracion,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>