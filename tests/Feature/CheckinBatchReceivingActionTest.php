<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'code' => 'WH-TEST-RCV',
        'name' => 'Kho Test Nhận Hàng',
        'city' => 'Hà Nội',
        'is_active' => true,
    ]);

    $this->user = User::create([
        'name' => 'Thủ Kho Test',
        'email' => 'kho.test.rcv@ledmanager.com',
        'password' => bcrypt('password123'),
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);

    $this->productLine = ProductLine::create([
        'code' => 'P2.6-RCV',
        'name' => 'LED P2.6 Sự kiện',
        'pitch' => 2.6,
    ]);

    $this->batch = CheckinBatch::create([
        'code' => 'IN-RCV-001',
        'warehouse_id' => $this->warehouse->id,
        'batch_type' => CheckinBatchType::Transfer,
        'product_line_id' => $this->productLine->id,
        'quantity' => 2,
        'status' => BatchStatus::Pending,
        'created_by' => $this->user->id,
    ]);

    $this->asset1 = Asset::create([
        'serial_no' => 'GE-R26-000101',
        'qr_code' => 'GE-R26-000101',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => null,
        'current_status' => AssetStatus::Repairing,
        'size' => '0.5x0.5 m',
    ]);

    $this->asset2 = Asset::create([
        'serial_no' => 'GE-R26-000102',
        'qr_code' => 'GE-R26-000102',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => null,
        'current_status' => AssetStatus::InTransit,
        'size' => '0.5x0.5 m',
    ]);

    CheckinBatchItem::create([
        'checkin_batch_id' => $this->batch->id,
        'asset_id' => $this->asset1->id,
        'is_received' => false,
    ]);

    CheckinBatchItem::create([
        'checkin_batch_id' => $this->batch->id,
        'asset_id' => $this->asset2->id,
        'is_received' => false,
    ]);
});

test('can receive item with normal condition', function () {
    $response = $this->actingAs($this->user)->postJson(route('filament.checkin-receive-item'), [
        'batch_id' => $this->batch->id,
        'asset_id' => $this->asset1->id,
        'condition' => 'normal',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('item.is_received', true)
        ->assertJsonPath('item.condition', 'normal')
        ->assertJsonPath('item.serial_no', 'GE-R26-000101');

    $this->asset1->refresh();
    expect($this->asset1->current_status)->toBe(AssetStatus::Ready);
    expect($this->asset1->current_warehouse_id)->toBe($this->warehouse->id);

    $batchItem = CheckinBatchItem::where('checkin_batch_id', $this->batch->id)
        ->where('asset_id', $this->asset1->id)
        ->first();
    expect($batchItem->is_received)->toBeTrue();
    expect($batchItem->condition)->toBe('ok');
    expect($batchItem->received_at)->not->toBeNull();

    expect(AssetStatusLog::where('asset_id', $this->asset1->id)
        ->where('to_status', AssetStatus::Ready)
        ->exists())->toBeTrue();

    expect($this->batch->fresh()->status)->toBe(BatchStatus::InProgress);
});

test('can receive item with damaged condition', function () {
    $response = $this->actingAs($this->user)->postJson(route('filament.checkin-receive-item'), [
        'batch_id' => $this->batch->id,
        'asset_id' => $this->asset2->id,
        'condition' => 'damaged',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('item.is_received', true)
        ->assertJsonPath('item.condition', 'damaged');

    $this->asset2->refresh();
    expect($this->asset2->current_status)->toBe(AssetStatus::Repairing);
    expect($this->asset2->current_warehouse_id)->toBe($this->warehouse->id);

    $batchItem = CheckinBatchItem::where('checkin_batch_id', $this->batch->id)
        ->where('asset_id', $this->asset2->id)
        ->first();
    expect($batchItem->is_received)->toBeTrue();
    expect($batchItem->condition)->toBe('fault');
});

test('completeBatch marks remaining items as received and completes batch', function () {
    $response = $this->actingAs($this->user)->postJson(route('filament.checkin-complete-batch'), [
        'batch_id' => $this->batch->id,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true);

    expect($this->batch->fresh()->status)->toBe(BatchStatus::Completed);
    expect($this->batch->fresh()->completed_at)->not->toBeNull();

    $unreceivedCount = CheckinBatchItem::where('checkin_batch_id', $this->batch->id)
        ->where('is_received', false)
        ->count();
    expect($unreceivedCount)->toBe(0);
});

test('receiveItem validates required inputs', function () {
    $response = $this->actingAs($this->user)->postJson(route('filament.checkin-receive-item'), [
        'batch_id' => 99999,
        'asset_id' => 99999,
        'condition' => 'invalid-condition',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['batch_id', 'asset_id', 'condition']);
});

test('CheckinBatch complete method transitions assets and marks all items received', function () {
    $this->batch->complete($this->user);

    expect($this->batch->fresh()->status)->toBe(BatchStatus::Completed);
    expect($this->batch->fresh()->completed_at)->not->toBeNull();

    $this->asset1->refresh();
    $this->asset2->refresh();

    expect($this->asset1->current_warehouse_id)->toBe($this->warehouse->id);
    expect($this->asset1->current_status)->toBe(AssetStatus::Ready);
    expect($this->asset2->current_warehouse_id)->toBe($this->warehouse->id);
    expect($this->asset2->current_status)->toBe(AssetStatus::Ready);
});

test('CheckinBatchResource query includes eager loaded counts and progress calculation works', function () {
    $batchFromQuery = CheckinBatchResource::getEloquentQuery()
        ->find($this->batch->id);

    expect($batchFromQuery->items_count)->toBe(2);
    expect($batchFromQuery->received_items_count)->toBe(0);

    // Mark 1 item as received
    CheckinBatchItem::where('checkin_batch_id', $this->batch->id)
        ->where('asset_id', $this->asset1->id)
        ->update(['is_received' => true]);

    $batchFromQuery = CheckinBatchResource::getEloquentQuery()
        ->find($this->batch->id);

    expect($batchFromQuery->items_count)->toBe(2);
    expect($batchFromQuery->received_items_count)->toBe(1);
});
