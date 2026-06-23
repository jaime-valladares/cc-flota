<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar unidad | CC-Flota</title>

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
                                    Administrar unidad
                                </h3>
                                <p class="cc-subtitle">
                                    Localice una unidad para consultar su ficha, editar sus datos o gestionar su estado administrativo.
                                </p>
                            </div>

                            <div class="flex items-center gap-3">
                                <a href="{{ route('unidades.administrar') }}" class="cc-btn-secondary cc-btn-wide">
                                    Volver al sistema
                                </a>
                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        <form method="GET" action="{{ route('unidades.administrar.ventana') }}" class="mb-6">
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel">

                                <div class="cc-form-section" style="margin-top: 0; margin-bottom: 1.25rem;">
                                    <div class="cc-form-section-title">
                                        Búsqueda administrativa
                                    </div>
                                    <div class="cc-form-section-note">
                                        Ingrese criterios para localizar la unidad que desea administrar.
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-end">

                                    <div class="lg:col-span-3 cc-field">
                                        <label for="empresa_id">
                                            Empresa
                                        </label>
                                        <select id="empresa_id" name="empresa_id" class="cc-input">
                                            <option value="">Todas</option>

                                            @foreach ($empresas as $empresa)
                                                <option value="{{ $empresa->id }}"
                                                        @selected((string) $empresaId === (string) $empresa->id)>
                                                    {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="lg:col-span-2 cc-field">
                                        <label for="placa">
                                            Placa
                                        </label>
                                        <input id="placa"
                                               type="text"
                                               name="placa"
                                               value="{{ $placa }}"
                                               class="cc-input"
                                               placeholder="Ej. C123ABC">
                                    </div>

                                    <div class="lg:col-span-2 cc-field">
                                        <label for="estado">
                                            Estado
                                        </label>
                                        <select id="estado" name="estado" class="cc-input">
                                            <option value="">Todos</option>
                                            <option value="activo" @selected($estado === 'activo')>
                                                Activo
                                            </option>
                                            <option value="inactivo" @selected($estado === 'inactivo')>
                                                Inactivo
                                            </option>
                                        </select>
                                    </div>

                                    <div class="lg:col-span-5 cc-field">
                                        <label for="modelo_medicion">
                                            Modelo de medición
                                        </label>
                                        <select id="modelo_medicion" name="modelo_medicion" class="cc-input">
                                            <option value="">Todos</option>

                                            @foreach ($modelosMedicion as $valor => $texto)
                                                <option value="{{ $valor }}" @selected($modeloMedicion === $valor)>
                                                    {{ $texto }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                </div>

                                <div class="mt-5 border-t border-[var(--cc-card-border)] pt-5">
                                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                        <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                            Desde esta pantalla podrá acceder a la ficha administrativa o editar una unidad.
                                        </p>

                                        <div class="flex items-center gap-3 lg:justify-end">
                                            <button type="submit" class="cc-btn-primary">
                                                Buscar
                                            </button>

                                            <a href="{{ route('unidades.administrar.ventana') }}" class="cc-btn-secondary">
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
                                        Seleccione filtros para buscar unidades administrables.
                                    @elseif ($unidades->total() === 0)
                                        No se encontraron unidades con los criterios seleccionados.
                                    @elseif ($unidades->total() === 1)
                                        Se encontró 1 unidad para administrar.
                                    @else
                                        Se encontraron {{ $unidades->total() }} unidades para administrar.
                                    @endif
                                </p>
                            </div>

                            @if ($hayFiltros && $unidades->total() > 0)
                                <div class="text-sm text-[var(--cc-text-muted)]">
                                    Mostrando
                                    <span class="font-bold text-[var(--cc-text-main)]">{{ $unidades->firstItem() }}</span>
                                    -
                                    <span class="font-bold text-[var(--cc-text-main)]">{{ $unidades->lastItem() }}</span>
                                    de
                                    <span class="font-bold text-[var(--cc-text-main)]">{{ $unidades->total() }}</span>
                                </div>
                            @endif
                        </div>

                        @if (! $hayFiltros)
                            <div class="cc-empty-panel">
                                <h5>
                                    Búsqueda pendiente
                                </h5>
                                <p>
                                    Los resultados permanecerán vacíos hasta que localice una unidad por empresa, placa, estado o modelo de medición.
                                </p>
                            </div>
                        @elseif ($unidades->isEmpty())
                            <div class="cc-empty-panel">
                                <h5>
                                    Sin resultados
                                </h5>
                                <p>
                                    No hay unidades que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach ($unidades as $unidad)
                                    <article class="cc-result-card">
                                        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">

                                            <div class="flex-1 min-w-0">
                                                <div class="flex flex-wrap items-center gap-3">
                                                    <h5 class="font-[var(--cc-font-heading)] text-xl font-extrabold text-[var(--cc-text-heading)] tracking-[-0.03em] cc-cell-truncate">
                                                        {{ $unidad->placa }}
                                                    </h5>

                                                    @if ($unidad->estado === 'activo')
                                                        <span class="cc-badge cc-badge-active">
                                                            Activo
                                                        </span>
                                                    @else
                                                        <span class="cc-badge cc-badge-inactive">
                                                            Inactivo
                                                        </span>
                                                    @endif
                                                </div>

                                                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    <div>
                                                        <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                                            Empresa
                                                        </div>
                                                        <div class="mt-1 text-sm font-bold text-[var(--cc-text-main)] cc-cell-truncate">
                                                            @if ($unidad->empresa)
                                                                {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                            @else
                                                                Sin empresa
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                                            Modelo de medición
                                                        </div>
                                                        <div class="mt-1 text-sm font-bold text-[var(--cc-text-main)] cc-cell-truncate">
                                                            {{ $unidad->modelo_medicion_texto }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="font-[var(--cc-font-heading)] text-xs font-extrabold text-[var(--cc-text-muted)] uppercase tracking-wider">
                                                            Cobertura
                                                        </div>
                                                        <div class="mt-1 text-sm font-bold text-[var(--cc-text-main)]">
                                                            {{ $unidad->cantidad_tanques_con_licencia }} de {{ $unidad->total_tanques }} tanques
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 xl:justify-end">
                                                <a href="{{ route('unidades.show', $unidad) }}" class="cc-btn-primary cc-btn-wide">
                                                    Ver ficha
                                                </a>

                                                <a href="{{ route('unidades.edit', $unidad) }}" class="cc-btn-secondary cc-btn-wide">
                                                    Editar
                                                </a>
                                            </div>

                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                {{ $unidades->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>