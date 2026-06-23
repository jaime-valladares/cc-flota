<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar empresa | CC-Flota</title>

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
                                    Administrar empresa
                                </h3>
                                <p class="cc-subtitle">
                                    Localice una empresa cliente para consultar su ficha, editar sus datos o gestionar su estado administrativo.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('empresas.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="GET" action="{{ route('empresas.administrar.ventana') }}" class="mb-6">
                            <div class="cc-filter-panel">

                                <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
                                    <div class="cc-form-section-title">
                                        Búsqueda administrativa
                                    </div>
                                    <div class="cc-form-section-note">
                                        Ingrese criterios para localizar la empresa que desea administrar.
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
                                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                        <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                            @if ($esUsuarioDieselCop)
                                                Seleccione una empresa, use Todas o agregue otro criterio para localizar empresas.
                                            @else
                                                La búsqueda administrativa está limitada automáticamente a la empresa asignada a su usuario.
                                            @endif
                                        </p>

                                        <div class="flex items-center gap-3">
                                            <button type="submit" class="cc-btn-primary">
                                                Buscar
                                            </button>

                                            <a href="{{ route('empresas.administrar.ventana') }}" class="cc-btn-secondary">
                                                Resetear
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <div class="cc-section-heading">
                            <div>
                                <h4 class="cc-section-title">
                                    Resultado administrativo
                                </h4>

                                <p class="cc-section-note">
                                    @if (! $hayFiltros)
                                        Seleccione una empresa, NIT o estado para buscar una empresa.
                                    @elseif ($empresas->total() === 0)
                                        No se encontraron empresas con los criterios seleccionados.
                                    @elseif ($empresas->total() === 1)
                                        Se encontró 1 empresa para administrar.
                                    @else
                                        Se encontraron {{ $empresas->total() }} empresas para administrar.
                                    @endif
                                </p>
                            </div>

                            @if ($hayFiltros && $empresas->total() > 0)
                                <div class="text-sm text-[var(--cc-text-muted)]">
                                    Mostrando
                                    <span class="font-bold text-[var(--cc-text-main)]">{{ $empresas->firstItem() }}</span>
                                    -
                                    <span class="font-bold text-[var(--cc-text-main)]">{{ $empresas->lastItem() }}</span>
                                    de
                                    <span class="font-bold text-[var(--cc-text-main)]">{{ $empresas->total() }}</span>
                                </div>
                            @endif
                        </div>

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