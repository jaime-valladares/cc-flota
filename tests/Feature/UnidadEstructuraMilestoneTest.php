<?php

use App\Models\Empresa;
use App\Models\Marchamo;
use App\Models\Role;
use App\Models\Unidad;
use App\Models\User;
use App\Support\PlantillasPuntosSeguridad;

function usuarioEstructuraUnidad(): User
{
    $rol = Role::create([
        'codigo' => User::ROL_DIESEL_SUPER_ADMIN,
        'nombre' => 'Superadministrador de prueba',
        'alcance' => 'diesel_cop',
        'estado' => 'activo',
    ]);

    return User::factory()->create([
        'rol_id' => $rol->id,
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'empresa_id' => null,
    ]);
}

function empresaEstructuraUnidad(): Empresa
{
    return Empresa::create([
        'nombre_legal' => 'Empresa de estructura',
        'nombre_comercial' => 'Estructura',
        'nit' => 'TEST-ESTRUCTURA',
        'estado' => 'activa',
    ]);
}

function datosUnidadEstructural(Empresa $empresa, array $tanques, array $extra = []): array
{
    return array_merge([
        'empresa_id' => $empresa->id,
        'placa' => 'UNIDAD-ESTRUCTURA',
        'marca' => 'Prueba',
        'cantidad_tanques' => count($tanques),
        'tanques' => $tanques,
        'modelo_medicion' => 'kilometros_galon',
        'rendimiento_teorico_km_galon' => 8.5,
    ], $extra);
}

test('deriva totales para una unidad de un tanque cubierto', function () {
    $empresa = empresaEstructuraUnidad();

    $this->actingAs(usuarioEstructuraUnidad())
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 300, 'cubierto_por_licencia' => 1],
        ]))
        ->assertSessionHasNoErrors();

    $unidad = Unidad::firstOrFail();

    expect((float) $unidad->capacidad_total)->toBe(300.0)
        ->and((float) $unidad->capacidad_cubierta)->toBe(300.0)
        ->and($unidad->total_tanques)->toBe(1)
        ->and($unidad->cantidad_tanques_con_licencia)->toBe(1)
        ->and($unidad->tanquesUnidad)->toHaveCount(1);
});

test('deriva suma y cobertura parcial para dos tanques', function () {
    $empresa = empresaEstructuraUnidad();

    $this->actingAs(usuarioEstructuraUnidad())
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 300, 'cubierto_por_licencia' => 1],
            ['capacidad' => 200, 'cubierto_por_licencia' => 0],
        ]))
        ->assertSessionHasNoErrors();

    $unidad = Unidad::firstOrFail();

    expect((float) $unidad->capacidad_total)->toBe(500.0)
        ->and((float) $unidad->capacidad_cubierta)->toBe(300.0)
        ->and($unidad->cantidad_tanques_con_licencia)->toBe(1);
});

test('deriva suma para tres tanques', function () {
    $empresa = empresaEstructuraUnidad();

    $this->actingAs(usuarioEstructuraUnidad())
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 100, 'cubierto_por_licencia' => 1],
            ['capacidad' => 200, 'cubierto_por_licencia' => 1],
            ['capacidad' => 300, 'cubierto_por_licencia' => 0],
        ]))
        ->assertSessionHasNoErrors();

    expect((float) Unidad::firstOrFail()->capacidad_total)->toBe(600.0);
});

test('rechaza capacidad no positiva y cobertura vacia', function () {
    $empresa = empresaEstructuraUnidad();
    $usuario = usuarioEstructuraUnidad();

    $this->actingAs($usuario)
        ->from(route('unidades.create'))
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 0, 'cubierto_por_licencia' => 1],
        ]))
        ->assertSessionHasErrors('tanques.0.capacidad');

    $this->actingAs($usuario)
        ->from(route('unidades.create'))
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 100, 'cubierto_por_licencia' => 0],
        ]))
        ->assertSessionHasErrors('tanques');
});

test('valida rendimientos teoricos segun modelo', function () {
    $empresa = empresaEstructuraUnidad();
    $usuario = usuarioEstructuraUnidad();
    $tanques = [['capacidad' => 100, 'cubierto_por_licencia' => 1]];

    $sinKmGal = datosUnidadEstructural($empresa, $tanques);
    unset($sinKmGal['rendimiento_teorico_km_galon']);

    $this->actingAs($usuario)
        ->post(route('unidades.store'), $sinKmGal)
        ->assertSessionHasErrors('rendimiento_teorico_km_galon');

    $this->actingAs($usuario)
        ->post(route('unidades.store'), datosUnidadEstructural(
            $empresa,
            $tanques,
            ['modelo_medicion' => 'galones_hora']
        ))
        ->assertSessionHasErrors('rendimiento_teorico_gal_hora');

    $this->actingAs($usuario)
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, $tanques))
        ->assertSessionHasNoErrors();

    expect(Unidad::firstOrFail()->rendimiento_teorico_gal_hora)->toBeNull();
});

test('unidad registrada puede cambiar su estructura', function () {
    $empresa = empresaEstructuraUnidad();
    $usuario = usuarioEstructuraUnidad();

    $this->actingAs($usuario)
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 100, 'cubierto_por_licencia' => 1],
        ]));

    $unidad = Unidad::firstOrFail();

    $this->actingAs($usuario)
        ->put(route('unidades.update', $unidad), datosUnidadEstructural(
            $empresa,
            [
                ['capacidad' => 150, 'cubierto_por_licencia' => 1],
                ['capacidad' => 250, 'cubierto_por_licencia' => 0],
            ]
        ))
        ->assertSessionHasNoErrors();

    expect((float) $unidad->refresh()->capacidad_total)->toBe(400.0)
        ->and($unidad->tanquesUnidad)->toHaveCount(2);
});

