@php
    $queryParams = request()->query();

    $unidad = $licencia->unidad;

    $licenciaEditable =
        Auth::user()->tienePermiso('licencias.editar')
        && $licencia->estado === 'activa'
        && ! $licencia->esta_vencida;

    $licenciaRenovable =
        Auth::user()->tienePermiso('licencias.reactivar')
        && $licencia->estado === 'activa'
        && $licencia->esta_vencida;

    $licenciaReactivable =
        Auth::user()->tienePermiso('licencias.reactivar')
        && $licencia->estado === 'inactiva';

    $licenciaInactivable =
        Auth::user()->tienePermiso('licencias.inactivar')
        && $licencia->estado === 'activa'
        && ! $licencia->esta_vencida;

    $condicionAdvertencia = in_array(
        $licencia->condicion_vigencia,
        [
            'pendiente_activacion',
            'proxima_vencer',
        ],
        true
    );

    $condicionBloqueada = in_array(
        $licencia->condicion_vigencia,
        [
            'inactiva',
            'vencida',
            'no_definida',
        ],
        true
    );

    $unidadDisponibilidadPendiente = $unidad
        && in_array(
            $unidad->disponibilidad_operativa,
            [
                'asignacion_inicial_pendiente',
                'pendiente_activacion_operativa',
            ],
            true
        );
@endphp

