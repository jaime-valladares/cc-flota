<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 80rem;">
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Consulta de puntos de ruta
                        </h3>
                        <p class="cc-subtitle cc-subtitle-compact">
                            Consulte los puntos operativos disponibles como origen o destino para abastecimientos.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('puntos-ruta.consulta.ventana') }}"
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

                <div class="cc-summary-strip">
                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Total puntos
                        </span>
                        <span class="cc-summary-strip-value">
                            {{ $totalPuntosRuta }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Activos
                        </span>
                        <span class="cc-summary-strip-value cc-summary-strip-value-success">
                            {{ $puntosRutaActivos }}
                        </span>
                    </div>

                    <div class="cc-summary-strip-item">
                        <span class="cc-summary-strip-label">
                            Inactivos
                        </span>
                        <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                            {{ $puntosRutaInactivos }}
                        </span>
                    </div>
                </div>

                <form method="GET" action="{{ route('puntos-ruta.index') }}" class="mb-5">
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
                                <label for="punto_ruta_id">
                                    Punto de Ruta
                                </label>

                                <select id="punto_ruta_id" name="punto_ruta_id" class="cc-input">
                                    <option value="">Todos</option>

                                    @foreach ($puntosRutaSelector as $puntoRutaOpcion)
                                        <option
                                            value="{{ $puntoRutaOpcion->id }}"
                                            data-empresa-id="{{ $puntoRutaOpcion->empresa_id }}"
                                            @selected((string) $puntoRutaId === (string) $puntoRutaOpcion->id)
                                        >
                                            {{ $puntoRutaOpcion->nombre }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('punto_ruta_id')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="flex items-end gap-3">
                                <button type="submit" class="cc-btn-primary">
                                    Consultar
                                </button>

                                <a href="{{ route('puntos-ruta.index') }}" class="cc-btn-secondary">
                                    Limpiar
                                </a>
                            </div>

                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $puntosRuta->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $puntosRuta->firstItem() }}</span>
                        -
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $puntosRuta->lastItem() }}</span>
                        de
                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $puntosRuta->total() }}</span>
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
                @elseif ($puntosRuta->isEmpty())
                    <div class="cc-empty-panel cc-empty-panel-compact">
                        <h5>
                            Sin resultados
                        </h5>
                        <p>
                            No hay puntos de ruta que coincidan con los criterios seleccionados.
                        </p>
                    </div>
                @else
                    <div class="cc-table-wrapper">
                        <table class="cc-table">
                            <colgroup>
                                <col style="width: 26%;">
                                <col style="width: 26%;">
                                <col style="width: 34%;">
                                <col style="width: 14%;">
                            </colgroup>

                            <thead>
                                <tr>
                                    <th class="cc-text-left">Empresa</th>
                                    <th class="cc-text-left">Punto de Ruta</th>
                                    <th class="cc-text-left">Dirección</th>
                                    <th class="cc-text-left">Estado</th>
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($puntosRuta as $puntoRuta)
                                    <tr>
                                        <td class="cc-text-left cc-cell-truncate">
                                            {{ $puntoRuta->empresa->nombre_comercial ?: $puntoRuta->empresa->nombre_legal }}
                                        </td>

                                        <td class="cc-text-left cc-cell-truncate">
                                            <span class="cc-table-strong">
                                                {{ $puntoRuta->nombre }}
                                            </span>
                                        </td>

                                        <td class="cc-text-left">
                                            <span style="white-space: normal; line-height: 1.45;">
                                                {{ $puntoRuta->direccion }}
                                            </span>
                                        </td>

                                        <td class="cc-text-left">
                                            @if ($puntoRuta->estado === 'activo')
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
                        {{ $puntosRuta->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                    </div>
                @endif

            </div>
        </div>
    </div>

    <script>
        const empresaSelect = document.getElementById('empresa_id');
        const puntoRutaSelect = document.getElementById('punto_ruta_id');

        function filtrarPuntosRutaPorEmpresa() {
            if (!empresaSelect || !puntoRutaSelect) {
                return;
            }

            const empresaId = empresaSelect.value;
            const selectedOption = puntoRutaSelect.options[puntoRutaSelect.selectedIndex];

            Array.from(puntoRutaSelect.options).forEach(function (option) {
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
                puntoRutaSelect.value = '';
            }
        }

        if (empresaSelect && puntoRutaSelect) {
            empresaSelect.addEventListener('change', filtrarPuntosRutaPorEmpresa);
            filtrarPuntosRutaPorEmpresa();
        }
    </script>
</x-app-layout>