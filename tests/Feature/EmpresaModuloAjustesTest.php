<?php

use App\Models\Empresa;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\RolPermisosSeeder;

function prepararEmpresasModulo($test): void
{
    $test->seed([RolesSeeder::class, PermisosSeeder::class, RolPermisosSeeder::class]);
}

function usuarioEmpresasModulo(): User
{
    return User::factory()->create(['rol_id' => Role::where('codigo', User::ROL_DIESEL_ADMIN)->value('id'), 'tipo_usuario' => User::TIPO_DIESEL_COP, 'empresa_id' => null, 'estado' => 'activo']);
}

function empresaModulo(string $codigo, string $estado = 'activa'): Empresa
{
    return Empresa::create(['nombre_legal' => 'Empresa '.$codigo, 'nombre_comercial' => $codigo, 'nit' => '1000-'.str_pad((string) abs(crc32($codigo) % 1000000), 6, '0', STR_PAD_LEFT).'-001-1', 'direccion' => 'Dirección', 'telefono_empresa' => '2200-0000', 'correo_empresa' => strtolower($codigo).'@example.test', 'poc_nombre' => 'Contacto '.$codigo, 'poc_email' => 'poc.'.strtolower($codigo).'@example.test', 'poc_telefono' => '7000-0000', 'estado' => $estado]);
}

function datosUpdateEmpresa(Empresa $empresa): array
{
    return ['nombre_legal' => $empresa->nombre_legal, 'nombre_comercial' => $empresa->nombre_comercial, 'nit' => $empresa->nit, 'direccion' => 'Dirección actualizada', 'telefono_empresa' => '2200-1111', 'correo_empresa' => $empresa->correo_empresa, 'poc_nombre' => $empresa->poc_nombre, 'poc_email' => $empresa->poc_email, 'poc_telefono' => '7000-1111'];
}

test('consulta conserva activas e inactivas filtra estados y muestra estado al final', function () {
    prepararEmpresasModulo($this);
    $user = usuarioEmpresasModulo();
    $activa = empresaModulo('EMP-ACT');
    $inactiva = empresaModulo('EMP-INA', 'inactiva');
    $todas = $this->actingAs($user)->get(route('empresas.index', ['consultar' => 1]))->assertOk()->assertSee($activa->nombre_legal)->assertSee($inactiva->nombre_legal)->assertSee('data-filter-estado', false)->assertSee('Activa')->assertSee('Inactiva');
    expect($todas->getContent())->toMatch('/<th>Estado<\/th>\s*<\/tr>/');
    $this->actingAs($user)->get(route('empresas.index', ['consultar' => 1, 'estado' => 'activa']))->assertSee($activa->nombre_legal)->assertDontSee($inactiva->nombre_legal);
    $this->actingAs($user)->get(route('empresas.index', ['consultar' => 1, 'estado' => 'inactiva']))->assertSee($inactiva->nombre_legal)->assertDontSee($activa->nombre_legal);
    $this->actingAs($user)->get(route('empresas.index', ['consultar' => 1, 'estado' => '']))->assertSee($activa->nombre_legal)->assertSee($inactiva->nombre_legal);
});

test('consulta ubica resumen entre filtros y tabla solo despues de consultar con resultados', function () {
    prepararEmpresasModulo($this);
    $user = usuarioEmpresasModulo();
    empresaModulo('EMP-ORDEN');
    $this->actingAs($user)->get(route('empresas.index'))->assertOk()->assertDontSee('data-empresas-summary', false);
    $this->actingAs($user)->get(route('empresas.index', ['consultar' => 1, 'busqueda_empresa' => 'SIN-RESULTADOS']))->assertOk()->assertDontSee('data-empresas-summary', false);
    foreach (['empresas.index', 'empresas.consulta.ventana'] as $ruta) {
        $r = $this->actingAs($user)->get(route($ruta, ['consultar' => 1]))->assertOk()->assertSee('data-empresas-summary', false);
        $html = $r->getContent();
        expect(strpos($html, '<form'))->toBeLessThan(strpos($html, 'data-empresas-summary'))->and(strpos($html, 'data-empresas-summary'))->toBeLessThan(strpos($html, '<table'));
    }
});

test('administrar conserva inactiva con ficha sin editar y activa editable', function () {
    prepararEmpresasModulo($this);
    $user = usuarioEmpresasModulo();
    $activa = empresaModulo('ADM-ACT');
    $inactiva = empresaModulo('ADM-INA', 'inactiva');
    foreach (['empresas.administrar', 'empresas.administrar.ventana'] as $ruta) {
        $r = $this->actingAs($user)->get(route($ruta, ['consultar' => 1]))->assertOk()->assertSee($activa->nombre_legal)->assertSee($inactiva->nombre_legal);
        $r->assertSee(route($ruta === 'empresas.administrar' ? 'empresas.show' : 'empresas.show.ventana', $inactiva), false)->assertDontSee(route($ruta === 'empresas.administrar' ? 'empresas.edit' : 'empresas.edit.ventana', $inactiva), false)->assertSee(route($ruta === 'empresas.administrar' ? 'empresas.edit' : 'empresas.edit.ventana', $activa), false);
    }
});

test('backend bloquea editar update inactiva permite activa y mantiene reactivacion desde ficha', function () {
    prepararEmpresasModulo($this);
    $user = usuarioEmpresasModulo();
    $activa = empresaModulo('BACK-ACT');
    $inactiva = empresaModulo('BACK-INA', 'inactiva');
    $this->actingAs($user)->get(route('empresas.show', $inactiva))->assertOk();
    $this->actingAs($user)->get(route('empresas.edit', $activa))->assertOk();
    $this->actingAs($user)->get(route('empresas.edit', $inactiva))->assertForbidden();
    $this->actingAs($user)->put(route('empresas.update', $inactiva), datosUpdateEmpresa($inactiva))->assertForbidden();
    $this->actingAs($user)->put(route('empresas.update', $activa), datosUpdateEmpresa($activa))->assertSessionHasNoErrors();
    expect($activa->refresh()->direccion)->toBe('Dirección actualizada');
    $this->actingAs($user)->patch(route('empresas.reactivar',$inactiva))->assertRedirect();
    expect($inactiva->refresh()->estado)->toBe('activa');
});
