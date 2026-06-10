<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Consulta de empresas | CC-Flota</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800,900&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="font-sans antialiased">
        <div class="min-h-screen" style="background: var(--cc-bg-main);">
            <div class="cc-page-wrapper">
                <div class="cc-content-container">
                    <div class="cc-card">

                        <div class="cc-card-header">
                            <div>
                                <h3 class="cc-title">
                                    Consulta de empresas
                                </h3>
                                <p class="cc-subtitle">
                                    Consulte información general de las empresas cliente registradas en CC-Flota.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('empresas.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="border border-gray-200 rounded-lg p-4 bg-white">
                                <div class="text-sm font-bold text-gray-500 uppercase tracking-wider">
                                    Total empresas
                                </div>
                                <div class="mt-2 text-3xl font-black text-gray-900">
                                    {{ $totalEmpresas }}
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-lg p-4 bg-white">
                                <div class="text-sm font-bold text-gray-500 uppercase tracking-wider">
                                    Activas
                                </div>
                                <div class="mt-2 text-3xl font-black text-green-700">
                                    {{ $empresasActivas }}
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-lg p-4 bg-white">
                                <div class="text-sm font-bold text-gray-500 uppercase tracking-wider">
                                    Inactivas
                                </div>
                                <div class="mt-2 text-3xl font-black text-red-800">
                                    {{ $empresasInactivas }}
                                </div>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('empresas.consulta.ventana') }}" class="mb-6">
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">

                                    <div class="lg:col-span-4 cc-field">
                                        <label for="nombre_comercial">
                                            Nombre comercial
                                        </label>
                                        <input
                                            id="nombre_comercial"
                                            name="nombre_comercial"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $nombreComercial }}"
                                            placeholder="Buscar por nombre comercial"
                                        >
                                    </div>

                                    <div class="lg:col-span-3 cc-field">
                                        <label for="nit">
                                            NIT
                                        </label>
                                        <input
                                            id="nit"
                                            name="nit"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $nit }}"
                                            maxlength="17"
                                            placeholder="0000-000000-000-0"
                                        >

                                        @error('nit')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="lg:col-span-2 cc-field">
                                        <label for="estado">
                                            Estado
                                        </label>
                                        <select id="estado" name="estado" class="cc-input">
                                            <option value="">Seleccione</option>
                                            <option value="activa" @selected($estado === 'activa')>
                                                Activas
                                            </option>
                                            <option value="inactiva" @selected($estado === 'inactiva')>
                                                Inactivas
                                            </option>
                                        </select>

                                        @error('estado')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="lg:col-span-3 flex items-center gap-3">
                                        <button type="submit" class="cc-btn-primary">
                                            Buscar
                                        </button>

                                        <a href="{{ route('empresas.consulta.ventana') }}" class="cc-btn-secondary">
                                            Resetear
                                        </a>
                                    </div>

                                </div>

                                <div class="mt-4 border-t border-gray-200 pt-4">
                                    <p class="text-sm text-gray-500 italic">
                                        Use al menos un criterio de búsqueda para mostrar empresas.
                                    </p>
                                </div>
                            </div>
                        </form>

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h4 class="text-base font-black text-gray-900">
                                    Resultados
                                </h4>

                                <p class="text-sm text-gray-500 italic">
                                    @if (! $hayFiltros)
                                        Ingrese un nombre comercial, NIT o seleccione un estado para consultar empresas.
                                    @elseif ($empresas->total() === 0)
                                        No se encontraron empresas con los criterios seleccionados.
                                    @elseif ($empresas->total() === 1)
                                        Se encontró 1 empresa.
                                    @else
                                        Se encontraron {{ $empresas->total() }} empresas.
                                    @endif
                                </p>
                            </div>

                            @if ($hayFiltros && $empresas->total() > 0)
                                <div class="text-sm text-gray-500">
                                    Mostrando
                                    <span class="font-bold text-gray-700">{{ $empresas->firstItem() }}</span>
                                    -
                                    <span class="font-bold text-gray-700">{{ $empresas->lastItem() }}</span>
                                    de
                                    <span class="font-bold text-gray-700">{{ $empresas->total() }}</span>
                                </div>
                            @endif
                        </div>

                        @if (! $hayFiltros)
                            <div class="border border-dashed border-gray-300 rounded-lg p-6 bg-gray-50">
                                <h5 class="text-base font-black text-gray-900">
                                    Consulta pendiente
                                </h5>
                                <p class="mt-1 text-sm text-gray-500 italic">
                                    La tabla permanecerá vacía hasta que realice una búsqueda o filtre por empresas activas o inactivas.
                                </p>
                            </div>
                        @elseif ($empresas->isEmpty())
                            <div class="border border-dashed border-gray-300 rounded-lg p-6 bg-gray-50">
                                <h5 class="text-base font-black text-gray-900">
                                    Sin resultados
                                </h5>
                                <p class="mt-1 text-sm text-gray-500 italic">
                                    No hay empresas que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-table-wrapper">
                                <table class="cc-table">
                                    <colgroup>
                                        <col style="width: 27%;">
                                        <col style="width: 16%;">
                                        <col style="width: 17%;">
                                        <col style="width: 14%;">
                                        <col style="width: 26%;">
                                    </colgroup>

                                    <thead>
                                        <tr>
                                            <th class="cc-text-left">Nombre legal</th>
                                            <th class="cc-text-left">NIT</th>
                                            <th class="cc-text-left">Contacto</th>
                                            <th class="cc-text-left">Teléfono</th>
                                            <th class="cc-text-left">Correo empresa</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($empresas as $empresa)
                                            <tr>
                                                <td class="cc-text-left cc-cell-truncate">
                                                    {{ $empresa->nombre_legal }}
                                                </td>

                                                <td class="cc-text-left">
                                                    {{ $empresa->nit }}
                                                </td>

                                                <td class="cc-text-left cc-cell-truncate">
                                                    {{ $empresa->poc_nombre }}
                                                </td>

                                                <td class="cc-text-left">
                                                    {{ $empresa->poc_telefono ?? '—' }}
                                                </td>

                                                <td class="cc-text-left cc-cell-truncate">
                                                    {{ $empresa->correo_empresa }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6">
                                {{ $empresas->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>

        <script>
            function formatNit(value) {
                const digits = value.replace(/\D/g, '').slice(0, 14);

                if (digits.length <= 4) {
                    return digits;
                }

                if (digits.length <= 10) {
                    return `${digits.slice(0, 4)}-${digits.slice(4)}`;
                }

                if (digits.length <= 13) {
                    return `${digits.slice(0, 4)}-${digits.slice(4, 10)}-${digits.slice(10)}`;
                }

                return `${digits.slice(0, 4)}-${digits.slice(4, 10)}-${digits.slice(10, 13)}-${digits.slice(13)}`;
            }

            const nitInput = document.getElementById('nit');

            if (nitInput) {
                nitInput.addEventListener('input', function () {
                    this.value = formatNit(this.value);
                });
            }
        </script>
    </body>
</html>