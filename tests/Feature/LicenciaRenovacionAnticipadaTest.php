<?php

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\LicenciaRenovacion;
use App\Models\Role;
use App\Models\Unidad;
use App\Models\User;
use Carbon\Carbon;

function prepararRenovacionLicencia(int $diasRestantes, string $estado = 'activa'): array
{
    $rol = Role::firstOrCreate(
        ['codigo' => User::ROL_DIESEL_SUPER_ADMIN],
        [
            'nombre' => 'Superadministrador renovaciones',
            'alcance' => 'diesel_cop',
            'estado' => 'activo',
        ]
    );
    $usuario = User::factory()->create([
        'rol_id' => $rol->id,
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'empresa_id' => null,
    ]);
    $empresa = Empresa::create([
        'nombre_legal' => 'Empresa renovación',
        'nombre_comercial' => 'Renovación',
        'nit' => 'REN-'.uniqid(),
        'estado' => 'activa',
    ]);
    $unidad = Unidad::create([
        'empresa_id' => $empresa->id,
        'placa' => 'REN-'.uniqid(),
        'marca' => 'Prueba',
        'total_tanques' => 1,
        'cantidad_tanques_con_licencia' => 1,
        'capacidad_total' => 100,
        'capacidad_cubierta' => 100,
        'modelo_medicion' => 'kilometros_galon',
        'rendimiento_teorico_km_galon' => 8,
        'estado' => 'activa',
        'creado_por' => $usuario->id,
        'actualizado_por' => $usuario->id,
    ]);
    $licencia = Licencia::create([
        'empresa_id' => $empresa->id,
        'unidad_id' => $unidad->id,
        'periodo_vigencia_meses' => 12,
        'fecha_activacion' => now()->subYear()->toDateString(),
        'fecha_vencimiento' => now()->addDays($diasRestantes)->toDateString(),
        'estado' => $estado,
        'plantilla_puntos_seguridad' => 'plantilla_1_tanque',
        'creado_por' => $usuario->id,
        'actualizado_por' => $usuario->id,
    ]);

    return [$usuario, $empresa, $unidad, $licencia];
}

beforeEach(function () {
    Carbon::setTestNow(Carbon::create(2026, 8, 26, 10, 0, 0, 'America/El_Salvador'));
});

afterEach(function () {
    Carbon::setTestNow();
});

test('renovacion solo se muestra y permite dentro de los ultimos 30 dias', function () {
    [$usuario31, , , $licencia31] = prepararRenovacionLicencia(31);

    $this->actingAs($usuario31)
        ->get(route('licencias.show', $licencia31))
        ->assertOk()
        ->assertDontSee('Renovación anticipada');

    $this->actingAs($usuario31)
        ->patch(route('licencias.renovar', $licencia31), ['periodo_agregado_meses' => 3])
        ->assertSessionHasErrors('periodo_agregado_meses');

    foreach ([30, 1, 0] as $dias) {
        [$usuario, , , $licencia] = prepararRenovacionLicencia($dias);

        $this->actingAs($usuario)
            ->get(route('licencias.show', $licencia))
            ->assertOk()
            ->assertSee('Renovación anticipada');

        $this->actingAs($usuario)
            ->patch(route('licencias.renovar', $licencia), ['periodo_agregado_meses' => 3])
            ->assertSessionHasNoErrors();
    }
});

test('renovacion agrega 3 6 o 12 meses desde el vencimiento anterior y audita', function () {
    foreach ([3, 6, 12] as $periodo) {
        [$usuario, , , $licencia] = prepararRenovacionLicencia(20);
        $anterior = $licencia->fecha_vencimiento->copy();

        $this->actingAs($usuario)
            ->patch(route('licencias.renovar', $licencia), [
                'periodo_agregado_meses' => $periodo,
            ])
            ->assertSessionHasNoErrors();

        $esperada = $anterior->copy()->addMonthsNoOverflow($periodo);
        $evento = LicenciaRenovacion::where('licencia_id', $licencia->id)->sole();

        expect($licencia->refresh()->fecha_vencimiento->toDateString())
            ->toBe($esperada->toDateString())
            ->and($evento->fecha_vencimiento_anterior->toDateString())
            ->toBe($anterior->toDateString())
            ->and($evento->fecha_vencimiento_nueva->toDateString())
            ->toBe($esperada->toDateString())
            ->and($evento->periodo_agregado_meses)->toBe($periodo)
            ->and($evento->renovado_por)->toBe($usuario->id);
    }
});

