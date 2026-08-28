<?php

use App\Models\Abastecimiento;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;

function cicloFamilia($empresa, $unidad, $motorista, $usuario, string $fecha, string $modelo, float $real, float $teorico): Abastecimiento
{
    $c = cicloKmGalon($empresa, $unidad, $motorista, $motorista, $usuario, $fecha, $real - $teorico, $modelo);
    $c->update(['consumo_real_ciclo' => $real, 'combustible_consumido_ciclo' => $real, 'consumo_teorico_ciclo' => $teorico, 'diferencia_galones_ciclo' => $teorico - $real, 'costo_combustible_consumido_ciclo' => $real * 5]);
    if ($modelo === Abastecimiento::MODELO_GALONES_HORA) {
        $c->update(['horometro_anterior' => 100, 'horometro_actual' => 120, 'diferencia_horometro' => 20, 'rendimiento_teorico_gal_hora_snapshot' => $teorico / 20, 'galones_por_hora' => $real / 20]);
    }
    if ($modelo === Abastecimiento::MODELO_GALONES_VIAJE) {
        $c->update(['total_viajes' => 4, 'galones_teoricos' => $teorico]);
    }

    return $c->fresh();
}

test('familia fija modelos y selectores de unidad en backend', function () {
    prepararReporteKmGalon($this);
    $e = empresaKmGalon('FAM');
    $admin = usuarioKmGalon(User::ROL_DIESEL_ADMIN);
    $m = motoristaKmGalon($e, 'FAM');
    $km = unidadKmGalon($e, 'SEL-KM', 'kilometros_galon');
    $h = unidadKmGalon($e, 'SEL-HORA', 'galones_hora');
    $v = unidadKmGalon($e, 'SEL-VIAJE', 'galones_viaje');
    $ck = cicloFamilia($e, $km, $m, $admin, '2026-08-10', 'kilometros_galon', 8, 10);
    $ch = cicloFamilia($e, $h, $m, $admin, '2026-08-11', 'galones_hora', 8, 10);
    $cv = cicloFamilia($e, $v, $m, $admin, '2026-08-12', 'galones_viaje', 8, 10);
    foreach ([['reportes.rendimiento-km-galon', $km, $ck, [$ch, $cv]], ['reportes.rendimiento-galones-hora', $h, $ch, [$ck, $cv]], ['reportes.rendimiento-galones-viaje', $v, $cv, [$ck, $ch]]] as [$ruta,$unidad,$incluido,$excluidos]) {
        $r = $this->actingAs($admin)->get(route($ruta.'.index', ['consultar' => 1]))->assertOk()->assertSee($unidad->placa)->assertSee('data-report-cycle-row="'.$incluido->id.'"', false)->assertDontSee('name="modelo_medicion"', false);
        foreach ($excluidos as $x) {
            $r->assertDontSee('data-report-cycle-row="'.$x->id.'"', false);
        }
    }
    $this->actingAs($admin)->get(route('reportes.rendimiento-galones-hora.index', ['consultar' => 1, 'unidad_ids' => [$km->id]]))->assertNotFound();
});

