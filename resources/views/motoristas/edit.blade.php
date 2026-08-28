@php
    $queryParams = request()->query();
@endphp

<x-app-layout>
    <div class="cc-page-wrapper cc-va-scope">
        <div class="cc-content-container cc-operational-container">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Editar motorista
                        </h3>

                        <p class="cc-subtitle cc-subtitle-compact">
                            Actualice los datos personales y de contacto del motorista.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route(
                                'motoristas.show',
                                array_merge(
                                    ['motorista' => $motorista],
                                    $queryParams
                                )
                            ) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver a ficha
                        </a>

                        <a
                            href="{{ route(
                                'motoristas.administrar',
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
                        'motoristas.update',
                        array_merge(
                            ['motorista' => $motorista],
                            $queryParams
                        )
                    ) }}"
                    novalidate
                >
                    @csrf
                    @method('PUT')

                    @include('motoristas._form', [
                        'motorista' => $motorista,
                        'submitLabel' => 'Actualizar motorista',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>