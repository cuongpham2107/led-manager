<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

test('unauthenticated users are redirected to login', function () {
    $response = get('/');

    $response->assertRedirect('/login');
});

test('authenticated users can access the dashboard', function () {
    $user = User::factory()->create();

    $response = actingAs($user)->get('/');

    $response->assertSuccessful();
});
