@php
    $queryParams = request()->query();
@endphp

<x-app-layout>
    <div class="cc-page-wrapper">
        <div
            class="cc-content-container"
            style="max-width: 80rem;"
        >
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar gasolinera externa
                        </h3>

                        <p class="cc-subtitle cc-subtitle-compact">
                            Actualice la compañía y la dirección de la gasolinera externa.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route(
                                'gasolineras-externas.show',
                                array_merge(
                                    [
                                        'gasolineraExterna' =>
                                            $gasolineraExterna,
                                    ],
                                    $queryParams
                                )
                            ) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver a ficha
                        </a>

                        <a
                            href="{{ route(
                                'gasolineras-externas.administrar',
                                $queryParams
                            ) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver a administrar
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
                    action="{{ route(
                        'gasolineras-externas.update',
                        array_merge(
                            [
                                'gasolineraExterna' =>
                                    $gasolineraExterna,
                            ],
                            $queryParams
                        )
                    ) }}"
                    novalidate
                >
                    @csrf
                    @method('PUT')

                    @include('gasolineras-externas._form', [
                        'gasolineraExterna' => $gasolineraExterna,
                        'submitLabel' => 'Actualizar gasolinera',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>