test('reportes hora y viaje calculan clasificacion impacto resumen y cero seguro', function () {
    prepararReporteKmGalon($this);
    $e = empresaKmGalon('CAL');
    $admin = usuarioKmGalon(User::ROL_DIESEL_ADMIN);
    $m = motoristaKmGalon($e, 'CAL');
    foreach ([['galones_hora', 'reportes.rendimiento-galones-hora', 'Horas contabilizadas', 'h'], ['galones_viaje', 'reportes.rendimiento-galones-viaje', 'Viajes evaluados', 'viajes']] as [$modelo,$ruta,$label,$unidad]) {
        $u = unidadKmGalon($e, 'CAL-'.strtoupper($unidad), $modelo);
        $a = cicloFamilia($e, $u, $m, $admin, '2026-08-20', $modelo, 8, 10);
        $s = cicloFamilia($e, $u, $m, $admin, '2026-08-21', $modelo, 12, 10);
        $o = cicloFamilia($e, $u, $m, $admin, '2026-08-22', $modelo, 10, 10);
        $r = $this->actingAs($admin)->get(route($ruta.'.index', ['consultar' => 1]))->assertOk()->assertSee('Ahorro')->assertSee('Sobreconsumo')->assertSee('En objetivo')->assertSee('+$10.00')->assertSee('-$10.00')->assertSee('data-summary-label="'.$label.'"', false);
        $this->actingAs($admin)->get(route($ruta.'.show', $a))->assertOk()->assertSee('Consumo teórico')->assertSee('Costo efectivo por galón');
        $this->actingAs($admin)->get(route($ruta.'.ventana', ['consultar' => 1]))->assertOk();
        $this->actingAs($admin)->get(route($ruta.'.show.ventana', $a))->assertOk();
    }
});

test('familia comparte filtros modernos unidades completas viajes enteros y tabla estable', function () {
    prepararReporteKmGalon($this);
    $e = empresaKmGalon('STD');
    $admin = usuarioKmGalon(User::ROL_DIESEL_ADMIN);
    $m = motoristaKmGalon($e, 'STD');
    foreach ([['kilometros_galon', 'reportes.rendimiento-km-galon', 'km/gal'], ['galones_hora', 'reportes.rendimiento-galones-hora', 'gal/h'], ['galones_viaje', 'reportes.rendimiento-galones-viaje', 'gal/viaje']] as [$modelo,$ruta,$unidadRendimiento]) {
        $u = unidadKmGalon($e, 'STD-'.strtoupper($modelo), $modelo);
        $c = cicloFamilia($e, $u, $m, $admin, '2026-08-26', $modelo, 8, 10);
        $r = $this->actingAs($admin)->get(route($ruta.'.index', ['consultar' => 1]))->assertOk()
            ->assertSee('data-cc-filter-multiselect', false)->assertSee('data-cc-filter-radio', false)
            ->assertSee('cc-filter-multiselect-compact', false)->assertSee('cc-analytics-performance-table-wrapper', false)
            ->assertSee('Esperado</span> '.number_format($modelo === 'galones_viaje' ? 2.5 : ($modelo === 'galones_hora' ? 0.5 : 10), 2).' '.$unidadRendimiento, false)
            ->assertSee('Teórico</span> 10.00 gal', false)->assertSee('Real</span> 8.00 gal', false)
            ->assertSee('cc-result-count-value', false)->assertDontSee('<select class="cc-input" name="empresa_ids[]"', false);
        if ($modelo === 'galones_viaje') {
            $r->assertSee('4 viajes')->assertDontSee('4.00 viajes');
        }
        if ($modelo === 'galones_hora') {
            $r->assertSee('Horas contabilizadas')->assertDontSee('Horas trabajadas');
        }
    }
});

test('paneles modernos quedan como overlays compactos anclados a la izquierda', function () {
    $css = file_get_contents(resource_path('css/app.css'));

    expect($css)
        ->toMatch('/\.cc-filter-multiselect-compact \.cc-filter-multiselect-menu\s*\{[^}]*width:\s*75%;[^}]*right:\s*auto;[^}]*left:\s*0;/s')
        ->toMatch('/\.cc-va-analytics \.cc-filter-multiselect-readable \.cc-filter-multiselect-menu\s*\{[^}]*right:\s*auto;[^}]*left:\s*0;/s')
        ->toMatch('/\.cc-filter-multiselect-menu\s*\{[^}]*position:\s*absolute;[^}]*top:\s*calc\(100% \+ 0\.42rem\);[^}]*z-index:\s*5000;/s')
        ->toMatch('/\.cc-va-analytics \.cc-analytics-root-card \.cc-filter-multiselect-menu\s*\{[^}]*position:\s*absolute;[^}]*top:\s*calc\(100% \+ 0\.42rem\);[^}]*right:\s*auto;[^}]*left:\s*0;[^}]*z-index:\s*6001;/s');

    $javascript = file_get_contents(resource_path('js/app.js'));
    expect($javascript)->toContain("multiselect.classList.add('is-open')")
        ->toContain("multiselect.classList.remove('is-open')")
        ->not->toContain('has-open-dropdown');

    expect($css)->not->toContain('has-open-dropdown');

    $partial = file_get_contents(resource_path('views/reportes/rendimiento-km-galon/contenido.blade.php'));
    expect(substr_count($partial, 'class="cc-standard-filter-grid cc-analytics-report-filter-grid"'))->toBe(1)
        ->and($partial)->not->toContain('cc-analytics-filter-row');
});

