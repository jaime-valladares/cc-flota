@php
    $queryParams = collect(request()->query())
        ->except([
            'ruta',
            'return_to',
            'return_query',
        ])
        ->all();
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
                            Registro de ruta
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route(
                                'rutas.create.ventana',
                                $queryParams
                            ) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Abrir en nueva pestaña
                        </a>

                        <a
                            href="{{ route(
                                'rutas.index',
                                $queryParams
                            ) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Ir a consulta
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
                        'rutas.store',
                        $queryParams
                    ) }}"
                    novalidate
                >
                    @csrf

                    <input
                        type="hidden"
                        name="return_query"
                        value="{{ http_build_query($queryParams) }}"
                    >

                    @include('rutas._form', [
                        'ruta' => null,
                        'submitLabel' => 'Guardar ruta',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>