<section class="cc-info-panel mt-7 p-5">
    <div class="cc-form-section cc-form-section-compact !m-0">
        <div class="cc-form-section-title">
            Renovación anticipada
        </div>

        <div class="cc-form-section-note">
            Extienda la vigencia desde el vencimiento actual.
        </div>
    </div>

    <form
        method="POST"
        action="{{ route(
            'licencias.renovar',
            array_merge($queryParams, ['licencia' => $licencia])
        ) }}"
        class="mt-5 grid gap-4 md:grid-cols-2 xl:grid-cols-[1fr_1.25fr_1fr_auto] xl:items-end"
        onsubmit="return confirm('¿Está seguro de renovar anticipadamente esta licencia?');"
    >
        @csrf
        @method('PATCH')

        @if ($returnTo)
            <input type="hidden" name="return_to" value="{{ $returnTo }}">
        @endif

        <div class="cc-detail-item">
            <div class="cc-detail-label">
                Vencimiento actual
            </div>

            <div class="cc-detail-value">
                {{ $licencia->fecha_vencimiento->format('d/m/Y') }}
            </div>
        </div>

        <div class="cc-field">
            <label for="periodo_agregado_meses">
                Renovar por
                <span class="cc-required">*</span>
            </label>

            <select
                id="periodo_agregado_meses"
                name="periodo_agregado_meses"
                class="cc-input"
                data-vencimiento-actual="{{ $licencia->fecha_vencimiento->format('Y-m-d') }}"
                required
            >
                <option value="">Seleccione un período</option>
                @foreach ([3, 6, 12] as $periodo)
                    <option value="{{ $periodo }}" @selected(old('periodo_agregado_meses') == $periodo)>
                        {{ $periodo }} meses
                    </option>
                @endforeach
            </select>

            @error('periodo_agregado_meses')
                <div class="cc-error">{{ $message }}</div>
            @enderror
        </div>

        <div class="cc-detail-item">
            <div class="cc-detail-label">
                Nuevo vencimiento estimado
            </div>

            <div id="nuevo_vencimiento_estimado" class="cc-detail-value">
                Seleccione un período
            </div>
        </div>

        <div>
            <button type="submit" class="cc-btn-success cc-btn-form-action">
                Renovar
            </button>
        </div>
    </form>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const periodo = document.getElementById('periodo_agregado_meses');
        const resultado = document.getElementById('nuevo_vencimiento_estimado');

        function actualizarVencimiento() {
            if (! periodo || ! resultado || ! periodo.value) {
                if (resultado) resultado.textContent = 'Seleccione un período';
                return;
            }

            const [anio, mes, dia] = periodo.dataset.vencimientoActual
                .split('-')
                .map(Number);
            const meses = Number(periodo.value);
            const indiceMes = (mes - 1) + meses;
            const anioDestino = anio + Math.floor(indiceMes / 12);
            const mesDestino = indiceMes % 12;
            const ultimoDia = new Date(anioDestino, mesDestino + 1, 0).getDate();
            const fecha = new Date(anioDestino, mesDestino, Math.min(dia, ultimoDia));

            resultado.textContent = [
                String(fecha.getDate()).padStart(2, '0'),
                String(fecha.getMonth() + 1).padStart(2, '0'),
                fecha.getFullYear(),
            ].join('/');
        }

        periodo?.addEventListener('change', actualizarVencimiento);
        actualizarVencimiento();
    });
</script>
