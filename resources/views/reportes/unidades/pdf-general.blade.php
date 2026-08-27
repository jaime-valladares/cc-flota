<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de unidades</title>
    <style>
        @page { margin: 22mm 10mm 17mm; }
        html, body { background: #fff; }
        body { margin: 0; color: #25343b; font-family: DejaVu Sans, sans-serif; font-size: 7px; }
        .header { border-bottom: 2px solid #147d82; margin-bottom: 10px; padding-bottom: 7px; }
        .logo { width: 132px; height: auto; }
        .header-table, .meta-table, .report-table { width: 100%; border-collapse: collapse; }
        .header-title { color: #114d57; font-size: 17px; font-weight: bold; text-align: right; }
        .header-subtitle { color: #64757b; font-size: 8px; text-align: right; }
        .summary { margin: 0 0 7px; width: 100%; border-collapse: separate; border-spacing: 3px 0; }
        .summary td { background: #f1f7f7; border-bottom: 1px solid #b9d2d4; padding: 5px 8px; text-align: center; width: 25%; }
        .summary-value { color: #114d57; font-size: 12px; font-weight: bold; }
        .summary-label { color: #68797e; font-size: 6.5px; text-transform: uppercase; }
        .filters { background: #f5f9f9; border-left: 2px solid #147d82; margin-bottom: 7px; padding: 4px 7px; }
        .filters-title { color: #114d57; font-size: 8px; font-weight: bold; margin-bottom: 3px; }
        .filter { display: inline-block; margin-right: 16px; }
        .filter strong { color: #114d57; }
        .report-table, .report-table thead, .report-table tbody, .report-table th, .report-table td, .report-table span, .report-table strong { font-family: DejaVu Sans, sans-serif; }
        .report-table { table-layout: fixed; }
        .report-table thead { display: table-header-group; }
        .report-table tr { page-break-inside: avoid; }
        .report-table th { background: #126d73; border: 1px solid #0f5f64; color: #fff; font-size: 6.6px; padding: 5px 3px; text-align: center; }
        .report-table td, .report-table td span, .report-table td strong { font-size: 7px; }
        .report-table td { border: 1px solid #d3dfe1; padding: 4px 3px; vertical-align: top; word-wrap: break-word; }
        .report-table tbody tr:nth-child(even) { background: #f7f9f9; }
        .right { text-align: right; }
        .center { text-align: center; }
        .status { font-weight: 600; text-transform: uppercase; }
        .pill { display: inline; font-weight: 600; padding: 0; }
        .active { color: #147048; }
        .inactive { color: #9e3f34; }
        .pending { color: #9a671c; }
        .empty { color: #64757b; padding: 18px; text-align: center; }
        .footer-note { color: #77878c; font-size: 6.5px; margin-top: 6px; }
        .page-footer { border-top: 1px solid #cddbdd; bottom: -11mm; color: #77878c; font-size: 6.5px; left: 0; padding-top: 3px; position: fixed; right: 0; }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table"><tr>
            <td><img class="logo" src="{{ $logoPath }}" alt="CC-Flota"></td>
            <td><div class="header-title">Reporte de unidades</div><div class="header-subtitle">Alcance: {{ $alcance }} · Generado: {{ $generadoEn->format('d/m/Y H:i') }}</div></td>
        </tr></table>
    </div>

    <table class="summary"><tr>
        @foreach (['Resultados' => $resumen['resultados'], 'Registradas' => $resumen['registradas'], 'Activas' => $resumen['activas'], 'Operables' => $resumen['operables']] as $etiqueta => $valor)
            <td><div class="summary-value">{{ $valor }}</div><div class="summary-label">{{ $etiqueta }}</div></td>
        @endforeach
    </tr></table>

    @if ($filtrosAplicados !== [])
        <div class="filters">
            <div class="filters-title">Filtros aplicados</div>
            @foreach ($filtrosAplicados as $etiqueta => $valor)
                <span class="filter"><strong>{{ $etiqueta }}:</strong> {{ $valor }}</span>
            @endforeach
        </div>
    @endif

    <table class="report-table">
        <colgroup>
            <col style="width: 12%"><col style="width: 8%"><col style="width: 7%"><col style="width: 6%"><col style="width: 11%"><col style="width: 12%"><col style="width: 12%"><col style="width: 5%"><col style="width: 8%"><col style="width: 11%"><col style="width: 8%">
        </colgroup>
        <thead><tr>
            <th>Empresa</th><th>Nombre / Placa</th><th>Marca</th><th>Estado</th><th>Disponibilidad</th><th>Modelo de medición</th><th>Rendimiento Teórico</th><th>Tanques</th><th>Capacidad total</th><th>Licencia</th><th>Marchamos</th>
        </tr></thead>
        <tbody>
            @forelse ($unidades as $unidad)
                <tr data-pdf-unit="{{ $unidad->id }}">
                    <td>{{ $unidad->empresa?->nombre_comercial ?: ($unidad->empresa?->nombre_legal ?: '—') }}</td>
                    <td><strong>{{ $unidad->placa }}</strong></td>
                    <td>{{ $unidad->marca ?: '—' }}</td>
                    <td><span class="pill status {{ $unidad->estado === 'activa' ? 'active' : ($unidad->estado === 'inactiva' ? 'inactive' : 'pending') }}">{{ $unidad->estado_texto }}</span></td>
                    <td><span class="pill">{{ $unidad->disponibilidad_operativa_texto }}</span></td>
                    <td>{{ $unidad->modelo_medicion_texto }}</td>
                    <td class="center">{{ $unidad->rendimiento_teorico_reporte }}</td>
                    <td class="center">{{ $unidad->total_tanques }}</td>
                    <td class="center">{{ number_format((float) $unidad->capacidad_total, 2) }} gal</td>
                    <td>{{ $unidad->licencia?->condicion_vigencia_texto ?? 'Sin licencia' }}</td>
                    <td class="center">{{ $unidad->total_puntos_con_marchamo_asignado }} / {{ $unidad->total_puntos_que_requieren_marchamo }}</td>
                </tr>
            @empty
                <tr><td colspan="11" class="empty">No hay unidades que coincidan con los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
    </table>
    <div class="footer-note">Total de unidades: {{ $unidades->count() }}</div>
    <div class="page-footer">CC-Flota · Generado: {{ $generadoEn->format('d/m/Y H:i') }}</div>

    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_text(744, 574, "Página {PAGE_NUM} de {PAGE_COUNT}", null, 7, [0.35, 0.43, 0.46]);
        }
    </script>
</body>
</html>