test('renovacion usa suma calendaria segura para fin de mes', function () {
    Carbon::setTestNow(Carbon::create(2027, 1, 1, 10, 0, 0, 'America/El_Salvador'));
    [$usuario, , , $licencia] = prepararRenovacionLicencia(30);

    $this->actingAs($usuario)
        ->patch(route('licencias.renovar', $licencia), ['periodo_agregado_meses' => 3])
        ->assertSessionHasNoErrors();

    expect($licencia->refresh()->fecha_vencimiento->toDateString())->toBe('2027-04-30');
});

test('dos intentos sobre el mismo ciclo no producen doble renovacion', function () {
    [$usuario, , , $licencia] = prepararRenovacionLicencia(30);

    $this->actingAs($usuario)
        ->patch(route('licencias.renovar', $licencia), ['periodo_agregado_meses' => 3])
        ->assertSessionHasNoErrors();

    $this->actingAs($usuario)
        ->patch(route('licencias.renovar', $licencia), ['periodo_agregado_meses' => 3])
        ->assertSessionHasErrors('periodo_agregado_meses');

    expect(LicenciaRenovacion::where('licencia_id', $licencia->id)->count())->toBe(1);
});

test('un ciclo renovado puede renovarse otra vez al regresar a la ventana', function () {
    [$usuario, , , $licencia] = prepararRenovacionLicencia(30);

    $this->actingAs($usuario)
        ->patch(route('licencias.renovar', $licencia), ['periodo_agregado_meses' => 3])
        ->assertSessionHasNoErrors();

    Carbon::setTestNow($licencia->refresh()->fecha_vencimiento->copy()->subDays(30)->setTime(10, 0));

    $this->actingAs($usuario)
        ->patch(route('licencias.renovar', $licencia), ['periodo_agregado_meses' => 3])
        ->assertSessionHasNoErrors();

    expect(LicenciaRenovacion::where('licencia_id', $licencia->id)->count())->toBe(2);
});

test('renovacion anticipada bloquea licencia inactiva y vencida', function () {
    [$usuarioInactiva, , , $inactiva] = prepararRenovacionLicencia(10, 'inactiva');
    $this->actingAs($usuarioInactiva)
        ->patch(route('licencias.renovar', $inactiva), ['periodo_agregado_meses' => 3])
        ->assertSessionHasErrors('periodo_agregado_meses');

    [$usuarioVencida, , , $vencida] = prepararRenovacionLicencia(-1);
    $this->actingAs($usuarioVencida)
        ->patch(route('licencias.renovar', $vencida), ['periodo_agregado_meses' => 3])
        ->assertSessionHasErrors('periodo_agregado_meses');
});

test('usuario fuera del alcance empresarial no puede renovar', function () {
    [$usuario, , , $licencia] = prepararRenovacionLicencia(10);
    $otraEmpresa = Empresa::create([
        'nombre_legal' => 'Otra empresa',
        'nombre_comercial' => 'Otra',
        'nit' => 'REN-OTRA',
        'estado' => 'activa',
    ]);
    $usuario->update([
        'empresa_id' => $otraEmpresa->id,
        'tipo_usuario' => User::TIPO_EMPRESA,
    ]);

    $this->actingAs($usuario)
        ->patch(route('licencias.renovar', $licencia), ['periodo_agregado_meses' => 3])
        ->assertForbidden();

    expect(LicenciaRenovacion::count())->toBe(0);
});
