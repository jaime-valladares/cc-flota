<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Registro de licencia | CC-Flota</title>

        <!-- Fonts -->
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
                                    Registro de licencia
                                </h3>
                                <p class="cc-subtitle">
                                    Asigne cobertura Diesel Cop a una unidad existente, definiendo vigencia, vencimiento y plantilla de puntos de seguridad.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('licencias.create') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if ($errors->any())
                            <div class="cc-alert-danger">
                                <div class="font-bold">
                                    Revise la información ingresada.
                                </div>

                                <ul class="mt-2 list-disc list-inside">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('licencias.store') }}" novalidate>
                            @csrf

                            @include('licencias._form', [
                                'licencia' => null,
                                'empresas' => $empresas,
                                'unidades' => $unidades,
                                'periodosVigencia' => $periodosVigencia,
                                'esUsuarioDieselCop' => $esUsuarioDieselCop,
                                'submitLabel' => 'Guardar licencia',
                            ])
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>