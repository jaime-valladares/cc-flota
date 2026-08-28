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
                            Registro de motorista
                        </h3>

                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route('motoristas.create.ventana', $queryParams) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Abrir en nueva pestaña
                        </a>

                        <a
                            href="{{ route('motoristas.index', $queryParams) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver a Consulta
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
                    action="{{ route('motoristas.store', $queryParams) }}"
                    novalidate
                >
                    @csrf

                    @include('motoristas._form', [
                        'motorista' => null,
                        'submitLabel' => 'Guardar motorista',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>