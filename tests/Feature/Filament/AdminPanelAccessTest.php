<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('guests are redirected to the login page', function () {
    get('/admin')
        ->assertRedirect(route('filament.admin.auth.login'));
});

test('admin can access the admin panel dashboard', function () {
    $admin = User::factory()->admin()->create();

    actingAs($admin)
        ->get('/admin')
        ->assertOk();
});

test('regular users cannot access the admin panel', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
