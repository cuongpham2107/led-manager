<?php

use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'code' => 'WH-TEST',
        'name' => 'Kho Test',
        'city' => 'Hà Nội',
        'is_active' => true,
    ]);

    $this->user = User::create([
        'name' => 'Thủ Kho Test',
        'email' => 'kho.test@ledmanager.com',
        'password' => bcrypt('password123'),
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);
});

test('user can login via API with valid credentials', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'kho.test@ledmanager.com',
        'password' => 'password123',
        'device_name' => 'Test-Phone',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'token',
                'user' => [
                    'id',
                    'name',
                    'email',
                    'warehouse' => ['id', 'code', 'name'],
                ],
            ],
        ]);
});

test('user cannot login with incorrect password', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'kho.test@ledmanager.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

test('authenticated user can get profile and logout', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    // Get Profile
    $meResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/auth/me');

    $meResponse->assertOk()
        ->assertJsonPath('data.email', 'kho.test@ledmanager.com')
        ->assertJsonPath('data.warehouse.code', 'WH-TEST');

    // Logout
    $logoutResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/auth/logout');

    $logoutResponse->assertOk()
        ->assertJsonPath('success', true);
});
