<?php

use App\Models\Abastecimiento;
use App\Models\Empresa;
use App\Models\Motorista;
use App\Models\Permiso;
use App\Models\Role;
use App\Models\Unidad;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\RolPermisosSeeder;

function prepararReporteKmGalon($caso): void
{
    $caso->seed([RolesSeeder::class, PermisosSeeder::class, RolPermisosSeeder::class]);
}

function empresaKmGalon(string $codigo): Empresa
{
    return Empresa::query()->create(['nombre_legal' => "Empresa KM {$codigo}", 'nombre_comercial' => "KM {$codigo}", 'nit' => "NIT-KM-{$codigo}", 'estado' => 'activa']);
}

function unidadKmGalon(Empresa $empresa, string $placa, string $modelo = 'kilometros_galon'): Unidad
{
    return Unidad::query()->create(['empresa_id' => $empresa->id, 'placa' => $placa, 'marca' => 'Volvo', 'total_tanques' => 1, 'cantidad_tanques_con_licencia' => 1, 'capacidad_total' => 100, 'capacidad_cubierta' => 100, 'modelo_medicion' => $modelo, 'rendimiento_teorico_km_galon' => $modelo === 'kilometros_galon' ? 10 : null, 'rendimiento_teorico_gal_hora' => $modelo === 'galones_hora' ? 2 : null, 'estado' => 'activa']);
}

function motoristaKmGalon(Empresa $empresa, string $codigo): Motorista
{
    return Motorista::query()->create(['empresa_id' => $empresa->id, 'nombres' => "Motorista {$codigo}", 'apellidos' => 'Prueba', 'licencia' => "LIC{$codigo}", 'telefono' => '70000000', 'estado' => 'activo']);
}

function usuarioKmGalon(string $rol, ?Empresa $empresa = null): User
{
    return User::factory()->create(['rol_id' => Role::query()->where('codigo', $rol)->value('id'), 'tipo_usuario' => $empresa ? User::TIPO_EMPRESA : User::TIPO_DIESEL_COP, 'empresa_id' => $empresa?->id, 'estado' => 'activo']);
}

function abastecimientoKmGalon(array $datos): Abastecimiento
{
    $empresa = $datos['empresa'];
    $unidad = $datos['unidad'];
    $motorista = $datos['motorista'];
    $usuario = $datos['usuario'];
    $fecha = $datos['fecha'];
    $modelo = $datos['modelo'] ?? Abastecimiento::MODELO_KILOMETROS_GALON;

    return Abastecimiento::query()->create([
        'empresa_id' => $empresa->id, 'unidad_id' => $unidad->id, 'motorista_id' => $motorista->id,
        'abastecimiento_anterior_id' => $datos['anterior_id'] ?? null, 'registrado_por' => $usuario->id,
        'empresa_nombre_snapshot' => $empresa->nombre_comercial, 'unidad_placa_snapshot' => $unidad->placa,
        'unidad_marca_snapshot' => $unidad->marca, 'unidad_modelo_snapshot' => 'FH',
        'motorista_nombre_snapshot' => $motorista->nombre_completo, 'motorista_licencia_snapshot' => $motorista->licencia,
        'fecha_hora_abastecimiento' => $fecha, 'estado' => $datos['estado'] ?? 'registrado', 'modelo_medicion' => $modelo,
        'rendimiento_teorico_km_galon_snapshot' => $modelo === Abastecimiento::MODELO_KILOMETROS_GALON ? 10 : null,
        'rendimiento_teorico_gal_hora_snapshot' => $modelo === Abastecimiento::MODELO_GALONES_HORA ? 2 : null,
        'lectura_actual' => $datos['kilometraje_actual'] ?? 1000, 'lectura_anterior' => $datos['kilometraje_anterior'] ?? null,
        'diferencia_lectura' => $datos['kilometros'] ?? null, 'kilometraje_actual' => $datos['kilometraje_actual'] ?? 1000,
        'kilometraje_anterior' => $datos['kilometraje_anterior'] ?? null, 'diferencia_kilometraje' => $datos['kilometros'] ?? null,
        'volumen_inicial' => 50, 'volumen_cargado' => 10, 'volumen_final' => 60, 'capacidad_cubierta_snapshot' => 100,
        'volumen_final_anterior' => isset($datos['anterior_id']) ? 60 : null, 'combustible_consumido_ciclo' => $datos['consumo_real'] ?? null,
        'combustible_adicional_no_explicado' => 0, 'consumo_real_ciclo' => $datos['consumo_real'] ?? null,
        'consumo_teorico_ciclo' => $datos['consumo_teorico'] ?? null, 'diferencia_galones_ciclo' => $datos['diferencia'] ?? null,
        'costo_combustible_consumido_ciclo' => $datos['costo_consumido'] ?? null, 'tipo_origen' => 'externo',
        'origen_nombre_snapshot' => 'Gasolinera prueba', 'precio_galon' => 5, 'total_pagado' => 50, 'moneda' => 'USD',
        'valor_carga_snapshot' => 50, 'total_rutas' => 0, 'total_viajes' => 0,
        'galones_por_kilometro' => isset($datos['anterior_id']) ? 0.1 : null,
        'kilometros_por_galon' => isset($datos['anterior_id']) ? 10 : null,
        'total_tapones_abiertos' => 0, 'total_marchamos_reemplazados' => 0,
    ]);
}

