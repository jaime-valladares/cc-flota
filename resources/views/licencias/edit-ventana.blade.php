<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">

        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >

        <meta
            name="csrf-token"
            content="{{ csrf_token() }}"
        >

        <title>Editar licencia | CC-Flota</title>

        @include('layouts.partials.favicon')

        <link
            rel="preconnect"
            href="https://fonts.googleapis.com"
        >

        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin
        >

        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
            rel="stylesheet"
        >

        @vite([
            'resources/css/app.css',
            'resources/js/app.js',
        ])
    </head>

    <body class="antialiased">
        <div
            class="min-h-screen"
            style="background: var(--cc-bg-main);"
        >
            <div class="cc-page-wrapper cc-va-scope">
                <div
                    class="cc-window-container cc-operational-container"
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
                                        'licencias.show.ventana',
                                        $licencia
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver a ficha
                                </a>

                                <a
                                    href="{{ route(
                                        'licencias.administrar',
                                        request()->query()
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver a  Administrar
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
                                    La modificación de la licencia no cambia el
                                    estado administrativo de la unidad ni
                                    completa la asignación inicial de marchamos.
                                    Si la nueva fecha de activación queda en el
                                    futuro, la licencia permanecerá pendiente
                                    hasta ese día.
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

                            <input
                                type="hidden"
                                name="return_to"
                                value="ventana"
                            >

                            @include('licencias._form', [
                                'licencia' => $licencia,
                                'empresas' => $empresas ?? collect(),
                                'unidades' => $unidades ?? collect(),
                                'periodosVigencia' => $periodosVigencia,
                                'esUsuarioDieselCop' => $esUsuarioDieselCop ?? true,
                                'submitLabel' => 'Actualizar licencia',
                                'modoVentana' => true,
                            ])
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>