<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\RepairResultStatus;
use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use App\Models\Asset;
use App\Models\CheckoutBatch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductLine;
use App\Models\ReturnBatch;
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
        'name' => 'Công ty XYZ',
        'phone' => '0988776655',
    ]);

    $this->order = Order::create([
        'order_no' => 'ORD-RET-01',
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'request_date' => now()->toDateString(),
        'expected_return_date' => now()->addDays(3)->toDateString(),
        'status' => OrderStatus::Dispatched,
        'value' => 30000000,
        'area_m2' => 10,
    ]);

    $this->checkoutBatch = CheckoutBatch::create([
        'code' => 'OUT-RET-01',
        'order_id' => $this->order->id,
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'required_area_m2' => 10,
        'status' => BatchStatus::Dispatched,
        'created_by' => $this->user->id,
    ]);

    $this->returnBatch = ReturnBatch::create([
        'code' => 'RET-TEST-01',
        'checkout_batch_id' => $this->checkoutBatch->id,
        'return_date' => now()->toDateString(),
        'status' => ReturnBatchStatus::Pending,
        'created_by' => $this->user->id,
    ]);

    $this->productLine = ProductLine::create([
        'code' => 'P4.8-OUT',
        'name' => 'LED P4.8 Outdoor',
        'pitch' => 4.8,
    ]);

    $this->normalAsset = Asset::create([
        'serial_no' => 'CAB-NORMAL-01',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::InEvent,
        'size' => '0.5x0.5',
    ]);

    $this->damagedAsset = Asset::create([
        'serial_no' => 'CAB-DAMAGED-01',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::InEvent,
        'size' => '0.5x0.5',
    ]);
});

test('can scan normal asset and transition to ready', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/return-batches/{$this->returnBatch->id}/scan", [
            'code' => 'CAB-NORMAL-01',
            'grade' => ReturnGrade::Normal->value,
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($this->normalAsset->fresh()->current_status)->toBe(AssetStatus::Ready);
});

test('scanning damaged asset creates repair log and transitions to repairing', function () {
    $token = $this->user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/return-batches/{$this->returnBatch->id}/scan", [
            'code' => 'CAB-DAMAGED-01',
            'grade' => ReturnGrade::Damaged->value,
            'grade_note' => 'Chết cụm module LED góc dưới',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($this->damagedAsset->fresh()->current_status)->toBe(AssetStatus::Repairing);
    expect($this->damagedAsset->repairLogs()->count())->toBe(1);
    expect($this->damagedAsset->repairLogs()->first()->result_status)->toBe(RepairResultStatus::Pending);

    // Complete return batch
    $completeResponse = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/return-batches/{$this->returnBatch->id}/complete");

    $completeResponse->assertOk();
    expect($this->returnBatch->fresh()->status)->toBe(ReturnBatchStatus::Completed);
    expect($this->order->fresh()->status)->toBe(OrderStatus::Returned);
});
