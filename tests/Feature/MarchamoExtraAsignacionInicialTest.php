<?php

use App\Models\Empresa;
use App\Models\Marchamo;
use App\Models\PuntoSeguridadUnidad;
use App\Models\Role;
use App\Models\Unidad;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase;

function prepararUnidadParaExtras(TestCase $test, ?array $tanques = null): array
{
    $rol = Role::create([
        'codigo' => User::ROL_DIESEL_SUPER_ADMIN,
        'nombre' => 'Superadministrador extras',
        'alcance' => 'diesel_cop',
        'estado' => 'activo',
    ]);

    $usuario = User::factory()->create([
        'rol_id' => $rol->id,
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'empresa_id' => null,
    ]);

    $empresa = Empresa::create([
        'nombre_legal' => 'Empresa extras',
        'nombre_comercial' => 'Extras',
        'nit' => 'TEST-EXTRAS',
        'estado' => 'activa',
    ]);

    $tanques ??= [
        ['capacidad' => 300, 'cubierto_por_licencia' => 1],
    ];

    $test->actingAs($usuario)->post(route('unidades.store'), [
        'empresa_id' => $empresa->id,
        'placa' => 'UNIDAD-EXTRAS',
        'marca' => 'Prueba',
        'cantidad_tanques' => count($tanques),
        'tanques' => $tanques,
        'modelo_medicion' => 'kilometros_galon',
        'rendimiento_teorico_km_galon' => 8.5,
    ])->assertSessionHasNoErrors();

    $unidad = Unidad::firstOrFail();

    $test->actingAs($usuario)->post(route('licencias.store'), [
        'empresa_id' => $empresa->id,
        'unidad_id' => $unidad->id,
        'tanques_cubiertos' => $unidad->tanquesUnidad()->pluck('id')->all(),
        'periodo_vigencia_meses' => 12,
        'fecha_activacion' => now()->toDateString(),
    ])->assertSessionHasNoErrors();

    return [$usuario, $unidad->refresh(), $empresa];
}

function asignarTodosLosMarchamos(TestCase $test, User $usuario, Unidad $unidad): void
{
    $marchamos = $unidad->puntosSeguridad()
        ->orderBy('id')
        ->pluck('id')
        ->mapWithKeys(
            fn (int $id, int $indice): array => [
                $id => sprintf('%07d', 1000000 + $indice),
            ]
        )
        ->all();

    $test->actingAs($usuario)
        ->post(route('marchamos.asignacion-inicial.guardar-avance', $unidad), [
            'marchamos' => $marchamos,
        ])
        ->assertSessionHasNoErrors();
}

test('unidad con cero extras finaliza normalmente', function () {
    [$usuario, $unidad] = prepararUnidadParaExtras($this);
    asignarTodosLosMarchamos($this, $usuario, $unidad);

    $this->actingAs($usuario)
        ->post(route('marchamos.asignacion-inicial.finalizar', $unidad))
        ->assertSessionHasNoErrors();

    expect($unidad->refresh()->estado)->toBe('activa');
});

test('agrega uno y varios extras con codigos y orden automaticos', function () {
    [$usuario, $unidad] = prepararUnidadParaExtras($this);
    $ordenEstandar = (int) $unidad->puntosSeguridad()->max('orden');

    foreach (['Bomba auxiliar', 'Retorno auxiliar', 'Filtro auxiliar'] as $nombre) {
        $this->actingAs($usuario)
            ->post(route('marchamos.asignacion-inicial.extras.store', $unidad), [
                'nombre_punto' => $nombre,
            ])
            ->assertSessionHasNoErrors();
    }

    $extras = PuntoSeguridadUnidad::query()
        ->where('unidad_id', $unidad->id)
        ->where('plantilla_origen', 'extra')
        ->orderBy('orden')
        ->get();

    expect($extras)->toHaveCount(3)
        ->and($extras->pluck('codigo_punto')->all())
        ->toBe(['EXT-01', 'EXT-02', 'EXT-03'])
        ->and($extras->pluck('orden')->all())
        ->toBe([$ordenEstandar + 1, $ordenEstandar + 2, $ordenEstandar + 3])
        ->and($extras->every(fn ($punto) => $punto->posicion_tanque === 'Extra'
            && $punto->tipo_punto === 'extra'
            && $punto->requiere_marchamo
        ))->toBeTrue();
});

