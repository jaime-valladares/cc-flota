@php
    $queryParams = request()->query();

    $licencia = $unidad->licencia;

    $unidadRegistradaSinLicencia =
        $unidad->estado === 'registrada'
        && ! $licencia;

    $licenciaVigente =
        $licencia
        && $licencia->esta_vigente;

    $puedeEditar =
        $unidad->estado !== 'inactiva'
        && (
            $unidadRegistradaSinLicencia
            || $licenciaVigente
        );

    $puedeInactivar =
        $unidad->estado !== 'inactiva'
        && (
            $unidadRegistradaSinLicencia
            || $licenciaVigente
        );

    $disponibilidadBloqueadaPorLicencia = in_array(
        $unidad->disponibilidad_operativa,
        [
            'sin_licencia',
            'licencia_inactiva',
            'licencia_pendiente_activacion',
            'licencia_vencida',
        ],
        true
    );

    $disponibilidadPendiente = in_array(
        $unidad->disponibilidad_operativa,
        [
            'asignacion_inicial_pendiente',
            'pendiente_activacion_operativa',
        ],
        true
    );
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta
            name="viewport"
            content="width=device-width, initial-scale=1"
        >
        <meta
            name="csrf-token"
            content="{{ csrf_token() }}"
        >

        <title>
            Ficha administrativa de unidad | CC-Flota
        </title>

        @include('layouts.partials.favicon')

        <link
            rel="preconnect"
            href="https://fonts.googleapis.com"
        >
        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin
        >
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@400;450;500;600;700;800&family=Plus+Jakarta+Sans:wght@600;700;800&display=swap"
            rel="stylesheet"
        >

        @vite([
            'resources/css/app.css',
            'resources/js/app.js',
        ])
    </head>

    <body class="antialiased">
        <div
            class="min-h-screen"
            style="background: var(--cc-bg-main);"
        >
            <div class="cc-page-wrapper">
                <div
                    class="cc-window-container"
                    style="max-width: 79rem;"
                >
                    <div class="cc-card">

                        <div class="cc-card-header cc-card-header-compact">
                            <div>
                                <h3 class="cc-title cc-title-compact">
                                    Ficha administrativa de unidad
                                </h3>
                            </div>

                            <div class="flex items-center gap-3">
                                <a
                                    href="{{ route(
                                        'unidades.administrar.ventana',
                                        $queryParams
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-wide"
                                >
                                    Volver a administrar
                                </a>

                            </div>
                        </div>

                        @if (session('success'))
                            <div class="cc-alert cc-alert-success">
                                {{ session('success') }}
                            </div>
                        @endif

                        @if ($errors->any())
                            <div class="cc-alert cc-alert-danger">
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
                                    Unidad
                                </div>

                                <div class="cc-profile-title">
                                    {{ $unidad->placa }}
                                </div>

                                <div class="cc-profile-meta">
                                    <span>
                                        {{ $unidad->marca ?: 'Sin marca registrada' }}
                                    </span>

                                    @if ($unidad->empresa)
                                        <span>
                                            Empresa:
                                            {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
                                        </span>
                                    @else
                                        <span>
                                            Empresa: Sin empresa
                                        </span>
                                    @endif

                                    <span>
                                        Modelo:
                                        {{ $unidad->modelo_medicion_texto }}
                                    </span>
                                </div>
                            </div>

                            <div class="cc-profile-status">
                                @if ($unidad->estado === 'registrada')
                                    <span class="cc-badge cc-badge-pending">
                                        {{ $unidad->estado_texto }}
                                    </span>
                                @elseif ($unidad->estado === 'activa')
                                    <span class="cc-badge cc-badge-active">
                                        {{ $unidad->estado_texto }}
                                    </span>
                                @else
                                    <span class="cc-badge cc-badge-inactive">
                                        {{ $unidad->estado_texto }}
                                    </span>
                                @endif

                                @if ($unidad->disponibilidad_operativa === 'operable')
                                    <span class="cc-badge cc-badge-active">
                                        Operable
                                    </span>
                                @elseif ($disponibilidadPendiente)
                                    <span class="cc-badge cc-badge-pending">
                                        {{ $unidad->disponibilidad_operativa_texto }}
                                    </span>
                                @else
                                    <span class="cc-badge cc-badge-inactive">
                                        {{ $unidad->disponibilidad_operativa_texto }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        @if ($unidad->disponibilidad_operativa === 'operable')
                            <div class="cc-alert cc-alert-success">
                                <div class="font-bold">
                                    Unidad operable
                                </div>

                                <div class="mt-1">
                                    {{ $unidad->disponibilidad_operativa_descripcion }}
                                </div>
                            </div>
                        @elseif ($disponibilidadBloqueadaPorLicencia)
                            <div class="cc-alert cc-alert-danger">
                                <div class="font-bold">
                                    {{ $unidad->disponibilidad_operativa_texto }}
                                </div>

                                <div class="mt-1">
                                    {{ $unidad->disponibilidad_operativa_descripcion }}
                                </div>

                                @if (
                                    $licencia
                                    && $licencia->esta_vencida
                                )
                                    <div class="mt-2">
                                        Para restablecer esta capa operativa,
                                        la licencia debe renovarse desde el
                                        módulo de Licencias.
                                    </div>
                                @elseif (
                                    $licencia
                                    && $licencia->esta_inactiva
                                )
                                    <div class="mt-2">
                                        La licencia debe reactivarse desde su
                                        ficha antes de continuar operando con
                                        esta unidad.
                                    </div>
                                @elseif (
                                    $licencia
                                    && $licencia->esta_pendiente_activacion
                                )
                                    <div class="mt-2">
                                        La unidad se habilitará
                                        contractualmente cuando llegue la fecha
                                        de activación de la licencia.
                                    </div>
                                @endif
                            </div>
                        @else
                            <section class="cc-info-panel">
                                <div
                                    class="cc-form-section cc-form-section-compact"
                                    style="margin-top: 0; margin-bottom: 0;"
                                >
                                    <div class="cc-form-section-title">
                                        {{ $unidad->disponibilidad_operativa_texto }}
                                    </div>

                                    <div class="cc-form-section-note">
                                        {{ $unidad->disponibilidad_operativa_descripcion }}
                                    </div>
                                </div>
                            </section>
                        @endif

                        <div class="cc-detail-layout">

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Identificación
                                    </h5>

                                    <p>
                                        Datos principales de identificación
                                        de la unidad y empresa propietaria.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Empresa
                                        </div>

                                        <div class="cc-detail-value">
                                            @if ($unidad->empresa)
                                                {{ $unidad->empresa->nombre_comercial ?: $unidad->empresa->nombre_legal }}
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
                                            {{ $unidad->empresa->nit ?? 'No registrado' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Placa
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->placa }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Marca
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->marca ?: 'No registrada' }}
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Estado y disponibilidad
                                    </h5>

                                    <p>
                                        Diferencia entre el estado
                                        administrativo de la unidad y su
                                        disponibilidad real para operar.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Estado administrativo
                                        </div>

                                        <div class="cc-detail-value">
                                            @if ($unidad->estado === 'registrada')
                                                <span class="cc-badge cc-badge-pending">
                                                    {{ $unidad->estado_texto }}
                                                </span>
                                            @elseif ($unidad->estado === 'activa')
                                                <span class="cc-badge cc-badge-active">
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
                                            @if ($unidad->disponibilidad_operativa === 'operable')
                                                <span class="cc-badge cc-badge-active">
                                                    {{ $unidad->disponibilidad_operativa_texto }}
                                                </span>
                                            @elseif ($disponibilidadPendiente)
                                                <span class="cc-badge cc-badge-pending">
                                                    {{ $unidad->disponibilidad_operativa_texto }}
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-inactive">
                                                    {{ $unidad->disponibilidad_operativa_texto }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">
                                            Explicación
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->disponibilidad_operativa_descripcion }}
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Licencia
                                    </h5>

                                    <p>
                                        Vigencia contractual que habilita la
                                        configuración y operación de la
                                        unidad.
                                    </p>
                                </div>

                                @if ($licencia)
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
                                                @elseif ($licencia->esta_pendiente_activacion)
                                                    <span class="cc-badge cc-badge-pending">
                                                        {{ $licencia->condicion_vigencia_texto }}
                                                    </span>
                                                @else
                                                    <span class="cc-badge cc-badge-inactive">
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
                                                Vencimiento
                                            </div>

                                            <div class="cc-detail-value">
                                                {{ $licencia->vencimiento_relativo_texto }}
                                            </div>
                                        </div>

                                        <div class="cc-detail-item">
                                            <div class="cc-detail-label">
                                                Plantilla de seguridad
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

                                        @if ($licencia->estado === 'inactiva')
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
                                @else
                                    <div class="cc-info-panel">
                                        <div
                                            class="cc-form-section cc-form-section-compact"
                                            style="margin-top: 0; margin-bottom: 0;"
                                        >
                                            <div class="cc-form-section-title">
                                                Sin licencia registrada
                                            </div>

                                            <div class="cc-form-section-note">
                                                Diesel Cop debe registrar la
                                                licencia antes de iniciar la
                                                configuración de puntos de
                                                seguridad y marchamos.
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Asignación inicial de marchamos
                                    </h5>

                                    <p>
                                        Progreso de instalación de marchamos
                                        en los puntos de seguridad que protegen
                                        la unidad.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Estado de asignación
                                        </div>

                                        <div class="cc-detail-value">
                                            @if ($unidad->asignacion_inicial_marchamos_completa)
                                                <span class="cc-badge cc-badge-active">
                                                    Completa
                                                </span>
                                            @else
                                                <span class="cc-badge cc-badge-pending">
                                                    Pendiente
                                                </span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Puntos que requieren marchamo
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->total_puntos_que_requieren_marchamo }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Puntos con marchamo asignado
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->total_puntos_con_marchamo_asignado }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Puntos pendientes
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->total_puntos_pendientes_marchamo }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item cc-detail-item-wide">
                                        <div class="cc-detail-label">
                                            Resumen
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->asignacion_inicial_texto }}
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Tanques y cobertura Diesel Cop
                                    </h5>

                                    <p>
                                        Relación entre capacidad física de la
                                        unidad y cobertura protegida por el
                                        servicio.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Total de tanques
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->total_tanques }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Tanques protegidos
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->cantidad_tanques_con_licencia }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Capacidad total
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ number_format((float) $unidad->capacidad_total, 2) }}
                                            galones
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Capacidad cubierta
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ number_format((float) $unidad->capacidad_cubierta, 2) }}
                                            galones
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Medición operativa
                                    </h5>

                                    <p>
                                        Modelo utilizado para medir el consumo
                                        operativo de la unidad.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Modelo de medición
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->modelo_medicion_texto }}
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="cc-detail-section">
                                <div class="cc-detail-section-header">
                                    <h5>
                                        Control administrativo
                                    </h5>

                                    <p>
                                        Información de creación, actualización
                                        e inactivación administrativa del
                                        registro.
                                    </p>
                                </div>

                                <div class="cc-detail-grid">
                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Creado por
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->creadoPor->name ?? 'No registrado' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Fecha creación
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->created_at?->format('d/m/Y H:i') ?? 'No registrada' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Actualizado por
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->actualizadoPor->name ?? 'No registrado' }}
                                        </div>
                                    </div>

                                    <div class="cc-detail-item">
                                        <div class="cc-detail-label">
                                            Fecha actualización
                                        </div>

                                        <div class="cc-detail-value">
                                            {{ $unidad->updated_at?->format('d/m/Y H:i') ?? 'No registrada' }}
                                        </div>
                                    </div>

                                    @if ($unidad->estado === 'inactiva')
                                        <div class="cc-detail-item">
                                            <div class="cc-detail-label">
                                                Inactivado por
                                            </div>

                                            <div class="cc-detail-value">
                                                {{ $unidad->inactivadoPor->name ?? 'No registrado' }}
                                            </div>
                                        </div>

                                        <div class="cc-detail-item">
                                            <div class="cc-detail-label">
                                                Fecha inactivación
                                            </div>

                                            <div class="cc-detail-value">
                                                {{ $unidad->fecha_inactivacion?->format('d/m/Y H:i') ?? 'No registrada' }}
                                            </div>
                                        </div>

                                        <div class="cc-detail-item cc-detail-item-wide">
                                            <div class="cc-detail-label">
                                                Motivo de inactivación
                                            </div>

                                            <div class="cc-detail-value">
                                                {{ $unidad->motivo_inactivacion ?: 'No registrado' }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </section>

                        </div>

                        <div class="cc-actions cc-actions-split">
                            <div class="cc-actions-normal">

                                @if ($puedeEditar)
                                    <a
                                        href="{{ route(
                                            'unidades.edit.ventana',
                                            array_merge(
                                                $queryParams,
                                                ['unidad' => $unidad]
                                            )
                                        ) }}"
                                        class="cc-btn-primary cc-btn-form-action"
                                    >
                                        Editar unidad
                                    </a>
                                @endif

                                <a
                                    href="{{ route(
                                        'unidades.administrar.ventana',
                                        $queryParams
                                    ) }}"
                                    class="cc-btn-secondary cc-btn-form-action"
                                >
                                    Volver a administrar
                                </a>
                            </div>
                        </div>

                        @if (
                            $unidad->estado !== 'inactiva'
                            && ! $puedeEditar
                        )
                            <section class="cc-info-panel mt-7">
                                <div
                                    class="cc-form-section cc-form-section-compact"
                                    style="margin-top: 0; margin-bottom: 0;"
                                >
                                    <div class="cc-form-section-title">
                                        Edición no disponible
                                    </div>

                                    <div class="cc-form-section-note">
                                        {{ $unidad->disponibilidad_operativa_descripcion }}
                                    </div>
                                </div>
                            </section>
                        @endif

                        <section class="cc-danger-zone">
                            <div class="cc-danger-zone-header">
                                <div>
                                    <h5>
                                        Zona de riesgo
                                    </h5>

                                    <p>
                                        Modifique el estado administrativo de
                                        la unidad únicamente cuando exista una
                                        razón válida.
                                    </p>
                                </div>
                            </div>

                            @if ($unidad->estado === 'inactiva')
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'unidades.reactivar',
                                        array_merge(
                                            $queryParams,
                                            ['unidad' => $unidad]
                                        )
                                    ) }}"
                                    class="cc-danger-zone-form"
                                    onsubmit="return confirm('¿Está seguro de reactivar esta unidad?');"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <input
                                        type="hidden"
                                        name="return_to"
                                        value="ventana"
                                    >

                                    <div>
                                        <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                            Esta unidad se encuentra inactiva.
                                            Al reactivarla regresará al estado
                                            registrada.
                                        </p>

                                        <p class="mt-3 text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                            La reactivación de la unidad no
                                            modifica, reactiva ni renueva su
                                            licencia. Su disponibilidad
                                            operativa será calculada nuevamente
                                            según la licencia y los marchamos
                                            existentes.
                                        </p>
                                    </div>

                                    <button
                                        type="submit"
                                        class="cc-btn-success cc-btn-form-action"
                                    >
                                        Reactivar unidad
                                    </button>
                                </form>
                            @elseif ($puedeInactivar)
                                <form
                                    method="POST"
                                    action="{{ route(
                                        'unidades.inactivar',
                                        array_merge(
                                            $queryParams,
                                            ['unidad' => $unidad]
                                        )
                                    ) }}"
                                    class="cc-danger-zone-form"
                                    onsubmit="return confirm('¿Está seguro de inactivar esta unidad?');"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <input
                                        type="hidden"
                                        name="return_to"
                                        value="ventana"
                                    >

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
                                                value="Falta de uso"
                                                @selected(old('motivo_inactivacion') === 'Falta de uso')
                                            >
                                                Falta de uso
                                            </option>

                                            <option
                                                value="Unidad vendida"
                                                @selected(old('motivo_inactivacion') === 'Unidad vendida')
                                            >
                                                Unidad vendida
                                            </option>

                                            <option
                                                value="Unidad fuera de operación"
                                                @selected(old('motivo_inactivacion') === 'Unidad fuera de operación')
                                            >
                                                Unidad fuera de operación
                                            </option>

                                            <option
                                                value="Unidad reemplazada"
                                                @selected(old('motivo_inactivacion') === 'Unidad reemplazada')
                                            >
                                                Unidad reemplazada
                                            </option>

                                            <option
                                                value="Datos incorrectos en registro"
                                                @selected(old('motivo_inactivacion') === 'Datos incorrectos en registro')
                                            >
                                                Datos incorrectos en registro
                                            </option>

                                            <option
                                                value="Solicitud administrativa"
                                                @selected(old('motivo_inactivacion') === 'Solicitud administrativa')
                                            >
                                                Solicitud administrativa
                                            </option>

                                            <option
                                                value="Suspensión temporal"
                                                @selected(old('motivo_inactivacion') === 'Suspensión temporal')
                                            >
                                                Suspensión temporal
                                            </option>

                                            <option
                                                value="Otro"
                                                @selected(old('motivo_inactivacion') === 'Otro')
                                            >
                                                Otro
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
                                        Inactivar unidad
                                    </button>
                                </form>
                            @else
                                <div class="cc-danger-zone-form">
                                    <div>
                                        <p class="text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                            La inactivación administrativa no
                                            está disponible mientras la unidad
                                            se encuentre bloqueada por su
                                            licencia.
                                        </p>

                                        <p class="mt-3 text-sm text-[var(--cc-text-muted)] leading-relaxed">
                                            {{ $unidad->disponibilidad_operativa_descripcion }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </section>

                    </div>
                </div>
            </div>
        </div>
    </body>
</html>