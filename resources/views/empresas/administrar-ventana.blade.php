<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar empresa | CC-Flota</title>

        
        @include('layouts.partials.favicon')
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
                                    Administrar empresa
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
                                    Localice una empresa cliente para consultar su ficha, editar sus datos o gestionar su estado administrativo.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('empresas.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver a Administrar
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="GET" action="{{ route('empresas.administrar.ventana') }}" class="mb-5">
                            <input type="hidden" name="consultar" value="1">
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
                                                        {{ $empresaOpcion->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <select id="empresa_id" name="empresa_id" class="cc-input" disabled>
                                                @foreach ($empresasSelector as $empresaOpcion)
                                                    <option value="{{ $empresaOpcion->id }}" selected>
                                                        {{ $empresaOpcion->nombre_legal }}
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
                                    Los resultados permanecerán vacíos hasta que localice una empresa por nombre o estado.
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
                                    @php
                                        $unidadesActivas = $empresa->unidades_activas_count
                                            ?? $empresa->unidadesActivas_count
                                            ?? null;

                                        $unidadesRegistradas = $empresa->unidades_registradas_count
                                            ?? $empresa->unidadesRegistradas_count
                                            ?? null;

                                        if (is_null($unidadesActivas) || is_null($unidadesRegistradas)) {
                                            $unidadesActivas = \App\Models\Unidad::query()
                                                ->where('empresa_id', $empresa->id)
                                                ->where('estado', 'activa')
                                                ->count();

                                            $unidadesRegistradas = \App\Models\Unidad::query()
                                                ->where('empresa_id', $empresa->id)
                                                ->where('estado', 'registrada')
                                                ->count();
                                        }
                                    @endphp

                                    <article class="cc-result-card cc-result-card-compact">
                                        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">

                                            <div class="flex-1 min-w-0">
                                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-start">

                                                    <div>
                                                        <div class="flex flex-wrap items-center gap-2">
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
                                                    </div>

                                                    <div>
                                                        <div class="cc-result-label">
                                                            Contacto
                                                        </div>

                                                        <div class="cc-result-value cc-cell-truncate">
                                                            {{ $empresa->poc_nombre ?: 'Sin contacto' }}
                                                        </div>

                                                        <div class="cc-result-value-muted">
                                                            {{ $empresa->poc_telefono ?: 'Sin teléfono' }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="cc-result-label">
                                                            Unidades
                                                        </div>

                                                        <div class="cc-result-value">
                                                            {{ $unidadesActivas }} activas
                                                        </div>

                                                        <div class="cc-result-value-muted">
                                                            {{ $unidadesRegistradas }} registradas
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 xl:justify-end xl:min-w-[15rem]">
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
                                {{ $empresas->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>