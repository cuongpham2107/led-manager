<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Models\Asset;
use App\Models\CheckoutBatch;
use App\Models\Customer;
use App\Models\Order;
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

    $this->customer = Customer::create([
        'name' => 'Công ty ABC',
        'phone' => '0901234567',
    ]);

    $this->order = Order::create([
        'order_no' => 'ORD-TEST-01',
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'request_date' => now()->toDateString(),
        'expected_return_date' => now()->addDays(3)->toDateString(),
        'status' => OrderStatus::Draft,
        'value' => 50000000,
        'area_m2' => 20,
    ]);

    $this->batch = CheckoutBatch::create([
        'code' => 'OUT-TEST-01',
        'order_id' => $this->order->id,
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'required_area_m2' => 20,
        'status' => BatchStatus::Pending,
        'created_by' => $this->user->id,
    ]);

    $this->productLine = ProductLine::create([
        'code' => 'P3.9-OUT',
        'name' => 'LED P3.9 Outdoor',
        'pitch' => 3.9,
    ]);

    $this->asset = Asset::create([
        'serial_no' => 'CAB-P39-001',
        'qr_code' => 'QR-CAB-P39-001',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::Ready,
        'size' => '0.5x0.5',
    ]);
});

test('can list checkout batches and get details via API', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/checkout-batches');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data');

    $detailResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/checkout-batches/{$this->batch->id}");

    $detailResponse->assertOk()
        ->assertJsonPath('data.code', 'OUT-TEST-01');
});

test('can scan asset QR code to dispatch and update status', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $scanResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/checkout-batches/{$this->batch->id}/scan", [
            'code' => 'CAB-P39-001',
        ]);

    $scanResponse->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.scanned_count', 1);

    // Verify fetching detail batch with scanned items loaded works without 500 error
    $detailWithItems = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/checkout-batches/{$this->batch->id}");
    $detailWithItems->assertOk()
        ->assertJsonPath('data.items.0.asset.serial_no', 'CAB-P39-001');

    // Verify Asset status updated to InTransit
    expect($this->asset->fresh()->current_status)->toBe(AssetStatus::InTransit);

    // Verify status log created
    expect($this->asset->statusLogs()->count())->toBe(1);

    // Complete dispatch
    $completeResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/checkout-batches/{$this->batch->id}/complete");

    $completeResponse->assertOk();
    expect($this->batch->fresh()->status)->toBe(BatchStatus::Dispatched);
    expect($this->order->fresh()->status)->toBe(OrderStatus::Dispatched);
    expect($this->asset->fresh()->current_status)->toBe(AssetStatus::InEvent);
});