function cicloKmGalon(Empresa $empresa, Unidad $unidad, Motorista $aperturaMotorista, Motorista $cierreMotorista, User $usuario, string $fechaCierre, float $diferencia = -2, string $modelo = 'kilometros_galon', string $estado = 'registrado'): Abastecimiento
{
    $apertura = abastecimientoKmGalon(compact('empresa', 'unidad', 'usuario', 'modelo') + ['motorista' => $aperturaMotorista, 'fecha' => Carbon\Carbon::parse($fechaCierre)->subDay(), 'kilometraje_actual' => 1000]);
    return abastecimientoKmGalon(compact('empresa', 'unidad', 'usuario', 'modelo', 'estado') + ['motorista' => $cierreMotorista, 'fecha' => $fechaCierre, 'anterior_id' => $apertura->id, 'kilometraje_anterior' => 1000, 'kilometraje_actual' => 1100, 'kilometros' => 100, 'consumo_real' => 10, 'consumo_teorico' => 10 - $diferencia, 'diferencia' => -$diferencia, 'costo_consumido' => 50]);
}

test('ReporteRendimientoKmGalon permiso existe activo con matriz exacta', function () {
    prepararReporteKmGalon($this);
    $permiso = Permiso::query()->where('codigo', 'reportes.rendimiento-km-galon.consultar')->firstOrFail();
    expect($permiso->estado)->toBe('activo')->and($permiso->modulo)->toBe('reportes')
        ->and($permiso->roles()->orderBy('codigo')->pluck('codigo')->all())->toBe([User::ROL_DIESEL_ADMIN, User::ROL_DIESEL_AUDITOR, User::ROL_DIESEL_SUPER_ADMIN, User::ROL_EMPRESA_ADMIN, User::ROL_EMPRESA_AUDITOR, User::ROL_EMPRESA_SUPERVISOR]);
});

test('ReporteRendimientoKmGalon roles autorizados acceden y tecnico operador reciben 403', function () {
    prepararReporteKmGalon($this); $empresa = empresaKmGalon('ROLES');
    foreach ([User::ROL_DIESEL_SUPER_ADMIN, User::ROL_DIESEL_ADMIN, User::ROL_DIESEL_AUDITOR] as $rol) $this->actingAs(usuarioKmGalon($rol))->get(route('reportes.rendimiento-km-galon.index'))->assertOk();
    foreach ([User::ROL_EMPRESA_ADMIN, User::ROL_EMPRESA_SUPERVISOR, User::ROL_EMPRESA_AUDITOR] as $rol) $this->actingAs(usuarioKmGalon($rol, $empresa))->get(route('reportes.rendimiento-km-galon.index'))->assertOk();
    $this->actingAs(usuarioKmGalon(User::ROL_DIESEL_TECNICO))->get(route('reportes.rendimiento-km-galon.index'))->assertForbidden();
    $this->actingAs(usuarioKmGalon(User::ROL_EMPRESA_OPERADOR, $empresa))->get(route('reportes.rendimiento-km-galon.index'))->assertForbidden();
});

