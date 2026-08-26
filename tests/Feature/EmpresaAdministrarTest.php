<?php

use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;

function usuarioEmpresasAdministrar(?Empresa $empresa = null): User
{
    $rol = Role::query()->firstOrCreate(
        ['codigo' => User::ROL_DIESEL_SUPER_ADMIN],
        [
            'nombre' => 'Superadministrador de empresas',
            'alcance' => User::TIPO_DIESEL_COP,
            'estado' => 'activo',
        ]
    );

    return User::factory()->create([
        'rol_id' => $rol->id,
        'tipo_usuario' => $empresa
            ? User::TIPO_EMPRESA
            : User::TIPO_DIESEL_COP,
        'empresa_id' => $empresa?->id,
        'estado' => 'activo',
    ]);
}

function empresaParaAdministrar(
    string $nombre,
    string $nit,
    string $estado
): Empresa {
    return Empresa::query()->create([
        'nombre_legal' => $nombre,
        'nombre_comercial' => $nombre,
        'nit' => $nit,
        'estado' => $estado,
    ]);
}

test('consulta normal y ventana pueden devolver empresa inactiva', function () {
    $usuario = usuarioEmpresasAdministrar();
    $inactiva = empresaParaAdministrar(
        'Empresa Histórica Inactiva',
        '1000-000001-001-1',
        'inactiva'
    );

    foreach ([
        'empresas.index',
        'empresas.consulta.ventana',
    ] as $ruta) {
        $this->actingAs($usuario)
            ->get(route($ruta, [
                'consultar' => 1,
                'estado' => 'inactiva',
            ]))
            ->assertOk()
            ->assertSee($inactiva->nombre_comercial);
    }
});

test('administrar normal y ventana muestran activa y excluyen inactiva', function () {
    $usuario = usuarioEmpresasAdministrar();
    $activa = empresaParaAdministrar(
        'Empresa Administrable Activa',
        '1000-000002-002-2',
        'activa'
    );
    $inactiva = empresaParaAdministrar(
        'Empresa No Administrable Inactiva',
        '1000-000003-003-3',
        'inactiva'
    );

    foreach ([
        'empresas.administrar',
        'empresas.administrar.ventana',
    ] as $ruta) {
        $this->actingAs($usuario)
            ->get(route($ruta, ['consultar' => 1]))
            ->assertOk()
            ->assertSee($activa->nombre_comercial)
            ->assertDontSee($inactiva->nombre_comercial);
    }
});

test('parametros manipulados no incorporan inactivas en administrar', function () {
    $usuario = usuarioEmpresasAdministrar();
    $inactiva = empresaParaAdministrar(
        'Empresa Inactiva Manipulada',
        '1000-000004-004-4',
        'inactiva'
    );

    foreach ([
        'empresas.administrar',
        'empresas.administrar.ventana',
    ] as $ruta) {
        $this->actingAs($usuario)
            ->get(route($ruta, [
                'consultar' => 1,
                'estado' => 'inactiva',
                'empresa_ids' => [$inactiva->id],
                'empresa_id' => $inactiva->id,
            ]))
            ->assertOk()
            ->assertDontSee($inactiva->nombre_comercial);
    }
});

test('usuario de empresa mantiene aislamiento en administrar', function () {
    $propia = empresaParaAdministrar(
        'Empresa Propia Activa',
        '1000-000005-005-5',
        'activa'
    );
    $ajena = empresaParaAdministrar(
        'Empresa Ajena Activa',
        '1000-000006-006-6',
        'activa'
    );
    $usuario = usuarioEmpresasAdministrar($propia);

    $this->actingAs($usuario)
        ->get(route('empresas.administrar', [
            'consultar' => 1,
            'empresa_ids' => [$ajena->id],
            'empresa_id' => $ajena->id,
        ]))
        ->assertOk()
        ->assertSee($propia->nombre_comercial)
        ->assertDontSee($ajena->nombre_comercial);
});
