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
                            Editar unidad
                        </h3>
                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route(
                                'unidades.show',
                                array_merge(
                                    $queryParams,
                                    ['unidad' => $unidad]
                                )
                            ) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver a ficha
                        </a>

                        <a
                            href="{{ route(
                                'unidades.administrar',
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
                        'unidades.update',
                        array_merge(
                            $queryParams,
                            ['unidad' => $unidad]
                        )
                    ) }}"
                    novalidate
                >
                    @csrf
                    @method('PUT')

                    @include('unidades._form', [
                        'unidad' => $unidad,
                        'empresas' => $empresas,
                        'empresaUsuario' => $empresaUsuario ?? $unidad->empresa,
                        'modelosMedicion' => $modelosMedicion,
                        'esUsuarioDieselCop' => $esUsuarioDieselCop,
                        'submitLabel' => 'Actualizar unidad',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>