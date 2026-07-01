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
                <div class="cc-window-container" style="max-width: 80rem;">
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Administrar empresa
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
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

                        <form method="GET" action="{{ route('empresas.administrar.ventana') }}" class="mb-5">
                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Búsqueda administrativa
                                    </div>
                                </div>

                                <div class="cc-filter-inline-grid">

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

                                    <div class="cc-field">
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

                                    <div class="cc-filter-inline-actions">
                                        <button type="submit" class="cc-btn-primary">
                                            Consultar
                                        </button>

                                        <a href="{{ route('empresas.administrar.ventana') }}" class="cc-btn-secondary">
                                            Limpiar
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
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Búsqueda pendiente
                                </h5>
                                <p>
                                    Los resultados permanecerán vacíos hasta que localice una empresa por empresa, NIT o estado.
                                </p>
                            </div>
                        @elseif ($empresas->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>
                                <p>
                                    No hay empresas que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($empresas as $empresa)
                                    <article class="cc-result-card cc-result-card-compact">
                                        <div class="cc-result-grid">

                                            <div class="cc-result-main">
                                                <div class="cc-result-title-row">
                                                    <h5 class="cc-result-title cc-cell-truncate">
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

                                                <div class="cc-result-subtitle cc-cell-truncate">
                                                    {{ $empresa->nombre_comercial ?: 'Sin nombre comercial registrado' }}
                                                </div>
                                            </div>

                                            <div class="cc-result-meta">
                                                <div class="cc-result-label">
                                                    NIT
                                                </div>

                                                <div class="cc-result-value">
                                                    {{ $empresa->nit }}
                                                </div>
                                            </div>

                                            <div class="cc-result-meta">
                                                <div class="cc-result-label">
                                                    Contacto
                                                </div>

                                                <div class="cc-result-value cc-cell-truncate">
                                                    {{ $empresa->poc_nombre }}
                                                </div>

                                                <div class="cc-result-value-muted cc-cell-truncate">
                                                    {{ $empresa->poc_email }}
                                                </div>
                                            </div>

                                            <div class="cc-result-actions">
                                                <a href="{{ route('empresas.show.ventana', $empresa) }}" class="cc-btn-primary cc-btn-result">
                                                    Ver ficha
                                                </a>

                                                <a href="{{ route('empresas.edit.ventana', $empresa) }}" class="cc-btn-secondary cc-btn-result">
                                                    Editar
                                                </a>
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