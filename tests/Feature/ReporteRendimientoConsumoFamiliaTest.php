<?php

use App\Models\Abastecimiento;
use App\Models\User;

function cicloFamilia($empresa,$unidad,$motorista,$usuario,string $fecha,string $modelo,float $real,float $teorico): Abastecimiento
{
    $c=cicloKmGalon($empresa,$unidad,$motorista,$motorista,$usuario,$fecha,$real-$teorico,$modelo);
    $c->update(['consumo_real_ciclo'=>$real,'combustible_consumido_ciclo'=>$real,'consumo_teorico_ciclo'=>$teorico,'diferencia_galones_ciclo'=>$teorico-$real,'costo_combustible_consumido_ciclo'=>$real*5]);
    if($modelo===Abastecimiento::MODELO_GALONES_HORA)$c->update(['horometro_anterior'=>100,'horometro_actual'=>120,'diferencia_horometro'=>20,'rendimiento_teorico_gal_hora_snapshot'=>$teorico/20,'galones_por_hora'=>$real/20]);
    if($modelo===Abastecimiento::MODELO_GALONES_VIAJE)$c->update(['total_viajes'=>4,'galones_teoricos'=>$teorico]);
    return $c->fresh();
}

test('familia fija modelos y selectores de unidad en backend',function(){
    prepararReporteKmGalon($this);$e=empresaKmGalon('FAM');$admin=usuarioKmGalon(User::ROL_DIESEL_ADMIN);$m=motoristaKmGalon($e,'FAM');
    $km=unidadKmGalon($e,'SEL-KM','kilometros_galon');$h=unidadKmGalon($e,'SEL-HORA','galones_hora');$v=unidadKmGalon($e,'SEL-VIAJE','galones_viaje');
    $ck=cicloFamilia($e,$km,$m,$admin,'2026-08-10','kilometros_galon',8,10);$ch=cicloFamilia($e,$h,$m,$admin,'2026-08-11','galones_hora',8,10);$cv=cicloFamilia($e,$v,$m,$admin,'2026-08-12','galones_viaje',8,10);
    foreach([['reportes.rendimiento-km-galon',$km,$ck,[$ch,$cv]],['reportes.rendimiento-galones-hora',$h,$ch,[$ck,$cv]],['reportes.rendimiento-galones-viaje',$v,$cv,[$ck,$ch]]] as[$ruta,$unidad,$incluido,$excluidos]){
        $r=$this->actingAs($admin)->get(route($ruta.'.index',['consultar'=>1]))->assertOk()->assertSee($unidad->placa)->assertSee('data-report-cycle-row="'.$incluido->id.'"',false)->assertDontSee('name="modelo_medicion"',false);
        foreach($excluidos as $x)$r->assertDontSee('data-report-cycle-row="'.$x->id.'"',false);
    }
    $this->actingAs($admin)->get(route('reportes.rendimiento-galones-hora.index',['consultar'=>1,'unidad_ids'=>[$km->id]]))->assertNotFound();
});

test('reportes hora y viaje calculan clasificacion impacto resumen y cero seguro',function(){
    prepararReporteKmGalon($this);$e=empresaKmGalon('CAL');$admin=usuarioKmGalon(User::ROL_DIESEL_ADMIN);$m=motoristaKmGalon($e,'CAL');
    foreach([['galones_hora','reportes.rendimiento-galones-hora','Horas trabajadas','h'],['galones_viaje','reportes.rendimiento-galones-viaje','Viajes evaluados','viajes']] as[$modelo,$ruta,$label,$unidad]){
        $u=unidadKmGalon($e,'CAL-'.strtoupper($unidad),$modelo);$a=cicloFamilia($e,$u,$m,$admin,'2026-08-20',$modelo,8,10);$s=cicloFamilia($e,$u,$m,$admin,'2026-08-21',$modelo,12,10);$o=cicloFamilia($e,$u,$m,$admin,'2026-08-22',$modelo,10,10);
        $r=$this->actingAs($admin)->get(route($ruta.'.index',['consultar'=>1]))->assertOk()->assertSee('Ahorro')->assertSee('Sobreconsumo')->assertSee('En objetivo')->assertSee('+$10.00')->assertSee('-$10.00')->assertSee('data-summary-label="'.$label.'"',false);
        $this->actingAs($admin)->get(route($ruta.'.show',$a))->assertOk()->assertSee('Consumo teórico')->assertSee('Costo efectivo por galón');
        $this->actingAs($admin)->get(route($ruta.'.ventana',['consultar'=>1]))->assertOk();$this->actingAs($admin)->get(route($ruta.'.show.ventana',$a))->assertOk();
    }
});

test('familia protege detalle modelo tenant roles e historial inactivo',function(){
    prepararReporteKmGalon($this);$a=empresaKmGalon('SA');$b=empresaKmGalon('SB');$global=usuarioKmGalon(User::ROL_DIESEL_ADMIN);$ma=motoristaKmGalon($a,'SA');$mb=motoristaKmGalon($b,'SB');$ua=unidadKmGalon($a,'SEC-A','galones_hora');$ub=unidadKmGalon($b,'SEC-B','galones_hora');$ca=cicloFamilia($a,$ua,$ma,$global,'2026-08-10','galones_hora',8,10);$cb=cicloFamilia($b,$ub,$mb,$global,'2026-08-11','galones_hora',8,10);$ua->update(['estado'=>'inactiva']);$ma->update(['estado'=>'inactivo']);$user=usuarioKmGalon(User::ROL_EMPRESA_ADMIN,$a);
    $this->actingAs($user)->get(route('reportes.rendimiento-galones-hora.index',['consultar'=>1]))->assertOk()->assertSee('data-report-cycle-row="'.$ca->id.'"',false)->assertDontSee('data-report-cycle-row="'.$cb->id.'"',false);
    $this->actingAs($user)->get(route('reportes.rendimiento-galones-hora.show',$cb))->assertForbidden();
    $this->actingAs($global)->get(route('reportes.rendimiento-galones-viaje.show',$ca))->assertNotFound();
    $this->actingAs(usuarioKmGalon(User::ROL_DIESEL_TECNICO))->get(route('reportes.rendimiento-galones-hora.index'))->assertForbidden();
    $this->actingAs(usuarioKmGalon(User::ROL_EMPRESA_OPERADOR,$a))->get(route('reportes.rendimiento-galones-viaje.index'))->assertForbidden();
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
