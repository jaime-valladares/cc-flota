<?php

use App\Models\Role;
use App\Models\User;
use Database\Seeders\InitialAdminUserSeeder;
use Database\Seeders\PermisosSeeder;
use Database\Seeders\RecoveryAdminUserSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\RolPermisosSeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed([RolesSeeder::class, PermisosSeeder::class, RolPermisosSeeder::class]);
});

function passwordPolicyAdmin(): User
{
    return User::factory()->create([
        'rol_id' => Role::query()->where('codigo', User::ROL_DIESEL_SUPER_ADMIN)->value('id'),
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'empresa_id' => null,
        'estado' => 'activo',
    ]);
}

function passwordPolicyUserPayload(array $overrides = []): array
{
    return array_replace([
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'empresa_id' => null,
        'rol_id' => Role::query()->where('codigo', User::ROL_DIESEL_ADMIN)->value('id'),
        'name' => 'Usuario Política',
        'apellido' => 'Contraseña',
        'email' => 'password-policy@example.test',
        'telefono' => null,
        'cargo' => 'Pruebas',
    ], $overrides);
}

test('administrative creation rejects eleven character password', function () {
    $admin = passwordPolicyAdmin();

    $this->actingAs($admin)->post(route('usuarios.store'), passwordPolicyUserPayload([
        'password' => '12345678901',
        'password_confirmation' => '12345678901',
    ]))->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', ['email' => 'password-policy@example.test']);
});

test('administrative creation accepts a twelve character passphrase', function () {
    $admin = passwordPolicyAdmin();
    $passphrase = 'doce letras ok';

    $this->actingAs($admin)->post(route('usuarios.store'), passwordPolicyUserPayload([
        'password' => $passphrase,
        'password_confirmation' => $passphrase,
    ]))->assertSessionHasNoErrors();

    $user = User::query()->where('email', 'password-policy@example.test')->firstOrFail();
    expect(Hash::check($passphrase, $user->password))->toBeTrue();
});

test('administrative creation requires matching confirmation', function () {
    $admin = passwordPolicyAdmin();

    $this->actingAs($admin)->post(route('usuarios.store'), passwordPolicyUserPayload([
        'password' => 'doce letras ok',
        'password_confirmation' => 'confirmacion distinta',
    ]))->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', ['email' => 'password-policy@example.test']);
});

test('administrative edit with empty password preserves existing hash', function () {
    $admin = passwordPolicyAdmin();
    $target = User::factory()->create(passwordPolicyUserPayload(['email' => 'edit-empty@example.test']));
    $hash = $target->password;

    $this->actingAs($admin)->put(route('usuarios.update', $target), passwordPolicyUserPayload([
        'email' => $target->email,
        'password' => '',
        'password_confirmation' => '',
    ]))->assertSessionHasNoErrors();

    expect($target->refresh()->password)->toBe($hash);
});

test('administrative edit rejects eleven characters without changing hash', function () {
    $admin = passwordPolicyAdmin();
    $target = User::factory()->create(passwordPolicyUserPayload(['email' => 'edit-short@example.test']));
    $hash = $target->password;

    $this->actingAs($admin)->put(route('usuarios.update', $target), passwordPolicyUserPayload([
        'email' => $target->email,
        'password' => '12345678901',
        'password_confirmation' => '12345678901',
    ]))->assertSessionHasErrors('password');

    expect($target->refresh()->password)->toBe($hash);
});

test('administrative edit accepts and hashes a twelve character passphrase', function () {
    $admin = passwordPolicyAdmin();
    $target = User::factory()->create(passwordPolicyUserPayload(['email' => 'edit-valid@example.test']));
    $passphrase = 'frase larga segura';

    $this->actingAs($admin)->put(route('usuarios.update', $target), passwordPolicyUserPayload([
        'email' => $target->email,
        'password' => $passphrase,
        'password_confirmation' => $passphrase,
    ]))->assertSessionHasNoErrors();

    expect(Hash::check($passphrase, $target->refresh()->password))->toBeTrue();
});

test('initial administrator seeder rejects a short password for a new account', function () {
    config()->set('cc-flota.initial_admin.email', 'new-initial@example.test');
    config()->set('cc-flota.initial_admin.password', '12345678901');

    expect(fn () => $this->seed(InitialAdminUserSeeder::class))
        ->toThrow(RuntimeException::class, 'CC_FLOTA_INITIAL_ADMIN_PASSWORD debe tener al menos 12 caracteres.');

    $this->assertDatabaseMissing('users', ['email' => 'new-initial@example.test']);
});

test('recovery administrator seeder rejects a short password for a new master account', function () {
    config()->set('cc-flota.recovery_admin.email', 'new-recovery@example.test');
    config()->set('cc-flota.recovery_admin.password', '12345678901');

    expect(fn () => $this->seed(RecoveryAdminUserSeeder::class))
        ->toThrow(RuntimeException::class, 'CC_FLOTA_RECOVERY_ADMIN_PASSWORD debe tener al menos 12 caracteres.');

    $this->assertDatabaseMissing('users', ['email' => 'new-recovery@example.test']);
});

test('initial administrator seeder never replaces an existing historical password', function () {
    $email = 'existing-initial@example.test';
    $user = User::factory()->create(['email' => $email, 'password' => Hash::make('12345678')]);
    $hash = $user->password;
    config()->set('cc-flota.initial_admin.email', $email);
    config()->set('cc-flota.initial_admin.password', 'short');

    $this->seed(InitialAdminUserSeeder::class);

    expect($user->refresh()->password)->toBe($hash);
});

test('recovery seeder never replaces an existing master password', function () {
    $email = 'existing-recovery@example.test';
    $user = User::factory()->create([
        'email' => $email,
        'password' => Hash::make('12345678'),
        'rol_id' => Role::query()->where('codigo', User::ROL_DIESEL_SUPER_ADMIN)->value('id'),
        'tipo_usuario' => User::TIPO_DIESEL_COP,
        'empresa_id' => null,
        'estado' => 'activo',
        'es_cuenta_recuperacion' => true,
    ]);
    $hash = $user->password;
    config()->set('cc-flota.recovery_admin.email', $email);
    config()->set('cc-flota.recovery_admin.password', 'short');

    $this->seed(RecoveryAdminUserSeeder::class);

    expect($user->refresh()->password)->toBe($hash);
});
