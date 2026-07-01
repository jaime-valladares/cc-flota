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
                <div class="cc-window-container" style="max-width: 80rem;">
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Administrar unidad
                                </h3>
                                <p class="cc-subtitle cc-subtitle-compact">
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

                        <form method="GET" action="{{ route('unidades.administrar.ventana') }}" class="mb-5">
                            <input type="hidden" name="consultar" value="1">

                            <div class="cc-filter-panel cc-filter-panel-compact">

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

                                            <option value="registrada" @selected($estado === 'registrada')>
                                                Registrada
                                            </option>

                                            <option value="activa" @selected($estado === 'activa')>
                                                Activa
                                            </option>

                                            <option value="inactiva" @selected($estado === 'inactiva')>
                                                Inactiva
                                            </option>
                                        </select>
                                    </div>

                                    <div class="cc-field">
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

                                    <div class="cc-filter-inline-actions">
                                        <button type="submit" class="cc-btn-primary">
                                            Consultar
                                        </button>

                                        <a href="{{ route('unidades.administrar.ventana') }}" class="cc-btn-secondary">
                                            Limpiar
                                        </a>
                                    </div>

                                </div>
                            </div>
                        </form>

                        @if ($hayFiltros && $unidades->total() > 0)
                            <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
                                Mostrando
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $unidades->firstItem() }}</span>
                                -
                                <span class="mx-1 font-bold text-[var(--cc-text-main)]">{{ $unidades->lastItem() }}</span>
                                de
                                <span class="ml-1 font-bold text-[var(--cc-text-main)]">{{ $unidades->total() }}</span>
                            </div>
                        @endif

                        @if (! $hayFiltros)
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Búsqueda pendiente
                                </h5>
                                <p>
                                    Los resultados permanecerán vacíos hasta que localice una unidad por empresa, placa, estado o modelo de medición.
                                </p>
                            </div>
                        @elseif ($unidades->isEmpty())
                            <div class="cc-empty-panel cc-empty-panel-compact">
                                <h5>
                                    Sin resultados
                                </h5>
                                <p>
                                    No hay unidades que coincidan con los criterios seleccionados.
                                </p>
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach ($unidades as $unidad)
                                    <article class="cc-result-card cc-result-card-compact">
                                        <div class="cc-result-grid">

                                            <div class="cc-result-main">
                                                <div class="cc-result-title-row">
                                                    <h5 class="cc-result-title cc-cell-truncate">
                                                        {{ $unidad->placa }}
                                                    </h5>

                                                    @if ($unidad->estado === 'registrada')
                                                        <span class="cc-badge cc-badge-pending">
                                                            Registrada
                                                        </span>
                                                    @elseif ($unidad->estado === 'activa')
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
                                                    {{ $unidad->marca ?: 'Sin marca registrada' }}
                                                </div>
                                            </div>

                                            <div class="cc-result-meta">
                                                <div class="cc-result-label">
                                                    Empresa
                                                </div>

                                                @if ($unidad->empresa)
                                                    <div class="cc-result-value cc-cell-truncate">
                                                        {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                                    </div>
                                                @else
                                                    <div class="cc-result-value-muted">
                                                        Sin empresa
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="cc-result-meta">
                                                <div class="cc-result-label">
                                                    Cobertura
                                                </div>

                                                <div class="cc-result-value">
                                                    {{ $unidad->cantidad_tanques_con_licencia }} de {{ $unidad->total_tanques }} tanques
                                                </div>

                                                <div class="cc-result-value-muted">
                                                    {{ $unidad->modelo_medicion_texto }}
                                                </div>
                                            </div>

                                            <div class="cc-result-actions">
                                                <a href="{{ route('unidades.show.ventana', $unidad) }}" class="cc-btn-primary cc-btn-result">
                                                    Ver ficha
                                                </a>

                                                <a href="{{ route('unidades.edit.ventana', $unidad) }}" class="cc-btn-secondary cc-btn-result">
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