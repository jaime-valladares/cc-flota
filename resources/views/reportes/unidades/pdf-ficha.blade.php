<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ficha completa de la unidad {{ $unidad->placa }}</title>
    <style>
        @page { margin: 24mm 14mm 17mm; }
        html, body { background: #fff; }
        body { margin: 0; color: #25343b; font-family: DejaVu Sans, sans-serif; font-size: 9px; }
        .header { border-bottom: 1px solid #147d82; left: 0; padding-bottom: 4px; position: fixed; right: 0; top: -17mm; }
        .header-table, .data-table, .compact-table, .points-table, .summary-table { width: 100%; border-collapse: collapse; }
        .logo { width: 92px; height: auto; }
        .title { color: #114d57; font-size: 11px; font-weight: bold; text-align: right; }
        .plate { color: #e07132; font-size: 10px; font-weight: bold; text-align: right; }
        .hero { background: #eef7f7; border-left: 4px solid #147d82; margin-bottom: 12px; padding: 9px 11px; }
        .hero-grid { width: 100%; border-collapse: collapse; }
        .hero-grid td { padding: 2px 8px 2px 0; vertical-align: top; }
        .hero-label { color: #68797e; font-size: 6.5px; text-transform: uppercase; }
        .hero-value { color: #25343b; font-size: 8.5px; font-weight: bold; margin-top: 1px; }
        .hero-status { float: right; font-size: 9px; font-weight: bold; }
        .section { margin: 0 0 12px; page-break-inside: avoid; }
        .section.points { page-break-inside: auto; }
        .section-title { border-bottom: 1px solid #9ebfc1; color: #126d73; font-size: 11px; font-weight: bold; margin-bottom: 6px; padding-bottom: 3px; }
        .data-table td { border-bottom: 1px solid #e0e8e9; padding: 4px 5px; width: 25%; }
        .label { color: #68797e; font-size: 7px; text-transform: uppercase; }
        .value { font-size: 9px; font-weight: bold; margin-top: 2px; }
        .compact-wrap { width: 72%; margin: 0 auto; }
        .compact-table thead, .points-table thead { display: table-header-group; }
        .compact-table tr, .points-table tr { page-break-inside: avoid; }
        .compact-table th, .points-table th { background: #126d73; border: 1px solid #0f5f64; color: #fff; padding: 5px 4px; text-align: left; }
        .compact-table td, .points-table td { border: 1px solid #d3dfe1; padding: 4px; vertical-align: top; }
        .right { text-align: right; }
        .center { text-align: center; }
        .summary-table td { background: #f2f7f7; border-right: 3px solid #fff; padding: 7px; text-align: center; width: 25%; }
        .summary-number { color: #114d57; font-size: 13px; font-weight: bold; }
        .summary-label { color: #68797e; font-size: 7px; margin-top: 2px; text-transform: uppercase; }
        .points-table { font-size: 7px; table-layout: fixed; }
        .points-table .order { width: 5%; text-align: center; }
        .points-table .point { width: 24%; }
        .points-table .group { width: 12%; }
        .points-table .subgroup { width: 12%; }
        .points-table .position { width: 16%; }
        .points-table .seal { width: 16%; }
        .points-table .seal-status { width: 15%; }
        .points-table tbody tr:nth-child(even) { background: #f7f9f9; }
        .result { font-size: 9px; font-weight: bold; }
        .operable { color: #147048; }
        .not-operable { color: #9e3f34; }
        .empty { color: #64757b; padding: 12px; text-align: center; }
        .page-footer { border-top: 1px solid #cddbdd; bottom: -11mm; color: #77878c; font-size: 6.5px; left: 0; padding-top: 3px; position: fixed; right: 0; }
    </style>
</head>
<body>
    @php
        $licencia = $unidad->licencia;
        $puntos = $unidad->puntosSeguridad->sortBy('orden');
    @endphp
    <div class="header"><table class="header-table"><tr>
        <td><img class="logo" src="{{ $logoPath }}" alt="CC-Flota"></td>
        <td><div class="title">Ficha completa de la unidad</div><div class="plate">Nombre / Placa: {{ $unidad->placa }}</div></td>
    </tr></table></div>

    <div class="hero">
        <table class="hero-grid"><tr>
            @foreach (['Empresa' => $unidad->empresa?->nombre_comercial ?: $unidad->empresa?->nombre_legal, 'Marca' => $unidad->marca ?: 'No registrada', 'Modelo de medición' => $unidad->modelo_medicion_texto, 'Estado' => $unidad->estado_texto, 'Disponibilidad' => $unidad->disponibilidad_operativa_texto] as $etiqueta => $valor)
                <td><div class="hero-label">{{ $etiqueta }}</div><div class="hero-value">{{ $valor }}</div></td>
            @endforeach
        </tr></table>
    </div>

    <div class="section"><div class="section-title">Identificación</div><table class="data-table">
        <tr><td><div class="label">Empresa</div><div class="value">{{ $unidad->empresa?->nombre_comercial ?: $unidad->empresa?->nombre_legal }}</div></td><td><div class="label">Nombre / Placa</div><div class="value">{{ $unidad->placa }}</div></td><td><div class="label">Marca</div><div class="value">{{ $unidad->marca ?: 'No registrada' }}</div></td><td><div class="label">Modelo de medición</div><div class="value">{{ $unidad->modelo_medicion_texto }}</div></td></tr>
        <tr><td><div class="label">Rendimiento Teórico</div><div class="value">{{ $unidad->rendimiento_teorico_reporte }}</div></td><td><div class="label">Capacidad total</div><div class="value">{{ number_format((float) $unidad->capacidad_total, 2) }} gal</div></td><td><div class="label">Total de tanques</div><div class="value">{{ $unidad->total_tanques }}</div></td><td></td></tr>
    </table></div>

    <div class="section"><div class="section-title">Tanques físicos</div><div class="compact-wrap"><table class="compact-table">
        <thead><tr><th style="width:20%">Tanque</th><th class="right" style="width:30%">Capacidad</th><th style="width:50%">Cubierto por licencia</th></tr></thead><tbody>
        @forelse ($unidad->tanquesUnidad as $tanque)
            <tr><td>Tanque {{ $tanque->numero }}</td><td class="right">{{ number_format((float) $tanque->capacidad, 2) }} gal</td><td>{{ $tanque->cubierto_por_licencia ? 'Sí' : 'No' }}</td></tr>
        @empty <tr><td colspan="3" class="empty">Sin tanques físicos registrados.</td></tr> @endforelse
        </tbody>
    </table></div></div>

    <div class="section"><div class="section-title">Licencia</div>
        @if ($licencia)
            <table class="data-table"><tr>
                @foreach (['Estado administrativo' => $licencia->estado_texto, 'Condición de vigencia' => $licencia->condicion_vigencia_texto, 'Período' => $licencia->periodo_vigencia_texto, 'Fecha de activación' => $licencia->fecha_activacion?->format('d/m/Y') ?? 'Pendiente', 'Fecha de vencimiento' => $licencia->fecha_vencimiento?->format('d/m/Y') ?? 'Pendiente', 'Vencimiento relativo' => $licencia->vencimiento_relativo_texto, 'Plantilla de seguridad' => $licencia->plantilla_puntos_seguridad_texto, 'Puntos esperados' => $licencia->cantidad_puntos_seguridad_esperados ?? 'Pendiente', 'Tanques cubiertos' => $licencia->cantidad_tanques_cubiertos, 'Capacidad cubierta' => number_format((float) $licencia->capacidad_cubierta, 2).' gal'] as $etiqueta => $valor)
                    <td><div class="label">{{ $etiqueta }}</div><div class="value">{{ $valor }}</div></td>@if ($loop->iteration % 4 === 0)</tr><tr>@endif
                @endforeach
            </tr></table>
        @else <div class="empty">Sin licencia</div> @endif
    </div>

    <div class="section"><div class="section-title">Estado operacional</div><table class="data-table">
        <tr><td><div class="label">Estado administrativo</div><div class="value">{{ $unidad->estado_texto }}</div></td><td><div class="label">Disponibilidad operativa</div><div class="value">{{ $unidad->disponibilidad_operativa_texto }}</div></td><td colspan="2"><div class="label">Descripción</div><div class="value">{{ $unidad->disponibilidad_operativa_descripcion }}</div></td></tr>
        <tr><td><div class="label">Condición de licencia</div><div class="value">{{ $licencia?->condicion_vigencia_texto ?? 'Sin licencia' }}</div></td><td><div class="label">Marchamos asignados / requeridos</div><div class="value">{{ $unidad->total_puntos_con_marchamo_asignado }} / {{ $unidad->total_puntos_que_requieren_marchamo }}</div></td><td colspan="2"><div class="label">Resultado final</div><div class="result {{ $unidad->es_operable ? 'operable' : 'not-operable' }}">{{ $unidad->es_operable ? 'OPERABLE' : 'NO OPERABLE' }}</div></td></tr>
    </table></div>

    <div class="section"><div class="section-title">Resumen de seguridad</div><table class="summary-table"><tr>
        <td><div class="summary-number">{{ $unidad->total_puntos_que_requieren_marchamo }}</div><div class="summary-label">Puntos de seguridad</div></td><td><div class="summary-number">{{ $unidad->total_puntos_con_marchamo_asignado }}</div><div class="summary-label">Marchamos asignados</div></td><td><div class="summary-number">{{ $unidad->total_puntos_pendientes_marchamo }}</div><div class="summary-label">Pendientes</div></td><td><div class="summary-number">{{ $unidad->asignacion_marchamos_completa ? 'Completa' : 'Pendiente' }}</div><div class="summary-label">Cobertura</div></td>
    </tr></table></div>

    <div class="section points"><div class="section-title">Puntos de seguridad y marchamos actuales</div><table class="points-table">
        <thead><tr><th class="order">Orden</th><th class="point">Código / Punto</th><th class="group">Grupo</th><th class="subgroup">Subgrupo</th><th class="position">Posición / Tanque</th><th class="seal">Marchamo actual</th><th class="seal-status">Estado marchamo</th></tr></thead><tbody>
        @forelse ($puntos as $punto)
            <tr data-pdf-security-point="{{ $punto->id }}"><td class="order">{{ $punto->orden }}</td><td>{{ $punto->codigo_punto ?: 'Sin código' }} · {{ $punto->nombre_punto }}</td><td>{{ $punto->grupo ?: '—' }}</td><td>{{ $punto->subgrupo ?: '—' }}</td><td>{{ $punto->posicion_tanque ?: 'General' }}</td><td>{{ $punto->marchamoActual?->codigo_marchamo ?? 'Pendiente' }}</td><td>{{ $punto->marchamoActual?->estado_texto ?? 'Pendiente' }}</td></tr>
        @empty <tr><td colspan="7" class="empty">Sin puntos de seguridad registrados.</td></tr> @endforelse
        </tbody>
    </table></div>

    <div class="page-footer">CC-Flota · {{ $unidad->placa }} · Generado: {{ $generadoEn->format('d/m/Y H:i') }}</div>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_text(500, 817, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 7, [0.35, 0.43, 0.46]);
        }
    </script>
</body>
</html>
