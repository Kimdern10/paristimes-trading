<?php

use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('the owner can visit every admin page', function (string $route) {
    $this->actingAs(User::factory()->admin()->create());

    $this->get(route($route))->assertOk();
})->with(['dashboard', 'admin.photos', 'admin.business']);

test('users who are not the owner cannot open the admin', function (string $route) {
    $this->actingAs(User::factory()->create());

    $this->get(route($route))->assertForbidden();
})->with(['dashboard', 'admin.photos', 'admin.business']);