<x-app-layout>
    <div class="cc-page-wrapper">
        <div
            class="cc-content-container"
            style="max-width: 80rem;"
        >
            <div class="cc-card">

                <div class="cc-card-header cc-card-header-compact">
                    <div>
                        <h3 class="cc-title cc-title-compact">
                            Ficha administrativa de licencia
                        </h3>

                    </div>

                    <div class="flex items-center gap-3">
                        <a
                            href="{{ route(
                                'licencias.administrar',
                                $queryParams
                            ) }}"
                            class="cc-btn-secondary cc-btn-wide"
                        >
                            Volver a administrar
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
                            @foreach ($errors->all() as $error)
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
                            Licencia Diesel Cop
                        </div>

                        <div class="cc-profile-title">
                            {{ $unidad->placa ?? 'Sin Nombre / Placa' }}
                        </div>

                        <div class="cc-profile-meta">
                            <span>
                                {{ $unidad->marca ?? 'Sin marca registrada' }}
                            </span>

                            @if ($licencia->empresa)
                                <span>
                                    Empresa:
                                    {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                </span>
                            @else
                                <span>
                                    Empresa: Sin empresa
                                </span>
                            @endif

                            <span>
                                Vence:
                                {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'No registrado' }}
                            </span>
                        </div>
                    </div>

                    <div class="cc-profile-status flex flex-wrap justify-end gap-4">
                        <div class="flex flex-col items-end gap-1">
                            <span class="text-xs font-semibold text-[var(--cc-text-muted)]">
                                Estado administrativo
                            </span>

                            @if ($licencia->estado === 'activa')
                                <span class="cc-badge cc-badge-active">
                                    {{ $licencia->estado_texto }}
                                </span>
                            @else
                                <span class="cc-badge cc-badge-inactive">
                                    {{ $licencia->estado_texto }}
                                </span>
                            @endif
                        </div>

                        <div class="flex flex-col items-end gap-1">
                            <span class="text-xs font-semibold text-[var(--cc-text-muted)]">
                                Condición
                            </span>

                            @if ($licencia->esta_vigente)
                                <span class="cc-badge cc-badge-active">
                                    {{ $licencia->condicion_vigencia_texto }}
                                </span>
                            @elseif ($condicionAdvertencia)
                                <span class="cc-badge cc-badge-warning">
                                    {{ $licencia->condicion_vigencia_texto }}
                                </span>
                            @else
                                <span class="cc-badge cc-badge-danger">
                                    {{ $licencia->condicion_vigencia_texto }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($licencia->esta_vigente)
                    <div class="cc-status-strip cc-status-strip-active">
                        <div>
                            <strong>
                            Licencia vigente
                            </strong>

                            <span>
                                Vence el {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'día no registrado' }}
                                · {{ $licencia->vencimiento_relativo_texto }}
                            </span>
                        </div>
                    </div>
                @elseif ($licencia->esta_pendiente_activacion)
                    <div class="cc-status-strip cc-status-strip-warning">
                        <div>
                            <strong>
                            Licencia pendiente de activación
                            </strong>

                            <span>
                                Activación programada:
                                {{ $licencia->fecha_activacion?->format('d/m/Y') ?? 'día no registrado' }}
                            </span>
                        </div>
                    </div>
                @elseif ($licencia->esta_vencida)
                    <div class="cc-alert-danger">
                        <div class="font-bold">
                            Licencia vencida
                        </div>

                        <div class="mt-1">
                            {{ $licencia->habilitacion_operacion_texto }}
                        </div>

                        <div class="mt-2">
                            {{ $licencia->vencimiento_relativo_texto }}.
                            La unidad permanecerá bloqueada hasta renovar
                            esta licencia.
                        </div>
                    </div>
                @elseif ($licencia->esta_inactiva)
                    <div class="cc-alert-danger">
                        <div class="font-bold">
                            Licencia inactiva
                        </div>

                        <div class="mt-1">
                            {{ $licencia->habilitacion_operacion_texto }}
                        </div>

                        <div class="mt-2">
                            Debe reactivarse con un nuevo período y fecha
                            de activación antes de volver a habilitar la
                            capa contractual de la unidad.
                        </div>
                    </div>
                @else
                    <div class="cc-alert-danger">
                        <div class="font-bold">
                            Vigencia no definida
                        </div>

                        <div class="mt-1">
                            No es posible determinar correctamente la
                            vigencia de esta licencia.
                        </div>
                    </div>
                @endif

                <div class="cc-detail-layout">

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Unidad con licencia
                            </h5>

                            <p>
                                Identificación de la empresa y unidad
                                cubiertas por la licencia.
                            </p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Empresa
                                </div>

                                <div class="cc-detail-value">
                                    @if ($licencia->empresa)
                                        {{ $licencia->empresa->nombre_comercial ?: $licencia->empresa->nombre_legal }}
                                    @else
                                        Sin empresa
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    NIT empresa
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->empresa->nit ?? 'No registrado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Nombre / Placa
                                </div>

                                <div class="cc-detail-value">
                                    {{ $unidad->placa ?? 'Sin Nombre / Placa' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Marca
                                </div>

                                <div class="cc-detail-value">
                                    {{ $unidad->marca ?? 'No registrada' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Estado administrativo de unidad
                                </div>

                                <div class="cc-detail-value">
                                    @if (! $unidad)
                                        No disponible
                                    @elseif ($unidad->estado === 'activa')
                                        <span class="cc-badge cc-badge-active">
                                            {{ $unidad->estado_texto }}
                                        </span>
                                    @elseif ($unidad->estado === 'registrada')
                                        <span class="cc-badge cc-badge-warning">
                                            {{ $unidad->estado_texto }}
                                        </span>
                                    @else
                                        <span class="cc-badge cc-badge-inactive">
                                            {{ $unidad->estado_texto }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Disponibilidad operativa
                                </div>

                                <div class="cc-detail-value">
                                    @if (! $unidad)
                                        No disponible
                                    @elseif ($unidad->disponibilidad_operativa === 'operable')
                                        <span class="cc-badge cc-badge-active">
                                            {{ $unidad->disponibilidad_operativa_texto }}
                                        </span>
                                    @elseif ($unidadDisponibilidadPendiente)
                                        <span class="cc-badge cc-badge-warning">
                                            {{ $unidad->disponibilidad_operativa_texto }}
                                        </span>
                                    @else
                                        <span class="cc-badge cc-badge-danger">
                                            {{ $unidad->disponibilidad_operativa_texto }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if ($unidad)
                                <div class="cc-detail-item cc-detail-item-wide">
                                    <div class="cc-detail-label">
                                        Explicación de disponibilidad
                                    </div>

                                    <div class="cc-detail-value">
                                        {{ $unidad->disponibilidad_operativa_descripcion }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Vigencia contractual
                            </h5>

                            <p>
                                Estado administrativo y condición calculada
                                según las fechas registradas.
                            </p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Estado administrativo
                                </div>

                                <div class="cc-detail-value">
                                    @if ($licencia->estado === 'activa')
                                        <span class="cc-badge cc-badge-active">
                                            {{ $licencia->estado_texto }}
                                        </span>
                                    @else
                                        <span class="cc-badge cc-badge-inactive">
                                            {{ $licencia->estado_texto }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Condición de vigencia
                                </div>

                                <div class="cc-detail-value">
                                    @if ($licencia->esta_vigente)
                                        <span class="cc-badge cc-badge-active">
                                            {{ $licencia->condicion_vigencia_texto }}
                                        </span>
                                    @elseif ($condicionAdvertencia)
                                        <span class="cc-badge cc-badge-warning">
                                            {{ $licencia->condicion_vigencia_texto }}
                                        </span>
                                    @else
                                        <span class="cc-badge cc-badge-danger">
                                            {{ $licencia->condicion_vigencia_texto }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Período
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->periodo_vigencia_texto }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Fecha de activación
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->fecha_activacion?->format('d/m/Y') ?? 'No registrada' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Fecha de vencimiento
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'No registrada' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Vencimiento relativo
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->vencimiento_relativo_texto }}
                                </div>
                            </div>

                            <div class="cc-detail-item cc-detail-item-wide">
                                <div class="cc-detail-label">
                                    Habilitación contractual
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->habilitacion_operacion_texto }}
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Puntos de seguridad y marchamos
                            </h5>

                            <p>
                                Plantilla generada y avance de la asignación
                                inicial requerida para activar la unidad.
                            </p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Plantilla
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->plantilla_puntos_seguridad_texto }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Puntos esperados
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->cantidad_puntos_seguridad_esperados ?? 'No definido' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Puntos que requieren marchamo
                                </div>

                                <div class="cc-detail-value">
                                    {{ $unidad?->total_puntos_que_requieren_marchamo ?? 0 }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Marchamos asignados
                                </div>

                                <div class="cc-detail-value">
                                    {{ $unidad?->total_puntos_con_marchamo_asignado ?? 0 }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Marchamos pendientes
                                </div>

                                <div class="cc-detail-value">
                                    {{ $unidad?->total_puntos_pendientes_marchamo ?? 0 }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Estado de asignación inicial
                                </div>

                                <div class="cc-detail-value">
                                    @if ($unidad?->asignacion_inicial_marchamos_completa)
                                        <span class="cc-badge cc-badge-active">
                                            Completa
                                        </span>
                                    @else
                                        <span class="cc-badge cc-badge-warning">
                                            Pendiente
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Tanques protegidos
                                </div>

                                <div class="cc-detail-value">
                                    {{ $unidad->cantidad_tanques_con_licencia ?? 'No registrado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Capacidad cubierta
                                </div>

                                <div class="cc-detail-value">
                                    @if ($unidad)
                                        {{ number_format(
                                            (float) $unidad->capacidad_cubierta,
                                            2
                                        ) }}
                                        galones
                                    @else
                                        No registrada
                                    @endif
                                </div>
                            </div>

                            @if ($unidad)
                                <div class="cc-detail-item cc-detail-item-wide">
                                    <div class="cc-detail-label">
                                        Resumen de asignación
                                    </div>

                                    <div class="cc-detail-value">
                                        {{ $unidad->asignacion_inicial_texto }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                    <section class="cc-detail-section">
                        <div class="cc-detail-section-header">
                            <h5>
                                Control administrativo
                            </h5>

                            <p>
                                Usuarios, fechas y datos de trazabilidad de
                                la licencia.
                            </p>
                        </div>

                        <div class="cc-detail-grid">
                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Creado por
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->creadoPor
                                        ? trim($licencia->creadoPor->name . ' ' . ($licencia->creadoPor->apellido ?? ''))
                                        : 'No registrado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Fecha de creación
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->created_at?->format('d/m/Y H:i') ?? 'No registrada' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Actualizado por
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->actualizadoPor
                                        ? trim($licencia->actualizadoPor->name . ' ' . ($licencia->actualizadoPor->apellido ?? ''))
                                        : 'No registrado' }}
                                </div>
                            </div>

                            <div class="cc-detail-item">
                                <div class="cc-detail-label">
                                    Fecha de actualización
                                </div>

                                <div class="cc-detail-value">
                                    {{ $licencia->updated_at?->format('d/m/Y H:i') ?? 'No registrada' }}
                                </div>
                            </div>

                            @if ($licencia->estado === 'inactiva')
                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">
                                        Inactivada por
                                    </div>

                                    <div class="cc-detail-value">
                                        {{ $licencia->inactivadoPor
                                            ? trim($licencia->inactivadoPor->name . ' ' . ($licencia->inactivadoPor->apellido ?? ''))
                                            : 'No registrado' }}
                                    </div>
                                </div>

                                <div class="cc-detail-item">
                                    <div class="cc-detail-label">
                                        Fecha de inactivación
                                    </div>

                                    <div class="cc-detail-value">
                                        {{ $licencia->fecha_inactivacion?->format('d/m/Y H:i') ?? 'No registrada' }}
                                    </div>
                                </div>

                                <div class="cc-detail-item cc-detail-item-wide">
                                    <div class="cc-detail-label">
                                        Motivo de inactivación
                                    </div>

                                    <div class="cc-detail-value">
                                        {{ $licencia->motivo_inactivacion ?: 'No registrado' }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </section>

                </div>

                <div class="cc-actions cc-actions-split">
                    <div class="cc-actions-normal">
                        @if ($licenciaEditable)
                            <a
                                href="{{ route(
                                    'licencias.edit',
                                    array_merge(
                                        $queryParams,
                                        ['licencia' => $licencia]
                                    )
                                ) }}"
                                class="cc-btn-primary cc-btn-form-action"
                            >
                                Editar licencia
                            </a>
                        @endif

                        <a
                            href="{{ route(
                                'licencias.administrar',
                                $queryParams
                            ) }}"
                            class="cc-btn-secondary cc-btn-form-action"
                        >
                            Volver a administrar
                        </a>
                    </div>
                </div>

                @if (! $licenciaEditable)
                    <section class="cc-info-panel mt-7">
                        <div
                            class="cc-form-section cc-form-section-compact"
                            style="margin-top: 0; margin-bottom: 0;"
                        >
                            <div class="cc-form-section-title">
                                Edición no disponible
                            </div>

                            <div class="cc-form-section-note">
                                @if ($licencia->esta_vencida)
                                    La licencia debe renovarse desde esta ficha
                                    antes de volver a editarse.
                                @elseif ($licencia->esta_inactiva)
                                    La licencia debe reactivarse desde esta
                                    ficha antes de volver a editarse.
                                @else
                                    La condición actual de la licencia no
                                    permite su edición.
                                @endif
                            </div>
                        </div>
                    </section>
                @endif

                @if (
                    Auth::user()->tienePermiso('licencias.inactivar')
                    || Auth::user()->tienePermiso('licencias.reactivar')
                )
                    <section class="cc-danger-zone">
                    <div class="cc-danger-zone-header">
                        <div>
                            <h5>
                                Control de vigencia
                            </h5>

                            <p>
                                Inactive, reactive o renueve la licencia
                                únicamente cuando exista una razón válida.
                            </p>
                        </div>
                    </div>

                    @if ($licenciaInactivable)
                        <form
                            method="POST"
                            action="{{ route(
                                'licencias.inactivar',
                                array_merge(
                                    $queryParams,
                                    ['licencia' => $licencia]
                                )
                            ) }}"
                            class="cc-danger-zone-form"
                            onsubmit="return confirm('¿Está seguro de inactivar esta licencia?');"
                        >
                            @csrf
                            @method('PATCH')

                            <div class="cc-danger-zone-field">
                                <label for="motivo_inactivacion">
                                    Motivo de inactivación
                                    <span class="cc-required">*</span>
                                </label>

                                <select
                                    id="motivo_inactivacion"
                                    name="motivo_inactivacion"
                                    class="cc-input"
                                    required
                                >
                                    <option value="">
                                        Seleccione un motivo
                                    </option>

                                    <option
                                        value="Fin de cobertura"
                                        @selected(
                                            old('motivo_inactivacion')
                                            === 'Fin de cobertura'
                                        )
                                    >
                                        Fin de cobertura
                                    </option>

                                    <option
                                        value="Falta de pago"
                                        @selected(
                                            old('motivo_inactivacion')
                                            === 'Falta de pago'
                                        )
                                    >
                                        Falta de pago
                                    </option>

                                    <option
                                        value="Solicitud administrativa"
                                        @selected(
                                            old('motivo_inactivacion')
                                            === 'Solicitud administrativa'
                                        )
                                    >
                                        Solicitud administrativa
                                    </option>

                                    <option
                                        value="Cambio operativo"
                                        @selected(
                                            old('motivo_inactivacion')
                                            === 'Cambio operativo'
                                        )
                                    >
                                        Cambio operativo
                                    </option>

                                    <option
                                        value="Unidad fuera de servicio"
                                        @selected(
                                            old('motivo_inactivacion')
                                            === 'Unidad fuera de servicio'
                                        )
                                    >
                                        Unidad fuera de servicio
                                    </option>

                                    <option
                                        value="Corrección de registro"
                                        @selected(
                                            old('motivo_inactivacion')
                                            === 'Corrección de registro'
                                        )
                                    >
                                        Corrección de registro
                                    </option>
                                </select>

                                @error('motivo_inactivacion')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button
                                type="submit"
                                class="cc-btn-danger cc-btn-form-action"
                            >
                                Inactivar licencia
                            </button>
                        </form>
                    @elseif ($licenciaRenovable || $licenciaReactivable)
                        <form
                            method="POST"
                            action="{{ route(
                                'licencias.reactivar',
                                array_merge(
                                    $queryParams,
                                    ['licencia' => $licencia]
                                )
                            ) }}"
                            class="cc-danger-zone-form"
                            onsubmit="return confirm('{{ $licenciaRenovable ? '¿Está seguro de renovar esta licencia?' : '¿Está seguro de reactivar esta licencia?' }}');"
                        >
                            @csrf
                            @method('PATCH')

                            <div class="cc-danger-zone-field">
                                <label for="periodo_vigencia_meses">
                                    Nuevo período de vigencia
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

                                    <option
                                        value="3"
                                        @selected(
                                            old('periodo_vigencia_meses')
                                            === '3'
                                        )
                                    >
                                        3 meses
                                    </option>

                                    <option
                                        value="6"
                                        @selected(
                                            old('periodo_vigencia_meses')
                                            === '6'
                                        )
                                    >
                                        6 meses
                                    </option>

                                    <option
                                        value="12"
                                        @selected(
                                            old('periodo_vigencia_meses')
                                            === '12'
                                        )
                                    >
                                        12 meses
                                    </option>
                                </select>

                                @error('periodo_vigencia_meses')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="cc-danger-zone-field">
                                <label for="fecha_activacion">
                                    Nueva fecha de activación
                                    <span class="cc-required">*</span>
                                </label>

                                <input
                                    id="fecha_activacion"
                                    type="date"
                                    name="fecha_activacion"
                                    value="{{ old(
                                        'fecha_activacion',
                                        now()->format('Y-m-d')
                                    ) }}"
                                    class="cc-input"
                                    required
                                >

                                @error('fecha_activacion')
                                    <div class="cc-error">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <button
                                type="submit"
                                class="cc-btn-success cc-btn-form-action"
                            >
                                {{ $licenciaRenovable
                                    ? 'Renovar licencia'
                                    : 'Reactivar licencia' }}
                            </button>
                        </form>
                    @else
                        <div class="cc-danger-zone-form">
                            <div>
                                <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                    No hay una acción de cambio de estado
                                    disponible para la condición actual de
                                    esta licencia.
                                </p>
                            </div>
                        </div>
                    @endif
                    </section>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
