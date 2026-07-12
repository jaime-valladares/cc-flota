<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar gasolineras externas | CC-Flota</title>

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
                                    Administrar gasolineras externas
                                </h3>

                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('gasolineras-externas.administrar', request()->query()) }}"
                                   class="cc-btn-secondary cc-btn-wide">
                                    Volver a Administrar
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="GET" action="{{ route('gasolineras-externas.administrar.ventana') }}" class="mb-5">
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Filtros de consulta
                                    </div>
                                </div>

                                <div class="cc-standard-filter-grid cc-unidades-consulta-filter-grid">

                                    <div class="cc-field">
                                        <label for="busqueda_empresa">
                                            Buscar empresa
                                        </label>

                                        <input
                                            id="busqueda_empresa"
                                            name="busqueda_empresa"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $busquedaEmpresa ?? '' }}"
                                            maxlength="150"
                                            placeholder="Nombre legal o comercial"
                                        >

                                        @error('busqueda_empresa')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>

                                        @if ($esUsuarioDieselCop)
                                            <select id="empresa_id" name="empresa_id" class="cc-input">
                                                <option value="">Todas</option>

                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option value="{{ $empresaOpcion->id }}"
                                                            @selected((string) ($empresaId ?? '') === (string) $empresaOpcion->id)>
                                                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <select id="empresa_id_visible" class="cc-input" disabled>
                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option value="{{ $empresaOpcion->id }}" selected>
                                                        {{ $empresaOpcion->nombre_comercial ?: $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <input type="hidden" name="empresa_id" value="{{ $empresaId }}">
                                        @endif

                                        @error('empresa_id')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="compania">
                                            Buscar gasolinera
                                        </label>

                                        <input
                                            id="compania"
                                            name="compania"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $compania ?? '' }}"
                                            maxlength="150"
                                            placeholder="Compañía"
                                        >

                                        @error('compania')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-field">
                                        <label for="gasolinera_externa_id">
                                            Gasolinera
                                        </label>

                                        <select id="gasolinera_externa_id" name="gasolinera_externa_id" class="cc-input">
                                            <option value="">Todas</option>

                                            @foreach ($gasolinerasExternasSelector as $gasolineraOpcion)
                                                <option value="{{ $gasolineraOpcion->id }}"
                                                        @selected((string) ($gasolineraExternaId ?? '') === (string) $gasolineraOpcion->id)>
                                                    {{ $gasolineraOpcion->compania }} — {{ $gasolineraOpcion->direccion }}
                                                </option>
                                            @endforeach
                                        </select>

                                        @error('gasolinera_externa_id')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>

                                    <div class="cc-standard-filter-actions">
                                        <button type="submit" class="cc-btn-primary">
                                            Consultar
                                        </button>

                                        <a href="{{ route('gasolineras-externas.administrar.ventana') }}" class="cc-btn-secondary">
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $gasolinerasExternas->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $gasolinerasExternas->firstItem() }}</span>
                                -
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $gasolinerasExternas->lastItem() }}</span>
                                de
                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $gasolinerasExternas->total() }}</span>
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
                        @elseif ($gasolinerasExternas->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>

                                <p>
                                    No hay gasolineras externas que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-table-adaptive-wrapper">
                                <div class="cc-admin-result-list" style="min-width: 82rem;">
                                    @foreach ($gasolinerasExternas as $gasolineraExterna)
                                        <article class="cc-admin-result-card" style="min-width: 82rem; box-sizing: border-box;">
                                            <div style="display: grid; grid-template-columns: 14rem 13rem minmax(34rem, 1fr) 14rem; gap: 1rem; align-items: center;">

                                                <div style="min-width: 0;">
                                                    <div class="flex items-center gap-2" style="white-space: nowrap;">
                                                        <h5 class="cc-admin-result-title" style="margin: 0;">
                                                            {{ $gasolineraExterna->compania }}
                                                        </h5>

                                                        @if ($gasolineraExterna->estado === 'activa')
                                                            <span class="cc-badge cc-badge-active">
                                                                Activa
                                                            </span>
                                                        @else
                                                            <span class="cc-badge cc-badge-inactive">
                                                                Inactiva
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <div class="cc-admin-result-subtitle" style="white-space: nowrap;">
                                                        Gasolinera externa
                                                    </div>
                                                </div>

                                                <div style="min-width: 0;">
                                                    <div class="cc-admin-result-label">
                                                        Empresa
                                                    </div>

                                                    <div class="cc-admin-result-value" style="white-space: nowrap;">
                                                        {{ $gasolineraExterna->empresa->nombre_comercial ?: $gasolineraExterna->empresa->nombre_legal }}
                                                    </div>
                                                </div>

                                                <div style="min-width: 0;">
                                                    <div class="cc-admin-result-label">
                                                        Dirección
                                                    </div>

                                                    <div class="cc-admin-result-value" style="white-space: nowrap;">
                                                        {{ $gasolineraExterna->direccion }}
                                                    </div>
                                                </div>

                                                <div style="display: flex; gap: .75rem; justify-content: flex-end; align-items: center; white-space: nowrap; min-width: 0;">
                                                    <a href="{{ route('gasolineras-externas.show.ventana', $gasolineraExterna) }}"
                                                       class="cc-btn-secondary cc-btn-result">
                                                        Ver ficha
                                                    </a>

                                                    <a href="{{ route('gasolineras-externas.edit.ventana', $gasolineraExterna) }}"
                                                       class="cc-btn-primary cc-btn-result">
                                                        Editar
                                                    </a>
                                                </div>

                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </div>

                            <div class="mt-6">
                                {{ $gasolinerasExternas->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>