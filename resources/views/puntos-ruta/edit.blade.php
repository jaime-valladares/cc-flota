@php
    $queryParams = request()->query();

    $returnQuery = http_build_query(
        collect($queryParams)
            ->except([
                'puntoRuta',
                'return_to',
                'return_query',
            ])
            ->all()
    );
@endphp

<x-app-layout>
    <div class="cc-page-wrapper cc-va-scope">
        <div
            class="cc-content-container cc-operational-container"
        >
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar punto de ruta
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route(
                                'puntos-ruta.show',
                                array_merge(
                                    $queryParams,
                                    ['puntoRuta' => $puntoRuta]
                                )
                            ) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver a ficha
                        </a>

                        <a
                            href="{{ route(
                                'puntos-ruta.administrar',
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

                @if ($errors->any())
                    <div class="cc-alert cc-alert-danger">
                        <div class="font-bold">
                            Revise la información proporcionada.
                        </div>

                        <ul class="mt-2 list-disc list-inside">
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
                        'puntos-ruta.update',
                        array_merge(
                            $queryParams,
                            ['puntoRuta' => $puntoRuta]
                        )
                    ) }}"
                    novalidate
                >
                    @csrf
                    @method('PUT')

                    <input
                        type="hidden"
                        name="return_query"
                        value="{{ $returnQuery }}"
                    >

                    @include('puntos-ruta._form', [
                        'puntoRuta' => $puntoRuta,
                        'submitLabel' => 'Actualizar punto',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>