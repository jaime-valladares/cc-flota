<?php

use App\Models\Abastecimiento;
use App\Models\Empresa;
use App\Models\Motorista;
use App\Models\Permiso;
use App\Models\Role;
use App\Models\Unidad;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\RolPermisosSeeder;

beforeEach(function () {
    $this->seed([RolesSeeder::class, PermisosSeeder::class, RolPermisosSeeder::class]);
});

function gmEmpresa(string $codigo): Empresa
{
    return Empresa::create(['nombre_legal' => "Empresa $codigo", 'nombre_comercial' => $codigo, 'nit' => "NIT-$codigo", 'estado' => 'activa']);
}
function gmUnidad(Empresa $e, string $placa, string $modelo = 'kilometros_galon'): Unidad
{
    return Unidad::create(['empresa_id' => $e->id, 'placa' => $placa, 'marca' => 'Volvo', 'total_tanques' => 1, 'cantidad_tanques_con_licencia' => 1, 'capacidad_total' => 100, 'capacidad_cubierta' => 100, 'modelo_medicion' => $modelo, 'rendimiento_teorico_km_galon' => $modelo === 'kilometros_galon' ? 10 : null, 'rendimiento_teorico_gal_hora' => $modelo === 'galones_hora' ? 2 : null, 'estado' => 'activa']);
}
function gmMotorista(Empresa $e, string $codigo): Motorista
{
    return Motorista::create(['empresa_id' => $e->id, 'nombres' => "Motorista $codigo", 'apellidos' => 'Prueba', 'licencia' => "GM-$codigo", 'telefono' => '70000000', 'estado' => 'activo']);
}
function gmUsuario(string $rol, ?Empresa $e = null): User
{
    return User::factory()->create(['rol_id' => Role::where('codigo', $rol)->value('id'), 'tipo_usuario' => $e ? User::TIPO_EMPRESA : User::TIPO_DIESEL_COP, 'empresa_id' => $e?->id, 'estado' => 'activo']);
}
function gmAbastecimiento(array $d): Abastecimiento
{
    $e = $d['empresa'];
    $u = $d['unidad'];
    $m = $d['motorista'];

    return Abastecimiento::create(['empresa_id' => $e->id, 'unidad_id' => $u->id, 'motorista_id' => $m->id, 'abastecimiento_anterior_id' => $d['anterior_id'] ?? null, 'registrado_por' => $d['usuario']->id, 'empresa_nombre_snapshot' => $e->nombre_comercial, 'unidad_placa_snapshot' => $u->placa, 'unidad_marca_snapshot' => $u->marca, 'motorista_nombre_snapshot' => $m->nombre_completo, 'motorista_licencia_snapshot' => $m->licencia, 'fecha_hora_abastecimiento' => $d['fecha'], 'estado' => 'registrado', 'modelo_medicion' => $d['modelo'] ?? 'kilometros_galon', 'lectura_actual' => 1000, 'diferencia_lectura' => $d['km'] ?? null, 'kilometraje_actual' => 1000, 'diferencia_kilometraje' => $d['km'] ?? null, 'volumen_inicial' => 50, 'volumen_cargado' => 10, 'volumen_final' => 60, 'capacidad_cubierta_snapshot' => 100, 'combustible_consumido_ciclo' => $d['real'] ?? null, 'combustible_adicional_no_explicado' => 0, 'consumo_real_ciclo' => $d['real'] ?? null, 'consumo_teorico_ciclo' => $d['teorico'] ?? null, 'diferencia_galones_ciclo' => isset($d['real'],$d['teorico']) ? $d['teorico'] - $d['real'] : null, 'costo_combustible_consumido_ciclo' => $d['costo'] ?? null, 'tipo_origen' => 'externo', 'origen_nombre_snapshot' => 'Prueba', 'precio_galon' => 5, 'total_pagado' => 50, 'moneda' => 'USD', 'valor_carga_snapshot' => 50, 'total_viajes' => $d['viajes'] ?? 0, 'diferencia_horometro' => $d['horas'] ?? null, 'total_tapones_abiertos' => 0, 'total_marchamos_reemplazados' => 0]);
}

