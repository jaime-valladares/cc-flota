<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Nueva gasolinera | CC-Flota</title>

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
        @php
            $filtrosAdministracion = request()->query();
        @endphp

        <div class="cc-window-wrapper" style="padding-top: 2.1rem;">
            <div class="cc-window-container" style="max-width: 79rem;">
                <div class="cc-card">

                    <div class="cc-card-header cc-card-header-compact">
                        <div>
                            <h3 class="cc-title cc-title-compact">
                                Nueva gasolinera
                            </h3>

                        </div>

                        <div class="flex items-center gap-3">
                            <a
                                href="{{ route(
                                    'gasolineras.administrar.ventana',
                                    $filtrosAdministracion
                                ) }}"
                                class="cc-btn-secondary cc-btn-wide"
                            >
                                Volver al Sistema
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
                        action="{{ route('gasolineras.store') }}"
                        novalidate
                    >
                        @csrf

                        @foreach ($filtrosAdministracion as $nombreFiltro => $valorFiltro)
                            @if (is_array($valorFiltro))
                                @foreach ($valorFiltro as $valorItem)
                                    <input
                                        type="hidden"
                                        name="filtros_retorno[{{ $nombreFiltro }}][]"
                                        value="{{ $valorItem }}"
                                    >
                                @endforeach
                            @else
                                <input
                                    type="hidden"
                                    name="filtros_retorno[{{ $nombreFiltro }}]"
                                    value="{{ $valorFiltro }}"
                                >
                            @endif
                        @endforeach

                        @include('gasolineras._form', [
                            'gasolinera' => null,
                            'modoVentana' => true,
                            'submitLabel' => 'Crear gasolinera',
                            'filtrosAdministracion' => $filtrosAdministracion,
                        ])
                    </form>

                </div>
            </div>
        </div>
    </body>
</html>