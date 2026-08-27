<?php

use App\Models\Empresa;
use App\Models\Licencia;
use App\Models\Role;
use App\Models\Unidad;
use App\Models\User;
use Tests\TestCase;

function usuarioLicenciaTanques(): User
{
    $rol = Role::firstOrCreate(
        ['codigo' => User::ROL_DIESEL_SUPER_ADMIN],
        [
            'nombre' => 'Superadministrador de licencia tanques',
            'alcance' => 'diesel_cop',
            'estado' => 'activo',
        ]
    );

    return User::factory()->create([
        'rol_id' => $rol->id,
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'empresa_id' => null,
    ]);
}

function empresaLicenciaTanques(string $nit): Empresa
{
    return Empresa::create([
        'nombre_legal' => "Empresa {$nit}",
        'nombre_comercial' => $nit,
        'nit' => $nit,
        'estado' => 'activa',
    ]);
}

function crearUnidadFisica(TestCase $test, User $usuario, Empresa $empresa, string $placa): Unidad
{
    $test->actingAs($usuario)->post(route('unidades.store'), [
        'empresa_id' => $empresa->id,
        'placa' => $placa,
        'marca' => 'Etapa 1',
        'cantidad_tanques' => 2,
        'tanques' => [
            ['capacidad' => 125],
            ['capacidad' => 275],
        ],
        'modelo_medicion' => 'kilometros_galon',
        'rendimiento_teorico_km_galon' => 8,
    ])->assertSessionHasNoErrors();

    return Unidad::where('placa', $placa)->firstOrFail();
}

function datosLicenciaTanques(Empresa $empresa, Unidad $unidad, array $tanqueIds, array $extra = []): array
{
    return array_merge([
        'empresa_id' => $empresa->id,
        'unidad_id' => $unidad->id,
        'tanques_cubiertos' => $tanqueIds,
        'periodo_vigencia_meses' => 12,
        'fecha_activacion' => now()->toDateString(),
    ], $extra);
}

test('unidad se crea sin campos ni cobertura contractual y queda elegible', function () {
    $usuario = usuarioLicenciaTanques();
    $empresa = empresaLicenciaTanques('LT-ELEGIBLE');
    $unidad = crearUnidadFisica($this, $usuario, $empresa, 'LT-001');

    expect($unidad->estado)->toBe('registrada')
        ->and($unidad->licencia)->toBeNull()
        ->and($unidad->puntosSeguridad()->exists())->toBeFalse()
        ->and($unidad->marchamos()->exists())->toBeFalse()
        ->and($unidad->cantidad_tanques_con_licencia)->toBe(0)
        ->and((float) $unidad->capacidad_cubierta)->toBe(0.0)
        ->and($unidad->tanquesUnidad->every(
            fn ($tanque): bool => ! $tanque->cubierto_por_licencia
        ))->toBeTrue();

    $this->actingAs($usuario)
        ->get(route('licencias.create', ['empresa_id' => $empresa->id]))
        ->assertOk()
        ->assertSee('LT-001');
});

test('licencia exige cobertura y rechaza tanques de otra unidad o empresa', function () {
    $usuario = usuarioLicenciaTanques();
    $empresaA = empresaLicenciaTanques('LT-EMP-A');
    $empresaB = empresaLicenciaTanques('LT-EMP-B');
    $unidadA = crearUnidadFisica($this, $usuario, $empresaA, 'LT-A');
    $unidadB = crearUnidadFisica($this, $usuario, $empresaB, 'LT-B');

    $this->actingAs($usuario)
        ->post(route('licencias.store'), datosLicenciaTanques($empresaA, $unidadA, []))
        ->assertSessionHasErrors('tanques_cubiertos');

    $this->actingAs($usuario)
        ->post(route('licencias.store'), datosLicenciaTanques(
            $empresaA,
            $unidadA,
            [$unidadB->tanquesUnidad()->firstOrFail()->id]
        ))
        ->assertSessionHasErrors('tanques_cubiertos.0');

    expect(Licencia::count())->toBe(0);
});

test('licencia guarda detalles y snapshots y sincroniza cache legacy para M4', function () {
    $usuario = usuarioLicenciaTanques();
    $empresa = empresaLicenciaTanques('LT-SNAPSHOT');
    $unidad = crearUnidadFisica($this, $usuario, $empresa, 'LT-002');
    $tanque = $unidad->tanquesUnidad->last();

    $this->actingAs($usuario)
        ->post(route('licencias.store'), datosLicenciaTanques(
            $empresa,
            $unidad,
            [$tanque->id]
        ))
        ->assertSessionHasNoErrors();

    $licencia = $unidad->refresh()->licencia;
    $detalle = $licencia->tanquesCubiertos()->firstOrFail();

    expect($detalle->unidad_tanque_id)->toBe($tanque->id)
        ->and($detalle->numero_tanque_snapshot)->toBe($tanque->numero)
        ->and((float) $detalle->capacidad_snapshot)->toBe(275.0)
        ->and($licencia->cantidad_tanques_cubiertos)->toBe(1)
        ->and($licencia->capacidad_cubierta)->toBe(275.0)
        ->and($licencia->plantilla_puntos_seguridad)->toBe('plantilla_1_tanque')
        ->and($unidad->cantidad_tanques_con_licencia)->toBe(1)
        ->and((float) $unidad->capacidad_cubierta)->toBe(275.0)
        ->and($unidad->marchamos()->exists())->toBeFalse();
});

test('licencia futura permanece pendiente y no habilita marchamos', function () {
    $usuario = usuarioLicenciaTanques();
    $empresa = empresaLicenciaTanques('LT-FUTURA');
    $unidad = crearUnidadFisica($this, $usuario, $empresa, 'LT-003');

    $this->actingAs($usuario)
        ->post(route('licencias.store'), datosLicenciaTanques(
            $empresa,
            $unidad,
            $unidad->tanquesUnidad()->pluck('id')->all(),
            ['fecha_activacion' => now()->addDay()->toDateString()]
        ))
        ->assertSessionHasNoErrors();

    expect($unidad->refresh()->estado)->toBe('registrada')
        ->and($unidad->licencia->condicion_vigencia)->toBe('pendiente_activacion');

    $this->actingAs($usuario)
        ->post(route('marchamos.asignacion-inicial.guardar-avance', $unidad), [
            'marchamos' => [],
        ])
        ->assertForbidden();
});

test('mantiene unicidad de licencia por unidad', function () {
    $usuario = usuarioLicenciaTanques();
    $empresa = empresaLicenciaTanques('LT-UNICA');
    $unidad = crearUnidadFisica($this, $usuario, $empresa, 'LT-004');
    $datos = datosLicenciaTanques(
        $empresa,
        $unidad,
        $unidad->tanquesUnidad()->pluck('id')->all()
    );

    $this->actingAs($usuario)->post(route('licencias.store'), $datos)
        ->assertSessionHasNoErrors();
    $this->actingAs($usuario)->post(route('licencias.store'), $datos)
        ->assertSessionHasErrors('unidad_id');

    expect(Licencia::where('unidad_id', $unidad->id)->count())->toBe(1);
});