function cicloGestion(array $d): Abastecimiento
{
    $apertura = gmAbastecimiento($d + ['motorista' => $d['apertura'] ?? $d['motorista'], 'fecha' => Carbon\Carbon::parse($d['fecha'])->subDay(), 'real' => null, 'teorico' => null, 'costo' => null]);
    $c = gmAbastecimiento($d + ['anterior_id' => $apertura->id]);
    $real = $d['real'];
    $teorico = $d['teorico'];
    $costo = $d['costo'];
    $c->update(['consumo_real_ciclo' => $real, 'consumo_teorico_ciclo' => $teorico, 'diferencia_galones_ciclo' => $teorico - $real, 'costo_combustible_consumido_ciclo' => $costo, 'diferencia_kilometraje' => $d['km'] ?? 0, 'diferencia_horometro' => $d['horas'] ?? null, 'total_viajes' => $d['viajes'] ?? 0]);

    return $c->fresh();
}

test('permiso específico tiene metadatos y matriz exacta sin alterar recargas', function () {
    $permiso = Permiso::where('codigo', 'reportes.gestion-combustible-motorista.consultar')->firstOrFail();
    expect($permiso->modulo)->toBe('reportes')
        ->and($permiso->accion)->toBe('consultar')
        ->and($permiso->alcance)->toBe('ambos')
        ->and($permiso->estado)->toBe('activo')
        ->and($permiso->roles()->orderBy('codigo')->pluck('codigo')->all())->toBe([
            User::ROL_DIESEL_ADMIN, User::ROL_DIESEL_AUDITOR, User::ROL_DIESEL_SUPER_ADMIN,
            User::ROL_EMPRESA_ADMIN, User::ROL_EMPRESA_AUDITOR, User::ROL_EMPRESA_SUPERVISOR,
        ]);

    $rolesRecargas = [
        User::ROL_DIESEL_SUPER_ADMIN, User::ROL_DIESEL_ADMIN, User::ROL_DIESEL_TECNICO,
        User::ROL_EMPRESA_ADMIN, User::ROL_EMPRESA_SUPERVISOR, User::ROL_EMPRESA_OPERADOR,
    ];
    foreach (['recargas_tanques.registrar', 'recargas_tanques.anular'] as $codigo) {
        expect(Permiso::where('codigo', $codigo)->firstOrFail()->roles()->orderBy('codigo')->pluck('codigo')->all())
            ->toBe(collect($rolesRecargas)->sort()->values()->all());
    }
});

test('matriz de roles protege index y ficha con permiso específico', function () {
    $empresa = gmEmpresa('GM-ROLES');
    $creador = gmUsuario(User::ROL_DIESEL_ADMIN);
    $motorista = gmMotorista($empresa, 'ROLES');
    $unidad = gmUnidad($empresa, 'GM-ROLES');
    cicloGestion(['empresa' => $empresa, 'unidad' => $unidad, 'motorista' => $motorista, 'usuario' => $creador, 'fecha' => '2026-08-10', 'real' => 8, 'teorico' => 10, 'costo' => 40]);

    foreach ([User::ROL_DIESEL_SUPER_ADMIN, User::ROL_DIESEL_ADMIN, User::ROL_DIESEL_AUDITOR] as $rol) {
        $usuario = gmUsuario($rol);
        $this->actingAs($usuario)->get(route('reportes.gestion-combustible-motorista.index'))->assertOk();
        $this->actingAs($usuario)->get(route('reportes.gestion-combustible-motorista.show', ['motorista' => $motorista->id, 'consultar' => 1]))->assertOk();
    }
    foreach ([User::ROL_EMPRESA_ADMIN, User::ROL_EMPRESA_SUPERVISOR, User::ROL_EMPRESA_AUDITOR] as $rol) {
        $usuario = gmUsuario($rol, $empresa);
        $this->actingAs($usuario)->get(route('reportes.gestion-combustible-motorista.index'))->assertOk();
        $this->actingAs($usuario)->get(route('reportes.gestion-combustible-motorista.show', ['motorista' => $motorista->id, 'consultar' => 1]))->assertOk();
    }
    foreach ([[User::ROL_DIESEL_TECNICO, null], [User::ROL_EMPRESA_OPERADOR, $empresa]] as [$rol, $tenant]) {
        $usuario = gmUsuario($rol, $tenant);
        $this->actingAs($usuario)->get(route('reportes.gestion-combustible-motorista.index'))->assertForbidden();
        $this->actingAs($usuario)->get(route('reportes.gestion-combustible-motorista.show', ['motorista' => $motorista->id, 'consultar' => 1]))->assertForbidden();
    }
});

