<x-app-layout>
    @php
        $empresaNombre = $unidad->empresa
            ? (
                $unidad->empresa->nombre_comercial
                ?: $unidad->empresa->nombre_legal
            )
            : 'Sin empresa';

        $modeloTexto = match ($unidad->modelo_medicion) {
            'kilometros_galon' => 'Kilómetros por galón',
            'galones_hora' => 'Horas por galón',
            'galones_viaje' => 'Galones por viaje',
            default => 'No definido',
        };

        $unidadDescripcion = collect([
            $unidad->marca,
            $unidad->modelo,
        ])
            ->filter()
            ->implode(' · ');

        $rutaVolver = route(
            'abastecimientos.index',
            [
                'empresa_ids' => [
                    $unidad->empresa_id,
                ],
                'placas' => [
                    $unidad->placa,
                ],
                'consultar' => 1,
            ]
        );

        $rutaVentana = route(
            'abastecimientos.create.ventana',
            $unidad
        );

        $kilometrajeAnterior = $ultimoAbastecimiento
            ? (
                ! is_null($ultimoAbastecimiento->kilometraje_actual)
                    ? (float) $ultimoAbastecimiento->kilometraje_actual
                    : (
                        ! is_null($ultimoAbastecimiento->lectura_actual)
                            ? (float) $ultimoAbastecimiento->lectura_actual
                            : null
                    )
            )
            : null;

        $horometroAnterior = (
            $unidad->modelo_medicion === 'galones_hora'
            && $ultimoAbastecimiento
            && ! is_null($ultimoAbastecimiento->horometro_actual)
        )
            ? (float) $ultimoAbastecimiento->horometro_actual
            : null;

        $volumenFinalAnterior = $ultimoAbastecimiento
            ? (float) $ultimoAbastecimiento->volumen_final
            : null;

        $tipoOrigenActual = old(
            'tipo_origen',
            'interno'
        );

        $marchamosAnteriores = collect(
            old('marchamos', [])
        );

        $rutasAnteriores = collect(
            old('rutas', [])
        );

        $tanquesAnteriores = collect(
            old('tanques', [])
        );
    @endphp

    <div class="cc-page-wrapper">
        <div
            class="cc-content-container"
            style="max-width: 80rem;"
        >
            <div class="cc-card">

                <div
                    class="cc-card-header cc-card-header-compact"
                    style="
                        display: grid;
                        grid-template-columns: minmax(0, 1fr) auto;
                        align-items: start;
                        gap: 1rem;
                    "
                >
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Registrar abastecimiento
                        </h3>

                    </div>

                    <div
                        style="
                            display: grid;
                            grid-template-columns: max-content max-content;
                            align-items: center;
                            justify-content: end;
                            gap: .75rem;
                        "
                    >
                        <a
                            href="{{ $rutaVentana }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="cc-btn-secondary"
                            style="
                                display: inline-flex;
                                width: auto;
                                min-width: 0;
                                align-items: center;
                                justify-content: center;
                                white-space: nowrap;
                            "
                        >
                            Abrir en nueva pestaña
                        </a>

                        <a
                            href="{{ $rutaVolver }}"
                            class="cc-btn-secondary"
                            style="
                                display: inline-flex;
                                width: auto;
                                min-width: 0;
                                align-items: center;
                                justify-content: center;
                                white-space: nowrap;
                            "
                        >
                            Volver a abastecimientos
                        </a>
                    </div>
                </div>

                @if (session('success'))
                    <div class="cc-alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="cc-alert-danger">
                        <div class="font-bold">
                            Revise la información ingresada.
                        </div>

                        <ul class="mt-2 list-disc list-inside">
                            @foreach (collect($errors->all())->unique()->values() as $error)
                                <li>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="cc-profile-summary">
                    <div>
                        <div class="cc-profile-eyebrow">
                            Unidad
                        </div>

                        <div class="cc-profile-title">
                            {{ $unidad->placa }}
                        </div>

                        <div
                            class="cc-profile-meta
                                   flex flex-wrap
                                   gap-x-5 gap-y-2"
                        >
                            <span>
                                <strong>Empresa:</strong>
                                {{ $empresaNombre }}
                            </span>

                            <span>
                                <strong>Unidad:</strong>
                                {{
                                    $unidadDescripcion
                                    ?: 'Sin marca ni modelo registrados'
                                }}
                            </span>

                            <span>
                                <strong>Modelo de medición:</strong>
                                {{ $modeloTexto }}
                            </span>

                            <span>
                                <strong>Capacidad cubierta:</strong>

                                {{
                                    number_format(
                                        (float)
                                        $unidad->capacidad_cubierta,
                                        2
                                    )
                                }}
                                gal
                            </span>
                        </div>
                    </div>

                    <div class="cc-profile-status">
                        <span class="cc-badge cc-badge-active">
                            Operativa
                        </span>

                        @if ($esPrimerAbastecimiento)
                            <span class="cc-badge cc-badge-warning">
                                Línea base
                            </span>
                        @else
                            <span class="cc-badge cc-badge-active">
                                Ciclo en seguimiento
                            </span>
                        @endif
                    </div>
                </div>

                <section class="cc-detail-section mt-5">
                    <div class="cc-detail-section-header">
                        <h5>
                            Estado del ciclo
                        </h5>

                        @if ($esPrimerAbastecimiento)
                            <p>
                                Este es el primer abastecimiento de la unidad.
                                Se establecerá la línea base para los cálculos
                                posteriores.
                            </p>
                        @else
                            <p>
                                Este abastecimiento cerrará el ciclo iniciado
                                por el registro anterior y abrirá uno nuevo.
                            </p>
                        @endif
                    </div>

                    <div class="cc-summary-strip">
                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">
                                Último kilometraje
                            </span>

                            <span class="cc-summary-strip-value">
                                @if (is_null($kilometrajeAnterior))
                                    Sin registro
                                @else
                                    {{ number_format($kilometrajeAnterior, 2) }}
                                    km
                                @endif
                            </span>
                        </div>

                        @if ($unidad->modelo_medicion === 'galones_hora')
                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Último horómetro
                                </span>

                                <span class="cc-summary-strip-value">
                                    @if (is_null($horometroAnterior))
                                        Sin registro
                                    @else
                                        {{ number_format($horometroAnterior, 2) }}
                                        h
                                    @endif
                                </span>
                            </div>
                        @endif

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">
                                Combustible final anterior
                            </span>

                            <span class="cc-summary-strip-value">
                                @if (is_null($volumenFinalAnterior))
                                    Sin registro
                                @else
                                    {{
                                        number_format(
                                            $volumenFinalAnterior,
                                            2
                                        )
                                    }}
                                    gal
                                @endif
                            </span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">
                                Tapones disponibles
                            </span>

                            <span class="cc-summary-strip-value">
                                {{ $tapones->count() }}
                            </span>
                        </div>

                        <div class="cc-summary-strip-item">
                            <span class="cc-summary-strip-label">
                                Fecha y hora
                            </span>

                            <span class="cc-summary-strip-value">
                                Automática
                            </span>
                        </div>
                    </div>
                </section>

                <form
                    method="POST"
                    action="{{ route(
                        'abastecimientos.store',
                        $unidad
                    ) }}"
                    id="form-abastecimiento"
                    class="mt-6"
                    novalidate
                >
                    @csrf

                    <input
                        type="hidden"
                        name="empresa_id"
                        value="{{ $unidad->empresa_id }}"
                    >

                    <input
                        type="hidden"
                        name="unidad_id"
                        value="{{ $unidad->id }}"
                    >

                    <input
                        type="hidden"
                        name="ultimo_abastecimiento_id"
                        value="{{ $ultimoAbastecimiento?->id }}"
                    >

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Datos operativos
                            </h5>

                            <p>
                                Registre al motorista responsable, el
                                kilometraje actual y el combustible existente
                                antes de iniciar la carga. Las unidades por
                                horas también requieren la lectura del
                                horómetro.
                            </p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            <div
                                class="grid gap-5 md:grid-cols-2
                                       {{
                                           $unidad->modelo_medicion
                                           === 'galones_hora'
                                               ? 'xl:grid-cols-4'
                                               : 'xl:grid-cols-3'
                                       }}"
                            >
                                <div class="cc-field">
                                    <label for="motorista_id">
                                        Motorista
                                        <span class="cc-required">*</span>
                                    </label>

                                    <select
                                        id="motorista_id"
                                        name="motorista_id"
                                        class="cc-input"
                                        required
                                    >
                                        <option value="">
                                            Seleccione un motorista
                                        </option>

                                        @foreach ($motoristas as $motorista)
                                            <option
                                                value="{{ $motorista->id }}"
                                                @selected(
                                                    (string)
                                                    old('motorista_id')
                                                    ===
                                                    (string)
                                                    $motorista->id
                                                )
                                            >
                                                {{ $motorista->nombre_completo }}
                                                · Lic.
                                                {{ $motorista->licencia }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('motorista_id')
                                        <div class="cc-error">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div class="cc-field">
                                    <label for="kilometraje_actual">
                                        Kilometraje actual
                                        <span class="cc-required">*</span>
                                    </label>

                                    <input
                                        id="kilometraje_actual"
                                        type="number"
                                        name="kilometraje_actual"
                                        value="{{ old('kilometraje_actual') }}"
                                        class="cc-input"
                                        min="{{ $kilometrajeAnterior ?? 0 }}"
                                        step="0.01"
                                        required
                                        placeholder="0.00"
                                    >

                                    <div class="cc-table-adaptive-muted mt-1">
                                        Kilometraje acumulado del odómetro.
                                    </div>

                                    @error('kilometraje_actual')
                                        <div class="cc-error">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                @if ($unidad->modelo_medicion === 'galones_hora')
                                    <div class="cc-field">
                                        <label for="horometro_actual">
                                            Horómetro actual
                                            <span class="cc-required">*</span>
                                        </label>

                                        <input
                                            id="horometro_actual"
                                            type="number"
                                            name="horometro_actual"
                                            value="{{ old('horometro_actual') }}"
                                            class="cc-input"
                                            min="{{ $horometroAnterior ?? 0 }}"
                                            step="0.01"
                                            required
                                            placeholder="0.00"
                                        >

                                        <div class="cc-table-adaptive-muted mt-1">
                                            Horas acumuladas del horómetro.
                                        </div>

                                        @error('horometro_actual')
                                            <div class="cc-error">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                @endif

                                <div class="cc-field">
                                    <label for="volumen_inicial">
                                        Combustible antes de cargar (Galones)
                                        <span class="cc-required">*</span>
                                    </label>

                                    <input
                                        id="volumen_inicial"
                                        type="number"
                                        name="volumen_inicial"
                                        value="{{ old('volumen_inicial') }}"
                                        class="cc-input"
                                        min="0"
                                        max="{{ $unidad->capacidad_cubierta }}"
                                        step="0.01"
                                        required
                                        placeholder="0.00"
                                        data-volumen-inicial
                                    >

                                    <div class="cc-table-adaptive-muted mt-1">
                                        Total estimado en los tanques cubiertos
                                        de la unidad.
                                    </div>

                                    @error('volumen_inicial')
                                        <div class="cc-error">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section mt-6">
                        <div class="cc-detail-section-header">
                            <h5>
                                Origen del combustible
                            </h5>

                            <p>
                                Seleccione un único origen. No se permite
                                combinar combustible interno y externo en la
                                misma operación.
                            </p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            <div
                                class="grid gap-4
                                       md:grid-cols-2"
                            >
                                <label
                                    class="cc-admin-result-card
                                           cursor-pointer"
                                >
                                    <div class="flex items-start gap-3">
                                        <input
                                            type="radio"
                                            name="tipo_origen"
                                            value="interno"
                                            class="mt-1 h-5 w-5"
                                            data-tipo-origen
                                            @checked(
                                                $tipoOrigenActual
                                                === 'interno'
                                            )
                                        >

                                        <div>
                                            <div
                                                class="cc-admin-result-title"
                                            >
                                                Gasolinera interna
                                            </div>

                                            <div
                                                class="cc-admin-result-value-muted"
                                            >
                                                Descuenta inventario de uno o
                                                varios tanques internos.
                                            </div>
                                        </div>
                                    </div>
                                </label>

                                <label
                                    class="cc-admin-result-card
                                           cursor-pointer"
                                >
                                    <div class="flex items-start gap-3">
                                        <input
                                            type="radio"
                                            name="tipo_origen"
                                            value="externo"
                                            class="mt-1 h-5 w-5"
                                            data-tipo-origen
                                            @checked(
                                                $tipoOrigenActual
                                                === 'externo'
                                            )
                                        >

                                        <div>
                                            <div
                                                class="cc-admin-result-title"
                                            >
                                                Gasolinera externa
                                            </div>

                                            <div
                                                class="cc-admin-result-value-muted"
                                            >
                                                Registra galones, precio por
                                                galón y total pagado.
                                            </div>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            @error('tipo_origen')
                                <div class="cc-error mt-3">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </section>

                    <section
                        class="cc-detail-section mt-6"
                        data-origen-interno
                    >
                        <div class="cc-detail-section-header">
                            <h5>
                                Salida de inventario interno
                            </h5>

                            <p>
                                Seleccione una gasolinera y distribuya la carga
                                entre sus tanques. Cada tanque puede aparecer
                                una sola vez.
                            </p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            <div class="cc-field">
                                <label for="gasolinera_interna_id">
                                    Gasolinera interna
                                    <span class="cc-required">*</span>
                                </label>

                                <select
                                    id="gasolinera_interna_id"
                                    name="gasolinera_interna_id"
                                    class="cc-input"
                                    data-gasolinera-interna
                                >
                                    <option value="">
                                        Seleccione una gasolinera
                                    </option>

                                    @foreach (
                                        $gasolinerasInternas
                                        as $gasolinera
                                    )
                                        <option
                                            value="{{ $gasolinera->id }}"
                                            @selected(
                                                (string)
                                                old(
                                                    'gasolinera_interna_id'
                                                )
                                                ===
                                                (string)
                                                $gasolinera->id
                                            )
                                        >
                                            {{ $gasolinera->nombre }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('gasolinera_interna_id')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="mt-5">
                                @forelse (
                                    $gasolinerasInternas
                                    as $gasolinera
                                )
                                    <div
                                        data-tanques-gasolinera="{{
                                            $gasolinera->id
                                        }}"
                                        class="hidden"
                                    >
                                        <div
                                            class="cc-table-adaptive-wrapper"
                                        >
                                            <table
                                                class="cc-table-adaptive"
                                                style="min-width: 58rem;"
                                            >
                                                <thead>
                                                    <tr>
                                                        <th>Sel.</th>
                                                        <th>Tanque</th>
                                                        <th>Inventario</th>
                                                        <th>Mínimo</th>
                                                        <th>Galones a retirar</th>
                                                        <th>Saldo estimado</th>
                                                    </tr>
                                                </thead>

                                                <tbody>
                                                    @foreach (
                                                        $gasolinera->tanques
                                                        as $tanque
                                                    )
                                                        @php
                                                            $indiceTanque =
                                                                'g'
                                                                . $gasolinera->id
                                                                . 't'
                                                                . $tanque->id;

                                                            $lineaAnterior =
                                                                $tanquesAnteriores
                                                                    ->first(
                                                                        fn (
                                                                            $linea
                                                                        ) =>
                                                                            (int)
                                                                            (
                                                                                $linea[
                                                                                    'tanque_id'
                                                                                ]
                                                                                ?? 0
                                                                            )
                                                                            ===
                                                                            (int)
                                                                            $tanque->id
                                                                    );

                                                            $tanqueSeleccionado =
                                                                ! is_null(
                                                                    $lineaAnterior
                                                                );

                                                            $galonesAnteriores =
                                                                $lineaAnterior[
                                                                    'galones'
                                                                ]
                                                                ?? '';
                                                        @endphp

                                                        <tr
                                                            data-fila-tanque
                                                            data-gasolinera-id="{{
                                                                $gasolinera->id
                                                            }}"
                                                            data-inventario-minimo="{{
                                                                $tanque->volumen_minimo_alerta
                                                            }}"
                                                            class="{{
                                                                (float) $tanque->volumen_actual
                                                                <=
                                                                (float) $tanque->volumen_minimo_alerta
                                                                    ? 'bg-amber-50'
                                                                    : ''
                                                            }}"
                                                        >
                                                            <td>
                                                                <input
                                                                    type="checkbox"
                                                                    class="h-5 w-5"
                                                                    data-tanque-check
                                                                    @checked(
                                                                        $tanqueSeleccionado
                                                                    )
                                                                >
                                                            </td>

                                                            <td>
                                                                <div
                                                                    class="cc-table-adaptive-strong"
                                                                >
                                                                    {{
                                                                        $tanque->nombre
                                                                    }}
                                                                </div>
                                                            </td>

                                                            <td>
                                                                {{
                                                                    number_format(
                                                                        (float)
                                                                        $tanque
                                                                            ->volumen_actual,
                                                                        2
                                                                    )
                                                                }}
                                                                gal
                                                            </td>

                                                            <td>
                                                                {{
                                                                    number_format(
                                                                        (float)
                                                                        $tanque
                                                                            ->volumen_minimo_alerta,
                                                                        2
                                                                    )
                                                                }}
                                                                gal

                                                                @if (
                                                                    (float) $tanque->volumen_actual
                                                                    <=
                                                                    (float) $tanque->volumen_minimo_alerta
                                                                )
                                                                    <div
                                                                        class="cc-table-adaptive-muted mt-1"
                                                                        style="color: #b45309; font-weight: 700;"
                                                                    >
                                                                        Bajo mínimo
                                                                    </div>
                                                                @endif
                                                            </td>

                                                            <td>
                                                                <input
                                                                    type="hidden"
                                                                    name="tanques[{{
                                                                        $indiceTanque
                                                                    }}][tanque_id]"
                                                                    value="{{
                                                                        $tanque->id
                                                                    }}"
                                                                    data-tanque-id-input
                                                                    @disabled(
                                                                        ! $tanqueSeleccionado
                                                                    )
                                                                >

                                                                <input
                                                                    type="number"
                                                                    name="tanques[{{
                                                                        $indiceTanque
                                                                    }}][galones]"
                                                                    value="{{
                                                                        $galonesAnteriores
                                                                    }}"
                                                                    class="cc-input"
                                                                    min="0.01"
                                                                    max="{{
                                                                        $tanque->volumen_actual
                                                                    }}"
                                                                    step="0.01"
                                                                    placeholder="0.00"
                                                                    data-tanque-galones
                                                                    data-inventario="{{
                                                                        $tanque->volumen_actual
                                                                    }}"
                                                                    @disabled(
                                                                        ! $tanqueSeleccionado
                                                                    )
                                                                >
                                                            </td>

                                                            <td>
                                                                <span
                                                                    data-saldo-tanque
                                                                >
                                                                    {{
                                                                        number_format(
                                                                            (float)
                                                                            $tanque
                                                                                ->volumen_actual,
                                                                            2
                                                                        )
                                                                    }}
                                                                </span>
                                                                gal

                                                                <div
                                                                    class="cc-table-adaptive-muted mt-1 hidden"
                                                                    style="color: #b45309; font-weight: 700;"
                                                                    data-alerta-minimo
                                                                >
                                                                    Quedará bajo mínimo
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @empty
                                    <div
                                        class="cc-empty-panel
                                               cc-empty-panel-compact"
                                    >
                                        <h5>
                                            Sin gasolineras internas
                                        </h5>

                                        <p>
                                            La empresa no posee gasolineras
                                            internas con inventario disponible.
                                        </p>
                                    </div>
                                @endforelse
                            </div>

                            @error('tanques')
                                <div class="cc-error mt-3">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </section>

                    <section
                        class="cc-detail-section mt-6 hidden"
                        data-origen-externo
                    >
                        <div class="cc-detail-section-header">
                            <h5>
                                Compra externa
                            </h5>

                            <p>
                                Registre la estación utilizada, los galones
                                cargados y el precio unitario en dólares.
                            </p>
                        </div>

                        <div style="padding: 1rem 1.2rem;">
                            <div
                                class="grid gap-5
                                       md:grid-cols-3"
                            >
                                <div class="cc-field">
                                    <label for="gasolinera_externa_id">
                                        Gasolinera externa
                                        <span class="cc-required">*</span>
                                    </label>

                                    <select
                                        id="gasolinera_externa_id"
                                        name="gasolinera_externa_id"
                                        class="cc-input"
                                        data-campo-externo
                                    >
                                        <option value="">
                                            Seleccione una gasolinera
                                        </option>

                                        @foreach (
                                            $gasolinerasExternas
                                            as $gasolineraExterna
                                        )
                                            <option
                                                value="{{
                                                    $gasolineraExterna->id
                                                }}"
                                                @selected(
                                                    (string)
                                                    old(
                                                        'gasolinera_externa_id'
                                                    )
                                                    ===
                                                    (string)
                                                    $gasolineraExterna->id
                                                )
                                            >
                                                {{ $gasolineraExterna->compania }}
                                                —
                                                {{ $gasolineraExterna->direccion }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="cc-field">
                                    <label for="galones_externos">
                                        Galones cargados
                                        <span class="cc-required">*</span>
                                    </label>

                                    <input
                                        id="galones_externos"
                                        type="number"
                                        name="galones_externos"
                                        value="{{ old('galones_externos') }}"
                                        class="cc-input"
                                        min="0.01"
                                        step="0.01"
                                        placeholder="0.00"
                                        data-campo-externo
                                        data-galones-externos
                                    >
                                </div>

                                <div class="cc-field">
                                    <label for="precio_galon">
                                        Precio por galón
                                        <span class="cc-required">*</span>
                                    </label>

                                    <input
                                        id="precio_galon"
                                        type="number"
                                        name="precio_galon"
                                        value="{{ old('precio_galon') }}"
                                        class="cc-input"
                                        min="0.0001"
                                        step="0.0001"
                                        placeholder="0.0000"
                                        data-campo-externo
                                        data-precio-galon
                                    >
                                </div>
                            </div>

                            <div
                                class="cc-summary-strip mt-5"
                            >
                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">
                                        Total estimado
                                    </span>

                                    <span class="cc-summary-strip-value">
                                        $
                                        <span data-total-externo>
                                            0.00
                                        </span>
                                    </span>
                                </div>

                                <div class="cc-summary-strip-item">
                                    <span class="cc-summary-strip-label">
                                        Moneda
                                    </span>

                                    <span class="cc-summary-strip-value">
                                        USD
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>

                    @if ($requiereRutas)
                        <section class="cc-detail-section mt-6">
                            <div class="cc-detail-section-header">
                                <h5>
                                    Rutas del ciclo
                                </h5>

                                <p>
                                    Registre las rutas realizadas desde el
                                    abastecimiento anterior. Una misma ruta
                                    puede declararse más de una vez.
                                </p>
                            </div>

                            <div style="padding: 1rem 1.2rem;">
                                <div
                                    id="contenedor-rutas"
                                    class="space-y-4"
                                ></div>

                                <button
                                    type="button"
                                    class="cc-btn-secondary
                                           cc-btn-form-action
                                           mt-4"
                                    id="boton-agregar-ruta"
                                >
                                    Agregar ruta
                                </button>

                                @error('rutas')
                                    <div class="cc-error mt-3">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </section>
                    @endif

                    <section class="cc-detail-section mt-6">
                        <div class="cc-detail-section-header">
                            <h5>
                                Tapones abiertos y marchamos nuevos
                            </h5>

                            <p>
                                Seleccione exclusivamente los tapones abiertos
                                durante la carga. Cada uno requiere un nuevo
                                código globalmente único de 7 dígitos.
                            </p>
                        </div>

                        <div class="cc-table-adaptive-wrapper">
                            <table
                                class="cc-table-adaptive"
                                style="min-width: 70rem;"
                            >
                                <thead>
                                    <tr>
                                        <th>Sel.</th>
                                        <th>Orden</th>
                                        <th>Tapón</th>
                                        <th>Posición</th>
                                        <th>Marchamo retirado</th>
                                        <th>Nuevo marchamo</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @forelse ($tapones as $indice => $punto)
                                        @php
                                            $lineaAnterior =
                                                $marchamosAnteriores
                                                    ->first(
                                                        fn ($linea) =>
                                                            (int)
                                                            (
                                                                $linea[
                                                                    'punto_seguridad_id'
                                                                ]
                                                                ?? 0
                                                            )
                                                            ===
                                                            (int)
                                                            $punto->id
                                                    );

                                            $seleccionado =
                                                ! is_null(
                                                    $lineaAnterior
                                                );

                                            $codigoNuevo =
                                                $lineaAnterior[
                                                    'nuevo_codigo_marchamo'
                                                ]
                                                ?? '';
                                        @endphp

                                        <tr
                                            data-fila-marchamo
                                            class="{{
                                                $seleccionado
                                                    ? ''
                                                    : 'opacity-80'
                                            }}"
                                        >
                                            <td>
                                                <input
                                                    type="checkbox"
                                                    class="h-5 w-5"
                                                    data-marchamo-check
                                                    @checked($seleccionado)
                                                >
                                            </td>

                                            <td>
                                                {{ $punto->orden }}
                                            </td>

                                            <td>
                                                <div
                                                    class="cc-table-adaptive-strong"
                                                >
                                                    {{
                                                        $punto->nombre_punto
                                                    }}
                                                </div>

                                                <div
                                                    class="cc-table-adaptive-muted"
                                                >
                                                    {{
                                                        $punto->codigo_punto
                                                        ?: 'Sin código'
                                                    }}
                                                </div>
                                            </td>

                                            <td>
                                                {{
                                                    $punto->posicion_tanque
                                                    ?: 'No definida'
                                                }}
                                            </td>

                                            <td>
                                                <div
                                                    class="cc-table-adaptive-strong"
                                                >
                                                    {{
                                                        $punto
                                                            ->marchamoActual
                                                            ?->codigo_marchamo
                                                        ?: 'Sin marchamo'
                                                    }}
                                                </div>

                                                <div
                                                    class="cc-table-adaptive-muted
                                                           text-[var(--cc-success)]"
                                                >
                                                    Activo
                                                </div>
                                            </td>

                                            <td>
                                                <input
                                                    type="hidden"
                                                    name="marchamos[{{
                                                        $indice
                                                    }}][punto_seguridad_id]"
                                                    value="{{ $punto->id }}"
                                                    data-marchamo-punto
                                                    @disabled(! $seleccionado)
                                                >

                                                <input
                                                    type="text"
                                                    name="marchamos[{{
                                                        $indice
                                                    }}][nuevo_codigo_marchamo]"
                                                    value="{{ $codigoNuevo }}"
                                                    class="cc-input"
                                                    maxlength="7"
                                                    inputmode="numeric"
                                                    pattern="\d{7}"
                                                    placeholder="0000000"
                                                    autocomplete="off"
                                                    data-marchamo-codigo
                                                    @disabled(! $seleccionado)
                                                >

                                                <div
                                                    class="cc-table-adaptive-muted
                                                           mt-1"
                                                    data-marchamo-ayuda
                                                >
                                                    {{
                                                        $seleccionado
                                                            ? 'Código requerido'
                                                            : 'Seleccione el tapón'
                                                    }}
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td
                                                colspan="6"
                                                class="text-center
                                                       text-[var(--cc-text-muted)]
                                                       py-8"
                                            >
                                                La unidad no posee tapones
                                                disponibles para abastecimiento.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @error('marchamos')
                            <div class="cc-error m-5">
                                {{ $message }}
                            </div>
                        @enderror
                    </section>

                    <section class="cc-detail-section mt-6">
                        <div class="cc-detail-section-header">
                            <h5>
                                Resumen de la operación
                            </h5>

                            <p>
                                Revise los valores calculados antes de registrar.
                                La operación se guardará de forma indivisible.
                            </p>
                        </div>

                        <div class="cc-summary-strip">
                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Combustible inicial
                                </span>

                                <span class="cc-summary-strip-value">
                                    <span data-resumen-inicial>
                                        0.00
                                    </span>
                                    gal
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Combustible cargado
                                </span>

                                <span class="cc-summary-strip-value">
                                    <span data-resumen-cargado>
                                        0.00
                                    </span>
                                    gal
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Combustible final
                                </span>

                                <span
                                    class="cc-summary-strip-value"
                                    data-resumen-final-contenedor
                                >
                                    <span data-resumen-final>
                                        0.00
                                    </span>
                                    gal
                                </span>
                            </div>

                            <div class="cc-summary-strip-item">
                                <span class="cc-summary-strip-label">
                                    Tapones abiertos
                                </span>

                                <span class="cc-summary-strip-value">
                                    <span data-resumen-marchamos>
                                        0
                                    </span>
                                </span>
                            </div>
                        </div>

                        <div
                            class="cc-callout cc-callout-warning m-5"
                        >
                            <span class="cc-callout-marker"></span>

                            <div>
                                <div class="cc-callout-title">
                                    Confirmación requerida
                                </div>

                                <div class="cc-callout-text">
                                    Al guardar se actualizará el inventario
                                    interno cuando corresponda, se registrará
                                    el abastecimiento y se sustituirán los
                                    marchamos seleccionados. Si una validación
                                    falla, no se aplicará ningún cambio.
                                </div>
                            </div>
                        </div>

                        <div class="cc-form-actions px-5 pb-5">
                            <a
                                href="{{ $rutaVolver }}"
                                class="cc-btn-secondary
                                       cc-btn-form-action"
                            >
                                Cancelar
                            </a>

                            <button
                                type="submit"
                                class="cc-btn-primary
                                       cc-btn-form-action"
                                id="boton-registrar-abastecimiento"
                            >
                                Registrar abastecimiento
                            </button>
                        </div>
                    </section>
                </form>
            </div>
        </div>
    </div>

    @if ($requiereRutas)
        <template id="plantilla-ruta">
            <div
                class="cc-admin-result-card"
                data-fila-ruta
            >
                <div
                    class="grid gap-4
                           md:grid-cols-[1fr_16rem_auto]
                           md:items-end"
                >
                    <div class="cc-field">
                        <label>
                            Ruta
                            <span class="cc-required">*</span>
                        </label>

                        <select
                            class="cc-input"
                            data-ruta-select
                        >
                            <option value="">
                                Seleccione una ruta
                            </option>

                            @foreach ($rutas as $ruta)
                                <option
                                    value="{{ $ruta->id }}"
                                    data-kilometros="{{
                                        $ruta->kilometros_estimados
                                    }}"
                                    data-galones="{{
                                        $ruta->galones_estimados
                                    }}"
                                >
                                    {{ $ruta->ruta }}
                                    ·
                                    {{
                                        number_format(
                                            (float)
                                            $ruta->kilometros_estimados,
                                            1
                                        )
                                    }}
                                    km
                                    ·
                                    {{
                                        number_format(
                                            (float)
                                            $ruta->galones_estimados,
                                            1
                                        )
                                    }}
                                    gal
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="cc-field">
                        <label>
                            Recorrido
                            <span class="cc-required">*</span>
                        </label>

                        <select
                            class="cc-input"
                            data-ruta-tipo
                        >
                            <option value="ida">
                                Ida
                            </option>

                            <option value="ida_vuelta">
                                Ida y vuelta
                            </option>
                        </select>
                    </div>

                    <button
                        type="button"
                        class="cc-btn-danger
                               cc-btn-form-action"
                        data-eliminar-ruta
                    >
                        Quitar
                    </button>
                </div>
            </div>
        </template>
    @endif

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function () {
                const capacidadUnidad = Number(
                    @json((float) $unidad->capacidad_cubierta)
                );

                const formulario = document.getElementById(
                    'form-abastecimiento'
                );

                const radiosOrigen = Array.from(
                    document.querySelectorAll(
                        '[data-tipo-origen]'
                    )
                );

                const seccionInterna = document.querySelector(
                    '[data-origen-interno]'
                );

                const seccionExterna = document.querySelector(
                    '[data-origen-externo]'
                );

                const gasolineraInterna = document.querySelector(
                    '[data-gasolinera-interna]'
                );

                const volumenInicial = document.querySelector(
                    '[data-volumen-inicial]'
                );

                const galonesExternos = document.querySelector(
                    '[data-galones-externos]'
                );

                const precioGalon = document.querySelector(
                    '[data-precio-galon]'
                );

                function numero(valor) {
                    const convertido = Number(valor);

                    return Number.isFinite(convertido)
                        ? convertido
                        : 0;
                }

                function tipoOrigenSeleccionado() {
                    return radiosOrigen.find(
                        function (radio) {
                            return radio.checked;
                        }
                    )?.value || '';
                }

                function actualizarOrigen() {
                    const tipo = tipoOrigenSeleccionado();
                    const interno = tipo === 'interno';

                    seccionInterna.classList.toggle(
                        'hidden',
                        ! interno
                    );

                    seccionExterna.classList.toggle(
                        'hidden',
                        interno
                    );

                    if (gasolineraInterna) {
                        gasolineraInterna.disabled = ! interno;
                    }

                    document
                        .querySelectorAll(
                            '[data-campo-externo]'
                        )
                        .forEach(function (campo) {
                            campo.disabled = interno;
                        });

                    document
                        .querySelectorAll(
                            '[data-fila-tanque]'
                        )
                        .forEach(function (fila) {
                            const visible =
                                interno
                                && fila.dataset.gasolineraId
                                === gasolineraInterna?.value;

                            const checkbox = fila.querySelector(
                                '[data-tanque-check]'
                            );

                            if (! visible && checkbox) {
                                checkbox.checked = false;
                            }

                            actualizarFilaTanque(fila);
                        });

                    actualizarGasolineraInterna();
                    actualizarResumen();
                }

                function actualizarGasolineraInterna() {
                    const gasolineraId =
                        gasolineraInterna?.value || '';

                    document
                        .querySelectorAll(
                            '[data-tanques-gasolinera]'
                        )
                        .forEach(function (contenedor) {
                            contenedor.classList.toggle(
                                'hidden',
                                contenedor.dataset
                                    .tanquesGasolinera
                                    !== gasolineraId
                            );
                        });
                }

                function actualizarFilaTanque(fila) {
                    const checkbox = fila.querySelector(
                        '[data-tanque-check]'
                    );

                    const inputId = fila.querySelector(
                        '[data-tanque-id-input]'
                    );

                    const inputGalones = fila.querySelector(
                        '[data-tanque-galones]'
                    );

                    const saldo = fila.querySelector(
                        '[data-saldo-tanque]'
                    );

                    const alertaMinimo = fila.querySelector(
                        '[data-alerta-minimo]'
                    );

                    const seleccionado =
                        checkbox?.checked
                        && tipoOrigenSeleccionado()
                            === 'interno'
                        && fila.dataset.gasolineraId
                            === gasolineraInterna?.value;

                    if (inputId) {
                        inputId.disabled = ! seleccionado;
                    }

                    if (inputGalones) {
                        inputGalones.disabled = ! seleccionado;

                        if (! seleccionado) {
                            inputGalones.value = '';
                        }

                        const inventario = numero(
                            inputGalones.dataset.inventario
                        );

                        const minimo = numero(
                            fila.dataset.inventarioMinimo
                        );

                        const saldoEstimado = Math.max(
                            0,
                            inventario
                            - numero(inputGalones.value)
                        );

                        saldo.textContent =
                            saldoEstimado.toFixed(2);

                        if (alertaMinimo) {
                            alertaMinimo.classList.toggle(
                                'hidden',
                                ! seleccionado
                                || saldoEstimado > minimo
                            );
                        }

                        fila.classList.toggle(
                            'bg-amber-50',
                            inventario <= minimo
                            || (
                                seleccionado
                                && saldoEstimado <= minimo
                            )
                        );
                    }

                    fila.classList.toggle(
                        'opacity-80',
                        ! seleccionado
                    );
                }

                function galonesInternos() {
                    return Array.from(
                        document.querySelectorAll(
                            '[data-tanque-galones]:not(:disabled)'
                        )
                    ).reduce(
                        function (total, campo) {
                            return total
                                + numero(campo.value);
                        },
                        0
                    );
                }

                function galonesCargados() {
                    return tipoOrigenSeleccionado()
                        === 'interno'
                            ? galonesInternos()
                            : numero(
                                galonesExternos?.value
                            );
                }

                function actualizarResumen() {
                    const inicial = numero(
                        volumenInicial?.value
                    );

                    const cargado = galonesCargados();

                    const final = inicial + cargado;

                    document.querySelector(
                        '[data-resumen-inicial]'
                    ).textContent = inicial.toFixed(2);

                    document.querySelector(
                        '[data-resumen-cargado]'
                    ).textContent = cargado.toFixed(2);

                    document.querySelector(
                        '[data-resumen-final]'
                    ).textContent = final.toFixed(2);

                    const contenedorFinal =
                        document.querySelector(
                            '[data-resumen-final-contenedor]'
                        );

                    contenedorFinal.classList.toggle(
                        'cc-summary-strip-value-danger',
                        final > capacidadUnidad
                    );

                    const marchamosSeleccionados =
                        document.querySelectorAll(
                            '[data-marchamo-check]:checked'
                        ).length;

                    document.querySelector(
                        '[data-resumen-marchamos]'
                    ).textContent =
                        marchamosSeleccionados;

                    if (
                        galonesExternos
                        && precioGalon
                    ) {
                        document.querySelector(
                            '[data-total-externo]'
                        ).textContent = (
                            numero(galonesExternos.value)
                            * numero(precioGalon.value)
                        ).toFixed(2);
                    }
                }

                radiosOrigen.forEach(
                    function (radio) {
                        radio.addEventListener(
                            'change',
                            actualizarOrigen
                        );
                    }
                );

                gasolineraInterna?.addEventListener(
                    'change',
                    function () {
                        document
                            .querySelectorAll(
                                '[data-fila-tanque]'
                            )
                            .forEach(function (fila) {
                                const checkbox =
                                    fila.querySelector(
                                        '[data-tanque-check]'
                                    );

                                if (
                                    fila.dataset
                                        .gasolineraId
                                    !==
                                    gasolineraInterna.value
                                    && checkbox
                                ) {
                                    checkbox.checked = false;
                                }

                                actualizarFilaTanque(fila);
                            });

                        actualizarGasolineraInterna();
                        actualizarResumen();
                    }
                );

                document
                    .querySelectorAll(
                        '[data-fila-tanque]'
                    )
                    .forEach(function (fila) {
                        const checkbox = fila.querySelector(
                            '[data-tanque-check]'
                        );

                        const galones = fila.querySelector(
                            '[data-tanque-galones]'
                        );

                        checkbox?.addEventListener(
                            'change',
                            function () {
                                actualizarFilaTanque(
                                    fila
                                );

                                actualizarResumen();
                            }
                        );

                        galones?.addEventListener(
                            'input',
                            function () {
                                actualizarFilaTanque(
                                    fila
                                );

                                actualizarResumen();
                            }
                        );
                    });

                document
                    .querySelectorAll(
                        '[data-fila-marchamo]'
                    )
                    .forEach(function (fila) {
                        const checkbox = fila.querySelector(
                            '[data-marchamo-check]'
                        );

                        const punto = fila.querySelector(
                            '[data-marchamo-punto]'
                        );

                        const codigo = fila.querySelector(
                            '[data-marchamo-codigo]'
                        );

                        const ayuda = fila.querySelector(
                            '[data-marchamo-ayuda]'
                        );

                        function actualizarMarchamo() {
                            const seleccionado =
                                checkbox.checked;

                            punto.disabled = ! seleccionado;
                            codigo.disabled = ! seleccionado;

                            fila.classList.toggle(
                                'opacity-80',
                                ! seleccionado
                            );

                            ayuda.textContent = seleccionado
                                ? 'Código requerido'
                                : 'Seleccione el tapón';

                            if (! seleccionado) {
                                codigo.value = '';
                            }

                            actualizarResumen();
                        }

                        checkbox.addEventListener(
                            'change',
                            actualizarMarchamo
                        );

                        actualizarMarchamo();
                    });

                [
                    volumenInicial,
                    galonesExternos,
                    precioGalon,
                ]
                    .filter(Boolean)
                    .forEach(function (campo) {
                        campo.addEventListener(
                            'input',
                            actualizarResumen
                        );
                    });

                @if ($requiereRutas)
                    const contenedorRutas =
                        document.getElementById(
                            'contenedor-rutas'
                        );

                    const plantillaRuta =
                        document.getElementById(
                            'plantilla-ruta'
                        );

                    const botonAgregarRuta =
                        document.getElementById(
                            'boton-agregar-ruta'
                        );

                    let contadorRutas = 0;

                    function agregarRuta(
                        rutaId = '',
                        tipoRecorrido = 'ida'
                    ) {
                        const fragmento =
                            plantillaRuta.content
                                .cloneNode(true);

                        const fila = fragmento.querySelector(
                            '[data-fila-ruta]'
                        );

                        const selectorRuta =
                            fragmento.querySelector(
                                '[data-ruta-select]'
                            );

                        const selectorTipo =
                            fragmento.querySelector(
                                '[data-ruta-tipo]'
                            );

                        const botonEliminar =
                            fragmento.querySelector(
                                '[data-eliminar-ruta]'
                            );

                        selectorRuta.name =
                            'rutas['
                            + contadorRutas
                            + '][ruta_id]';

                        selectorTipo.name =
                            'rutas['
                            + contadorRutas
                            + '][tipo_recorrido]';

                        selectorRuta.value = rutaId;
                        selectorTipo.value =
                            tipoRecorrido;

                        botonEliminar.addEventListener(
                            'click',
                            function () {
                                fila.remove();
                            }
                        );

                        contenedorRutas.appendChild(
                            fragmento
                        );

                        contadorRutas++;
                    }

                    botonAgregarRuta.addEventListener(
                        'click',
                        function () {
                            agregarRuta();
                        }
                    );

                    const rutasAnteriores =
                        @json($rutasAnteriores->values());

                    if (rutasAnteriores.length > 0) {
                        rutasAnteriores.forEach(
                            function (ruta) {
                                agregarRuta(
                                    ruta.ruta_id || '',
                                    ruta.tipo_recorrido
                                        || 'ida'
                                );
                            }
                        );
                    } else {
                        agregarRuta();
                    }
                @endif

                formulario.addEventListener(
                    'submit',
                    function (event) {
                        const cargado =
                            galonesCargados();

                        const inicial = numero(
                            volumenInicial?.value
                        );

                        if (cargado <= 0) {
                            event.preventDefault();

                            window.alert(
                                'Debe registrar una cantidad de combustible mayor que cero.'
                            );

                            return;
                        }

                        if (
                            inicial + cargado
                            > capacidadUnidad
                        ) {
                            event.preventDefault();

                            window.alert(
                                'El combustible inicial más la carga excede la capacidad cubierta de la unidad.'
                            );

                            return;
                        }

                        const seleccionados =
                            Array.from(
                                document.querySelectorAll(
                                    '[data-marchamo-check]:checked'
                                )
                            );

                        if (
                            seleccionados.length === 0
                        ) {
                            event.preventDefault();

                            window.alert(
                                'Debe seleccionar al menos un tapón abierto.'
                            );

                            return;
                        }

                        for (
                            const checkbox
                            of seleccionados
                        ) {
                            const fila =
                                checkbox.closest(
                                    '[data-fila-marchamo]'
                                );

                            const codigo =
                                fila.querySelector(
                                    '[data-marchamo-codigo]'
                                );

                            if (
                                ! /^\d{7}$/.test(
                                    codigo.value.trim()
                                )
                            ) {
                                event.preventDefault();

                                window.alert(
                                    'Cada tapón seleccionado debe tener un nuevo código de exactamente 7 dígitos.'
                                );

                                codigo.focus();

                                return;
                            }
                        }

                        const confirmado =
                            window.confirm(
                                '¿Está seguro de registrar este abastecimiento? La operación actualizará inventario cuando corresponda y reemplazará los marchamos seleccionados.'
                            );

                        if (! confirmado) {
                            event.preventDefault();
                        }
                    }
                );

                actualizarOrigen();
                actualizarResumen();
            }
        );
    </script>
</x-app-layout>