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
            <div class="cc-window-wrapper" style="padding-top: 2.1rem;">
                <div class="cc-window-container" style="max-width: 80rem;">
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Consulta de empresas
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
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

                        <div class="cc-summary-strip">
                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Total empresas
                                </span>
                                <span class="cc-summary-strip-value">
                                    {{ $totalEmpresas }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Activas
                                </span>
                                <span class="cc-summary-strip-value cc-summary-strip-value-success">
                                    {{ $empresasActivas }}
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Inactivas
                                </span>
                                <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                                    {{ $empresasInactivas }}
                                </span>
                            </div>
                        </div>

                        <form method="GET" action="{{ route('empresas.consulta.ventana') }}" class="mb-5">
                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Filtros de consulta
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

                                        <a href="{{ route('empresas.consulta.ventana') }}" class="cc-btn-secondary">
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
                                    Consulta pendiente
                                </h5>
                                <p>
                                    Los resultados permanecerán vacíos hasta que realice una búsqueda.
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
                            <div class="cc-table-wrapper">
                                <table class="cc-table">
                                    <colgroup>
                                        <col style="width: 24%;">
                                        <col style="width: 13%;">
                                        <col style="width: 19%;">
                                        <col style="width: 13%;">
                                        <col style="width: 21%;">
                                        <col style="width: 10%;">
                                    </colgroup>

                                    <thead>
                                        <tr>
                                            <th class="cc-text-left">Nombre legal</th>
                                            <th class="cc-text-left">Estado</th>
                                            <th class="cc-text-left">Contacto</th>
                                            <th class="cc-text-left">Teléfono</th>
                                            <th class="cc-text-left">Correo empresa</th>
                                            <th class="cc-text-left">Unidades</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @foreach ($empresas as $empresa)
                                            @php
                                                $unidadesActivas = $empresa->unidades_activas_count
                                                    ?? $empresa->unidadesActivas_count
                                                    ?? null;

                                                $unidadesRegistradas = $empresa->unidades_registradas_count
                                                    ?? $empresa->unidadesRegistradas_count
                                                    ?? null;

                                                if (is_null($unidadesActivas) || is_null($unidadesRegistradas)) {
                                                    if (method_exists($empresa, 'unidades')) {
                                                        $unidadesActivas = \App\Models\Unidad::query()
                                                            ->where('empresa_id', $empresa->id)
                                                            ->where('estado', 'activa')
                                                            ->count();

                                                        $unidadesRegistradas = \App\Models\Unidad::query()
                                                            ->where('empresa_id', $empresa->id)
                                                            ->where('estado', 'registrada')
                                                            ->count();
                                                    } else {
                                                        $unidadesActivas = 0;
                                                        $unidadesRegistradas = 0;
                                                    }
                                                }
                                            @endphp

                                            <tr>
                                                <td class="cc-text-left cc-cell-truncate">
                                                    <span class="cc-table-strong">
                                                        {{ $empresa->nombre_legal }}
                                                    </span>

                                                    @if ($empresa->nombre_comercial && $empresa->nombre_comercial !== $empresa->nombre_legal)
                                                        <div class="text-xs text-[var(--cc-text-muted)]">
                                                            {{ $empresa->nombre_comercial }}
                                                        </div>
                                                    @endif
                                                </td>

                                                <td class="cc-text-left">
                                                    @if ($empresa->estado === 'activa')
                                                        <span class="cc-badge cc-badge-active">
                                                            Activa
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-inactive">
                                                            Inactiva
                                                        </span>
                                                    @endif
                                                </td>

                                                <td class="cc-text-left cc-cell-truncate">
                                                    {{ $empresa->poc_nombre ?: '—' }}
                                                </td>

                                                <td class="cc-text-left">
                                                    {{ $empresa->poc_telefono ?? '—' }}
                                                </td>

                                                <td class="cc-text-left cc-cell-truncate">
                                                    {{ $empresa->correo_empresa ?: '—' }}
                                                </td>

                                                <td class="cc-text-left">
                                                    <div class="font-bold text-[var(--cc-text-main)]">
                                                        {{ $unidadesActivas }} activas
                                                    </div>

                                                    <div class="text-xs text-[var(--cc-text-muted)]">
                                                        {{ $unidadesRegistradas }} registradas
                                                    </div>
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