test('familia protege detalle modelo tenant roles e historial inactivo', function () {
    prepararReporteKmGalon($this);
    $a = empresaKmGalon('SA');
    $b = empresaKmGalon('SB');
    $global = usuarioKmGalon(User::ROL_DIESEL_ADMIN);
    $ma = motoristaKmGalon($a, 'SA');
    $mb = motoristaKmGalon($b, 'SB');
    $ua = unidadKmGalon($a, 'SEC-A', 'galones_hora');
    $ub = unidadKmGalon($b, 'SEC-B', 'galones_hora');
    $ca = cicloFamilia($a, $ua, $ma, $global, '2026-08-10', 'galones_hora', 8, 10);
    $cb = cicloFamilia($b, $ub, $mb, $global, '2026-08-11', 'galones_hora', 8, 10);
    $ua->update(['estado' => 'inactiva']);
    $ma->update(['estado' => 'inactivo']);
    $user = usuarioKmGalon(User::ROL_EMPRESA_ADMIN, $a);
    $this->actingAs($user)->get(route('reportes.rendimiento-galones-hora.index', ['consultar' => 1]))->assertOk()->assertSee('data-report-cycle-row="'.$ca->id.'"', false)->assertDontSee('data-report-cycle-row="'.$cb->id.'"', false);
    $this->actingAs($user)->get(route('reportes.rendimiento-galones-hora.show', $cb))->assertForbidden();
    $this->actingAs($global)->get(route('reportes.rendimiento-galones-viaje.show', $ca))->assertNotFound();
    $this->actingAs(usuarioKmGalon(User::ROL_DIESEL_TECNICO))->get(route('reportes.rendimiento-galones-hora.index'))->assertForbidden();
    $this->actingAs(usuarioKmGalon(User::ROL_EMPRESA_OPERADOR, $a))->get(route('reportes.rendimiento-galones-viaje.index'))->assertForbidden();
});

test('menu lateral expone la familia de rendimiento segun el permiso backend', function () {
    prepararReporteKmGalon($this);
    $autorizado = usuarioKmGalon(User::ROL_DIESEL_AUDITOR);
    $this->actingAs($autorizado)->get(route('reportes.rendimiento-galones-hora.index'))
        ->assertOk()
        ->assertSeeInOrder(['Unidades', 'Rendimiento km/gal', 'Rendimiento gal/hora', 'Rendimiento gal/viaje'])
        ->assertSee('href="'.route('reportes.rendimiento-galones-hora.index').'"', false)
        ->assertSee('href="'.route('reportes.rendimiento-galones-viaje.index').'"', false)
        ->assertSee('cc-sidebar-sublink-active', false);

    $noAutorizado = usuarioKmGalon(User::ROL_DIESEL_TECNICO);
    $this->actingAs($noAutorizado)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Rendimiento gal/hora')
        ->assertDontSee('Rendimiento gal/viaje');
});