test('gestión por motorista atribuye al cierre y consolida ciclos unidades y motoristas', function () {
    $e = gmEmpresa('GM-A');
    $admin = gmUsuario(User::ROL_DIESEL_ADMIN);
    $apertura = gmMotorista($e, 'AP');
    $m = gmMotorista($e, 'CI');
    $otro = gmMotorista($e, 'OT');
    $u1 = gmUnidad($e, 'GM-1');
    $u2 = gmUnidad($e, 'GM-2');
    cicloGestion(['empresa' => $e, 'unidad' => $u1, 'apertura' => $apertura, 'motorista' => $m, 'usuario' => $admin, 'fecha' => '2026-08-10', 'real' => 8, 'teorico' => 10, 'costo' => 40, 'km' => 100]);
    cicloGestion(['empresa' => $e, 'unidad' => $u2, 'motorista' => $m, 'usuario' => $admin, 'fecha' => '2026-08-11', 'real' => 12, 'teorico' => 10, 'costo' => 60, 'km' => 200]);
    cicloGestion(['empresa' => $e, 'unidad' => $u1, 'motorista' => $otro, 'usuario' => $admin, 'fecha' => '2026-08-12', 'real' => 10, 'teorico' => 10, 'costo' => 50, 'km' => 50]);
    $this->actingAs($admin)->get(route('reportes.gestion-combustible-motorista.index', ['consultar' => 1]))->assertOk()
        ->assertSee('data-report-driver-row="'.$m->id.'"', false)->assertSee('data-report-driver-row="'.$otro->id.'"', false)
        ->assertDontSee('data-report-driver-row="'.$apertura->id.'"', false)->assertSee('data-summary-label="Motoristas evaluados" data-summary-value="2"', false);
    foreach ([['empresa_ids' => [$e->id]], ['motorista_ids' => [$m->id]], ['unidad_ids' => [$u2->id]]] as $filtro) {
        $this->actingAs($admin)->get(route('reportes.gestion-combustible-motorista.index', ['consultar' => 1] + $filtro))->assertOk()->assertSee('data-report-driver-row="'.$m->id.'"', false);
    }
});

test('costo promedio es ponderado por galones y no promedio simple', function () {
    $e = gmEmpresa('GM-P');
    $admin = gmUsuario(User::ROL_DIESEL_ADMIN);
    $m = gmMotorista($e, 'P');
    $u = gmUnidad($e, 'GM-P');
    cicloGestion(['empresa' => $e, 'unidad' => $u, 'motorista' => $m, 'usuario' => $admin, 'fecha' => '2026-08-10', 'real' => 100, 'teorico' => 100, 'costo' => 400]);
    cicloGestion(['empresa' => $e, 'unidad' => $u, 'motorista' => $m, 'usuario' => $admin, 'fecha' => '2026-08-11', 'real' => 300, 'teorico' => 300, 'costo' => 1500]);
    $this->actingAs($admin)->get(route('reportes.gestion-combustible-motorista.index', ['consultar' => 1]))->assertOk()
        ->assertSee('data-summary-label="Galones consumidos" data-summary-value="400.00 gal"', false)
        ->assertSee('data-summary-label="Costo promedio por galón" data-summary-value="$4.75/gal"', false)->assertDontSee('$4.50/gal');
});

