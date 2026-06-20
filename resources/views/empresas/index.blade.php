<x-app-layout>
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
                        <a href="{{ route('empresas.consulta.ventana') }}"
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

                <div class="cc-metric-grid">
                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Total empresas
                        </div>
                        <div class="cc-metric-value">
                            {{ $totalEmpresas }}
                        </div>
                    </div>

                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Activas
                        </div>
                        <div class="cc-metric-value cc-metric-value-success">
                            {{ $empresasActivas }}
                        </div>
                    </div>

                    <div class="cc-metric-card">
                        <div class="cc-metric-label">
                            Inactivas
                        </div>
                        <div class="cc-metric-value cc-metric-value-danger">
                            {{ $empresasInactivas }}
                        </div>
                    </div>
                </div>

                <form method="GET" action="{{ route('empresas.index') }}" class="mb-6">
                    <div class="cc-filter-panel">

                        <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
                            <div class="cc-form-section-title">
                                Filtros de consulta
                            </div>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">

                            <div class="lg:col-span-5 cc-field">
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

                            <div class="lg:col-span-4 cc-field">
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

                        </div>

                        <div class="mt-5 border-t border-[var(--cc-card-border)] pt-5">
                            <div class="flex items-center justify-end gap-3">
                                <button type="submit" class="cc-btn-primary">
                                    Buscar
                                </button>

                                <a href="{{ route('empresas.index') }}" class="cc-btn-secondary">
                                    Resetear
                                </a>
                            </div>
                        </div>
                    </div>
                </form>

                @if ($hayFiltros && $empresas->total() > 0)
                    <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                        Mostrando
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $empresas->firstItem() }}</span>
                        -
                        <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $empresas->lastItem() }}</span>
                        de
                        <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $empresas->total() }}</span>
                    </div>
                @endif

                @if (! $hayFiltros)
                    <div class="cc-empty-panel">
                        <h5>
                            Consulta pendiente
                        </h5>
                        <p>
                            Los resultados permanecerán vacíos hasta que realice una búsqueda.
                        </p>
                    </div>
                @elseif ($empresas->isEmpty())
                    <div class="cc-empty-panel">
                        <h5>
                            Sin resultados
                        </h5>
                        <p>
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
</x-app-layout>