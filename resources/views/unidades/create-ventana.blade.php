<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Registro de unidad | CC-Flota</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
            <div class="cc-page-wrapper">
                <div class="cc-form-container">
                    <div class="cc-card">

                        <div class="cc-card-header">
                            <div>
                                <h3 class="cc-title">
                                    Registro de unidad
                                </h3>
                                <p class="cc-subtitle">
                                    Complete la información de la unidad, sus tanques, capacidad cubierta y modelo de medición.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('unidades.create') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST" action="{{ route('unidades.store') }}" novalidate>
                            @csrf

                            @include('unidades._form', [
                                'unidad' => null,
                                'empresas' => $empresas,
                                'modelosMedicion' => $modelosMedicion,
                                'esUsuarioDieselCop' => $esUsuarioDieselCop,
                                'submitLabel' => 'Guardar unidad',
                            ])
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>