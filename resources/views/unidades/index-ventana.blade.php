<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Consulta unidades | CC-Flota</title>

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
                                    Consulta unidades
                                </h3>
                                <p class="cc-subtitle">
                                    Consulte las unidades registradas en el sistema. Esta pantalla es únicamente informativa.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('unidades.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <div class="text-sm text-gray-500 font-semibold">
                                    Total unidades
                                </div>
                                <div class="text-2xl font-black text-gray-900">
                                    {{ $totalUnidades }}
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <div class="text-sm text-gray-500 font-semibold">
                                    Activas
                                </div>
                                <div class="text-2xl font-black text-gray-900">
                                    {{ $totalActivas }}
                                </div>
                            </div>

                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                                <div class="text-sm text-gray-500 font-semibold">
                                    Inactivas
                                </div>
                                <div class="text-2xl font-black text-gray-900">
                                    {{ $totalInactivas }}
                                </div>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('unidades.consulta.ventana') }}" class="mb-6">
                            <input type="hidden" name="consultar" value="1">

                            <div class="border border-gray-200 rounded-lg p-5 bg-gray-50">

                                <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
                                    <div class="cc-form-section-title">
                                        Filtros de consulta
                                    </div>
                                    <div class="cc-form-section-note">
                                        Utilice los filtros para localizar unidades por empresa, placa, estado o modelo de medición.
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">

                                    <div class="lg:col-span-3 cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>
                                        <select id="empresa_id" name="empresa_id" class="cc-input">
                                            <option value="">Todas</option>

                                            @foreach ($empresas as $empresa)
                                                <option value="{{ $empresa->id }}"
                                                        @selected((string) $empresaId === (string) $empresa->id)>
                                                    {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="lg:col-span-2 cc-field">
                                        <label for="placa">
                                            Placa
                                        </label>
                                        <input id="placa"
                                               type="text"
                                               name="placa"
                                               value="{{ $placa }}"
                                               class="cc-input"
                                               placeholder="Ej. C123ABC">
                                    </div>

                                    <div class="lg:col-span-2 cc-field">
                                        <label for="estado">
                                            Estado
                                        </label>
                                        <select id="estado" name="estado" class="cc-input">
                                            <option value="">Todos</option>
                                            <option value="activo" @selected($estado === 'activo')>
                                                Activo
                                            </option>
                                            <option value="inactivo" @selected($estado === 'inactivo')>
                                                Inactivo
                                            </option>
                                        </select>
                                    </div>

                                    <div class="lg:col-span-3 cc-field">
                                        <label for="modelo_medicion">
                                            Modelo de medición
                                        </label>
                                        <select id="modelo_medicion" name="modelo_medicion" class="cc-input">
                                            <option value="">Todos</option>

                                            @foreach ($modelosMedicion as $valor => $texto)
                                                <option value="{{ $valor }}" @selected($modeloMedicion === $valor)>
                                                    {{ $texto }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="lg:col-span-2 flex items-center gap-3">
                                        <button type="submit" class="cc-btn-primary">
                                            Buscar
                                        </button>

                                        <a href="{{ route('unidades.consulta.ventana') }}" class="cc-btn-secondary">
                                            Resetear
                                        </a>
                                    </div>

                                </div>

                                <div class="mt-4 border-t border-gray-200 pt-4">
                                    <p class="text-sm text-gray-500 italic">
                                        La consulta permite visualizar unidades, sin modificar información.
                                    </p>
                                </div>
                            </div>
                        </form>

                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <h4 class="text-base font-black text-gray-900">
                                    Resultado de consulta
                                </h4>

                                <p class="text-sm text-gray-500 italic">
                                    @if (! $hayFiltros)
                                        Seleccione filtros para consultar unidades.
                                    @elseif ($unidades->total() === 0)
                                        No se encontraron unidades con los criterios seleccionados.
                                    @elseif ($unidades->total() === 1)
                                        Se encontró 1 unidad.
                                    @else
                                        Se encontraron {{ $unidades->total() }} unidades.
                                    @endif
                                </p>
                            </div>

                            @if ($hayFiltros && $unidades->total() > 0)
                                <div class="text-sm text-gray-500">
                                    Mostrando
                                    <span class="font-bold text-gray-700">{{ $unidades->firstItem() }}</span>
                                    -
                                    <span class="font-bold text-gray-700">{{ $unidades->lastItem() }}</span>
                                    de
                                    <span class="font-bold text-gray-700">{{ $unidades->total() }}</span>
                                </div>
                            @endif
                        </div>

                        @if (! $hayFiltros)
                            <div class="border border-dashed border-gray-300 rounded-lg p-6 bg-gray-50">
                                <h5 class="text-base font-black text-gray-900">
                                    Consulta pendiente
                                </h5>
                                <p class="mt-1 text-sm text-gray-500 italic">
                                    Los resultados permanecerán vacíos hasta que realice una búsqueda.
                                </p>
                            </div>
                        @elseif ($unidades->isEmpty())
                            <div class="border border-dashed border-gray-300 rounded-lg p-6 bg-gray-50">
                                <h5 class="text-base font-black text-gray-900">
                                    Sin resultados
                                </h5>
                                <p class="mt-1 text-sm text-gray-500 italic">
                                    No hay unidades que coincidan con los filtros seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($unidades as $unidad)
                                    <article class="border border-gray-200 rounded-xl bg-white p-5 shadow-sm">
                                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">

                                            <div class="lg:col-span-3 min-w-0">
                                                <div class="flex items-center gap-3 min-w-0">
                                                    <h5 class="text-xl font-black text-gray-900 cc-cell-truncate">
                                                        {{ $unidad->placa }}
                                                    </h5>

                                                    @if ($unidad->estado === 'activo')
                                                        <span class="cc-badge cc-badge-active">
                                                            Activo
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-inactive">
                                                            Inactivo
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="mt-1 text-sm text-gray-500 cc-cell-truncate">
                                                    {{ $unidad->marca ?: 'Sin marca registrada' }}
                                                </div>
                                            </div>

                                            <div class="lg:col-span-2 min-w-0">
                                                <div class="text-xs font-black text-gray-500 uppercase tracking-wider">
                                                    Empresa
                                                </div>

                                                @if ($unidad->empresa)
                                                    <div class="mt-1 font-bold text-gray-900 cc-cell-truncate">
                                                        {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                    </div>
                                                @else
                                                    <div class="mt-1 text-sm text-gray-500 italic">
                                                        Sin empresa
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="lg:col-span-2">
                                                <div class="text-xs font-black text-gray-500 uppercase tracking-wider">
                                                    Tanques
                                                </div>

                                                <div class="mt-1 font-bold text-gray-900">
                                                    {{ $unidad->total_tanques }} totales
                                                </div>
                                            </div>

                                            <div class="lg:col-span-2">
                                                <div class="text-xs font-black text-gray-500 uppercase tracking-wider">
                                                    Cobertura
                                                </div>

                                                <div class="mt-1 font-bold text-gray-900">
                                                    {{ $unidad->cantidad_tanques_con_licencia }} de {{ $unidad->total_tanques }} tanques
                                                </div>
                                            </div>

                                            <div class="lg:col-span-3 min-w-0">
                                                <div class="text-xs font-black text-gray-500 uppercase tracking-wider">
                                                    Modelo de medición
                                                </div>

                                                <div class="mt-1 font-bold text-gray-900 cc-cell-truncate">
                                                    {{ $unidad->modelo_medicion_texto }}
                                                </div>
                                            </div>

                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                {{ $unidades->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>