test('ReporteRendimientoKmGalon estado inicial limpiar y consulta completa', function () {
    prepararReporteKmGalon($this); $e=empresaKmGalon('INICIAL'); $u=unidadKmGalon($e,'KM-I'); $m=motoristaKmGalon($e,'I'); $admin=usuarioKmGalon(User::ROL_DIESEL_ADMIN); $c=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-10 12:00:00');
    $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index'))->assertOk()->assertDontSee('data-report-cycle-row=',false)->assertSee('No hay resultados para mostrar.')->assertSee('href="'.route('reportes.rendimiento-km-galon.index').'" class="cc-btn-secondary">Limpiar',false);
    $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1]))->assertSee('data-report-cycle-row="'.$c->id.'"',false);
});

test('ReporteRendimientoKmGalon excluye lineas base otros modelos y anulados', function () {
    prepararReporteKmGalon($this); $e=empresaKmGalon('EXC'); $admin=usuarioKmGalon(User::ROL_DIESEL_ADMIN); $m=motoristaKmGalon($e,'E');
    $km=unidadKmGalon($e,'KM-OK'); $linea=abastecimientoKmGalon(['empresa'=>$e,'unidad'=>$km,'motorista'=>$m,'usuario'=>$admin,'fecha'=>'2026-08-01']);
    $completo=cicloKmGalon($e,$km,$m,$m,$admin,'2026-08-02');
    $hora=cicloKmGalon($e,unidadKmGalon($e,'KM-H','galones_hora'),$m,$m,$admin,'2026-08-03',1,'galones_hora');
    $viaje=cicloKmGalon($e,unidadKmGalon($e,'KM-V','galones_viaje'),$m,$m,$admin,'2026-08-04',1,'galones_viaje');
    $anulado=cicloKmGalon($e,$km,$m,$m,$admin,'2026-08-05',1,'kilometros_galon','anulado');
    $r=$this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1]))->assertOk()->assertSee('data-report-cycle-row="'.$completo->id.'"',false);
    foreach([$linea,$hora,$viaje,$anulado] as $x) $r->assertDontSee('data-report-cycle-row="'.$x->id.'"',false);
});

test('ReporteRendimientoKmGalon tenant limita listado filtros manipulados y detalle cross tenant', function () {
    prepararReporteKmGalon($this); $a=empresaKmGalon('TA'); $b=empresaKmGalon('TB'); $global=usuarioKmGalon(User::ROL_DIESEL_ADMIN);
    $ma=motoristaKmGalon($a,'TA'); $mb=motoristaKmGalon($b,'TB'); $ua=unidadKmGalon($a,'KM-TA'); $ub=unidadKmGalon($b,'KM-TB'); $ca=cicloKmGalon($a,$ua,$ma,$ma,$global,'2026-08-10'); $cb=cicloKmGalon($b,$ub,$mb,$mb,$global,'2026-08-11'); $user=usuarioKmGalon(User::ROL_EMPRESA_ADMIN,$a);
    $this->actingAs($user)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1]))->assertSee('data-report-cycle-row="'.$ca->id.'"',false)->assertDontSee('data-report-cycle-row="'.$cb->id.'"',false);
    $this->actingAs($user)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1,'empresa_ids'=>[$b->id],'unidad_ids'=>[$ub->id],'motorista_ids'=>[$mb->id]]))->assertOk()->assertDontSee('data-report-cycle-row="'.$cb->id.'"',false);
    $this->actingAs($user)->get(route('reportes.rendimiento-km-galon.show',$ca))->assertOk();
    $this->actingAs($user)->get(route('reportes.rendimiento-km-galon.show',$cb))->assertForbidden();
});