test('reutiliza el primer codigo extra disponible sin colisiones', function () {
    [$usuario, $unidad] = prepararUnidadParaExtras($this);

    foreach (range(1, 3) as $numero) {
        $this->actingAs($usuario)->post(
            route('marchamos.asignacion-inicial.extras.store', $unidad),
            ['nombre_punto' => "Extra {$numero}"]
        );
    }

    $extraDos = PuntoSeguridadUnidad::where('unidad_id', $unidad->id)
        ->where('codigo_punto', 'EXT-02')
        ->firstOrFail();

    $this->actingAs($usuario)->delete(
        route('marchamos.asignacion-inicial.extras.destroy', [$unidad, $extraDos])
    );

    $this->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.extras.store', $unidad),
        ['nombre_punto' => 'Extra reutilizado']
    );

    expect(PuntoSeguridadUnidad::where('unidad_id', $unidad->id)
        ->where('codigo_punto', 'EXT-02')->count())->toBe(1);
});

test('limita extras a diez y rechaza el undecimo', function () {
    [$usuario, $unidad] = prepararUnidadParaExtras($this);

    foreach (range(1, 10) as $numero) {
        $this->actingAs($usuario)
            ->post(route('marchamos.asignacion-inicial.extras.store', $unidad), [
                'nombre_punto' => "Extra {$numero}",
            ])
            ->assertSessionHasNoErrors();
    }

    $this->actingAs($usuario)
        ->post(route('marchamos.asignacion-inicial.extras.store', $unidad), [
            'nombre_punto' => 'Extra 11',
        ])
        ->assertSessionHasErrors('nombre_punto');

    expect(PuntoSeguridadUnidad::where('unidad_id', $unidad->id)
        ->where('plantilla_origen', 'extra')->count())->toBe(10);
});

test('permite renombrar y eliminar extra antes de finalizar', function () {
    [$usuario, $unidad] = prepararUnidadParaExtras($this);

    $this->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.extras.store', $unidad),
        ['nombre_punto' => 'Nombre inicial']
    );

    $extra = PuntoSeguridadUnidad::where('unidad_id', $unidad->id)
        ->where('plantilla_origen', 'extra')->firstOrFail();

    $this->actingAs($usuario)
        ->patch(route('marchamos.asignacion-inicial.extras.update', [$unidad, $extra]), [
            'nombre_punto' => 'Nombre corregido',
        ])
        ->assertSessionHasNoErrors();

    expect($extra->refresh()->nombre_punto)->toBe('Nombre corregido');

    $this->actingAs($usuario)
        ->delete(route('marchamos.asignacion-inicial.extras.destroy', [$unidad, $extra]))
        ->assertSessionHasNoErrors();

    expect(PuntoSeguridadUnidad::find($extra->id))->toBeNull();
});

test('eliminar extra con marchamo provisional no deja marchamo huerfano', function () {
    [$usuario, $unidad] = prepararUnidadParaExtras($this);

    $this->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.extras.store', $unidad),
        ['nombre_punto' => 'Extra provisional']
    );

    $extra = PuntoSeguridadUnidad::where('unidad_id', $unidad->id)
        ->where('plantilla_origen', 'extra')->firstOrFail();

    $this->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.guardar-avance', $unidad),
        ['marchamos' => [$extra->id => '7654321']]
    );

    $marchamoId = $extra->refresh()->marchamo_actual_id;
    expect($marchamoId)->not->toBeNull();

    $this->actingAs($usuario)
        ->delete(route('marchamos.asignacion-inicial.extras.destroy', [$unidad, $extra]))
        ->assertSessionHasNoErrors();

    expect(PuntoSeguridadUnidad::find($extra->id))->toBeNull()
        ->and(Marchamo::find($marchamoId))->toBeNull();
});

test('guardar avance conserva extra y no permite finalizarlo sin marchamo', function () {
    [$usuario, $unidad] = prepararUnidadParaExtras($this);

    $this->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.extras.store', $unidad),
        ['nombre_punto' => 'Extra pendiente']
    );

    $extra = PuntoSeguridadUnidad::where('unidad_id', $unidad->id)
        ->where('plantilla_origen', 'extra')->firstOrFail();

    $estandar = $unidad->puntosSeguridad()
        ->where('plantilla_origen', '!=', 'extra')
        ->pluck('id')
        ->mapWithKeys(fn (int $id, int $indice): array => [
            $id => sprintf('%07d', 2000000 + $indice),
        ])->all();

    $this->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.guardar-avance', $unidad),
        ['marchamos' => $estandar]
    );

    expect($extra->refresh()->exists)->toBeTrue()
        ->and($extra->marchamo_actual_id)->toBeNull();

    $this->actingAs($usuario)
        ->post(route('marchamos.asignacion-inicial.finalizar', $unidad))
        ->assertSessionHasErrors('finalizar');

    expect($unidad->refresh()->estado)->toBe('registrada');
});

