<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('password can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
});

test('correct password must be provided to update password', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('updatePassword', 'current_password')
        ->assertRedirect('/profile');
});

test('new password must contain at least twelve characters', function () {
    $user = User::factory()->create();
    $originalHash = $user->password;

    $this->actingAs($user)->from('/profile')->put('/password', [
        'current_password' => 'password',
        'password' => '12345678901',
        'password_confirmation' => '12345678901',
    ])->assertSessionHasErrorsIn('updatePassword', 'password');

    expect($user->refresh()->password)->toBe($originalHash);
});

test('twelve character passphrase can replace the current password', function () {
    $user = User::factory()->create();
    $passphrase = 'doce letras ok';

    $this->actingAs($user)->from('/profile')->put('/password', [
        'current_password' => 'password',
        'password' => $passphrase,
        'password_confirmation' => $passphrase,
    ])->assertSessionHasNoErrors()->assertRedirect('/profile');

    expect(Hash::check($passphrase, $user->refresh()->password))->toBeTrue();
});

test('current password remains required', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->from('/profile')->put('/password', [
        'password' => 'doce letras ok',
        'password_confirmation' => 'doce letras ok',
    ])->assertSessionHasErrorsIn('updatePassword', 'current_password');
});
