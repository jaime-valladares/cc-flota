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
                            Editar licencia
                        </h3>
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-3">
                        <a
                            href="{{ route(
                                'licencias.edit.ventana',
                                $licencia
                            ) }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Abrir en nueva pestaña
                        </a>

                        <a
                            href="{{ route(
                                'licencias.show',
                                $licencia
                            ) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver a ficha
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

                <div class="cc-callout cc-callout-info mb-5">
                    <div class="cc-callout-marker"></div>

                    <div>
                        <div class="cc-callout-title">
                            Alcance de la edición
                        </div>

                        <div class="cc-callout-text">
                            La modificación de la licencia no cambia el estado
                            administrativo de la unidad ni completa la
                            asignación inicial de marchamos. Si la nueva fecha
                            de activación queda en el futuro, la licencia
                            permanecerá pendiente hasta ese día.
                        </div>
                    </div>
                </div>

                <form
                    method="POST"
                    action="{{ route(
                        'licencias.update',
                        $licencia
                    ) }}"
                    novalidate
                >
                    @csrf
                    @method('PUT')

                    @include('licencias._form', [
                        'licencia' => $licencia,
                        'empresas' => $empresas ?? collect(),
                        'unidades' => $unidades ?? collect(),
                        'periodosVigencia' => $periodosVigencia,
                        'esUsuarioDieselCop' => $esUsuarioDieselCop ?? true,
                        'submitLabel' => 'Actualizar licencia',
                        'modoVentana' => false,
                    ])
                </form>

            </div>
        </div>
    </div>
</x-app-layout>