test('consolidación económica consistencia multimodelo y detalle ponderado', function () {
    $e = gmEmpresa('GM-M');
    $admin = gmUsuario(User::ROL_DIESEL_ADMIN);
    $m = gmMotorista($e, 'M');
    $uk = gmUnidad($e, 'GM-K', 'kilometros_galon');
    $uh = gmUnidad($e, 'GM-H', 'galones_hora');
    $uv = gmUnidad($e, 'GM-V', 'galones_viaje');
    cicloGestion(['empresa' => $e, 'unidad' => $uk, 'motorista' => $m, 'usuario' => $admin, 'fecha' => '2026-08-01', 'real' => 80, 'teorico' => 100, 'costo' => 400, 'km' => 10000]);
    cicloGestion(['empresa' => $e, 'unidad' => $uh, 'motorista' => $m, 'usuario' => $admin, 'fecha' => '2026-08-02', 'real' => 105, 'teorico' => 100, 'costo' => 525, 'horas' => 50, 'modelo' => 'galones_hora']);
    cicloGestion(['empresa' => $e, 'unidad' => $uv, 'motorista' => $m, 'usuario' => $admin, 'fecha' => '2026-08-03', 'real' => 85, 'teorico' => 100, 'costo' => 425, 'viajes' => 20, 'km' => 5000, 'modelo' => 'galones_viaje']);
    $r = $this->actingAs($admin)->get(route('reportes.gestion-combustible-motorista.index', ['consultar' => 1]))->assertOk()->assertSee('+$150.00')
        ->assertSee('cc-summary-grid-row-six')->assertSee('cc-analytics-table-driver-management')
        ->assertDontSee('<th>Modelos</th>', false)->assertDontSee('<th>Consumo teórico</th>', false)
        ->assertSee('data-cc-filter-radio', false);
    expect(substr_count($r->getContent(), 'data-cc-filter-multiselect'))->toBe(4)
        ->and(substr_count($r->getContent(), 'cc-standard-filter-grid'))->toBe(1)
        ->and(substr_count($r->getContent(), '<th>'))->toBe(11);
    $detalle = $this->actingAs($admin)->get(route('reportes.gestion-combustible-motorista.show', ['motorista' => $m->id, 'consultar' => 1]))->assertOk()
        ->assertSee('+$10.00 / 1,000 km')->assertSee('data-model-breakdown="kilometros_galon"', false)->assertSee('data-model-breakdown="galones_hora"', false)->assertSee('data-model-breakdown="galones_viaje"', false)
        ->assertSee('Consumo teórico')->assertSee('Ciclos con ahorro')->assertSee('Ciclos con sobreconsumo')->assertSee('data-cc-table-start', false)
        ->assertSee('cc-badge-active', false)->assertDontSee('Rendimiento promedio general');
    expect(substr_count($detalle->getContent(), 'cc-summary-grid-row-four'))->toBe(4);
});

test('filtros usan cierre y protegen tenant en consulta detalle y pdf', function () {
    $a = gmEmpresa('GM-TA');
    $b = gmEmpresa('GM-TB');
    $admin = gmUsuario(User::ROL_DIESEL_ADMIN);
    $ma = gmMotorista($a, 'TA');
    $mb = gmMotorista($b, 'TB');
    $ua = gmUnidad($a, 'GM-TA');
    $ub = gmUnidad($b, 'GM-TB');
    $ca = cicloGestion(['empresa' => $a, 'unidad' => $ua, 'motorista' => $ma, 'usuario' => $admin, 'fecha' => '2026-08-10 23:59:59', 'real' => 8, 'teorico' => 10, 'costo' => 40]);
    cicloGestion(['empresa' => $b, 'unidad' => $ub, 'motorista' => $mb, 'usuario' => $admin, 'fecha' => '2026-08-11', 'real' => 12, 'teorico' => 10, 'costo' => 60]);
    $user = gmUsuario(User::ROL_EMPRESA_ADMIN, $a);
    $this->actingAs($user)->get(route('reportes.gestion-combustible-motorista.index', ['consultar' => 1, 'fecha_desde' => '2026-08-10', 'fecha_hasta' => '2026-08-10', 'resultado' => 'ahorro']))->assertOk()->assertSee('data-report-driver-row="'.$ma->id.'"', false)->assertDontSee('data-report-driver-row="'.$mb->id.'"', false);
    $this->actingAs($user)->get(route('reportes.gestion-combustible-motorista.index', ['consultar' => 1, 'empresa_ids' => [$b->id], 'motorista_ids' => [$mb->id], 'unidad_ids' => [$ub->id]]))->assertForbidden();
    $this->actingAs($user)->get(route('reportes.gestion-combustible-motorista.show', ['motorista' => $mb->id, 'consultar' => 1]))->assertForbidden();
    $doc = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    Pdf::shouldReceive('loadView')->once()->withArgs(fn (string $vista, array $datos) => $vista === 'reportes.gestion-combustible-motorista.pdf-general' && $datos['motoristas']->count() === 1 && $datos['periodoEvaluado'] === '10/08/2026 – 10/08/2026' && $datos['filtrosAplicados']['Resultado'] === 'Ahorro')->andReturn($doc);
    $doc->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
    $doc->shouldReceive('download')->once()->andReturn(response('PDF'));
    $this->actingAs($user)->get(route('reportes.gestion-combustible-motorista.pdf', ['consultar' => 1, 'fecha_desde' => '2026-08-10', 'fecha_hasta' => '2026-08-10', 'resultado' => 'ahorro']))->assertOk();
    $this->actingAs($user)->get(route('reportes.gestion-combustible-motorista.pdf', ['consultar' => 1, 'empresa_ids' => [$b->id]]))->assertForbidden();
});

