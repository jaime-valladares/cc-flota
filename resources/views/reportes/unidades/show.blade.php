<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 72rem;">
            <section class="cc-card">
                <header class="cc-card-header cc-card-header-compact">
                    <div>
                        <p class="text-sm font-semibold text-[var(--cc-text-muted)]">Ficha completa de la unidad</p>
                        <h1 class="cc-title cc-title-compact">{{ $unidad->placa }}</h1>
                    </div>

                    <a href="{{ route('reportes.unidades.index', ['empresa_id' => $unidad->empresa_id]) }}" class="cc-btn-secondary">
                        Cambiar unidad
                    </a>
                </header>

                <div class="mt-5 rounded-xl border border-[var(--cc-border)] p-5">
                    <p class="font-bold text-[var(--cc-text-main)]">
                        {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                    </p>
                    <p class="mt-2 text-sm text-[var(--cc-text-muted)]">
                        Infraestructura preparada. El contenido ejecutivo y la generación de PDF se implementarán en etapas posteriores.
                    </p>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
