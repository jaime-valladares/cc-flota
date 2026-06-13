<x-app-layout>
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
                        <a href="{{ route('unidades.consulta.ventana') }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="cc-btn-secondary cc-btn-wide">
                            Abrir en nueva pestaña
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

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

                <form method="GET" action="{{ route('unidades.index') }}" class="mb-6">
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

                            <div class="lg:col-span-2 cc-field">
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

                            <div class="lg:col-span-3 flex items-center gap-3">
                                <button type="submit" class="cc-btn-primary">
                                    Buscar
                                </button>

                                <a href="{{ route('unidades.index') }}" class="cc-btn-secondary">
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
                    <div class="cc-table-wrapper">
                        <table class="cc-table">
                            <colgroup>
                                <col style="width: 18%;">
                                <col style="width: 24%;">
                                <col style="width: 14%;">
                                <col style="width: 14%;">
                                <col style="width: 18%;">
                                <col style="width: 12%;">
                            </colgroup>

                            <thead>
                                <tr>
                                    <th class="cc-text-left">Placa</th>
                                    <th class="cc-text-left">Empresa</th>
                                    <th class="cc-text-center">Tanques</th>
                                    <th class="cc-text-center">Cobertura</th>
                                    <th class="cc-text-left">Modelo medición</th>
                                    <th class="cc-text-center">Estado</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($unidades as $unidad)
                                    <tr>
                                        <td class="cc-text-left">
                                            <div class="font-bold text-gray-900 cc-cell-truncate">
                                                {{ $unidad->placa }}
                                            </div>
                                            <div class="text-sm text-gray-500 cc-cell-truncate">
                                                {{ $unidad->marca ?: 'Sin marca registrada' }}
                                            </div>
                                        </td>

                                        <td class="cc-text-left">
                                            @if ($unidad->empresa)
                                                <div class="font-bold text-gray-900 cc-cell-truncate">
                                                    {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    {{ $unidad->empresa->nit }}
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-500 italic">
                                                    Sin empresa
                                                </span>
                                            @endif
                                        </td>

                                        <td class="cc-text-center">
                                            <div class="font-bold text-gray-900">
                                                {{ $unidad->total_tanques }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                totales
                                            </div>
                                        </td>

                                        <td class="cc-text-center">
                                            <div class="font-bold text-gray-900">
                                                {{ $unidad->cantidad_tanques_con_licencia }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                protegidos
                                            </div>
                                        </td>

                                        <td class="cc-text-left">
                                            <div class="font-bold text-gray-900 cc-cell-truncate">
                                                {{ $unidad->modelo_medicion_texto }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ number_format((float) $unidad->capacidad_cubierta, 2) }} galones cubiertos
                                            </div>
                                        </td>

                                        <td class="cc-text-center">
                                            @if ($unidad->estado === 'activo')
                                                <span class="cc-badge cc-badge-active">
                                                    Activo
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Inactivo
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6">
                        {{ $unidades->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>