@php
    $queryParams = request()->query();

    $empresaActualSelector = collect([$motorista->empresa])
        ->filter()
        ->values();
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Editar motorista | CC-Flota</title>

        @include('layouts.partials.favicon')

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap" rel="stylesheet">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
            <div class="cc-page-wrapper">
                <div class="cc-window-container" style="max-width: 80rem;">
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Editar motorista
                                </h3>

                                <p class="cc-subtitle cc-subtitle-compact">
                                    Actualice la información del motorista disponible para solicitudes de abastecimiento.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('motoristas.show.ventana', array_merge(['motorista' => $motorista], $queryParams)) }}"
                                   class="cc-btn-secondary cc-btn-wide">
                                    Volver a ficha
                                </a>

                                <a href="{{ route('motoristas.administrar.ventana', $queryParams) }}"
                                   class="cc-btn-secondary cc-btn-wide">
                                    Volver a administrar
                                </a>

                                <a href="{{ route('motoristas.administrar', $queryParams) }}"
                                   class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="POST"
                              action="{{ route('motoristas.update', array_merge(['motorista' => $motorista], $queryParams)) }}"
                              novalidate>
                            @csrf
                            @method('PUT')

                            <input type="hidden" name="return_to" value="ventana">

                            @include('motoristas._form', [
                                'motorista' => $motorista,
                                'submitLabel' => 'Actualizar motorista',
                                'modoVentana' => true,

                                /*
                                 * En edición la empresa no debe modificarse.
                                 * Se fuerza el formulario a mostrar solo la empresa actual
                                 * como campo bloqueado, aunque el usuario sea Diesel Cop.
                                 */
                                'esUsuarioDieselCop' => false,
                                'empresasSelector' => $empresaActualSelector,
                            ])
                        </form>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>