test('ReporteRendimientoKmGalon filtros empresa unidad motorista y busqueda', function () {
    prepararReporteKmGalon($this); $a=empresaKmGalon('FA'); $b=empresaKmGalon('FB'); $admin=usuarioKmGalon(User::ROL_DIESEL_AUDITOR); $ma=motoristaKmGalon($a,'FA'); $mb=motoristaKmGalon($b,'FB'); $ua=unidadKmGalon($a,'KM-BUSCADA'); $ub=unidadKmGalon($b,'KM-OTRA'); $ca=cicloKmGalon($a,$ua,$ma,$ma,$admin,'2026-08-10'); $cb=cicloKmGalon($b,$ub,$mb,$mb,$admin,'2026-08-11');
    foreach([['empresa_ids'=>[$a->id]],['unidad_ids'=>[$ua->id]],['motorista_ids'=>[$ma->id]],['busqueda'=>'BUSCADA'],['busqueda'=>(string)$ca->id]] as $f) $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1]+$f))->assertSee('data-report-cycle-row="'.$ca->id.'"',false)->assertDontSee('data-report-cycle-row="'.$cb->id.'"',false);
});

test('ReporteRendimientoKmGalon fechas usan cierre e incluyen todo fecha hasta', function () {
    prepararReporteKmGalon($this); $e=empresaKmGalon('FECHA'); $admin=usuarioKmGalon(User::ROL_DIESEL_ADMIN); $m=motoristaKmGalon($e,'F'); $u=unidadKmGalon($e,'KM-F'); $antes=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-09 23:59:59'); $inicio=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-10 00:00:00'); $fin=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-10 23:59:59'); $despues=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-11 00:00:00');
    $r=$this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1,'fecha_desde'=>'2026-08-10','fecha_hasta'=>'2026-08-10']))->assertOk()->assertSee('data-report-cycle-row="'.$inicio->id.'"',false)->assertSee('data-report-cycle-row="'.$fin->id.'"',false);
    $r->assertDontSee('data-report-cycle-row="'.$antes->id.'"',false)->assertDontSee('data-report-cycle-row="'.$despues->id.'"',false);
});

test('ReporteRendimientoKmGalon resultados impacto y resumen son correctos', function () {
    prepararReporteKmGalon($this); $e=empresaKmGalon('RES'); $admin=usuarioKmGalon(User::ROL_DIESEL_ADMIN); $m=motoristaKmGalon($e,'R'); $u=unidadKmGalon($e,'KM-R'); $ahorro=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-10',-2); $sobre=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-11',3); $objetivo=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-12',0);
    foreach([['ahorro',$ahorro],['sobreconsumo',$sobre],['en_objetivo',$objetivo]] as [$f,$esperado]) $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1,'resultado'=>$f]))->assertSee('data-report-cycle-row="'.$esperado->id.'"',false);
    $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1]))->assertSee('+$10.00')->assertSee('-$15.00')->assertSee('data-summary-label="Ciclos evaluados" data-summary-value="3"',false)->assertSee('data-summary-label="Kilómetros recorridos" data-summary-value="300.00 km"',false)->assertSee('data-summary-label="Consumo teórico" data-summary-value="29.00 gal"',false)->assertSee('data-summary-label="Consumo real" data-summary-value="30.00 gal"',false)->assertSee('data-summary-label="Galones ahorrados" data-summary-value="2.00 gal"',false)->assertSee('data-summary-label="Sobreconsumo" data-summary-value="3.00 gal"',false)->assertSee('data-summary-label="Impacto económico neto" data-summary-value="-$5.00"',false);
});