test('resumenes siguen filtros solo tras consultar y usan estructura cuatro mas tres', function () {
    prepararReporteKmGalon($this);
    $e = empresaKmGalon('VIS');
    $admin = usuarioKmGalon(User::ROL_DIESEL_ADMIN);
    $m = motoristaKmGalon($e, 'VIS');
    foreach ([['kilometros_galon', 'reportes.rendimiento-km-galon'], ['galones_hora', 'reportes.rendimiento-galones-hora'], ['galones_viaje', 'reportes.rendimiento-galones-viaje']] as [$modelo,$ruta]) {
        $u = unidadKmGalon($e, 'VIS-'.strtoupper($modelo), $modelo);
        cicloFamilia($e, $u, $m, $admin, '2026-08-25', $modelo, 8, 10);
        $sin = $this->actingAs($admin)->get(route($ruta.'.index'))->assertOk()->assertDontSee('data-report-summary', false);
        $con = $this->actingAs($admin)->get(route($ruta.'.index', ['consultar' => 1]))->assertOk()->assertSee('data-report-summary', false)->assertSee('cc-summary-grid-row-four', false)->assertSee('cc-summary-grid-row-three', false);
        expect(strpos($con->getContent(), '<form'))->toBeLessThan(strpos($con->getContent(), 'data-report-summary'))
            ->and(strpos($con->getContent(), 'data-report-summary'))->toBeLessThan(strpos($con->getContent(), 'cc-result-count'))
            ->and(substr_count($con->getContent(), 'data-summary-label='))->toBe(7);
    }
});

test('consulta excluye consumos nulos y aperturas de distinta empresa o unidad', function () {
    prepararReporteKmGalon($this);
    $a = empresaKmGalon('ROB-A');
    $b = empresaKmGalon('ROB-B');
    $admin = usuarioKmGalon(User::ROL_DIESEL_ADMIN);
    $m = motoristaKmGalon($a, 'ROB');
    $u = unidadKmGalon($a, 'ROB-OK');
    $valido = cicloFamilia($a, $u, $m, $admin, '2026-08-20', 'kilometros_galon', 8, 10);
    $nulo = cicloFamilia($a, $u, $m, $admin, '2026-08-21', 'kilometros_galon', 8, 10);
    $nulo->update(['consumo_real_ciclo' => null]);
    $cruzado = cicloFamilia($a, $u, $m, $admin, '2026-08-22', 'kilometros_galon', 8, 10);
    $otra = unidadKmGalon($b, 'ROB-OTRA');
    $cruzado->abastecimientoAnterior->update(['empresa_id' => $b->id, 'unidad_id' => $otra->id]);
    $r = $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index', ['consultar' => 1]))->assertOk()->assertSee('data-report-cycle-row="'.$valido->id.'"', false);
    $r->assertDontSee('data-report-cycle-row="'.$nulo->id.'"', false)->assertDontSee('data-report-cycle-row="'.$cruzado->id.'"', false);
});

test('pdf principal de cada modelo reutiliza filtros e incluye resultados sin paginacion', function () {
    prepararReporteKmGalon($this);
    $e = empresaKmGalon('PDF-L');
    $admin = usuarioKmGalon(User::ROL_DIESEL_AUDITOR);
    $m = motoristaKmGalon($e, 'PDF-L');
    $capturas = [];
    $config = [['kilometros_galon', 'reportes.rendimiento-km-galon'], ['galones_hora', 'reportes.rendimiento-galones-hora'], ['galones_viaje', 'reportes.rendimiento-galones-viaje']];
    foreach ($config as [$modelo,$ruta]) {
        $u = unidadKmGalon($e, 'PDF-'.strtoupper($modelo), $modelo);
        foreach (range(1, 12) as $n) {
            cicloFamilia($e, $u, $m, $admin, '2026-08-'.str_pad((string) $n, 2, '0', STR_PAD_LEFT), $modelo, 8, 10);
        }
    }
    $doc = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    Pdf::shouldReceive('loadView')->times(3)->withArgs(function (string $vista, array $datos) use (&$capturas): bool {
        $capturas[$datos['rutaBase']] = $datos;

        return $vista === 'reportes.rendimiento-km-galon.pdf-general';
    })->andReturn($doc);
    $doc->shouldReceive('setPaper')->times(3)->with('a4', 'landscape')->andReturnSelf();
    $doc->shouldReceive('download')->times(3)->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));
    foreach ($config as [$modelo,$ruta]) {
        $this->actingAs($admin)->get(route($ruta.'.pdf', ['consultar' => 1, 'busqueda' => 'PDF-'.strtoupper($modelo), 'page' => 2]))->assertOk();
    }
    foreach ($config as [$modelo,$ruta]) {
        expect($capturas[$ruta]['ciclos'])->toHaveCount(12)->and($capturas[$ruta]['ciclos']->every(fn ($c) => $c->modelo_medicion === $modelo))->toBeTrue()->and($capturas[$ruta]['filtrosAplicados']['Búsqueda'])->toBe('PDF-'.strtoupper($modelo));
        $html = view('reportes.rendimiento-km-galon.pdf-general', $capturas[$ruta])->render();
        expect(substr_count($html, 'data-pdf-cycle='))->toBe(12)->and($html)->toContain($capturas[$ruta]['titulo'])->not->toContain('<button');
    }
});

