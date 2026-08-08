<?php

use App\Enums\UserRole;
use App\Models\User;

test('user factory has default user role and admin state', function () {
    $user = User::factory()->make();
    $admin = User::factory()->admin()->make();

    expect($user->role)->toBe(UserRole::User)
        ->and($admin->role)->toBe(UserRole::Admin);
});
