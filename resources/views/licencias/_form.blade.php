@php
    $licencia = $licencia ?? null;
    $esEdicion = ! is_null($licencia);
    $modoVentana = $modoVentana ?? false;

    $empresaActual = old(
        'empresa_id',
        $licencia->empresa_id ?? ($empresaSeleccionadaId ?? '')
    );

    $unidadActual = old(
        'unidad_id',
        $licencia->unidad_id ?? ''
    );

    $periodoActual = old(
        'periodo_vigencia_meses',
        $licencia->periodo_vigencia_meses ?? ''
    );

    $fechaActivacionActual = old(
        'fecha_activacion',
        $licencia?->fecha_activacion?->format('Y-m-d')
            ?? now()->format('Y-m-d')
    );

    $empresaFija = ! $esUsuarioDieselCop;
    $tanquesCubiertosActuales = collect(old('tanques_cubiertos', []))
        ->map(fn ($id) => (string) $id);
@endphp

<div class="cc-grid cc-grid-compact">

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Unidad a licenciar
        </div>
    </div>

    @if ($esEdicion)
        <div class="cc-field">
            <label>
                Empresa
            </label>

            <input
                type="text"
                class="cc-input"
                value="{{ $licencia->empresa?->nombre_comercial
                    ?: $licencia->empresa?->nombre_legal
                    ?: 'Sin empresa' }}"
                disabled
            >
        </div>

        <div class="cc-field">
            <label>
                Nombre / Placa
            </label>

            <input
                type="text"
                class="cc-input"
                value="{{ $licencia->unidad?->placa ?? 'Sin unidad' }}"
                disabled
            >
        </div>
    @else
        <div class="cc-field">
            <label for="empresa_id">
                Empresa
                <span class="cc-required">*</span>
            </label>

            @if ($esUsuarioDieselCop)
                <select
                    id="empresa_id"
                    name="empresa_id"
                    class="cc-input"
                    required
                >
                    <option value="">
                        Seleccione una empresa
                    </option>

                    @foreach ($empresas as $empresa)
                        <option
                            value="{{ $empresa->id }}"
                            @selected(
                                (string) $empresaActual
                                === (string) $empresa->id
                            )
                        >
                            {{ $empresa->nombre_comercial
                                ?: $empresa->nombre_legal }}
                        </option>
                    @endforeach
                </select>
            @else
                <select
                    id="empresa_id_visible"
                    class="cc-input"
                    disabled
                >
                    @foreach ($empresas as $empresa)
                        <option
                            value="{{ $empresa->id }}"
                            @selected(
                                (string) $empresaActual
                                === (string) $empresa->id
                            )
                        >
                            {{ $empresa->nombre_comercial
                                ?: $empresa->nombre_legal }}
                        </option>
                    @endforeach
                </select>

                <input
                    id="empresa_id"
                    type="hidden"
                    name="empresa_id"
                    value="{{ $empresaActual ?: ($empresas->first()->id ?? '') }}"
                >
            @endif

            @error('empresa_id')
                <div class="cc-error">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="cc-field">
            <label for="unidad_id">
                Nombre / Placa
                <span class="cc-required">*</span>
            </label>

            <select
                id="unidad_id"
                name="unidad_id"
                class="cc-input"
                required
                @disabled(blank($empresaActual))
            >
                @if (blank($empresaActual))
                    <option value="">
                        Seleccione una empresa primero
                    </option>
                @elseif ($unidades->isEmpty())
                    <option value="">
                        No hay unidades elegibles
                    </option>
                @else
                    <option value="">
                        Seleccione una unidad
                    </option>

                    @foreach ($unidades as $unidad)
                        <option
                            value="{{ $unidad->id }}"
                            data-placa="{{ $unidad->placa }}"
                            data-marca="{{ $unidad->marca ?: 'Sin marca registrada' }}"
                            data-total-tanques="{{ $unidad->total_tanques }}"
                            @selected(
                                (string) $unidadActual
                                === (string) $unidad->id
                            )
                        >
                            {{ $unidad->placa }}

                            @if ($esUsuarioDieselCop && $unidad->empresa)
                                —
                                {{ $unidad->empresa->nombre_comercial
                                    ?: $unidad->empresa->nombre_legal }}
                            @endif
                        </option>
                    @endforeach
                @endif
            </select>

            @error('unidad_id')
                <div class="cc-error">
                    {{ $message }}
                </div>
            @enderror

            @if (
                filled($empresaActual)
                && $unidades->isEmpty()
            )
                <div class="cc-field-help">
                    La empresa seleccionada no posee unidades disponibles
                    para registrar una nueva licencia.
                </div>
            @endif
        </div>

        <div
            id="unidad_resumen_panel"
            class="cc-col-span-2 hidden -mt-2"
        >
            <div
                id="unidad_resumen_identidad"
                class="text-xs font-medium leading-5 text-[var(--cc-text-muted)]"
            ></div>
        </div>

        <div class="cc-form-section-slim">
            <div class="cc-form-section-title">
                Cobertura de licencia
            </div>
        </div>

        <div class="cc-field cc-col-span-2">
            <label>
                Tanques disponibles
                <span class="cc-required">*</span>
            </label>

            @foreach ($unidades as $unidad)
                <div
                    data-tanques-unidad="{{ $unidad->id }}"
                    class="cc-grid cc-grid-compact gap-3"
                    style="{{ (string) $unidadActual === (string) $unidad->id ? '' : 'display: none;' }}"
                >
                    @foreach ($unidad->tanquesUnidad as $tanqueUnidad)
                        <label class="!mb-0 flex items-start gap-2 !text-sm !font-medium">
                            <input
                                type="checkbox"
                                class="mt-0.5"
                                name="tanques_cubiertos[]"
                                value="{{ $tanqueUnidad->id }}"
                                data-tanque-licencia
                                data-numero="{{ $tanqueUnidad->numero }}"
                                data-capacidad="{{ number_format((float) $tanqueUnidad->capacidad, 2, '.', '') }}"
                                @checked($tanquesCubiertosActuales->contains((string) $tanqueUnidad->id))
                                disabled
                            >
                            Tanque {{ $tanqueUnidad->numero }} —
                            {{ number_format((float) $tanqueUnidad->capacidad, 2) }} gal
                        </label>
                    @endforeach
                </div>
            @endforeach

            <div
                data-tanques-unidad-vacia
                class="mt-1 text-xs leading-5 text-[var(--cc-text-muted)]"
                style="{{ filled($unidadActual) ? 'display: none;' : '' }}"
            >
                Seleccione una unidad para ver sus tanques.
            </div>

            <div
                data-tanques-unidad-ayuda
                class="mt-1 text-xs leading-5 text-[var(--cc-text-muted)]"
                style="{{ filled($unidadActual) ? '' : 'display: none;' }}"
            >
                Seleccione uno o más tanques.
            </div>

            @error('tanques_cubiertos')
                <div class="cc-error">{{ $message }}</div>
            @enderror
            @error('tanques_cubiertos.*')
                <div class="cc-error">{{ $message }}</div>
            @enderror

            <div
                id="unidad_resumen"
                class="mt-2 hidden border-t border-[var(--cc-border)] pt-2 text-xs leading-5"
            >
                <div
                    id="unidad_resumen_cobertura"
                    class="font-medium text-[var(--cc-text)]"
                ></div>

                <div
                    id="unidad_resumen_plantilla"
                    class="text-[var(--cc-text-muted)]"
                ></div>
            </div>
        </div>
    @endif

    <div class="cc-form-section-slim">
        <div class="cc-form-section-title">
            Vigencia de la licencia
        </div>
    </div>

    <div class="cc-field">
        <label for="periodo_vigencia_meses">
            Período de vigencia
            <span class="cc-required">*</span>
        </label>

        <select
            id="periodo_vigencia_meses"
            name="periodo_vigencia_meses"
            class="cc-input"
            required
        >
            <option value="">
                Seleccione un período
            </option>

            @foreach ($periodosVigencia as $valor => $texto)
                <option
                    value="{{ $valor }}"
                    @selected(
                        (string) $periodoActual
                        === (string) $valor
                    )
                >
                    {{ $texto }}
                </option>
            @endforeach
        </select>

        @error('periodo_vigencia_meses')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror
    </div>

    <div class="cc-field">
        <label for="fecha_activacion">
            Fecha de activación
            <span class="cc-required">*</span>
        </label>

        <input
            id="fecha_activacion"
            type="date"
            name="fecha_activacion"
            value="{{ $fechaActivacionActual }}"
            class="cc-input"
            required
            @if (! $esEdicion)
                min="{{ now()->toDateString() }}"
            @endif
        >

        @error('fecha_activacion')
            <div class="cc-error">
                {{ $message }}
            </div>
        @enderror

        <div class="mt-2 text-xs leading-5 text-[var(--cc-text-muted)]">
            Una fecha futura dejará la licencia pendiente de activación
            hasta que llegue el día indicado.
        </div>
    </div>

    <div class="cc-col-span-2">
        <div class="cc-status-strip cc-status-strip-active !px-4 !py-3">
            <div>
                <strong class="block">
                    Fecha de vencimiento estimada
                </strong>

                <span id="fecha_vencimiento_preview" class="mt-1 block">
                    Seleccione la fecha de activación y el período de
                    vigencia.
                </span>
            </div>
        </div>
    </div>

