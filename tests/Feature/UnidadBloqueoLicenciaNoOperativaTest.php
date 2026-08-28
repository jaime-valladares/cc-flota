<?php

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\Role;
use App\Models\Unidad;
use App\Models\UnidadTanque;
use App\Models\User;

function escenarioBloqueoLicencia(string $condicion, string $estadoUnidad = 'activa'): array
{
    $rol = Role::firstOrCreate(['codigo' => User::ROL_DIESEL_SUPER_ADMIN], ['nombre' => 'Super licencia unidad', 'alcance' => 'diesel_cop', 'estado' => 'activo']);
    $usuario = User::factory()->create(['rol_id' => $rol->id, 'tipo_usuario' => User::TIPO_DIESEL_COP, 'empresa_id' => null]);
    $empresa = Empresa::create(['nombre_legal' => 'Empresa licencia '.$condicion, 'nombre_comercial' => 'Licencia '.$condicion, 'nit' => 'LIC-'.$condicion, 'estado' => 'activa']);
    $unidad = Unidad::create(['empresa_id' => $empresa->id, 'placa' => 'UNI-'.$condicion, 'total_tanques' => 1, 'cantidad_tanques_con_licencia' => 1, 'capacidad_total' => 100, 'capacidad_cubierta' => 100, 'modelo_medicion' => 'kilometros_galon', 'rendimiento_teorico_km_galon' => 8, 'estado' => $estadoUnidad]);
    UnidadTanque::create(['unidad_id' => $unidad->id, 'numero' => 1, 'capacidad' => 100, 'cubierto_por_licencia' => true]);

    $fechas = match ($condicion) {
        'vigente' => [now()->subDay(), now()->addYear(), 'activa'],
        'vencida' => [now()->subYear(), now()->subDay(), 'activa'],
        'pendiente' => [now()->addDay(), now()->addYear(), 'activa'],
        default => [now()->subDay(), now()->addYear(), 'inactiva'],
    };
    Licencia::create(['empresa_id' => $empresa->id, 'unidad_id' => $unidad->id, 'periodo_vigencia_meses' => 12, 'fecha_activacion' => $fechas[0], 'fecha_vencimiento' => $fechas[1], 'estado' => $fechas[2], 'plantilla_puntos_seguridad' => 'plantilla_1_tanque']);

    return [$usuario, $empresa, $unidad];
}

function datosUpdateBloqueo(Empresa $empresa): array
{
    return ['empresa_id' => $empresa->id, 'placa' => 'UNI-ACTUALIZADA', 'cantidad_tanques' => 1, 'tanques' => [['capacidad' => 100]], 'modelo_medicion' => 'kilometros_galon', 'rendimiento_teorico_km_galon' => 8];
}

test('edicion permite licencia vigente y bloquea vencida inactiva y pendiente', function () {
    [$usuario, $empresa, $vigente] = escenarioBloqueoLicencia('vigente');
    $this->actingAs($usuario)->get(route('unidades.edit', $vigente))->assertOk();
    $this->actingAs($usuario)->put(route('unidades.update', $vigente), datosUpdateBloqueo($empresa))->assertSessionHasNoErrors();

    foreach (['vencida', 'inactiva', 'pendiente'] as $condicion) {
        [$usuarioCaso, $empresaCaso, $unidad] = escenarioBloqueoLicencia($condicion);
        $this->actingAs($usuarioCaso)->get(route('unidades.edit', $unidad))->assertForbidden();
        $this->actingAs($usuarioCaso)->put(route('unidades.update', $unidad), datosUpdateBloqueo($empresaCaso))->assertForbidden();
        $this->actingAs($usuarioCaso)->get(route('unidades.administrar', ['consultar' => 1]))->assertDontSee(route('unidades.edit', $unidad), false);
    }
});

test('unidad registrada sin licencia conserva edicion', function () {
    [$usuario, $empresa] = escenarioBloqueoLicencia('vigente');
    $unidad = Unidad::create(['empresa_id' => $empresa->id, 'placa' => 'SIN-LIC', 'total_tanques' => 1, 'cantidad_tanques_con_licencia' => 0, 'capacidad_total' => 100, 'capacidad_cubierta' => 0, 'modelo_medicion' => 'kilometros_galon', 'rendimiento_teorico_km_galon' => 8, 'estado' => 'registrada']);
    $this->actingAs($usuario)->get(route('unidades.edit', $unidad))->assertOk();
});

test('reactivacion exige licencia vigente cuando existe', function () {
    [$usuario, , $vigente] = escenarioBloqueoLicencia('vigente', 'inactiva');
    $this->actingAs($usuario)->get(route('unidades.show', $vigente))->assertSee('Reactivar unidad');
    $this->actingAs($usuario)->patch(route('unidades.reactivar', $vigente))->assertRedirect();

    foreach (['vencida', 'inactiva', 'pendiente'] as $condicion) {
        [$usuarioCaso, , $unidad] = escenarioBloqueoLicencia($condicion, 'inactiva');
        $this->actingAs($usuarioCaso)->get(route('unidades.show', $unidad))->assertDontSee('Reactivar unidad');
        $this->actingAs($usuarioCaso)->patch(route('unidades.reactivar', $unidad))->assertForbidden();
    }
});
