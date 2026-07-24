<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Consulta de usuarios | CC-Flota</title>
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
                Consulta de usuarios
            </h3>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('usuarios.index', request()->query()) }}"
               class="cc-btn-secondary cc-btn-wide">
                Volver al Sistema
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="cc-alert cc-alert-success">
            {{ session('success') }}
        </div>
    @endif

    <div class="cc-summary-strip">
        <div class="cc-summary-strip-item">
            <span class="cc-summary-strip-label">
                {{ $hayFiltros ? 'Resultados' : 'Total usuarios' }}
            </span>
            <span class="cc-summary-strip-value">
                {{ $hayFiltros ? $usuarios->total() : $totalUsuarios }}
            </span>
        </div>

        <div class="cc-summary-strip-item">
            <span class="cc-summary-strip-label">Activos</span>
            <span class="cc-summary-strip-value cc-summary-strip-value-success">
                {{ $usuariosActivos }}
            </span>
        </div>

        <div class="cc-summary-strip-item">
            <span class="cc-summary-strip-label">Inactivos</span>
            <span class="cc-summary-strip-value cc-summary-strip-value-danger">
                {{ $usuariosInactivos }}
            </span>
        </div>
    </div>

    @php
        $accionFiltro = route('usuarios.consulta.ventana');
        $rutaLimpiar = route('usuarios.consulta.ventana');
    @endphp


    @include('usuarios._filtros')

    @if ($hayFiltros && $usuarios->total() > 0)
        <div class="mb-4 flex justify-end text-sm text-[var(--cc-text-muted)]">
            Mostrando
            <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                {{ $usuarios->firstItem() }}
            </span>
            -
            <span class="mx-1 font-bold text-[var(--cc-text-main)]">
                {{ $usuarios->lastItem() }}
            </span>
            de
            <span class="ml-1 font-bold text-[var(--cc-text-main)]">
                {{ $usuarios->total() }}
            </span>
        </div>
    @endif

    @if (! $hayFiltros)
        <div class="cc-empty-panel cc-empty-panel-compact">
            <h5>Consulta pendiente</h5>
            <p>Los resultados permanecerán vacíos hasta que realice una búsqueda.</p>
        </div>
    @elseif ($usuarios->isEmpty())
        <div class="cc-empty-panel cc-empty-panel-compact">
            <h5>Sin resultados</h5>
            <p>No hay usuarios que coincidan con los criterios seleccionados.</p>
        </div>
    @else
        <div class="cc-table-adaptive-wrapper">
            <table class="cc-table-adaptive" style="min-width: 72rem;">
                <thead>
                    <tr>
                        <th style="width: 27%;">Usuario</th>
                        <th style="width: 24%;">Empresa</th>
                        <th style="width: 24%;">Rol</th>
                        <th style="width: 13%;">Tipo</th>
                        <th style="width: 12%;">Estado</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($usuarios as $usuario)
                        <tr>
                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{ trim($usuario->name . ' ' . ($usuario->apellido ?? '')) }}
                                </div>
                                <div class="cc-table-adaptive-muted">
                                    {{ $usuario->email }}
                                </div>
                            </td>

                            <td>
                                @if ($usuario->empresa)
                                    <div class="cc-table-adaptive-strong">
                                        {{ $usuario->empresa->nombre_comercial ?: $usuario->empresa->nombre_legal }}
                                    </div>
                                    <div class="cc-table-adaptive-muted">
                                        {{ $usuario->empresa->nit }}
                                    </div>
                                @else
                                    Diesel Cop
                                @endif
                            </td>

                            <td>
                                <div class="cc-table-adaptive-strong">
                                    {{ $usuario->role->nombre ?? 'Sin rol' }}
                                </div>
                                <div class="cc-table-adaptive-muted">
                                    {{ $usuario->role->codigo ?? '—' }}
                                </div>
                            </td>

                            <td>
                                {{ $usuario->tipo_usuario === 'diesel_cop' ? 'Diesel Cop' : 'Empresa' }}
                            </td>

                            <td>
                                @if ($usuario->estado === 'activo')
                                    <span class="cc-badge cc-badge-active">Activo</span>
                                @else
                                    <span class="cc-badge cc-badge-inactive">Inactivo</span>
                                @endif
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $usuarios
                ->appends(array_merge(request()->query(), ['consultar' => 1]))
                ->links() }}
        </div>
    @endif
</div>


<script>
    document.querySelectorAll('[data-cc-filter-multiselect]').forEach(function (multiselect) {
        const toggle = multiselect.querySelector('[data-cc-filter-toggle]');
        const menu = multiselect.querySelector('[data-cc-filter-menu]');
        const label = multiselect.querySelector('[data-cc-filter-label]');
        const master = multiselect.querySelector('[data-cc-filter-master]');
        const checkboxes = Array.from(
            multiselect.querySelectorAll('[data-cc-filter-checkbox]')
        );
        const defaultLabel = label?.dataset.defaultLabel || 'Todos';

        function updateLabel() {
            const selected = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            });

            if (selected.length === 0) {
                label.textContent = defaultLabel;
            } else if (selected.length === 1) {
                const option = selected[0].closest('[data-cc-filter-option]');
                const optionLabel = option?.querySelector('[data-cc-filter-option-label]');
                label.textContent = optionLabel
                    ? optionLabel.textContent.trim()
                    : '1 seleccionado';
            } else {
                label.textContent = selected.length + ' seleccionados';
            }

            if (master) {
                master.checked =
                    selected.length === checkboxes.length
                    && checkboxes.length > 0;
                master.indeterminate =
                    selected.length > 0
                    && selected.length < checkboxes.length;
            }
        }

        toggle?.addEventListener('click', function () {
            document
                .querySelectorAll('[data-cc-filter-menu]')
                .forEach(function (otherMenu) {
                    if (otherMenu !== menu) {
                        otherMenu.classList.remove('is-open');
                    }
                });

            document
                .querySelectorAll('[data-cc-filter-toggle]')
                .forEach(function (otherToggle) {
                    if (otherToggle !== toggle) {
                        otherToggle.classList.remove('is-open');
                    }
                });

            toggle.classList.toggle('is-open');
            menu.classList.toggle('is-open');
        });

        master?.addEventListener('change', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = master.checked;
            });

            updateLabel();
        });

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updateLabel);
        });

        updateLabel();
    });

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-cc-filter-multiselect]')) {
            return;
        }

        document
            .querySelectorAll('[data-cc-filter-toggle], [data-cc-filter-menu]')
            .forEach(function (element) {
                element.classList.remove('is-open');
            });
    });
</script>
</div>
</div>
</div>
</body>
</html>