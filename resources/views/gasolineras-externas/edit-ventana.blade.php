@php
    $queryParams = request()->query();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Editar gasolinera externa | CC-Flota</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
            rel="stylesheet"
        >

        @vite(['resources/css/app.css', 'resources/js/app.js'])
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
                                    Editar gasolinera externa
                                </h3>

                                <p class="cc-subtitle cc-subtitle-compact">
                                    Actualice la compañía y la dirección de la gasolinera externa.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    href="{{ route(
                                        'gasolineras-externas.show.ventana',
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
                                        'gasolineras-externas.administrar.ventana',
                                        $queryParams
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver a administrar
                                </a>

                                <a
                                    href="{{ route(
                                        'gasolineras-externas.administrar',
                                        $queryParams
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver al sistema
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

                            <input
                                type="hidden"
                                name="return_to"
                                value="ventana"
                            >

                            @include('gasolineras-externas._form', [
                                'gasolineraExterna' => $gasolineraExterna,
                                'submitLabel' => 'Actualizar gasolinera',
                                'modoVentana' => true,
                            ])
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>