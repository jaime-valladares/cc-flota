<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Consulta de empresas | CC-Flota</title>

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
                                    Consulta de empresas
                                </h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('empresas.index') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver a Consulta
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
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">
                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Filtros de consulta
                                    </div>
                                </div>

                                <div class="cc-standard-filter-grid">

                                    <div class="cc-field">
                                        <label for="busqueda">
                                            Búsqueda de Empresa por Nombre
                                        </label>

                                        <input
                                            id="busqueda"
                                            name="busqueda"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $busqueda ?? '' }}"
                                            maxlength="150"
                                            placeholder="Nombre legal de la empresa"
                                        >

                                        @error('busqueda')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="empresa_multiselect_button">
                                            Empresa
                                        </label>

                                        @if ($esUsuarioDieselCop)
                                            @php
                                                $empresaIdsSeleccionadas = collect($empresaIds ?? [])
                                                    ->map(fn ($id) => (string) $id)
                                                    ->all();

                                                $totalEmpresasSelector = $empresasSelector->count();
                                                $totalEmpresasSeleccionadas = count($empresaIdsSeleccionadas);

                                                if ($totalEmpresasSeleccionadas === 0 || $totalEmpresasSeleccionadas === $totalEmpresasSelector) {
                                                    $textoEmpresasSeleccionadas = 'Todas';
                                                } elseif ($totalEmpresasSeleccionadas === 1) {
                                                    $empresaSeleccionada = $empresasSelector->firstWhere('id', (int) $empresaIdsSeleccionadas[0]);

                                                    $textoEmpresasSeleccionadas = $empresaSeleccionada
                                                        ? $empresaSeleccionada->nombre_legal
                                                        : '1 seleccionada';
                                                } else {
                                                    $textoEmpresasSeleccionadas = $totalEmpresasSeleccionadas . ' seleccionadas';
                                                }
                                            @endphp

                                            <div
                                                class="cc-filter-multiselect"
                                                data-cc-filter-multiselect
                                                data-all-text="Todas"
                                                data-singular-text="seleccionada"
                                                data-plural-text="seleccionadas"
                                            >
                                                <button
                                                    id="empresa_multiselect_button"
                                                    type="button"
                                                    class="cc-filter-multiselect-toggle"
                                                    data-cc-filter-toggle
                                                >
                                                    <span data-cc-filter-label>
                                                        {{ $textoEmpresasSeleccionadas }}
                                                    </span>

                                                    <span class="cc-filter-multiselect-arrow">
                                                        ⌄
                                                    </span>
                                                </button>

                                                <div class="cc-filter-multiselect-menu" data-cc-filter-menu>

                                                    <label class="cc-filter-multiselect-option cc-filter-multiselect-option-master">
                                                        <input
                                                            type="checkbox"
                                                            data-cc-filter-check-all
                                                            @checked($totalEmpresasSeleccionadas > 0 && $totalEmpresasSeleccionadas === $totalEmpresasSelector)
                                                        >

                                                        <span>
                                                            Seleccionar todo
                                                        </span>
                                                    </label>

                                                    <div class="cc-filter-multiselect-list" data-cc-filter-options>
                                                        @foreach ($empresasSelector as $empresaOpcion)
                                                            @php
                                                                $empresaOpcionId = (string) $empresaOpcion->id;
                                                                $empresaOpcionTexto = $empresaOpcion->nombre_legal;

                                                                $empresaOpcionSeleccionada = in_array($empresaOpcionId, $empresaIdsSeleccionadas, true);

                                                                $empresaTextoBusqueda = \Illuminate\Support\Str::lower($empresaOpcionTexto);
                                                            @endphp

                                                            <label
                                                                class="cc-filter-multiselect-option"
                                                                data-cc-filter-option
                                                                data-cc-filter-text="{{ $empresaTextoBusqueda }}"
                                                            >
                                                                <input
                                                                    type="checkbox"
                                                                    name="empresa_ids[]"
                                                                    value="{{ $empresaOpcion->id }}"
                                                                    data-cc-filter-checkbox
                                                                    @checked($empresaOpcionSeleccionada)
                                                                >

                                                                <span data-cc-filter-option-label>
                                                                    {{ $empresaOpcionTexto }}
                                                                </span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>

                                            @error('empresa_ids')
                                                <div class="cc-error">
                                                    {{ $message }}
                                                </div>
                                            @enderror

                                            @error('empresa_ids.*')
                                                <div class="cc-error">
                                                    {{ $message }}
                                                </div>
                                            @enderror
                                        @else
                                            <select id="empresa_id" name="empresa_id" class="cc-input" disabled>
                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option value="{{ $empresaOpcion->id }}" selected>
                                                        {{ $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    <div class="cc-field">
                                        <label for="estado">
                                            Estado
                                        </label>

                                        <select id="estado" name="estado" class="cc-input">
                                            <option value="">Todos</option>
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

                                    <div class="cc-standard-filter-actions">
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
                            <div class="cc-table-adaptive-wrapper">
                                <table class="cc-table-adaptive" style="min-width: 78rem;">
                                    <thead>
                                        <tr>
                                            <th style="width: 26%;">Nombre legal</th>
                                            <th style="width: 20%;">Nombre comercial</th>
                                            <th style="width: 20%;">Contacto</th>
                                            <th style="width: 16%;">Teléfono</th>
                                            <th style="width: 18%;">Unidades</th>
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
                                                <td class="cc-table-adaptive-nowrap">
                                                    <div class="cc-table-adaptive-strong">
                                                        {{ $empresa->nombre_legal }}
                                                    </div>
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    {{ $empresa->nombre_comercial ?: '—' }}
                                                </td>

                                                <td class="cc-table-adaptive-break">
                                                    <div class="cc-table-adaptive-strong">
                                                        {{ $empresa->poc_nombre ?: '—' }}
                                                    </div>

                                                    @if ($empresa->correo_empresa)
                                                        <div class="cc-table-adaptive-muted">
                                                            {{ $empresa->correo_empresa }}
                                                        </div>
                                                    @endif
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    {{ $empresa->poc_telefono ?: $empresa->telefono_empresa ?: '—' }}
                                                </td>

                                                <td class="cc-table-adaptive-nowrap">
                                                    <div class="cc-table-adaptive-strong">
                                                        {{ $unidadesActivas }} activas
                                                    </div>

                                                    <div class="cc-table-adaptive-muted">
                                                        {{ $unidadesRegistradas }} registradas
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-6">
                                {{ $empresas->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>