test('ReporteRendimientoKmGalon clasifica por consumos y calcula impacto neto sin ambiguedad de signo', function () {
    prepararReporteKmGalon($this); $e=empresaKmGalon('SIGNO'); $admin=usuarioKmGalon(User::ROL_DIESEL_ADMIN); $m=motoristaKmGalon($e,'SIGNO'); $u=unidadKmGalon($e,'KM-SIGNO');
    $sobre=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-20',30); $sobre->update(['diferencia_kilometraje'=>2400,'consumo_teorico_ciclo'=>240,'consumo_real_ciclo'=>270,'diferencia_galones_ciclo'=>-30,'costo_combustible_consumido_ciclo'=>1350]);
    $ahorro=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-21',-30); $ahorro->update(['diferencia_kilometraje'=>2400,'consumo_teorico_ciclo'=>240,'consumo_real_ciclo'=>210,'diferencia_galones_ciclo'=>30,'costo_combustible_consumido_ciclo'=>1050]);
    $objetivo=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-22',0); $objetivo->update(['diferencia_kilometraje'=>2400,'consumo_teorico_ciclo'=>240,'consumo_real_ciclo'=>240,'diferencia_galones_ciclo'=>0,'costo_combustible_consumido_ciclo'=>1200]);

    $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1,'resultado'=>'sobreconsumo']))->assertOk()->assertSee('data-report-cycle-row="'.$sobre->id.'"',false)->assertSee('30.00 gal')->assertSee('Sobreconsumo')->assertSee('-$150.00')->assertDontSee('data-report-cycle-row="'.$ahorro->id.'"',false);
    $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1,'resultado'=>'ahorro']))->assertOk()->assertSee('data-report-cycle-row="'.$ahorro->id.'"',false)->assertSee('30.00 gal')->assertSee('Ahorro')->assertSee('+$150.00')->assertDontSee('data-report-cycle-row="'.$sobre->id.'"',false);
    $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1,'resultado'=>'en_objetivo']))->assertOk()->assertSee('data-report-cycle-row="'.$objetivo->id.'"',false)->assertSee('En objetivo')->assertSee('$0.00');
    $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1]))->assertSee('data-summary-label="Impacto económico neto" data-summary-value="$0.00"',false);
});

test('ReporteRendimientoKmGalon detalle atribuye cierre muestra ambos motoristas e impactos', function () {
    prepararReporteKmGalon($this); $e=empresaKmGalon('DET'); $admin=usuarioKmGalon(User::ROL_DIESEL_ADMIN); $a=motoristaKmGalon($e,'APERTURA'); $c=motoristaKmGalon($e,'CIERRE'); $u=unidadKmGalon($e,'KM-DET'); $ciclo=cicloKmGalon($e,$u,$a,$c,$admin,'2026-08-10',-2);
    $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.show',$ciclo))->assertOk()->assertSee('Motorista principal: '.$c->nombre_completo)->assertSee('Motorista apertura')->assertSee($a->nombre_completo)->assertSee('Motorista cierre · principal')->assertSee($c->nombre_completo)->assertSee('Cambio de motorista durante el ciclo')->assertSee('Abastecimiento de apertura')->assertSee('Abastecimiento de cierre')->assertSee('+$10.00')->assertSee('Ahorro');
});

test('ReporteRendimientoKmGalon pagina de diez conserva query y segunda pagina', function () {
    prepararReporteKmGalon($this); $e=empresaKmGalon('PAG'); $admin=usuarioKmGalon(User::ROL_DIESEL_AUDITOR); $m=motoristaKmGalon($e,'P'); $u=unidadKmGalon($e,'KM-P'); $ciclos=collect(range(1,12))->map(fn($n)=>cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-'.str_pad((string)$n,2,'0',STR_PAD_LEFT),-1));
    $r1=$this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1,'resultado'=>'ahorro']))->assertOk()->assertSee('consultar=1',false)->assertSee('resultado=ahorro',false); expect(substr_count($r1->getContent(),'data-report-cycle-row='))->toBe(10);
    $r2=$this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.index',['consultar'=>1,'resultado'=>'ahorro','page'=>2]))->assertOk(); expect(substr_count($r2->getContent(),'data-report-cycle-row='))->toBe(2);
});

test('ReporteRendimientoKmGalon variantes ventana preservan contexto y seguridad', function () {
    prepararReporteKmGalon($this); $e=empresaKmGalon('VEN'); $admin=usuarioKmGalon(User::ROL_DIESEL_ADMIN); $m=motoristaKmGalon($e,'V'); $u=unidadKmGalon($e,'KM-VEN'); $c=cicloKmGalon($e,$u,$m,$m,$admin,'2026-08-10'); $q=['consultar'=>1,'resultado'=>'ahorro','page'=>2];
    $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.ventana',$q))->assertOk()->assertSee('Volver al sistema')->assertSee('resultado=ahorro',false);
    $this->actingAs($admin)->get(route('reportes.rendimiento-km-galon.show.ventana',['ciclo'=>$c->id]+$q))->assertOk()->assertSee(route('reportes.rendimiento-km-galon.ventana',$q))->assertDontSee('Abrir en nueva pestaña');
});
