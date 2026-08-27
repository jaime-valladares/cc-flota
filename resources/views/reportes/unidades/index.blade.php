<x-app-layout>
    <div class="cc-page-wrapper">
        <div class="cc-content-container" style="max-width: 72rem;">
            <section class="cc-card">
                <header class="cc-card-header cc-card-header-compact">
                    <div>
                        <h1 class="cc-title cc-title-compact">Ficha completa de la unidad</h1>
                        <p class="mt-2 text-sm text-[var(--cc-text-muted)]">
                            Seleccione la empresa y la unidad que desea consultar.
                        </p>
                    </div>
                </header>

                <form method="GET" action="{{ route('reportes.unidades.index') }}" class="mt-5">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="cc-field">
                            <label for="empresa_id">Empresa</label>

                            <select
                                id="empresa_id"
                                name="empresa_id"
                                class="cc-input"
                                @disabled(! $esDieselCop)
                                @if ($esDieselCop) onchange="this.form.submit()" @endif
                            >
                                @if ($esDieselCop)
                                    <option value="">Seleccione una empresa</option>
                                @endif

                                @foreach ($empresas as $empresa)
                                    <option value="{{ $empresa->id }}" @selected((int) $empresaId === (int) $empresa->id)>
                                        {{ $empresa->nombre_comercial ?: $empresa->nombre_legal }}
                                    </option>
                                @endforeach
                            </select>

                            @unless ($esDieselCop)
                                <input type="hidden" name="empresa_id" value="{{ $empresaId }}">
                            @endunless
                        </div>

                        <div class="cc-field">
                            <label for="unidad_id">Unidad</label>

                            <select
                                id="unidad_id"
                                name="unidad_id"
                                class="cc-input"
                                @disabled(! $empresaId)
                            >
                                <option value="">Seleccione una unidad</option>
                                @foreach ($unidades as $unidad)
                                    <option value="{{ $unidad->id }}" @selected((int) request('unidad_id') === (int) $unidad->id)>
                                        {{ $unidad->placa }}{{ $unidad->marca ? ' · '.$unidad->marca : '' }} · {{ ucfirst($unidad->estado) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 flex justify-end">
                        <button type="submit" name="consultar" value="1" class="cc-btn-primary" @disabled(! $empresaId)>
                            Consultar
                        </button>
                    </div>

                    @if ($empresaId && $unidades->isEmpty())
                        <div class="cc-alert cc-alert-info mt-5">
                            La empresa seleccionada no tiene unidades registradas.
                        </div>
                    @endif
                </form>
            </section>
        </div>
    </div>

</x-app-layout>
