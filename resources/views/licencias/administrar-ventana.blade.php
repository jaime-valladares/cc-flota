<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Administrar licencia | CC-Flota</title>

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
                                    Volver a Administrar
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
                                        Búsqueda administrativa
                                    </div>
                                </div>

                                <div class="cc-filter-inline-grid-unidades">

                                    <div class="cc-field">
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

                                    <div class="cc-field">
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

                                    <div class="cc-field">
                                        <label for="estado">
                                            Estado
                                        </label>

                                        <select id="estado" name="estado" class="cc-input">
                                            <option value="">Todos</option>
                                            <option value="activa" @selected($estado === 'activa')>
                                                Activa
                                            </option>
                                            <option value="inactiva" @selected($estado === 'inactiva')>
                                                Inactiva
                                            </option>
                                        </select>
                                    </div>

                                    <div class="cc-field">
                                        <label for="periodo_vigencia_meses">
                                            Vigencia
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

                                    <div class="cc-filter-inline-actions">
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
                                    Los resultados permanecerán vacíos hasta que localice una licencia por empresa, placa, estado o vigencia.
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
                            <div class="space-y-3">
                                @foreach ($licencias as $licencia)
                                    <article class="cc-result-card cc-result-card-compact">
                                        <div class="flex flex-col xl:flex-row xl:items-center xl:justify-between gap-5">

                                            <div class="flex-1 min-w-0">
                                                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 items-start">

                                                    <div>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <h5 class="cc-result-title cc-cell-truncate">
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

                                                        <div class="cc-result-subtitle cc-cell-truncate">
                                                            {{ $licencia->unidad->marca ?? 'Sin marca registrada' }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="cc-result-label">
                                                            Empresa
                                                        </div>

                                                        <div class="cc-result-value cc-cell-truncate">
                                                            @if ($licencia->empresa)
                                                                {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                                            @else
                                                                Sin empresa
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="cc-result-label">
                                                            Vigencia
                                                        </div>

                                                        <div class="cc-result-value">
                                                            {{ $licencia->periodo_vigencia_texto }}
                                                        </div>

                                                        <div class="cc-result-value-muted">
                                                            Vence {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'no registrado' }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="cc-result-label">
                                                            Puntos esperados
                                                        </div>

                                                        <div class="cc-result-value">
                                                            {{ $licencia->cantidad_puntos_seguridad_esperados }}
                                                        </div>

                                                        <div class="cc-result-value-muted cc-cell-truncate">
                                                            {{ $licencia->plantilla_puntos_seguridad_texto }}
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>

                                            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 xl:justify-end xl:min-w-[15rem]">
                                                <a href="{{ route('licencias.show.ventana', $licencia) }}" class="cc-btn-primary cc-btn-result">
                                                    Ver ficha
                                                </a>

                                                <a href="{{ route('licencias.edit.ventana', $licencia) }}" class="cc-btn-secondary cc-btn-result">
                                                    Editar
                                                </a>
                                            </div>

                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <div class="mt-6">
                                {{ $licencias->links() }}
                            </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>