test('pdf detalle de los tres modelos conserva seguridad snapshots e impacto', function () {
    prepararReporteKmGalon($this);
    $a = empresaKmGalon('PDF-D');
    $b = empresaKmGalon('PDF-X');
    $admin = usuarioKmGalon(User::ROL_DIESEL_ADMIN);
    $ma = motoristaKmGalon($a, 'AP');
    $mc = motoristaKmGalon($a, 'CI');
    $capturas = [];
    $config = [['kilometros_galon', 'reportes.rendimiento-km-galon'], ['galones_hora', 'reportes.rendimiento-galones-hora'], ['galones_viaje', 'reportes.rendimiento-galones-viaje']];
    $ciclos = [];
    foreach ($config as [$modelo,$ruta]) {
        $u = unidadKmGalon($a, 'PDFD-'.strtoupper($modelo), $modelo);
        $c = cicloFamilia($a, $u, $ma, $admin, '2026-08-20', $modelo, 8, 10);
        $c->update(['motorista_id' => $mc->id, 'motorista_nombre_snapshot' => $mc->nombre_completo]);
        $ciclos[$ruta] = $c->fresh();
    }
    $doc = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    Pdf::shouldReceive('loadView')->times(3)->withArgs(function (string $vista, array $datos) use (&$capturas): bool {
        $capturas[$datos['rutaBase']] = $datos;

        return $vista === 'reportes.rendimiento-km-galon.pdf-detalle';
    })->andReturn($doc);
    $doc->shouldReceive('setPaper')->times(3)->with('a4', 'portrait')->andReturnSelf();
    $doc->shouldReceive('download')->times(3)->andReturn(response('PDF'));
    foreach ($config as [$modelo,$ruta]) {
        $this->actingAs($admin)->get(route($ruta.'.show.pdf', $ciclos[$ruta]))->assertOk();
    }
    foreach ($config as [$modelo,$ruta]) {
        expect($capturas[$ruta]['ciclo']->modelo_medicion)->toBe($modelo)->and($capturas[$ruta]['ciclo']->motorista_nombre_snapshot)->toBe($mc->nombre_completo)->and($capturas[$ruta]['ciclo']->impacto_economico_reporte)->toBe(10.0);
        $html = view('reportes.rendimiento-km-galon.pdf-detalle', $capturas[$ruta])->render();
        expect($html)->toContain('Motorista apertura')->toContain('Motorista cierre')->toContain('Costo efectivo por galón')->toContain('+$10.00');
    }
    $empresaUser = usuarioKmGalon(User::ROL_EMPRESA_ADMIN, $b);
    $this->actingAs($empresaUser)->get(route('reportes.rendimiento-km-galon.show.pdf', $ciclos['reportes.rendimiento-km-galon']))->assertForbidden();
    $this->actingAs($admin)->get(route('reportes.rendimiento-galones-hora.show.pdf', $ciclos['reportes.rendimiento-km-galon']))->assertNotFound();
});
