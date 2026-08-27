<?php

use App\Models\Empresa;
use App\Models\Permiso;
use App\Models\Role;
use App\Models\Unidad;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\RolPermisosSeeder;

function prepararPermisosFichaUnidad($caso): void
{
    $caso->seed([
        RolesSeeder::class,
        PermisosSeeder::class,
        RolPermisosSeeder::class,
    ]);
}

function empresaReporte(string $sufijo): Empresa
{
    return Empresa::query()->create([
        'nombre_legal' => "Empresa Reporte {$sufijo}",
        'nombre_comercial' => "Reporte {$sufijo}",
        'nit' => "NIT-REPORTE-{$sufijo}",
        'estado' => 'activa',
    ]);
}

function unidadReporte(Empresa $empresa, string $placa, string $estado = 'activa'): Unidad
{
    return Unidad::query()->create([
        'empresa_id' => $empresa->id,
        'placa' => $placa,
        'marca' => 'Marca Reporte',
        'total_tanques' => 1,
        'cantidad_tanques_con_licencia' => 0,
        'capacidad_total' => 100,
        'capacidad_cubierta' => 0,
        'modelo_medicion' => 'kilometros_galon',
        'rendimiento_teorico_km_galon' => 10,
        'estado' => $estado,
    ]);
}

function usuarioReporte(string $rolCodigo, ?Empresa $empresa = null): User
{
    $rol = Role::query()->where('codigo', $rolCodigo)->firstOrFail();

    return User::factory()->create([
        'rol_id' => $rol->id,
        'tipo_usuario' => $empresa
            ? User::TIPO_EMPRESA
            : User::TIPO_DIESEL_COP,
        'empresa_id' => $empresa?->id,
        'estado' => 'activo',
    ]);
}

test('permiso de ficha de unidad existe activo y tiene la matriz exacta de roles', function () {
    prepararPermisosFichaUnidad($this);

    $permiso = Permiso::query()
        ->where('codigo', 'reportes.unidades.ficha')
        ->firstOrFail();

    expect($permiso->estado)->toBe('activo')
        ->and($permiso->modulo)->toBe('reportes')
        ->and($permiso->roles()->orderBy('codigo')->pluck('codigo')->all())
        ->toBe([
            User::ROL_DIESEL_ADMIN,
            User::ROL_DIESEL_AUDITOR,
            User::ROL_DIESEL_SUPER_ADMIN,
            User::ROL_EMPRESA_ADMIN,
            User::ROL_EMPRESA_AUDITOR,
            User::ROL_EMPRESA_SUPERVISOR,
        ]);
});

test('roles definidos acceden y tecnico y operador reciben 403', function () {
    prepararPermisosFichaUnidad($this);
    $empresa = empresaReporte('ROLES');

    foreach ([
        User::ROL_DIESEL_SUPER_ADMIN,
        User::ROL_DIESEL_ADMIN,
        User::ROL_DIESEL_AUDITOR,
    ] as $rol) {
        $this->actingAs(usuarioReporte($rol))
            ->get(route('reportes.unidades.index'))
            ->assertOk();
    }

    foreach ([
        User::ROL_EMPRESA_ADMIN,
        User::ROL_EMPRESA_SUPERVISOR,
        User::ROL_EMPRESA_AUDITOR,
    ] as $rol) {
        $this->actingAs(usuarioReporte($rol, $empresa))
            ->get(route('reportes.unidades.index'))
            ->assertOk();
    }

    $this->actingAs(usuarioReporte(User::ROL_DIESEL_TECNICO))
        ->get(route('reportes.unidades.index'))
        ->assertForbidden();

    $this->actingAs(usuarioReporte(User::ROL_EMPRESA_OPERADOR, $empresa))
        ->get(route('reportes.unidades.index'))
        ->assertForbidden();
});

test('diesel cop puede seleccionar empresas distintas y ver solamente sus unidades', function () {
    prepararPermisosFichaUnidad($this);
    $empresaA = empresaReporte('A');
    $empresaB = empresaReporte('B');
    unidadReporte($empresaA, 'RPT-A-001');
    unidadReporte($empresaB, 'RPT-B-001');
    $usuario = usuarioReporte(User::ROL_DIESEL_ADMIN);

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.index', ['empresa_id' => $empresaA->id]))
        ->assertOk()
        ->assertSee('Reporte A')
        ->assertSee('Reporte B')
        ->assertSee('RPT-A-001')
        ->assertDontSee('RPT-B-001');

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.index', ['empresa_id' => $empresaB->id]))
        ->assertOk()
        ->assertSee('RPT-B-001')
        ->assertDontSee('RPT-A-001');
});

