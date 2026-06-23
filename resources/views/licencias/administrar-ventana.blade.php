<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar licencia | CC-Flota</title>

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
            <div class="cc-page-wrapper">
                <div class="cc-window-container" style="max-width: 73rem;">
                    <div class="cc-card">

                        <div class="cc-card-header">
                            <div>
                                <h3 class="cc-title">
                                    Administrar licencia
                                </h3>
                                <p class="cc-subtitle">
                                    Localice una licencia para consultar su ficha, editar su vigencia o gestionar su estado.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('licencias.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @include('licencias.partials.consulta-contenido', [
                            'accionFormulario' => route('licencias.administrar.ventana'),
                            'rutaReset' => route('licencias.administrar.ventana'),
                            'modoAdministrar' => true,
                        ])

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>