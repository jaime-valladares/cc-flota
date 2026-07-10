<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Consulta de rutas | CC-Flota</title>

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
                                    Consulta de rutas
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
                                    Consulte las rutas configuradas entre puntos operativos y sus valores estimados de recorrido y consumo.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('rutas.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="GET" action="{{ route('rutas.consulta.ventana') }}" class="mb-5">
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Filtros de consulta
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-[1.2fr_1.2fr_auto] gap-4 items-end">

                                    <div class="cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>

                                        @if ($esUsuarioDieselCop)
                                            <select id="empresa_id" name="empresa_id" class="cc-input">
                                                <option value="">Todas</option>

                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option value="{{ $empresaOpcion->id }}" @selected((string) $empresaId === (string) $empresaOpcion->id)>
                                                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <select id="empresa_id" name="empresa_id" class="cc-input" disabled>
                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option value="{{ $empresaOpcion->id }}" selected>
                                                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif

                                        @error('empresa_id')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="ruta_id">
                                            Ruta
                                        </label>

                                        <select id="ruta_id" name="ruta_id" class="cc-input">
                                            <option value="">Todas</option>

                                            @foreach ($rutasSelector as $rutaOpcion)
                                                <option
                                                    value="{{ $rutaOpcion->id }}"
                                                    data-empresa-id="{{ $rutaOpcion->empresa_id }}"
                                                    @selected((string) $rutaId === (string) $rutaOpcion->id)
                                                >
                                                    {{ $rutaOpcion->ruta }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('ruta_id')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="flex items-end gap-3">
                                        <button type="submit" class="cc-btn-primary">
                                            Consultar
                                        </button>

                                        <a href="{{ route('rutas.consulta.ventana') }}" class="cc-btn-secondary">
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $rutas->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $rutas->firstItem() }}</span>
                                -
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $rutas->lastItem() }}</span>
                                de
                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $rutas->total() }}</span>
                            </div>
                        @endif

                        @if (! $hayFiltros)
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Consulta pendiente
                                </h5>
                                <p>
                                    Los resultados permanecerán vacíos hasta que seleccione una empresa o realice una búsqueda.
                                </p>
                            </div>
                        @elseif ($rutas->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>
                                <p>
                                    No hay rutas que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-table-wrapper">
                                <table class="cc-table">
                                    <colgroup>
                                        <col style="width: 24%;">
                                        <col style="width: 28%;">
                                        <col style="width: 18%;">
                                        <col style="width: 15%;">
                                        <col style="width: 15%;">
                                    </colgroup>

                                    <thead>
                                        <tr>
                                            <th class="cc-text-left">Empresa</th>
                                            <th class="cc-text-left">Ruta</th>
                                            <th class="cc-text-left">Kilómetros</th>
                                            <th class="cc-text-left">Galones</th>
                                            <th class="cc-text-left">Rendimiento</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($rutas as $ruta)
                                            <tr>
                                                <td class="cc-text-left cc-cell-truncate">
                                                    {{ $ruta->empresa->nombre_comercial ?: $ruta->empresa->nombre_legal }}
                                                </td>

                                                <td class="cc-text-left cc-cell-truncate">
                                                    <span class="cc-table-strong">
                                                        {{ $ruta->ruta }}
                                                    </span>
                                                </td>

                                                <td class="cc-text-left">
                                                    {{ number_format((float) $ruta->kilometros_estimados, 2) }} km
                                                </td>

                                                <td class="cc-text-left">
                                                    {{ number_format((float) $ruta->galones_estimados, 2) }} gal
                                                </td>

                                                <td class="cc-text-left">
                                                    @if ((float) $ruta->galones_estimados > 0)
                                                        {{ number_format((float) $ruta->kilometros_estimados / (float) $ruta->galones_estimados, 2) }} km/gal
                                                    @else
                                                        No disponible
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6">
                                {{ $rutas->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <script>
            const empresaSelect = document.getElementById('empresa_id');
            const rutaSelect = document.getElementById('ruta_id');

            function filtrarRutasPorEmpresa() {
                if (!empresaSelect || !rutaSelect) {
                    return;
                }

                const empresaId = empresaSelect.value;
                const selectedOption = rutaSelect.options[rutaSelect.selectedIndex];

                Array.from(rutaSelect.options).forEach(function (option) {
                    if (!option.value) {
                        option.hidden = false;
                        return;
                    }

                    option.hidden = empresaId !== '' && option.dataset.empresaId !== empresaId;
                });

                if (
                    selectedOption &&
                    selectedOption.value &&
                    empresaId !== '' &&
                    selectedOption.dataset.empresaId !== empresaId
                ) {
                    rutaSelect.value = '';
                }
            }

            if (empresaSelect && rutaSelect) {
                empresaSelect.addEventListener('change', filtrarRutasPorEmpresa);
                filtrarRutasPorEmpresa();
            }
        </script>
    </body>
</html>