test('usuario empresa ve empresa bloqueada y solamente sus unidades', function () {
    prepararPermisosFichaUnidad($this);
    $empresaPropia = empresaReporte('PROPIA');
    $empresaAjena = empresaReporte('AJENA');
    unidadReporte($empresaPropia, 'RPT-PROPIA');
    unidadReporte($empresaAjena, 'RPT-AJENA');

    $this->actingAs(usuarioReporte(User::ROL_EMPRESA_ADMIN, $empresaPropia))
        ->get(route('reportes.unidades.index'))
        ->assertOk()
        ->assertSee('Reporte PROPIA')
        ->assertSee('disabled', false)
        ->assertSee('RPT-PROPIA')
        ->assertDontSee('Reporte AJENA')
        ->assertDontSee('RPT-AJENA');
});

test('usuario empresa consulta su propia unidad mediante el boton', function () {
    prepararPermisosFichaUnidad($this);
    $empresa = empresaReporte('CONSULTA-PROPIA');
    $unidad = unidadReporte($empresa, 'RPT-CONSULTA-PROPIA');
    $usuario = usuarioReporte(User::ROL_EMPRESA_ADMIN, $empresa);

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.index', [
            'empresa_id' => $empresa->id,
            'unidad_id' => $unidad->id,
            'consultar' => 1,
        ]))
        ->assertRedirect(route('reportes.unidades.show', $unidad));

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.show', $unidad))
        ->assertOk()
        ->assertSee('RPT-CONSULTA-PROPIA');
});

test('acceso directo de usuario empresa a unidad de otro tenant devuelve 403', function () {
    prepararPermisosFichaUnidad($this);
    $empresaPropia = empresaReporte('TENANT-PROPIO');
    $empresaAjena = empresaReporte('TENANT-AJENO');
    $unidadAjena = unidadReporte($empresaAjena, 'RPT-CROSS');

    $usuario = usuarioReporte(User::ROL_EMPRESA_AUDITOR, $empresaPropia);

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.show', $unidadAjena))
        ->assertForbidden();

    $this->actingAs($usuario)
        ->get(route('reportes.unidades.index', [
            'empresa_id' => $empresaPropia->id,
            'unidad_id' => $unidadAjena->id,
            'consultar' => 1,
        ]))
        ->assertForbidden();
});

test('diesel cop puede abrir unidades pertenecientes a empresas distintas', function () {
    prepararPermisosFichaUnidad($this);
    $empresaA = empresaReporte('APERTURA-A');
    $empresaB = empresaReporte('APERTURA-B');
    $unidadA = unidadReporte($empresaA, 'RPT-ABRIR-A');
    $unidadB = unidadReporte($empresaB, 'RPT-ABRIR-B');
    $usuario = usuarioReporte(User::ROL_DIESEL_AUDITOR);

    foreach ([$unidadA, $unidadB] as $unidad) {
        $this->actingAs($usuario)
            ->get(route('reportes.unidades.index', [
                'empresa_id' => $unidad->empresa_id,
                'unidad_id' => $unidad->id,
                'consultar' => 1,
            ]))
            ->assertRedirect(route('reportes.unidades.show', $unidad));

        $this->actingAs($usuario)
            ->get(route('reportes.unidades.show', $unidad))
            ->assertOk();
    }
});

test('selector de reportes incluye unidades registradas activas e inactivas', function () {
    prepararPermisosFichaUnidad($this);
    $empresa = empresaReporte('ESTADOS');
    unidadReporte($empresa, 'RPT-REGISTRADA', 'registrada');
    unidadReporte($empresa, 'RPT-ACTIVA', 'activa');
    unidadReporte($empresa, 'RPT-INACTIVA', 'inactiva');

    $this->actingAs(usuarioReporte(User::ROL_EMPRESA_SUPERVISOR, $empresa))
        ->get(route('reportes.unidades.index'))
        ->assertOk()
        ->assertSee('RPT-REGISTRADA')
        ->assertSee('RPT-ACTIVA')
        ->assertSee('RPT-INACTIVA');
});

test('selector usa ids y boton consultar sin navegacion automatica por unidad', function () {
    prepararPermisosFichaUnidad($this);
    $empresa = empresaReporte('SELECTOR');
    $unidad = unidadReporte($empresa, 'RPT-SELECTOR');

    $this->actingAs(usuarioReporte(User::ROL_EMPRESA_SUPERVISOR, $empresa))
        ->get(route('reportes.unidades.index'))
        ->assertOk()
        ->assertSee('name="unidad_id"', false)
        ->assertSee('value="'.$unidad->id.'"', false)
        ->assertSee('Consultar')
        ->assertDontSee('window.location.assign', false)
        ->assertDontSee(
            'value="'.route('reportes.unidades.show', $unidad).'"',
            false
        );
});