</div>

<div class="cc-actions cc-actions-compact">
    <button
        type="submit"
        class="cc-btn-primary cc-btn-form-action"
        @disabled(! $esEdicion && $unidades->isEmpty())
    >
        {{ $submitLabel }}
    </button>

    @if ($esEdicion)
        <a
            href="{{ $modoVentana
                ? route('licencias.show.ventana', $licencia)
                : route('licencias.show', $licencia) }}"
            class="cc-btn-secondary cc-btn-form-action"
        >
            Cancelar
        </a>
    @else
        <a
            href="{{ $modoVentana
                ? route('licencias.consulta.ventana')
                : route('licencias.index') }}"
            class="cc-btn-secondary cc-btn-form-action"
        >
            Cancelar
        </a>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const empresaSelect =
            document.getElementById('empresa_id');

        const unidadSelect =
            document.getElementById('unidad_id');

        const unidadResumenPanel =
            document.getElementById('unidad_resumen_panel');

        const unidadResumen =
            document.getElementById('unidad_resumen');

        const unidadResumenIdentidad =
            document.getElementById('unidad_resumen_identidad');

        const unidadResumenCobertura =
            document.getElementById('unidad_resumen_cobertura');

        const unidadResumenPlantilla =
            document.getElementById('unidad_resumen_plantilla');

        const tanquesUnidadVacia =
            document.querySelector('[data-tanques-unidad-vacia]');

        const tanquesUnidadAyuda =
            document.querySelector('[data-tanques-unidad-ayuda]');

        const periodoInput =
            document.getElementById('periodo_vigencia_meses');

        const fechaActivacionInput =
            document.getElementById('fecha_activacion');

        const fechaVencimientoPreview =
            document.getElementById('fecha_vencimiento_preview');

        function plantillaTexto(tanquesProtegidos) {
            if (tanquesProtegidos === 1) {
                return 'Plantilla 1 tanque · 29 puntos';
            }

            if (tanquesProtegidos === 2) {
                return 'Plantilla 2 tanques · 38 puntos';
            }

            if (tanquesProtegidos === 3) {
                return 'Plantilla 3 tanques · 49 puntos';
            }

            return 'Plantilla pendiente de definición';
        }

        function actualizarResumenUnidad() {
            if (
                ! unidadSelect
                || ! unidadResumen
                || ! unidadResumenIdentidad
                || ! unidadResumenCobertura
                || ! unidadResumenPlantilla
                || ! unidadResumenPanel
            ) {
                return;
            }

            const option =
                unidadSelect.options[unidadSelect.selectedIndex];

            if (! option || ! option.value) {
                unidadResumenIdentidad.textContent = '';
                unidadResumenCobertura.textContent = '';
                unidadResumenPlantilla.textContent = '';
                unidadResumenPanel.classList.add('hidden');
                unidadResumen.classList.add('hidden');

                return;
            }

            const placa =
                option.dataset.placa || 'Sin Nombre / Placa';

            const marca =
                option.dataset.marca || 'Sin marca registrada';

            const totalTanques =
                Number(option.dataset.totalTanques || 0);

            const panelTanques = document.querySelector(
                `[data-tanques-unidad="${option.value}"]`
            );
            const seleccionados = panelTanques
                ? Array.from(panelTanques.querySelectorAll('[data-tanque-licencia]:checked'))
                : [];
            const tanquesProtegidos = seleccionados.length;
            const capacidadCubierta = seleccionados.reduce(
                (total, tanque) => total + Number(tanque.dataset.capacidad || 0),
                0
            );

            unidadResumenIdentidad.textContent =
                `${placa} · ${marca}`;

            unidadResumenCobertura.textContent =
                `${tanquesProtegidos} de ${totalTanques} tanques · `
                + `${capacidadCubierta.toFixed(2)} gal cubiertos`;

            unidadResumenPlantilla.textContent =
                plantillaTexto(tanquesProtegidos);

            unidadResumenPanel.classList.remove('hidden');
            unidadResumen.classList.remove('hidden');
        }

        function actualizarTanquesUnidad(limpiarSeleccion = false) {
            const unidadId = unidadSelect?.value || '';

            document.querySelectorAll('[data-tanques-unidad]').forEach(function (panel) {
                const activo = panel.dataset.tanquesUnidad === unidadId;
                panel.style.display = activo ? '' : 'none';

                panel.querySelectorAll('[data-tanque-licencia]').forEach(function (input) {
                    if (limpiarSeleccion || ! activo) {
                        input.checked = false;
                    }

                    input.disabled = ! activo;
                });
            });

            if (tanquesUnidadVacia) {
                tanquesUnidadVacia.style.display = unidadId ? 'none' : '';
            }

            if (tanquesUnidadAyuda) {
                tanquesUnidadAyuda.style.display = unidadId ? '' : 'none';
            }

            actualizarResumenUnidad();
        }

        function formatearFecha(fecha) {
            const dia =
                String(fecha.getDate()).padStart(2, '0');

            const mes =
                String(fecha.getMonth() + 1).padStart(2, '0');

            const anio =
                fecha.getFullYear();

            return `${dia}/${mes}/${anio}`;
        }

        function sumarMesesSinDesbordamiento(fecha, meses) {
            const diaOriginal =
                fecha.getDate();

            const resultado =
                new Date(
                    fecha.getFullYear(),
                    fecha.getMonth(),
                    1
                );

            resultado.setMonth(
                resultado.getMonth() + meses
            );

            const ultimoDiaMesDestino =
                new Date(
                    resultado.getFullYear(),
                    resultado.getMonth() + 1,
                    0
                ).getDate();

            resultado.setDate(
                Math.min(
                    diaOriginal,
                    ultimoDiaMesDestino
                )
            );

            return resultado;
        }

        function calcularFechaVencimientoPreview() {
            if (
                ! periodoInput
                || ! fechaActivacionInput
                || ! fechaVencimientoPreview
            ) {
                return;
            }

            const periodo =
                Number(periodoInput.value);

            const fechaActivacion =
                fechaActivacionInput.value;

            if (! periodo || ! fechaActivacion) {
                fechaVencimientoPreview.textContent =
                    'Seleccione la fecha de activación y el período '
                    + 'de vigencia.';

                return;
            }

            const partes =
                fechaActivacion.split('-');

            if (partes.length !== 3) {
                fechaVencimientoPreview.textContent =
                    'La fecha seleccionada no es válida.';

                return;
            }

            const fecha =
                new Date(
                    Number(partes[0]),
                    Number(partes[1]) - 1,
                    Number(partes[2])
                );

            const vencimiento =
                sumarMesesSinDesbordamiento(
                    fecha,
                    periodo
                );

            fechaVencimientoPreview.textContent =
                `Vence el ${formatearFecha(vencimiento)}.`;
        }

        function cambiarEmpresa() {
            if (! empresaSelect) {
                return;
            }

            const empresaId =
                empresaSelect.value;

            const url =
                new URL(window.location.href);

            if (empresaId) {
                url.searchParams.set(
                    'empresa_id',
                    empresaId
                );
            } else {
                url.searchParams.delete('empresa_id');
            }

            url.searchParams.delete('unidad_id');

            window.location.assign(
                url.toString()
            );
        }

        if (
            empresaSelect
            && empresaSelect.tagName === 'SELECT'
            && ! empresaSelect.disabled
        ) {
            empresaSelect.addEventListener(
                'change',
                cambiarEmpresa
            );
        }

        unidadSelect?.addEventListener(
            'change',
            function () {
                actualizarTanquesUnidad(true);
            }
        );

        document.querySelectorAll('[data-tanque-licencia]').forEach(function (input) {
            input.addEventListener('change', actualizarResumenUnidad);
        });

        periodoInput?.addEventListener(
            'change',
            calcularFechaVencimientoPreview
        );

        fechaActivacionInput?.addEventListener(
            'change',
            calcularFechaVencimientoPreview
        );

        actualizarTanquesUnidad(false);
        calcularFechaVencimientoPreview();
    });
</script>
