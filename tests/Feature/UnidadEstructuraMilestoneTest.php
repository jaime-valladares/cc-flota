<?php

use App\Models\Empresa;
use App\Models\Marchamo;
use App\Models\Permiso;
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

test('deriva estructura física sin cobertura contractual', function () {
    $empresa = empresaEstructuraUnidad();

    $this->actingAs(usuarioEstructuraUnidad())
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 300, 'cubierto_por_licencia' => 1],
        ]))
        ->assertSessionHasNoErrors();

    $unidad = Unidad::firstOrFail();

    expect((float) $unidad->capacidad_total)->toBe(300.0)
        ->and((float) $unidad->capacidad_cubierta)->toBe(0.0)
        ->and($unidad->total_tanques)->toBe(1)
        ->and($unidad->cantidad_tanques_con_licencia)->toBe(0)
        ->and($unidad->tanquesUnidad)->toHaveCount(1);
});

test('deriva suma física sin interpretar flags legacy enviados', function () {
    $empresa = empresaEstructuraUnidad();

    $this->actingAs(usuarioEstructuraUnidad())
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 300, 'cubierto_por_licencia' => 1],
            ['capacidad' => 200, 'cubierto_por_licencia' => 0],
        ]))
        ->assertSessionHasNoErrors();

    $unidad = Unidad::firstOrFail();

    expect((float) $unidad->capacidad_total)->toBe(500.0)
        ->and((float) $unidad->capacidad_cubierta)->toBe(0.0)
        ->and($unidad->cantidad_tanques_con_licencia)->toBe(0);
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