test('pdf de ficha conserva motorista periodo filtros economía e historial completo', function () {
    $e = gmEmpresa('GM-PDF-D');
    $admin = gmUsuario(User::ROL_DIESEL_ADMIN);
    $m = gmMotorista($e, 'PDF-D');
    $otro = gmMotorista($e, 'PDF-O');
    $u = gmUnidad($e, 'GM-PDF-D');
    $incluido = cicloGestion(['empresa' => $e, 'unidad' => $u, 'motorista' => $m, 'usuario' => $admin, 'fecha' => '2026-08-10 12:00:00', 'real' => 8, 'teorico' => 10, 'costo' => 40, 'km' => 1000]);
    cicloGestion(['empresa' => $e, 'unidad' => $u, 'motorista' => $m, 'usuario' => $admin, 'fecha' => '2026-08-11 12:00:00', 'real' => 12, 'teorico' => 10, 'costo' => 60, 'km' => 1000]);
    cicloGestion(['empresa' => $e, 'unidad' => $u, 'motorista' => $otro, 'usuario' => $admin, 'fecha' => '2026-08-10 13:00:00', 'real' => 8, 'teorico' => 10, 'costo' => 40]);
    $query = ['consultar' => 1, 'fecha_desde' => '2026-08-10', 'fecha_hasta' => '2026-08-10', 'resultado' => 'ahorro'];
    $this->actingAs($admin)->get(route('reportes.gestion-combustible-motorista.show', ['motorista' => $m->id] + $query))->assertOk()
        ->assertSee(route('reportes.gestion-combustible-motorista.show.pdf', ['motorista' => $m->id] + $query));

    $captura = null;
    $doc = Mockery::mock(Barryvdh\DomPDF\PDF::class);
    Pdf::shouldReceive('loadView')->once()->withArgs(function (string $vista, array $datos) use (&$captura, $m, $incluido): bool {
        $captura = $datos;

        return $vista === 'reportes.gestion-combustible-motorista.pdf-detalle'
            && $datos['motorista']->is($m) && $datos['periodoEvaluado'] === '10/08/2026 – 10/08/2026'
            && $datos['ciclos']->count() === 1 && $datos['ciclos']->first()->is($incluido)
            && round((float) $datos['resumen']['impacto_neto'], 2) === 10.0;
    })->andReturn($doc);
    $doc->shouldReceive('setPaper')->once()->with('a4', 'landscape')->andReturnSelf();
    $doc->shouldReceive('download')->once()->andReturn(response('PDF'));
    $this->actingAs($admin)->get(route('reportes.gestion-combustible-motorista.show.pdf', ['motorista' => $m->id] + $query))->assertOk();
    expect(view('reportes.gestion-combustible-motorista.pdf-detalle', $captura)->render())
        ->toContain($m->nombre_completo, '10/08/2026 – 10/08/2026', 'data-pdf-driver-cycle="'.$incluido->id.'"', 'Consumo teórico', 'Impacto neto por 1,000 km');
});

