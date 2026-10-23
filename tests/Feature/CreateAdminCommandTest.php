<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('the command creates the owner\'s admin login', function () {
    $this->artisan('app:create-admin', ['--name' => 'Paris', '--email' => 'Owner@Example.com', '--password' => 'secret-pass-123'])
        ->assertSuccessful();

    $user = User::query()->sole();

    expect($user)
        ->email->toBe('owner@example.com')
        ->is_admin->toBeTrue()
        ->email_verified_at->not->toBeNull()
        ->and(Hash::check('secret-pass-123', $user->password))->toBeTrue();
});

test('the command makes an existing user an admin', function () {
    $user = User::factory()->create(['email' => 'owner@example.com']);

    $this->artisan('app:create-admin', ['--email' => 'owner@example.com', '--password' => 'secret-pass-123'])
        ->assertSuccessful();

    expect($user->fresh()->is_admin)->toBeTrue()
        ->and(User::query()->count())->toBe(1);
});

test('the command refuses an invalid email', function () {
    $this->artisan('app:create-admin', ['--name' => 'Paris', '--email' => 'not-an-email', '--password' => 'secret-pass-123'])
        ->assertFailed();

    expect(User::query()->count())->toBe(0);
});

test('nobody can sign up from the website', function () {
    $this->post('/register', [
        'name' => 'Stranger',
        'email' => 'stranger@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertNotFound();

    expect(User::query()->count())->toBe(0);
});
