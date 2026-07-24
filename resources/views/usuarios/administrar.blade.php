<x-app-layout>
<div class="cc-page-wrapper">
<div class="cc-content-container" style="max-width: 80rem;">
<div class="cc-card">
    <div class="cc-card-header cc-card-header-compact">
        <div>
            <h3 class="cc-title cc-title-compact">
                Administrar usuario
            </h3>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('usuarios.administrar.ventana', request()->query()) }}" target="_blank" rel="noopener noreferrer" class="cc-btn-secondary cc-btn-wide">
                Abrir en nueva pestaña
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="cc-alert cc-alert-success">
            {{ session('success') }}
        </div>
    @endif

    @php
        $accionFiltro = route('usuarios.administrar');
        $rutaLimpiar = route('usuarios.administrar');
    @endphp

    @include('usuarios._filtros')

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
        <div class="cc-admin-result-list">
            @foreach ($usuarios as $usuario)
                <article class="cc-admin-result-card">
                    <div class="cc-admin-result-grid">
                        <div class="cc-admin-result-main">
                            <div class="cc-admin-result-title-row">
                                <h5 class="cc-admin-result-title">
                                    {{ trim($usuario->name . ' ' . ($usuario->apellido ?? '')) }}
                                </h5>

                                @if ($usuario->estado === 'activo')
                                    <span class="cc-badge cc-badge-active">Activo</span>
                                @else
                                    <span class="cc-badge cc-badge-inactive">Inactivo</span>
                                @endif
                            </div>

                            <div class="cc-admin-result-subtitle">
                                {{ $usuario->email }}
                            </div>
                        </div>

                        <div class="cc-admin-result-meta">
                            <div class="cc-admin-result-label">Empresa</div>
                            <div class="cc-admin-result-value">
                                {{ $usuario->empresa
                                    ? ($usuario->empresa->nombre_comercial ?: $usuario->empresa->nombre_legal)
                                    : 'Diesel Cop' }}
                            </div>
                        </div>

                        <div class="cc-admin-result-meta">
                            <div class="cc-admin-result-label">Rol</div>
                            <div class="cc-admin-result-value">
                                {{ $usuario->role->nombre ?? 'Sin rol' }}
                            </div>
                            <div class="cc-admin-result-value-muted">
                                {{ $usuario->role->codigo ?? '—' }}
                            </div>
                        </div>

                        <div class="cc-admin-result-actions">
                            <a
                                href="{{ route('usuarios.show', array_merge(request()->query(), ['usuario' => $usuario])) }}"
                                class="cc-btn-primary cc-btn-result"
                            >
                                Ver ficha
                            </a>

                            @if ($usuario->estado === 'activo')
                                <a
                                    href="{{ route('usuarios.edit', array_merge(request()->query(), ['usuario' => $usuario])) }}"
                                    class="cc-btn-secondary cc-btn-result"
                                >
                                    Editar
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
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
</x-app-layout>