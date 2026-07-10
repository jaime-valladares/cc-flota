<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar licencia | CC-Flota</title>

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
                                    Administrar licencia
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
                                    Localice una licencia para consultar su ficha, editar su vigencia o gestionar su estado.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('licencias.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver a administrar
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="GET" action="{{ route('licencias.administrar.ventana') }}" class="mb-5">
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact cc-filter-panel-inline">

                                <div class="cc-form-section cc-form-section-compact" style="margin-top: 0;">
                                    <div class="cc-form-section-title">
                                        Filtros de administración
                                    </div>
                                </div>

                                <div class="cc-standard-filter-grid cc-unidades-consulta-filter-grid">

                                    <div class="cc-field">
                                        <label for="busqueda">
                                            Buscar empresa o placa
                                        </label>

                                        <input
                                            id="busqueda"
                                            name="busqueda"
                                            type="text"
                                            class="cc-input"
                                            value="{{ $busqueda ?? '' }}"
                                            maxlength="150"
                                            placeholder="Empresa o placa"
                                        >
                                    </div>

                                    <div class="cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>

                                        @if ($esUsuarioDieselCop)
                                            <select id="empresa_id" name="empresa_id" class="cc-input">
                                                <option value="">Todas</option>

                                                @foreach ($empresas as $empresa)
                                                    <option value="{{ $empresa->id }}"
                                                            @selected((string) $empresaId === (string) $empresa->id)>
                                                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @else
                                            <select id="empresa_id" name="empresa_id" class="cc-input" disabled>
                                                @foreach ($empresas as $empresa)
                                                    <option value="{{ $empresa->id }}" selected>
                                                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @endif
                                    </div>

                                    <div class="cc-field">
                                        <label for="placa">
                                            Placa
                                        </label>

                                        <select id="placa" name="placa" class="cc-input">
                                            <option value="">Todas</option>

                                            @foreach ($placasSelector as $placaOpcion)
                                                <option value="{{ $placaOpcion }}" @selected((string) $placa === (string) $placaOpcion)>
                                                    {{ $placaOpcion }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="cc-field">
                                        <label for="periodo_vigencia_meses">
                                            Período de vigencia
                                        </label>

                                        <select id="periodo_vigencia_meses" name="periodo_vigencia_meses" class="cc-input">
                                            <option value="">Todos</option>

                                            @foreach ($periodosVigencia as $valor => $texto)
                                                <option value="{{ $valor }}" @selected((string) $periodoVigencia === (string) $valor)>
                                                    {{ $texto }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="cc-standard-filter-actions">
                                        <button type="submit" class="cc-btn-primary">
                                            Consultar
                                        </button>

                                        <a href="{{ route('licencias.administrar.ventana') }}" class="cc-btn-secondary">
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $licencias->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->firstItem() }}</span>
                                -
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->lastItem() }}</span>
                                de
                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $licencias->total() }}</span>
                            </div>
                        @endif

                        @if (! $hayFiltros)
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Búsqueda pendiente
                                </h5>
                                <p>
                                    Los resultados permanecerán vacíos hasta que localice una licencia por empresa, placa o período de vigencia.
                                </p>
                            </div>
                        @elseif ($licencias->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>
                                <p>
                                    No hay licencias que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="cc-admin-result-list">
                                @foreach ($licencias as $licencia)
                                    <article class="cc-admin-result-card">
                                        <div class="grid gap-5 xl:grid-cols-12 xl:items-start">

                                            <div class="min-w-0 xl:col-span-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <h5 class="cc-admin-result-title">
                                                        {{ $licencia->unidad->placa ?? 'Sin placa' }}
                                                    </h5>

                                                    @if ($licencia->estado === 'activa')
                                                        <span class="cc-badge cc-badge-active">
                                                            Activa
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-inactive">
                                                            Inactiva
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="cc-admin-result-subtitle">
                                                    {{ $licencia->unidad->marca ?? 'Sin marca registrada' }}
                                                </div>
                                            </div>

                                            <div class="min-w-0 xl:col-span-3">
                                                <div class="cc-admin-result-label">
                                                    Empresa
                                                </div>

                                                @if ($licencia->empresa)
                                                    <div class="cc-admin-result-value">
                                                        {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                                    </div>

                                                    @if ($licencia->empresa->nit ?? false)
                                                        <div class="cc-admin-result-value-muted">
                                                            NIT: {{ $licencia->empresa->nit }}
                                                        </div>
                                                    @endif
                                                @else
                                                    <div class="cc-admin-result-value-muted">
                                                        Sin empresa
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="min-w-0 sm:grid sm:grid-cols-2 sm:gap-5 xl:col-span-4 xl:grid-cols-2">
                                                <div class="min-w-0">
                                                    <div class="cc-admin-result-label">
                                                        Vigencia
                                                    </div>

                                                    <div class="cc-admin-result-value">
                                                        {{ $licencia->periodo_vigencia_texto }}
                                                    </div>

                                                    <div class="cc-admin-result-value-muted">
                                                        Vence {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'no registrado' }}
                                                    </div>
                                                </div>

                                                <div class="min-w-0">
                                                    <div class="cc-admin-result-label">
                                                        Puntos esperados
                                                    </div>

                                                    <div class="cc-admin-result-value">
                                                        {{ $licencia->cantidad_puntos_seguridad_esperados }}
                                                    </div>

                                                    <div class="cc-admin-result-value-muted">
                                                        {{ $licencia->plantilla_puntos_seguridad_texto }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex flex-col sm:flex-row gap-3 xl:col-span-2 xl:justify-end xl:self-center">
                                                <a href="{{ route('licencias.show.ventana', $licencia) }}"
                                                   class="cc-btn-primary cc-btn-result w-full sm:w-auto">
                                                    Ver ficha
                                                </a>

                                                <a href="{{ route('licencias.edit.ventana', $licencia) }}"
                                                   class="cc-btn-secondary cc-btn-result w-full sm:w-auto">
                                                    Editar
                                                </a>
                                            </div>

                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                {{ $licencias->appends(array_merge(request()->query(), ['consultar' => 1]))->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>