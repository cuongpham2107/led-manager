<?php

use App\Models\User;
use Database\Seeders\LedOsDataSeeder;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    (new LedOsDataSeeder)->run();
});

test('checkin assets lazy loading endpoint requires authentication', function () {
    $response = $this->getJson(route('filament.checkin-assets'));
    $response->assertUnauthorized();
});

test('authenticated user can lazy load assets with pagination and search', function () {
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    // Page 1
    $response = $this->getJson(route('filament.checkin-assets', ['page' => 1, 'per_page' => 10]));
    $response->assertOk()
        ->assertJsonStructure([
            'items' => [
                '*' => ['id', 'serial_no', 'name', 'size', 'status', 'status_color'],
            ],
            'current_page',
            'last_page',
            'has_more',
            'total',
        ]);

    $data = $response->json();
    expect($data['current_page'])->toBe(1)
        ->and(count($data['items']))->toBe(10)
        ->and($data['total'])->toBeGreaterThan(10)
        ->and($data['has_more'])->toBeTrue();

    // Search by serial prefix
    $firstSerial = $data['items'][0]['serial_no'];
    $searchResponse = $this->getJson(route('filament.checkin-assets', ['search' => $firstSerial]));
    $searchResponse->assertOk();
    $searchData = $searchResponse->json();
    expect($searchData['items'])->not->toBeEmpty()
        ->and($searchData['items'][0]['serial_no'])->toBe($firstSerial);
});
