<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Editar gasolinera | CC-Flota</title>

        
        @include('layouts.partials.favicon')
<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/cc-flota/favicon.png') }}?v=3">
        <link rel="shortcut icon" type="image/png" href="{{ asset('images/cc-flota/favicon.png') }}?v=3">
        <link rel="apple-touch-icon" href="{{ asset('images/cc-flota/favicon.png') }}?v=3">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        <div class="cc-window-wrapper" style="padding-top: 2.1rem;">
            <div class="cc-window-container" style="max-width: 79rem;">
                <div class="cc-card">

                    <div class="cc-card-header cc-card-header-compact">
                        <div>
                            <h3 class="cc-title cc-title-compact">
                                Editar gasolinera
                            </h3>

                            <p class="cc-subtitle cc-subtitle-compact">
                                Actualice los datos generales, ubicación y contacto operativo de la gasolinera.
                            </p>
                        </div>

                        <div class="flex items-center gap-3">
                            <a href="{{ route('gasolineras.show.ventana', $gasolinera) }}" class="cc-btn-secondary cc-btn-wide">
                                Volver a ficha
                            </a>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('gasolineras.update', $gasolinera) }}" novalidate>
                        @csrf
                        @method('PUT')

                        @include('gasolineras._form', [
                            'gasolinera' => $gasolinera,
                            'modoVentana' => true,
                            'submitLabel' => 'Guardar cambios',
                        ])
                    </form>

                </div>
            </div>
        </div>
    </body>
</html>