test('finaliza con extra cubierto y luego bloquea agregar renombrar y eliminar', function () {
    [$usuario, $unidad] = prepararUnidadParaExtras($this);

    $this->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.extras.store', $unidad),
        ['nombre_punto' => 'Extra consolidado']
    );

    $extra = PuntoSeguridadUnidad::where('unidad_id', $unidad->id)
        ->where('plantilla_origen', 'extra')->firstOrFail();

    asignarTodosLosMarchamos($this, $usuario, $unidad);

    $this->actingAs($usuario)
        ->post(route('marchamos.asignacion-inicial.finalizar', $unidad))
        ->assertSessionHasNoErrors();

    expect($unidad->refresh()->estado)->toBe('activa')
        ->and($extra->refresh()->marchamo_actual_id)->not->toBeNull();

    $this->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.extras.store', $unidad),
        ['nombre_punto' => 'No permitido']
    )->assertStatus(422);

    $this->actingAs($usuario)->patch(
        route('marchamos.asignacion-inicial.extras.update', [$unidad, $extra]),
        ['nombre_punto' => 'No renombrar']
    )->assertStatus(422);

    $this->actingAs($usuario)->delete(
        route('marchamos.asignacion-inicial.extras.destroy', [$unidad, $extra])
    )->assertStatus(422);

    expect($extra->refresh()->nombre_punto)->toBe('Extra consolidado')
        ->and(Marchamo::where('punto_seguridad_id', $extra->id)->exists())->toBeTrue();

    $marchamoAnteriorId = $extra->marchamo_actual_id;

    $this->actingAs($usuario)
        ->post(route('marchamos.reemplazos.store', $unidad), [
            'reemplazos' => [[
                'seleccionado' => 1,
                'punto_seguridad_id' => $extra->id,
                'nuevo_codigo_marchamo' => '9999999',
                'motivo_reemplazo' => 'desgaste',
            ]],
        ])
        ->assertSessionHasNoErrors();

    expect($extra->refresh()->id)->toBe($extra->id)
        ->and($extra->marchamoActual->codigo_marchamo)->toBe('9999999')
        ->and(Marchamo::findOrFail($marchamoAnteriorId)->estado)->toBe('reemplazado');
});

test('edición física conserva contrato y marchamo provisional extra intactos', function () {
    [$usuario, $unidad, $empresa] = prepararUnidadParaExtras($this, [
        ['capacidad' => 100, 'cubierto_por_licencia' => 1],
        ['capacidad' => 200, 'cubierto_por_licencia' => 1],
    ]);

    $this->actingAs($usuario)->post(
        route('marchamos.asignacion-inicial.extras.store', $unidad),
        ['nombre_punto' => 'Extra preservado']
    );

    $extra = PuntoSeguridadUnidad::where('unidad_id', $unidad->id)
        ->where('plantilla_origen', 'extra')->firstOrFail();

    $this->actingAs($usuario)
        ->post(route('marchamos.asignacion-inicial.guardar-avance', $unidad), [
            'marchamos' => [$extra->id => '8765432'],
        ])
        ->assertSessionHasNoErrors();

    $extra->refresh();
    $extraId = $extra->id;
    $marchamoId = $extra->marchamo_actual_id;
    $estadoAsignacion = $extra->estado_asignacion;

    $this->actingAs($usuario)
        ->put(route('unidades.update', $unidad), [
            'empresa_id' => $empresa->id,
            'placa' => $unidad->placa,
            'marca' => $unidad->marca,
            'cantidad_tanques' => 2,
            'tanques' => [
                ['capacidad' => 100, 'cubierto_por_licencia' => 1],
                ['capacidad' => 200, 'cubierto_por_licencia' => 0],
            ],
            'modelo_medicion' => 'kilometros_galon',
            'rendimiento_teorico_km_galon' => 8.5,
        ])
        ->assertSessionHasNoErrors();

    $extra->refresh();
    $ultimoEstandar = (int) PuntoSeguridadUnidad::where('unidad_id', $unidad->id)
        ->where('plantilla_origen', '!=', 'extra')->max('orden');

    expect($extra->id)->toBe($extraId)
        ->and($extra->nombre_punto)->toBe('Extra preservado')
        ->and($extra->marchamo_actual_id)->toBe($marchamoId)
        ->and($extra->estado_asignacion)->toBe($estadoAsignacion)
        ->and($extra->marchamoActual->codigo_marchamo)->toBe('8765432')
        ->and($extra->orden)->toBe($ultimoEstandar + 1)
        ->and($unidad->refresh()->licencia->plantilla_puntos_seguridad)
        ->toBe('plantilla_2_tanques');
});