test('pdf de ficha bloquea motorista y filtros cross tenant', function () {
    $a = gmEmpresa('GM-PDF-TA');
    $b = gmEmpresa('GM-PDF-TB');
    $admin = gmUsuario(User::ROL_DIESEL_ADMIN);
    $ma = gmMotorista($a, 'PDF-TA');
    $mb = gmMotorista($b, 'PDF-TB');
    $ua = gmUnidad($a, 'GM-PDF-TA');
    $ub = gmUnidad($b, 'GM-PDF-TB');
    cicloGestion(['empresa' => $a, 'unidad' => $ua, 'motorista' => $ma, 'usuario' => $admin, 'fecha' => '2026-08-10', 'real' => 8, 'teorico' => 10, 'costo' => 40]);
    cicloGestion(['empresa' => $b, 'unidad' => $ub, 'motorista' => $mb, 'usuario' => $admin, 'fecha' => '2026-08-10', 'real' => 8, 'teorico' => 10, 'costo' => 40]);
    $user = gmUsuario(User::ROL_EMPRESA_ADMIN, $a);
    $this->actingAs($user)->get(route('reportes.gestion-combustible-motorista.show.pdf', ['motorista' => $mb->id, 'consultar' => 1]))->assertForbidden();
    $this->actingAs($user)->get(route('reportes.gestion-combustible-motorista.show.pdf', ['motorista' => $ma->id, 'consultar' => 1, 'empresa_ids' => [$b->id], 'unidad_ids' => [$ub->id], 'motorista_ids' => [$mb->id]]))->assertForbidden();
});

test('diez ciclos calculan siete favorables y setenta por ciento', function () {
    $e = gmEmpresa('GM-C');
    $admin = gmUsuario(User::ROL_DIESEL_ADMIN);
    $m = gmMotorista($e, 'C');
    $u = gmUnidad($e, 'GM-C');
    foreach (range(1, 10) as $n) {
        cicloGestion(['empresa' => $e, 'unidad' => $u, 'motorista' => $m, 'usuario' => $admin, 'fecha' => '2026-08-'.str_pad((string) $n, 2, '0', STR_PAD_LEFT), 'real' => $n <= 7 ? 9 : ($n <= 9 ? 11 : 10), 'teorico' => 10, 'costo' => 50]);
    }
    $this->actingAs($admin)->get(route('reportes.gestion-combustible-motorista.index', ['consultar' => 1]))->assertOk()->assertSee('70.0%')->assertDontSee('7 / 10');
    $this->actingAs($admin)->get(route('reportes.gestion-combustible-motorista.show', ['motorista' => $m->id, 'consultar' => 1]))->assertOk()->assertSee('Ciclos con ahorro')->assertSee('70.0%');
});

test('consolidación separa ahorro costo de sobreconsumo e impacto neto', function () {
    $e = gmEmpresa('GM-E');
    $admin = gmUsuario(User::ROL_DIESEL_ADMIN);
    $m = gmMotorista($e, 'E');
    $u = gmUnidad($e, 'GM-E');
    foreach ([[76, 100, 380], [107, 100, 535], [84, 100, 420]] as $n => [$real, $teorico, $costo]) {
        cicloGestion(['empresa' => $e, 'unidad' => $u, 'motorista' => $m, 'usuario' => $admin, 'fecha' => '2026-08-'.str_pad((string) ($n + 1), 2, '0', STR_PAD_LEFT), 'real' => $real, 'teorico' => $teorico, 'costo' => $costo]);
    }
    $this->actingAs($admin)->get(route('reportes.gestion-combustible-motorista.index', ['consultar' => 1]))->assertOk()
        ->assertSee('data-summary-label="Ahorro económico" data-summary-value="$200.00"', false)
        ->assertSee('data-summary-label="Costo de sobreconsumo" data-summary-value="$35.00"', false)
        ->assertSee('data-summary-label="Impacto económico neto" data-summary-value="+$165.00"', false);
});
