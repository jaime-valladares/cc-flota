<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-bi-container">

            <section class="cc-bi-hero cc-bi-hero-compact">
                <div class="cc-bi-hero-content">

                    <h2 class="cc-bi-title">
                        Análisis de Unidades y Cobertura
                    </h2>

                </div>
            </section>

            <section class="cc-bi-section cc-bi-filter-section">
                <div class="cc-bi-section-header cc-bi-section-header-row">
                    <div>
                        <h2>
                            Filtros de análisis
                        </h2>
                    </div>

                    <a href="{{ route('analisis.panel-operativo') }}" class="cc-btn-secondary">
                        Limpiar
                    </a>
                </div>

                <form method="GET" action="{{ route('analisis.panel-operativo') }}">
                    <div class="cc-bi-filter-toolbar">

                        <div class="cc-bi-multiselect {{ ! $esUsuarioDieselCop ? 'cc-bi-multiselect-disabled' : '' }}">
                            <label>
                                Empresa
                            </label>

                            <button
                                type="button"
                                class="cc-bi-multiselect-toggle"
                                data-dropdown-toggle="empresa"
                                @disabled(! $esUsuarioDieselCop)
                            >
                                <span data-dropdown-summary="empresa">
                                    @if (! $esUsuarioDieselCop)
                                        {{ optional($empresasFiltro->first())->nombre_legal ?: optional($empresasFiltro->first())->nombre_comercial }}
                                    @elseif ($empresasSeleccionadas->isEmpty() || $empresasSeleccionadas->count() === $empresasFiltro->count())
                                        Todas
                                    @else
                                        {{ $empresasSeleccionadas->count() }} seleccionadas
                                    @endif
                                </span>

                                <span class="cc-bi-multiselect-arrow">
                                    ▾
                                </span>
                            </button>

                            <div class="cc-bi-multiselect-menu" data-dropdown-menu="empresa">
                                @if ($esUsuarioDieselCop)
                                    <label class="cc-bi-multiselect-option cc-bi-multiselect-option-master">
                                        <input
                                            type="checkbox"
                                            class="cc-bi-check-all"
                                            data-target="empresa_ids"
                                            data-summary-target="empresa"
                                            data-summary-all="Todas"
                                            @checked($empresasSeleccionadas->isEmpty() || $empresasSeleccionadas->count() === $empresasFiltro->count())
                                        >

                                        <span>
                                            Todas
                                        </span>
                                    </label>

                                    <div class="cc-bi-multiselect-list">
                                        @foreach ($empresasFiltro as $empresa)
                                            <label class="cc-bi-multiselect-option">
                                                <input
                                                    type="checkbox"
                                                    name="empresa_ids[]"
                                                    value="{{ $empresa->id }}"
                                                    data-filter-group="empresa_ids"
                                                    data-summary-target="empresa"
                                                    data-summary-all="Todas"
                                                    @checked($empresasSeleccionadas->isEmpty() || $empresasSeleccionadas->contains((int) $empresa->id))
                                                >

                                                <span>
                                                    {{ $empresa->nombre_legal ?: $empresa->nombre_comercial }}
                                                </span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <label class="cc-bi-multiselect-option cc-bi-multiselect-option-master">
                                        <input type="checkbox" checked disabled>

                                        <span>
                                            {{ optional($empresasFiltro->first())->nombre_legal ?: optional($empresasFiltro->first())->nombre_comercial }}
                                        </span>
                                    </label>
                                @endif
                            </div>
                        </div>

                        <div class="cc-bi-multiselect">
                            <label>
                                Unidad
                            </label>

                            <button type="button" class="cc-bi-multiselect-toggle" data-dropdown-toggle="unidad">
                                <span data-dropdown-summary="unidad">
                                    @if ($unidadesSeleccionadas->isEmpty() || $unidadesSeleccionadas->count() === $unidadesFiltro->count())
                                        Todas
                                    @else
                                        {{ $unidadesSeleccionadas->count() }} seleccionadas
                                    @endif
                                </span>

                                <span class="cc-bi-multiselect-arrow">
                                    ▾
                                </span>
                            </button>

                            <div class="cc-bi-multiselect-menu" data-dropdown-menu="unidad">
                                <label class="cc-bi-multiselect-option cc-bi-multiselect-option-master">
                                    <input
                                        type="checkbox"
                                        class="cc-bi-check-all"
                                        data-target="unidad_ids"
                                        data-summary-target="unidad"
                                        data-summary-all="Todas"
                                        @checked($unidadesSeleccionadas->isEmpty() || $unidadesSeleccionadas->count() === $unidadesFiltro->count())
                                    >

                                    <span>
                                        Todas
                                    </span>
                                </label>

                                <div class="cc-bi-multiselect-list">
                                    @forelse ($unidadesFiltro as $unidadFiltro)
                                        <label class="cc-bi-multiselect-option">
                                            <input
                                                type="checkbox"
                                                name="unidad_ids[]"
                                                value="{{ $unidadFiltro->id }}"
                                                data-filter-group="unidad_ids"
                                                data-summary-target="unidad"
                                                data-summary-all="Todas"
                                                @checked($unidadesSeleccionadas->isEmpty() || $unidadesSeleccionadas->contains((int) $unidadFiltro->id))
                                            >

                                            <span>
                                                {{ $unidadFiltro->placa }}
                                            </span>
                                        </label>
                                    @empty
                                        <div class="cc-bi-filter-empty">
                                            No hay unidades disponibles.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="cc-bi-multiselect">
                            <label>
                                Modelo de medición
                            </label>

                            <button type="button" class="cc-bi-multiselect-toggle" data-dropdown-toggle="modelo">
                                <span data-dropdown-summary="modelo">
                                    @if ($modelosSeleccionados->isEmpty() || $modelosSeleccionados->count() === $modelosFiltro->count())
                                        Todos
                                    @else
                                        {{ $modelosSeleccionados->count() }} seleccionados
                                    @endif
                                </span>

                                <span class="cc-bi-multiselect-arrow">
                                    ▾
                                </span>
                            </button>

                            <div class="cc-bi-multiselect-menu" data-dropdown-menu="modelo">
                                <label class="cc-bi-multiselect-option cc-bi-multiselect-option-master">
                                    <input
                                        type="checkbox"
                                        class="cc-bi-check-all"
                                        data-target="modelos_medicion"
                                        data-summary-target="modelo"
                                        data-summary-all="Todos"
                                        @checked($modelosSeleccionados->isEmpty() || $modelosSeleccionados->count() === $modelosFiltro->count())
                                    >

                                    <span>
                                        Todos
                                    </span>
                                </label>

                                <div class="cc-bi-multiselect-list">
                                    @forelse ($modelosFiltro as $modelo)
                                        <label class="cc-bi-multiselect-option">
                                            <input
                                                type="checkbox"
                                                name="modelos_medicion[]"
                                                value="{{ $modelo }}"
                                                data-filter-group="modelos_medicion"
                                                data-summary-target="modelo"
                                                data-summary-all="Todos"
                                                @checked($modelosSeleccionados->isEmpty() || $modelosSeleccionados->contains($modelo))
                                            >

                                            <span>
                                                @switch($modelo)
                                                    @case('galones_hora')
                                                        Galones por hora
                                                        @break

                                                    @case('galones_kilometro')
                                                        Galones por kilómetro
                                                        @break

                                                    @case('galones_viaje')
                                                        Galones por viaje
                                                        @break

                                                    @default
                                                        No definido
                                                @endswitch
                                            </span>
                                        </label>
                                    @empty
                                        <div class="cc-bi-filter-empty">
                                            No hay modelos disponibles.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="cc-bi-multiselect">
                            <label>
                                Cantidad de tanques
                            </label>

                            <button type="button" class="cc-bi-multiselect-toggle" data-dropdown-toggle="tanques">
                                <span data-dropdown-summary="tanques">
                                    @if ($tanquesSeleccionados->isEmpty() || $tanquesSeleccionados->count() === $tanquesFiltro->count())
                                        Todos
                                    @else
                                        {{ $tanquesSeleccionados->count() }} seleccionados
                                    @endif
                                </span>

                                <span class="cc-bi-multiselect-arrow">
                                    ▾
                                </span>
                            </button>

                            <div class="cc-bi-multiselect-menu" data-dropdown-menu="tanques">
                                <label class="cc-bi-multiselect-option cc-bi-multiselect-option-master">
                                    <input
                                        type="checkbox"
                                        class="cc-bi-check-all"
                                        data-target="total_tanques"
                                        data-summary-target="tanques"
                                        data-summary-all="Todos"
                                        @checked($tanquesSeleccionados->isEmpty() || $tanquesSeleccionados->count() === $tanquesFiltro->count())
                                    >

                                    <span>
                                        Todos
                                    </span>
                                </label>

                                <div class="cc-bi-multiselect-list">
                                    @forelse ($tanquesFiltro as $totalTanques)
                                        <label class="cc-bi-multiselect-option">
                                            <input
                                                type="checkbox"
                                                name="total_tanques[]"
                                                value="{{ $totalTanques }}"
                                                data-filter-group="total_tanques"
                                                data-summary-target="tanques"
                                                data-summary-all="Todos"
                                                @checked($tanquesSeleccionados->isEmpty() || $tanquesSeleccionados->contains((int) $totalTanques))
                                            >

                                            <span>
                                                {{ $totalTanques }} {{ (int) $totalTanques === 1 ? 'tanque' : 'tanques' }}
                                            </span>
                                        </label>
                                    @empty
                                        <div class="cc-bi-filter-empty">
                                            No hay configuraciones disponibles.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <div class="cc-bi-filter-submit">
                            <button type="submit" class="cc-btn-primary">
                                Aplicar
                            </button>
                        </div>

                    </div>
                </form>
            </section>

            <section class="cc-bi-section">
                <div class="cc-bi-section-header">
                    <div>
                        <h2>
                            Indicadores principales
                        </h2>
                    </div>
                </div>

                <div class="cc-bi-kpi-groups">

                    <div class="cc-bi-kpi-group">
                        <div class="cc-bi-kpi-group-title">
                            Empresas
                        </div>

                        <div class="cc-bi-kpi-mini-grid">
                            <article class="cc-bi-kpi-card cc-bi-kpi-success">
                                <div class="cc-bi-kpi-label">
                                    Activas
                                </div>

                                <div class="cc-bi-kpi-value">
                                    {{ $kpis['empresas_activas'] }}
                                </div>

                                <div class="cc-bi-kpi-note">
                                    Disponibles.
                                </div>
                            </article>

                            <article class="cc-bi-kpi-card cc-bi-kpi-muted">
                                <div class="cc-bi-kpi-label">
                                    Inactivas
                                </div>

                                <div class="cc-bi-kpi-value">
                                    {{ $kpis['empresas_inactivas'] }}
                                </div>

                                <div class="cc-bi-kpi-note">
                                    Histórico.
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="cc-bi-kpi-group">
                        <div class="cc-bi-kpi-group-title">
                            Unidades
                        </div>

                        <div class="cc-bi-kpi-mini-grid cc-bi-kpi-mini-grid-three">
                            <article class="cc-bi-kpi-card cc-bi-kpi-success">
                                <div class="cc-bi-kpi-label">
                                    Activas
                                </div>

                                <div class="cc-bi-kpi-value">
                                    {{ $kpis['unidades_activas'] }}
                                </div>

                                <div class="cc-bi-kpi-note">
                                    Operativas.
                                </div>
                            </article>

                            <article class="cc-bi-kpi-card cc-bi-kpi-info">
                                <div class="cc-bi-kpi-label">
                                    Registradas
                                </div>

                                <div class="cc-bi-kpi-value">
                                    {{ $kpis['unidades_registradas'] }}
                                </div>

                                <div class="cc-bi-kpi-note">
                                    Pendientes.
                                </div>
                            </article>

                            <article class="cc-bi-kpi-card cc-bi-kpi-muted">
                                <div class="cc-bi-kpi-label">
                                    Inactivas
                                </div>

                                <div class="cc-bi-kpi-value">
                                    {{ $kpis['unidades_inactivas'] }}
                                </div>

                                <div class="cc-bi-kpi-note">
                                    Histórico.
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="cc-bi-kpi-group">
                        <div class="cc-bi-kpi-group-title">
                            Cobertura
                        </div>

                        <div class="cc-bi-kpi-mini-grid cc-bi-kpi-mini-grid-three">
                            <article class="cc-bi-kpi-card cc-bi-kpi-success">
                                <div class="cc-bi-kpi-label">
                                    Completa
                                </div>

                                <div class="cc-bi-kpi-value">
                                    {{ $kpis['unidades_cobertura_completa'] }}
                                </div>

                                <div class="cc-bi-kpi-note">
                                    Cubiertas.
                                </div>
                            </article>

                            <article class="cc-bi-kpi-card cc-bi-kpi-danger">
                                <div class="cc-bi-kpi-label">
                                    Incompleta
                                </div>

                                <div class="cc-bi-kpi-value">
                                    {{ $kpis['unidades_cobertura_incompleta'] }}
                                </div>

                                <div class="cc-bi-kpi-note">
                                    Revisión.
                                </div>
                            </article>

                            <article class="cc-bi-kpi-card cc-bi-kpi-warning">
                                <div class="cc-bi-kpi-label">
                                    Sin licencia
                                </div>

                                <div class="cc-bi-kpi-value">
                                    {{ $kpis['unidades_sin_licencia_activa'] }}
                                </div>

                                <div class="cc-bi-kpi-note">
                                    Documental.
                                </div>
                            </article>
                        </div>
                    </div>

                    <div class="cc-bi-kpi-group">
                        <div class="cc-bi-kpi-group-title">
                            Marchamos
                        </div>

                        <div class="cc-bi-kpi-mini-grid">
                            <article class="cc-bi-kpi-card cc-bi-kpi-success">
                                <div class="cc-bi-kpi-label">
                                    Activos
                                </div>

                                <div class="cc-bi-kpi-value">
                                    {{ $kpis['marchamos_activos'] }}
                                </div>

                                <div class="cc-bi-kpi-note">
                                    Vigentes.
                                </div>
                            </article>

                            <article class="cc-bi-kpi-card cc-bi-kpi-info">
                                <div class="cc-bi-kpi-label">
                                    Reemplazados
                                </div>

                                <div class="cc-bi-kpi-value">
                                    {{ $kpis['marchamos_reemplazados'] }}
                                </div>

                                <div class="cc-bi-kpi-note">
                                    Cerrados.
                                </div>
                            </article>
                        </div>
                    </div>

                </div>
            </section>

            <section class="cc-bi-section">
                <div class="cc-bi-section-header">
                    <div>
                        <h2>
                            Señales de seguimiento
                        </h2>
                    </div>
                </div>

                <div class="cc-bi-health-grid">
                    @foreach ($saludOperativa as $item)
                        <article class="cc-bi-health-card cc-bi-health-{{ $item['nivel'] }}">
                            <div class="cc-bi-health-main">
                                <span>
                                    {{ $item['titulo'] }}
                                </span>

                                <strong>
                                    {{ $item['valor'] }}
                                </strong>
                            </div>

                            <div class="cc-bi-health-status">
                                {{ $item['estado'] }}
                            </div>

                            <p>
                                {{ $item['detalle'] }}
                            </p>
                        </article>
                    @endforeach
                </div>
            </section>

            <section class="cc-bi-section">
                <div class="cc-bi-section-header cc-bi-section-header-row">
                    <div>
                        <h2>
                            Resumen por empresa
                        </h2>
                    </div>

                    <div class="cc-bi-section-pill">
                        {{ $resumenConsolidado->total() }} empresas
                    </div>
                </div>

                <div class="cc-bi-table-card">
                    <div class="cc-table-wrapper cc-bi-table-wrapper cc-bi-table-scroll">
                        <table class="cc-table cc-bi-table cc-bi-consolidated-table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Estado</th>
                                    <th>Total unidades</th>
                                    <th>Activas</th>
                                    <th>Registradas</th>
                                    <th>Inactivas</th>
                                    <th>Cobertura completa</th>
                                    <th>Cobertura incompleta</th>
                                    <th>Marchamos activos</th>
                                    <th>Reemplazados</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($resumenConsolidado as $resumen)
                                    <tr>
                                        <td>
                                            <span class="cc-bi-table-strong">
                                                {{ $resumen['empresa']->nombre_legal ?: $resumen['empresa']->nombre_comercial }}
                                            </span>
                                        </td>

                                        <td>
                                            @if ($resumen['empresa']->estado === 'activa')
                                                <span class="cc-badge cc-badge-active">
                                                    Activa
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Inactiva
                                                </span>
                                            @endif
                                        </td>

                                        <td>{{ $resumen['total_unidades'] }}</td>
                                        <td>{{ $resumen['unidades_activas'] }}</td>
                                        <td>{{ $resumen['unidades_registradas'] }}</td>
                                        <td>{{ $resumen['unidades_inactivas'] }}</td>
                                        <td>{{ $resumen['cobertura_completa'] }}</td>
                                        <td>{{ $resumen['cobertura_incompleta'] }}</td>
                                        <td>{{ $resumen['marchamos_activos'] }}</td>
                                        <td>{{ $resumen['marchamos_reemplazados'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10">
                                            No hay información disponible para los filtros seleccionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($resumenConsolidado->hasPages())
                    <div class="cc-bi-pagination">
                        {{ $resumenConsolidado->links() }}
                    </div>
                @endif
            </section>

            <section class="cc-bi-section">
                <div class="cc-bi-section-header cc-bi-section-header-row">
                    <div>
                        <h2>
                            Análisis por unidad
                        </h2>
                    </div>

                    <div class="cc-bi-section-pill">
                        {{ $unidadesAnaliticas->total() }} unidades
                    </div>
                </div>

                <div class="cc-bi-table-card">
                    <div class="cc-table-wrapper cc-bi-table-wrapper cc-bi-table-scroll">
                        <table class="cc-table cc-bi-table cc-bi-analytics-table">
                            <thead>
                                <tr>
                                    <th>Empresa</th>
                                    <th>Unidad</th>
                                    <th>Estado unidad</th>
                                    <th>Modelo medición</th>
                                    <th>Tanques</th>
                                    <th>Licencia</th>
                                    <th>Marchamos activos</th>
                                    <th>Cobertura</th>
                                    <th>Situación</th>
                                    <th>Acción sugerida</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse ($unidadesAnaliticas as $item)
                                    <tr>
                                        <td>
                                            <span class="cc-bi-table-strong">
                                                {{ $item['empresa']->nombre_legal ?: $item['empresa']->nombre_comercial }}
                                            </span>
                                        </td>

                                        <td>
                                            <span class="cc-bi-table-strong">
                                                {{ $item['unidad']->placa }}
                                            </span>
                                        </td>

                                        <td>
                                            @if ($item['unidad']->estado === 'activa')
                                                <span class="cc-badge cc-badge-active">
                                                    Activa
                                                </span>
                                            @elseif ($item['unidad']->estado === 'registrada')
                                                <span class="cc-badge cc-badge-info">
                                                    Registrada
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    Inactiva
                                                </span>
                                            @endif
                                        </td>

                                        <td>
                                            {{ $item['modelo_medicion'] }}
                                        </td>

                                        <td>
                                            {{ $item['total_tanques'] }}
                                        </td>

                                        <td>
                                            {{ $item['licencia_texto'] }}
                                        </td>

                                        <td>
                                            {{ $item['marchamos_activos'] }}
                                        </td>

                                        <td>
                                            <div class="cc-bi-progress">
                                                <span style="width: {{ min(100, max(0, $item['porcentaje_cobertura'])) }}%;"></span>
                                            </div>

                                            <div class="cc-bi-progress-label">
                                                {{ $item['puntos_asignados'] }} / {{ $item['total_puntos'] }}
                                                · {{ $item['porcentaje_cobertura'] }}%
                                            </div>
                                        </td>

                                        <td>
                                            {{ $item['situacion'] }}
                                        </td>

                                        <td>
                                            {{ $item['accion_sugerida'] }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10">
                                            No hay unidades disponibles para los filtros seleccionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if ($unidadesAnaliticas->hasPages())
                    <div class="cc-bi-pagination">
                        {{ $unidadesAnaliticas->links() }}
                    </div>
                @endif
            </section>

        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropdownToggles = document.querySelectorAll('[data-dropdown-toggle]');
            const masterCheckboxes = document.querySelectorAll('.cc-bi-check-all');

            function closeAllDropdowns(exceptKey = null) {
                document.querySelectorAll('[data-dropdown-menu]').forEach(function (menu) {
                    if (menu.dataset.dropdownMenu !== exceptKey) {
                        menu.classList.remove('is-open');
                    }
                });

                document.querySelectorAll('[data-dropdown-toggle]').forEach(function (toggle) {
                    if (toggle.dataset.dropdownToggle !== exceptKey) {
                        toggle.classList.remove('is-open');
                    }
                });
            }

            function updateSummary(targetGroup, summaryTarget, allText) {
                const children = document.querySelectorAll('[data-filter-group="' + targetGroup + '"]');
                const checkedChildren = document.querySelectorAll('[data-filter-group="' + targetGroup + '"]:checked');
                const summary = document.querySelector('[data-dropdown-summary="' + summaryTarget + '"]');
                const master = document.querySelector('.cc-bi-check-all[data-target="' + targetGroup + '"]');

                if (!summary) {
                    return;
                }

                if (children.length === 0) {
                    summary.textContent = allText;
                    return;
                }

                if (checkedChildren.length === children.length) {
                    summary.textContent = allText;

                    if (master) {
                        master.checked = true;
                    }

                    return;
                }

                if (checkedChildren.length === 0) {
                    summary.textContent = 'Ninguno';

                    if (master) {
                        master.checked = false;
                    }

                    return;
                }

                summary.textContent = checkedChildren.length + ' seleccionados';

                if (master) {
                    master.checked = false;
                }
            }

            dropdownToggles.forEach(function (toggle) {
                toggle.addEventListener('click', function (event) {
                    if (toggle.disabled) {
                        return;
                    }

                    event.stopPropagation();

                    const key = toggle.dataset.dropdownToggle;
                    const menu = document.querySelector('[data-dropdown-menu="' + key + '"]');

                    if (!menu) {
                        return;
                    }

                    const isOpen = menu.classList.contains('is-open');

                    closeAllDropdowns(key);

                    menu.classList.toggle('is-open', !isOpen);
                    toggle.classList.toggle('is-open', !isOpen);
                });
            });

            document.addEventListener('click', function () {
                closeAllDropdowns();
            });

            document.querySelectorAll('[data-dropdown-menu]').forEach(function (menu) {
                menu.addEventListener('click', function (event) {
                    event.stopPropagation();
                });
            });

            masterCheckboxes.forEach(function (masterCheckbox) {
                const targetGroup = masterCheckbox.dataset.target;
                const summaryTarget = masterCheckbox.dataset.summaryTarget;
                const allText = masterCheckbox.dataset.summaryAll || 'Todos';

                if (!targetGroup) {
                    return;
                }

                const children = document.querySelectorAll('[data-filter-group="' + targetGroup + '"]');

                masterCheckbox.addEventListener('change', function () {
                    children.forEach(function (child) {
                        child.checked = masterCheckbox.checked;
                    });

                    updateSummary(targetGroup, summaryTarget, allText);
                });

                children.forEach(function (child) {
                    child.addEventListener('change', function () {
                        updateSummary(targetGroup, summaryTarget, allText);
                    });
                });

                updateSummary(targetGroup, summaryTarget, allText);
            });
        });
    </script>
</x-app-layout>