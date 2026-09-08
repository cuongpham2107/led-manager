<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductLine;
use App\Models\RepairLog;
use App\Models\ReturnBatch;
use App\Models\ReturnBatchItem;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'code' => 'WH-RET-RCV-TEST',
        'name' => 'Kho Test Nhập Trả RCV',
        'city' => 'Hà Nội',
        'is_active' => true,
    ]);

    $this->user = User::create([
        'name' => 'Thủ Kho Nhập Trả',
        'email' => 'kho.ret.rcv@ledmanager.com',
        'password' => bcrypt('password123'),
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);

    $this->customer = Customer::create([
        'name' => 'Công ty Test Event',
        'phone' => '0912345678',
    ]);

    $this->productLine = ProductLine::create([
        'code' => 'P2.6-RET',
        'name' => 'LED P2.6 Return',
        'pitch' => 2.6,
    ]);

    $this->order = Order::create([
        'order_no' => 'ORD-RET-TEST-01',
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'request_date' => now()->toDateString(),
        'status' => OrderStatus::Dispatched,
        'event' => 'Sự kiện Year End Party',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
    ]);

    $this->checkoutBatch = CheckoutBatch::create([
        'code' => 'OUT-RET-TEST-01',
        'order_id' => $this->order->id,
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => BatchStatus::Dispatched,
        'created_by' => $this->user->id,
    ]);

    $this->asset1 = Asset::create([
        'serial_no' => 'RET-CB-001',
        'qr_code' => 'RET-CB-001',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::InTransit,
        'size' => '0.5x0.5 m',
    ]);

    $this->asset2 = Asset::create([
        'serial_no' => 'RET-CB-002',
        'qr_code' => 'RET-CB-002',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::InTransit,
        'size' => '0.5x0.5 m',
    ]);

    $cbItem1 = CheckoutBatchItem::create([
        'checkout_batch_id' => $this->checkoutBatch->id,
        'asset_id' => $this->asset1->id,
        'is_dispatched' => true,
    ]);

    $cbItem2 = CheckoutBatchItem::create([
        'checkout_batch_id' => $this->checkoutBatch->id,
        'asset_id' => $this->asset2->id,
        'is_dispatched' => true,
    ]);

    $this->returnBatch = ReturnBatch::create([
        'code' => 'RET-TEST-001',
        'checkout_batch_id' => $this->checkoutBatch->id,
        'return_date' => now()->toDateString(),
        'status' => ReturnBatchStatus::InProgress,
        'created_by' => $this->user->id,
    ]);

    $this->retItem1 = ReturnBatchItem::create([
        'return_batch_id' => $this->returnBatch->id,
        'asset_id' => $this->asset1->id,
        'checkout_batch_item_id' => $cbItem1->id,
        'is_received' => false,
    ]);

    $this->retItem2 = ReturnBatchItem::create([
        'return_batch_id' => $this->returnBatch->id,
        'asset_id' => $this->asset2->id,
        'checkout_batch_item_id' => $cbItem2->id,
        'is_received' => false,
    ]);
});

test('can receive return item with normal condition via endpoint', function () {
    $response = $this->actingAs($this->user)->postJson(route('filament.return-receive-item'), [
        'batch_id' => $this->returnBatch->id,
        'asset_id' => $this->asset1->id,
        'condition' => 'normal',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('item.is_received', true)
        ->assertJsonPath('item.condition', 'normal');

    $this->asset1->refresh();
    expect($this->asset1->current_status)->toBe(AssetStatus::Ready);
    expect($this->asset1->current_warehouse_id)->toBe($this->warehouse->id);

    $this->retItem1->refresh();
    expect($this->retItem1->is_received)->toBeTrue();
    expect($this->retItem1->grade)->toBe(ReturnGrade::Normal);
    expect($this->retItem1->received_at)->not->toBeNull();

    expect(AssetStatusLog::where('asset_id', $this->asset1->id)
        ->where('to_status', AssetStatus::Ready)
        ->exists())->toBeTrue();
});

test('can receive return item with damaged condition via endpoint', function () {
    $response = $this->actingAs($this->user)->postJson(route('filament.return-receive-item'), [
        'batch_id' => $this->returnBatch->id,
        'asset_id' => $this->asset2->id,
        'condition' => 'damaged',
        'note' => 'Vỡ góc module LED',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('item.is_received', true)
        ->assertJsonPath('item.condition', 'damaged');

    $this->asset2->refresh();
    expect($this->asset2->current_status)->toBe(AssetStatus::Repairing);

    $this->retItem2->refresh();
    expect($this->retItem2->is_received)->toBeTrue();
    expect($this->retItem2->grade)->toBe(ReturnGrade::Damaged);
    expect($this->retItem2->grade_note)->toBe('Vỡ góc module LED');

    expect(RepairLog::where('asset_id', $this->asset2->id)->exists())->toBeTrue();
    expect(AssetStatusLog::where('asset_id', $this->asset2->id)
        ->where('to_status', AssetStatus::Repairing)
        ->exists())->toBeTrue();
});

test('complete method handles unreceived items as missing and completes return and checkout batches', function () {
    // Only receive asset 1
    $this->actingAs($this->user)->postJson(route('filament.return-receive-item'), [
        'batch_id' => $this->returnBatch->id,
        'asset_id' => $this->asset1->id,
        'condition' => 'normal',
    ]);

    // Complete the batch (asset 2 remains unreceived)
    $this->returnBatch->complete($this->user);

    expect($this->returnBatch->fresh()->status)->toBe(ReturnBatchStatus::Completed);
    expect($this->checkoutBatch->fresh()->status)->toBe(BatchStatus::Completed);
    expect($this->order->fresh()->status)->toBe(OrderStatus::Returned);

    $this->asset1->refresh();
    expect($this->asset1->current_status)->toBe(AssetStatus::Ready);

    $this->asset2->refresh();
    expect($this->asset2->current_status)->toBe(AssetStatus::Missing);
});
