<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function rolSuperadminCuentaMaestra(): Role
{
    return Role::query()->create([
        'codigo' => User::ROL_DIESEL_SUPER_ADMIN,
        'nombre' => 'Superadministrador',
        'alcance' => User::TIPO_DIESEL_COP,
        'estado' => 'activo',
    ]);
}

function crearSuperadminFuncional(Role $rol): User
{
    return User::factory()->create([
        'id' => 1,
        'rol_id' => $rol->id,
        'name' => 'Administrador',
        'email' => 'admin@cc-flota.local',
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'empresa_id' => null,
        'estado' => 'activo',
        'es_cuenta_recuperacion' => false,
    ]);
}

function crearCuentaMaestra(Role $rol): User
{
    return User::factory()->create([
        'id' => 9,
        'rol_id' => $rol->id,
        'name' => 'Jaime',
        'email' => 'jaime.ricardo.valladares@gmail.com',
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'empresa_id' => null,
        'estado' => 'activo',
        'es_cuenta_recuperacion' => true,
    ]);
}

test('cuenta maestra no aparece en consulta ni administracion de usuarios', function () {
    $rol = rolSuperadminCuentaMaestra();
    $administrador = crearSuperadminFuncional($rol);
    $maestra = crearCuentaMaestra($rol);

    $this->actingAs($administrador)
        ->get(route('usuarios.index', ['consultar' => 1]))
        ->assertOk()
        ->assertDontSee($maestra->email)
        ->assertSee($administrador->email);

    $this->actingAs($administrador)
        ->get(route('usuarios.administrar', ['consultar' => 1]))
        ->assertOk()
        ->assertDontSee($maestra->email)
        ->assertSee($administrador->email);
});

test('accesos administrativos directos a cuenta maestra responden 404', function () {
    $rol = rolSuperadminCuentaMaestra();
    $administrador = crearSuperadminFuncional($rol);
    $maestra = crearCuentaMaestra($rol);

    $this->actingAs($administrador)
        ->get(route('usuarios.show', $maestra))
        ->assertNotFound();

    $this->actingAs($administrador)
        ->get(route('usuarios.edit', $maestra))
        ->assertNotFound();
});

test('update administrativo de cuenta maestra devuelve 404 y no muta', function () {
    $rol = rolSuperadminCuentaMaestra();
    $administrador = crearSuperadminFuncional($rol);
    $maestra = crearCuentaMaestra($rol);

    $this->actingAs($administrador)
        ->put(route('usuarios.update', $maestra), [
            'name' => 'Nombre alterado',
            'email' => 'alterado@example.com',
        ])
        ->assertNotFound();

    expect($maestra->refresh()->name)->toBe('Jaime')
        ->and($maestra->email)->toBe('jaime.ricardo.valladares@gmail.com');
});

test('cuenta maestra no puede inactivarse ni reactivarse por endpoints ordinarios', function () {
    $rol = rolSuperadminCuentaMaestra();
    $administrador = crearSuperadminFuncional($rol);
    $maestra = crearCuentaMaestra($rol);

    $this->actingAs($administrador)
        ->patch(route('usuarios.inactivar', $maestra), [
            'motivo_inactivacion' => 'Solicitud administrativa',
        ])
        ->assertNotFound();

    $this->actingAs($administrador)
        ->patch(route('usuarios.reactivar', $maestra))
        ->assertNotFound();

    expect($maestra->refresh()->estado)->toBe('activo');
});

test('perfil muestra identidad bloqueada y backend rechaza cambios de nombre o email', function () {
    $rol = rolSuperadminCuentaMaestra();
    $maestra = crearCuentaMaestra($rol);

    $this->actingAs($maestra)
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('La identidad de la Cuenta Maestra es inmutable.')
        ->assertSee('disabled', false)
        ->assertSee(route('password.update'), false);

    $this->actingAs($maestra)
        ->patch(route('profile.update'), [
            'name' => 'Nombre alterado',
            'email' => 'alterado@example.com',
        ])
        ->assertForbidden();

    expect($maestra->refresh()->name)->toBe('Jaime')
        ->and($maestra->email)->toBe('jaime.ricardo.valladares@gmail.com');
});

test('cuenta maestra puede cambiar su propia contraseña con validaciones normales', function () {
    $rol = rolSuperadminCuentaMaestra();
    $maestra = crearCuentaMaestra($rol);

    $this->actingAs($maestra)
        ->from(route('profile.edit'))
        ->put(route('password.update'), [
            'current_password' => 'password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect(Hash::check(
        'new-secure-password',
        $maestra->refresh()->password
    ))->toBeTrue();
});

test('cuenta maestra autentica y conserva bypass global', function () {
    $rol = rolSuperadminCuentaMaestra();
    $maestra = crearCuentaMaestra($rol);

    $this->post('/login', [
        'email' => $maestra->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($maestra);

    $this->actingAs($maestra)
        ->get(route('usuarios.create'))
        ->assertOk();

    expect($maestra->cumpleInvariantesCuentaMaestra())->toBeTrue()
        ->and($maestra->empresa_id)->toBeNull()
        ->and($maestra->estado)->toBe('activo');
});

test('modelo impide romper invariantes o eliminar cuenta maestra', function () {
    $rol = rolSuperadminCuentaMaestra();
    $otroRol = Role::query()->create([
        'codigo' => User::ROL_DIESEL_ADMIN,
        'nombre' => 'Administrador Diesel Cop',
        'alcance' => User::TIPO_DIESEL_COP,
        'estado' => 'activo',
    ]);
    $maestra = crearCuentaMaestra($rol);

    foreach ([
        ['rol_id' => $otroRol->id],
        ['empresa_id' => 123],
        ['tipo_usuario' => User::TIPO_EMPRESA],
        ['estado' => 'inactivo'],
        ['es_cuenta_recuperacion' => false],
    ] as $cambio) {
        expect(fn () => $maestra->update($cambio))
            ->toThrow(LogicException::class);

        $maestra->refresh();
    }

    expect(fn () => $maestra->delete())
        ->toThrow(LogicException::class);

    $this->assertDatabaseHas('users', [
        'id' => 9,
        'rol_id' => $rol->id,
        'empresa_id' => null,
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'estado' => 'activo',
        'es_cuenta_recuperacion' => true,
    ]);
});

test('superadministrador funcional conserva comportamiento ordinario', function () {
    $rol = rolSuperadminCuentaMaestra();
    $administrador = crearSuperadminFuncional($rol);
    crearCuentaMaestra($rol);

    expect($administrador->esCuentaMaestra())->toBeFalse();

    $this->actingAs($administrador)
        ->get(route('usuarios.show', $administrador))
        ->assertOk();

    $this->actingAs($administrador)
        ->patch(route('profile.update'), [
            'name' => 'Administrador funcional',
            'email' => $administrador->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('profile.edit'));

    expect($administrador->refresh()->name)
        ->toBe('Administrador funcional');
});
