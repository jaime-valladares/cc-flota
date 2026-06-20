<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container">
            <div class="cc-card">

                <div class="cc-card-header">
                    <div>
                        <h3 class="cc-title">
                            Administrar empresa
                        </h3>
                        <p class="cc-subtitle">
                            Localice una empresa cliente para consultar su ficha, editar sus datos o gestionar su estado administrativo.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('empresas.administrar.ventana') }}"
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

                <form method="GET" action="{{ route('empresas.administrar') }}" class="mb-6">
                    <div class="cc-filter-panel">

                        <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
                            <div class="cc-form-section-title">
                                Búsqueda administrativa
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

                                @error('empresa_id')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
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

                                <a href="{{ route('empresas.administrar') }}" class="cc-btn-secondary">
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
                            Búsqueda pendiente
                        </h5>
                        <p>
                            Los resultados permanecerán vacíos hasta que localice una empresa por empresa, NIT o estado.
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
                    <div class="space-y-4">
                        @foreach ($empresas as $empresa)
                            <article class="cc-result-card">
                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">

                                    <div class="lg:col-span-4 min-w-0">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <h5 class="font-[var(--cc-font-heading)] text-xl font-extrabold text-[var(--cc-text-heading)] tracking-[-0.03em] cc-cell-truncate">
                                                {{ $empresa->nombre_legal }}
                                            </h5>

                                            @if ($empresa->estado === 'activa')
                                                <span class="cc-badge cc-badge-active">
                                                    Activa
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Inactiva
                                                </span>
                                            @endif
                                        </div>

                                        <div class="mt-1 text-sm font-medium text-[var(--cc-text-muted)] cc-cell-truncate">
                                            {{ $empresa->nombre_comercial ?: 'Sin nombre comercial registrado' }}
                                        </div>
                                    </div>

                                    <div class="lg:col-span-2 min-w-0">
                                        <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                            NIT
                                        </div>

                                        <div class="mt-1 font-bold text-[var(--cc-text-main)]">
                                            {{ $empresa->nit }}
                                        </div>
                                    </div>

                                    <div class="lg:col-span-3 min-w-0">
                                        <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                            Contacto
                                        </div>

                                        <div class="mt-1 font-bold text-[var(--cc-text-main)] cc-cell-truncate">
                                            {{ $empresa->poc_nombre }}
                                        </div>

                                        <div class="text-sm font-medium text-[var(--cc-text-muted)] cc-cell-truncate">
                                            {{ $empresa->poc_email }}
                                        </div>
                                    </div>

                                    <div class="lg:col-span-3">
                                        <div class="flex items-center justify-end gap-3">
                                            <a href="{{ route('empresas.show', $empresa) }}" class="cc-btn-primary">
                                                Ver ficha
                                            </a>

                                            <a href="{{ route('empresas.edit', $empresa) }}" class="cc-btn-secondary">
                                                Editar
                                            </a>
                                        </div>
                                    </div>

                                </div>
                            </article>
                        @endforeach
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