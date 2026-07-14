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

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Editar ruta | CC-Flota</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

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
            <div class="cc-page-wrapper">
                <div
                    class="cc-window-container"
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
                                        'rutas.show.ventana',
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
                                        'rutas.administrar.ventana',
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
                                name="return_to"
                                value="ventana"
                            >

                            <input
                                type="hidden"
                                name="return_query"
                                value="{{ $returnQuery }}"
                            >

                            @include('rutas._form', [
                                'ruta' => $ruta,
                                'submitLabel' => 'Actualizar ruta',
                                'modoVentana' => true,
                            ])
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>