test('rechaza capacidad no positiva y no exige cobertura al registrar', function () {
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
        ->assertSessionHasNoErrors();
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

test('unidad activa puede abrir edicion y guardar cambios estructurales permitidos', function () {
    $empresa = empresaEstructuraUnidad();
    $usuario = usuarioEstructuraUnidad();

    $this->actingAs($usuario)
        ->post(route('unidades.store'), datosUnidadEstructural($empresa, [
            ['capacidad' => 100, 'cubierto_por_licencia' => 1],
        ]));

    $unidad = Unidad::firstOrFail();
    $unidad->update(['estado' => 'activa']);

    $this->actingAs($usuario)
        ->get(route('unidades.edit', $unidad))
        ->assertOk();

    $this->actingAs($usuario)
        ->put(route('unidades.update', $unidad), datosUnidadEstructural(
            $empresa,
            [['capacidad' => 500, 'cubierto_por_licencia' => 1]]
        ))
        ->assertSessionHasNoErrors();

    expect((float) $unidad->refresh()->capacidad_total)->toBe(500.0);
});

test('unidad inactiva bloquea formulario y update directo', function () {
    $empresa = empresaEstructuraUnidad();
    $usuario = usuarioEstructuraUnidad();
    $this->actingAs($usuario)->post(route('unidades.store'), datosUnidadEstructural($empresa, [
        ['capacidad' => 100, 'cubierto_por_licencia' => 1],
    ]));
    $unidad = Unidad::firstOrFail();
    $unidad->update(['estado' => 'inactiva']);

    $this->actingAs($usuario)->get(route('unidades.edit', $unidad))->assertForbidden();
    $this->actingAs($usuario)->get(route('unidades.edit.ventana', $unidad))->assertForbidden();
    $this->actingAs($usuario)->put(route('unidades.update', $unidad), datosUnidadEstructural(
        $empresa, [['capacidad' => 500, 'cubierto_por_licencia' => 1]]
    ))->assertForbidden();
    expect((float) $unidad->refresh()->capacidad_total)->toBe(100.0);
});

test('administrar muestra editar para registradas y activas pero no para inactivas', function () {
    $empresa = empresaEstructuraUnidad();
    $usuario = usuarioEstructuraUnidad();
    $registrada = Unidad::create(['empresa_id'=>$empresa->id,'placa'=>'EDIT-REG','marca'=>'Prueba','total_tanques'=>1,'cantidad_tanques_con_licencia'=>0,'capacidad_total'=>100,'capacidad_cubierta'=>0,'modelo_medicion'=>'kilometros_galon','rendimiento_teorico_km_galon'=>8,'estado'=>'registrada']);
    $activa = Unidad::create(['empresa_id'=>$empresa->id,'placa'=>'EDIT-ACT','marca'=>'Prueba','total_tanques'=>1,'cantidad_tanques_con_licencia'=>0,'capacidad_total'=>100,'capacidad_cubierta'=>0,'modelo_medicion'=>'kilometros_galon','rendimiento_teorico_km_galon'=>8,'estado'=>'activa']);
    $inactiva = Unidad::create(['empresa_id'=>$empresa->id,'placa'=>'EDIT-INA','marca'=>'Prueba','total_tanques'=>1,'cantidad_tanques_con_licencia'=>0,'capacidad_total'=>100,'capacidad_cubierta'=>0,'modelo_medicion'=>'kilometros_galon','rendimiento_teorico_km_galon'=>8,'estado'=>'inactiva']);

    $respuesta = $this->actingAs($usuario)->get(route('unidades.administrar',['consultar'=>1]))->assertOk();
    foreach ([$registrada,$activa,$inactiva] as $unidad) $respuesta->assertSee(route('unidades.show',$unidad),false);
    $respuesta->assertSee(route('unidades.edit',$registrada),false)->assertSee(route('unidades.edit',$activa),false)->assertDontSee(route('unidades.edit',$inactiva),false);
});

test('usuario que administra sin permiso editar conserva ficha pero no ve editar', function () {
    $empresa = empresaEstructuraUnidad();
    $rol = Role::create(['codigo'=>User::ROL_DIESEL_TECNICO,'nombre'=>'Técnico sin edición','alcance'=>'diesel_cop','estado'=>'activo']);
    foreach (['unidades.consultar','unidades.administrar'] as $codigo) {
        $permiso = Permiso::create(['codigo'=>$codigo,'modulo'=>'unidades','accion'=>(string) str($codigo)->after('.'),'nombre'=>$codigo,'alcance'=>'global','estado'=>'activo']);
        $rol->permisos()->attach($permiso->id);
    }
    $usuario = User::factory()->create(['rol_id'=>$rol->id,'tipo_usuario'=>User::TIPO_DIESEL_COP,'empresa_id'=>null,'estado'=>'activo']);
    $unidad = Unidad::create(['empresa_id'=>$empresa->id,'placa'=>'SIN-EDITAR','marca'=>'Prueba','total_tanques'=>1,'cantidad_tanques_con_licencia'=>0,'capacidad_total'=>100,'capacidad_cubierta'=>0,'modelo_medicion'=>'kilometros_galon','rendimiento_teorico_km_galon'=>8,'estado'=>'activa']);

    $this->actingAs($usuario)->get(route('unidades.administrar',['consultar'=>1]))
        ->assertOk()->assertSee(route('unidades.show',$unidad),false)->assertDontSee(route('unidades.edit',$unidad),false);
    $this->actingAs($usuario)->get(route('unidades.edit',$unidad))->assertForbidden();
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
            'tanques_cubiertos' => $unidad->tanquesUnidad()->take(2)->pluck('id')->all(),
            'periodo_vigencia_meses' => 12,
            'fecha_activacion' => now()->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    expect($unidad->licencia()->firstOrFail()->plantilla_puntos_seguridad)
        ->toBe('plantilla_2_tanques');
});

test('editar unidad no cambia cobertura contractual ni plantilla', function () {
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
            'tanques_cubiertos' => $unidad->tanquesUnidad()->pluck('id')->all(),
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

    expect($unidad->cantidad_tanques_con_licencia)->toBe(2)
        ->and($unidad->licencia->plantilla_puntos_seguridad)
        ->toBe('plantilla_2_tanques')
        ->and($unidad->puntosSeguridad)->toHaveCount(
            PlantillasPuntosSeguridad::cantidadEsperada('plantilla_2_tanques')
        )
        ->and($unidad->puntosSeguridad->pluck('plantilla_origen')->unique()->all())
        ->toBe(['plantilla_2_tanques']);
});

test('bloquea cambio de cantidad física cuando ya existe licencia', function () {
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
            'tanques_cubiertos' => $unidad->tanquesUnidad()->pluck('id')->all(),
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
        ]))
        ->assertSessionHasErrors('cantidad_tanques');

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