test('unidad activa no puede cambiar su estructura', function () {
    $empresa = empresaEstructuraUnidad();
    $usuario = usuarioEstructuraUnidad();

    $this->actingAs($usuario)
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 100, 'cubierto_por_licencia' => 1],
        ]));

    $unidad = Unidad::firstOrFail();
    $unidad->update(['estado' => 'activa']);

    $this->actingAs($usuario)
        ->put(route('unidades.update', $unidad), datosUnidadEstructural(
            $empresa,
            [['capacidad' => 500, 'cubierto_por_licencia' => 1]]
        ))
        ->assertForbidden();

    expect((float) $unidad->refresh()->capacidad_total)->toBe(100.0);
});

test('licencia selecciona plantilla desde cobertura derivada', function () {
    $empresa = empresaEstructuraUnidad();
    $usuario = usuarioEstructuraUnidad();

    $this->actingAs($usuario)
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 100, 'cubierto_por_licencia' => 1],
            ['capacidad' => 200, 'cubierto_por_licencia' => 1],
            ['capacidad' => 300, 'cubierto_por_licencia' => 0],
        ]));

    $unidad = Unidad::firstOrFail();

    $this->actingAs($usuario)
        ->post(route('licencias.store'), [
            'empresa_id' => $empresa->id,
            'unidad_id' => $unidad->id,
            'periodo_vigencia_meses' => 12,
            'fecha_activacion' => now()->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect($unidad->licencia()->firstOrFail()->plantilla_puntos_seguridad)
        ->toBe('plantilla_2_tanques');
});

test('reconcilia licencia y puntos si cambia cobertura sin avance de marchamos', function () {
    $empresa = empresaEstructuraUnidad();
    $usuario = usuarioEstructuraUnidad();

    $this->actingAs($usuario)
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 100, 'cubierto_por_licencia' => 1],
            ['capacidad' => 200, 'cubierto_por_licencia' => 1],
        ]));

    $unidad = Unidad::firstOrFail();

    $this->actingAs($usuario)
        ->post(route('licencias.store'), [
            'empresa_id' => $empresa->id,
            'unidad_id' => $unidad->id,
            'periodo_vigencia_meses' => 12,
            'fecha_activacion' => now()->toDateString(),
        ]);

    $this->actingAs($usuario)
        ->put(route('unidades.update', $unidad), datosUnidadEstructural($empresa, [
            ['capacidad' => 100, 'cubierto_por_licencia' => 1],
            ['capacidad' => 200, 'cubierto_por_licencia' => 0],
        ]))
        ->assertSessionHasNoErrors();

    $unidad->refresh();

    expect($unidad->cantidad_tanques_con_licencia)->toBe(1)
        ->and($unidad->licencia->plantilla_puntos_seguridad)
        ->toBe('plantilla_1_tanque')
        ->and($unidad->puntosSeguridad)->toHaveCount(
            PlantillasPuntosSeguridad::cantidadEsperada('plantilla_1_tanque')
        )
        ->and($unidad->puntosSeguridad->pluck('plantilla_origen')->unique()->all())
        ->toBe(['plantilla_1_tanque']);
});

test('bloquea cambio de plantilla cuando existe avance provisional de marchamos', function () {
    $empresa = empresaEstructuraUnidad();
    $usuario = usuarioEstructuraUnidad();

    $this->actingAs($usuario)
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 100, 'cubierto_por_licencia' => 1],
            ['capacidad' => 200, 'cubierto_por_licencia' => 1],
        ]));

    $unidad = Unidad::firstOrFail();

    $this->actingAs($usuario)
        ->post(route('licencias.store'), [
            'empresa_id' => $empresa->id,
            'unidad_id' => $unidad->id,
            'periodo_vigencia_meses' => 12,
            'fecha_activacion' => now()->toDateString(),
        ]);

    $punto = $unidad->puntosSeguridad()->firstOrFail();

    $this->actingAs($usuario)
        ->post(route('marchamos.asignacion-inicial.guardar-avance', $unidad), [
            'marchamos' => [$punto->id => '7654321'],
        ])
        ->assertSessionHasNoErrors();

    expect($punto->refresh()->marchamo_actual_id)->not->toBeNull();

    $puntoId = $punto->id;
    $marchamoId = $punto->marchamo_actual_id;
    $estadoAsignacion = $punto->estado_asignacion;
    $cantidadPuntos = $unidad->puntosSeguridad()->count();

    $this->actingAs($usuario)
        ->from(route('unidades.edit', $unidad))
        ->put(route('unidades.update', $unidad), datosUnidadEstructural($empresa, [
            ['capacidad' => 100, 'cubierto_por_licencia' => 1],
            ['capacidad' => 200, 'cubierto_por_licencia' => 0],
        ]))
        ->assertSessionHasErrors('tanques');

    $unidad->refresh();

    expect($unidad->cantidad_tanques_con_licencia)->toBe(2)
        ->and($unidad->licencia->plantilla_puntos_seguridad)
        ->toBe('plantilla_2_tanques')
        ->and($unidad->puntosSeguridad()->count())->toBe($cantidadPuntos)
        ->and($punto->refresh()->id)->toBe($puntoId)
        ->and($punto->marchamo_actual_id)->toBe($marchamoId)
        ->and($punto->estado_asignacion)->toBe($estadoAsignacion)
        ->and(Marchamo::findOrFail($marchamoId)->codigo_marchamo)->toBe('7654321');
});
