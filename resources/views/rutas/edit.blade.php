@php
    $queryParams = collect(request()->query())
        ->except([
            'ruta',
            'return_to',
            'return_query',
        ])
        ->all();

    $returnQuery = http_build_query($queryParams);
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
                            Editar ruta
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route(
                                'rutas.show',
                                array_merge(
                                    $queryParams,
                                    ['ruta' => $ruta]
                                )
                            ) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver a ficha
                        </a>

                        <a
                            href="{{ route(
                                'rutas.administrar',
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
                        'rutas.update',
                        array_merge(
                            $queryParams,
                            ['ruta' => $ruta]
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

                    @include('rutas._form', [
                        'ruta' => $ruta,
                        'submitLabel' => 'Actualizar ruta',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>