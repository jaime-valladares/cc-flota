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
                            Registro de licencia
                        </h3>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <a
                            href="{{ route('licencias.create.ventana') }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Abrir en nueva pestaña
                        </a>

                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="cc-alert-danger">
                        <div class="font-bold">
                            Revise la información ingresada.
                        </div>

                        <ul class="mt-2 list-inside list-disc">
                            @foreach ($errors->all() as $error)
                                <li>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="cc-status-strip mb-6">
                    <div>
                        <strong>Flujo de habilitación</strong>
                        <span>
                            La unidad permanecerá registrada hasta completar
                            la asignación inicial de marchamos.
                        </span>
                    </div>
                </div>

                @if ($unidades->isEmpty())
                    <div class="cc-alert-warning">
                        <div class="font-bold">
                            No hay unidades elegibles
                        </div>

                        <div class="mt-1">
                            Actualmente no existen unidades registradas,
                            pertenecientes a empresas activas y sin licencia
                            previa disponibles para este proceso.
                        </div>
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route('licencias.store') }}"
                    novalidate
                >
                    @csrf

                    @include('licencias._form', [
                        'licencia' => null,
                        'empresas' => $empresas,
                        'unidades' => $unidades,
                        'periodosVigencia' => $periodosVigencia,
                        'esUsuarioDieselCop' => $esUsuarioDieselCop,
                        'submitLabel' => 'Guardar licencia',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
