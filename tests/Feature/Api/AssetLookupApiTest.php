<?php

use App\Enums\AssetStatus;
use App\Models\Asset;
use App\Models\ProductLine;
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

    $this->productLine = ProductLine::create([
        'code' => 'P2.6-IN',
        'name' => 'LED P2.6 Indoor',
        'pitch' => 2.6,
        'module_resolution' => '192x192',
    ]);

    $this->asset = Asset::create([
        'serial_no' => 'LED-P26-001',
        'qr_code' => 'QR-LED-P26-001',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::Ready,
        'size' => '0.5x0.5',
    ]);
});

test('can lookup asset by serial number or qr code', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    // Lookup by serial
    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/assets/lookup?code=LED-P26-001');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.asset.serial_no', 'LED-P26-001')
        ->assertJsonPath('data.asset.product_line.name', 'LED P2.6 Indoor')
        ->assertJsonPath('data.asset.current_warehouse.code', 'WH-TEST');

    // Lookup by QR
    $qrResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/assets/lookup?code=QR-LED-P26-001');

    $qrResponse->assertOk()
        ->assertJsonPath('data.asset.serial_no', 'LED-P26-001');
});
