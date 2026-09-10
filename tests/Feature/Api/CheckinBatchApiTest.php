<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'code' => 'WH-IN',
        'name' => 'Kho Nhập Test',
        'city' => 'Hà Nội',
        'is_active' => true,
    ]);

    $this->user = User::create([
        'name' => 'Thủ Kho Nhập',
        'email' => 'kho-nhap.test@ledmanager.com',
        'password' => bcrypt('password123'),
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);

    $this->productLine = ProductLine::create([
        'code' => 'P2.6-IN',
        'name' => 'LED P2.6 Indoor',
        'pitch' => 2.6,
    ]);

    $this->batch = CheckinBatch::create([
        'code' => 'IN-TEST-01',
        'warehouse_id' => $this->warehouse->id,
        'batch_type' => CheckinBatchType::Production,
        'product_line_id' => $this->productLine->id,
        'quantity' => 5,
        'status' => BatchStatus::Pending,
        'production_note' => 'Lô test nhập kho',
        'created_by' => $this->user->id,
    ]);

    // Asset currently in a different warehouse, status Repairing
    $this->asset = Asset::create([
        'serial_no' => 'P26-260829-001',
        'qr_code' => 'P26-260829-001',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => null,
        'current_status' => AssetStatus::Repairing,
        'size' => '0.5x0.5',
    ]);
});

test('can list check-in batches and get details via API', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/checkin-batches');

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['id', 'code', 'status', 'batch_type', 'quantity', 'warehouse', 'product_line'],
            ],
            'pagination' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('success', true);
});

test('can fetch a single check-in batch by id', function () {
    $response = $this->actingAs($this->user)->getJson("/api/v1/checkin-batches/{$this->batch->id}");

    $response->assertOk()
        ->assertJsonPath('data.code', 'IN-TEST-01')
        ->assertJsonPath('data.batch_type.value', 'production')
        ->assertJsonPath('data.quantity', 5);
});

test('returns 404 for non-existent check-in batch', function () {
    $response = $this->actingAs($this->user)->getJson('/api/v1/checkin-batches/99999');

    $response->assertNotFound()
        ->assertJsonPath('success', false);
});

test('can scan an asset serial to mark it received in the batch', function () {
    $response = $this->actingAs($this->user)->postJson("/api/v1/checkin-batches/{$this->batch->id}/scan", [
        'code' => $this->asset->serial_no,
        'condition' => 'ok',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.item.is_received', true)
        ->assertJsonPath('data.item.condition', 'ok')
        ->assertJsonPath('data.scanned_count', 1)
        ->assertJsonPath('data.target_items_count', 5)
        ->assertJsonPath('data.progress_percent', 20);

    // Asset should now be Ready and in this warehouse
    $this->asset->refresh();
    expect($this->asset->current_status)->toBe(AssetStatus::Ready);
    expect($this->asset->current_warehouse_id)->toBe($this->warehouse->id);

    // Item recorded
    expect(CheckinBatchItem::where('checkin_batch_id', $this->batch->id)
        ->where('asset_id', $this->asset->id)
        ->where('is_received', true)
        ->exists())->toBeTrue();

    // Status log recorded
    expect(AssetStatusLog::where('asset_id', $this->asset->id)
        ->where('source_type', CheckinBatch::class)
        ->where('source_id', $this->batch->id)
        ->where('to_status', AssetStatus::Ready)
        ->exists())->toBeTrue();

    // Batch status moved to in_progress
    expect($this->batch->fresh()->status)->toBe(BatchStatus::InProgress);
});

test('scanning the same asset twice is idempotent at the item level', function () {
    $this->actingAs($this->user)->postJson("/api/v1/checkin-batches/{$this->batch->id}/scan", [
        'code' => $this->asset->serial_no,
    ])->assertOk();

    $response = $this->actingAs($this->user)->postJson("/api/v1/checkin-batches/{$this->batch->id}/scan", [
        'code' => $this->asset->serial_no,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('data.item.is_received', true);

    // Only one item row for this asset
    expect(CheckinBatchItem::where('checkin_batch_id', $this->batch->id)
        ->where('asset_id', $this->asset->id)
        ->count())->toBe(1);
});

test('scanning an unknown serial returns 404', function () {
    $response = $this->actingAs($this->user)->postJson("/api/v1/checkin-batches/{$this->batch->id}/scan", [
        'code' => 'DOES-NOT-EXIST',
    ]);

    $response->assertNotFound()
        ->assertJsonPath('success', false);
});

test('scanning is blocked once batch is completed', function () {
    $this->batch->update(['status' => BatchStatus::Completed, 'completed_at' => now()]);

    $response = $this->actingAs($this->user)->postJson("/api/v1/checkin-batches/{$this->batch->id}/scan", [
        'code' => $this->asset->serial_no,
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('complete endpoint marks the batch as completed', function () {
    $this->actingAs($this->user)->postJson("/api/v1/checkin-batches/{$this->batch->id}/scan", [
        'code' => $this->asset->serial_no,
    ])->assertOk();

    $response = $this->actingAs($this->user)->postJson("/api/v1/checkin-batches/{$this->batch->id}/complete");

    $response->assertOk()
        ->assertJsonPath('data.status.value', 'completed');

    expect($this->batch->fresh()->completed_at)->not->toBeNull();
});

test('unauthenticated requests are rejected', function () {
    $this->getJson('/api/v1/checkin-batches')->assertUnauthorized();
    $this->postJson("/api/v1/checkin-batches/{$this->batch->id}/scan", ['code' => 'X'])->assertUnauthorized();
});

test('active filter hides completed and cancelled check-in batches', function () {
    $completed = CheckinBatch::create([
        'code' => 'IN-DONE-01',
        'warehouse_id' => $this->warehouse->id,
        'batch_type' => CheckinBatchType::Production,
        'product_line_id' => $this->productLine->id,
        'quantity' => 3,
        'status' => BatchStatus::Completed,
        'created_by' => $this->user->id,
    ]);

    $cancelled = CheckinBatch::create([
        'code' => 'IN-CANCEL-01',
        'warehouse_id' => $this->warehouse->id,
        'batch_type' => CheckinBatchType::Production,
        'product_line_id' => $this->productLine->id,
        'quantity' => 3,
        'status' => BatchStatus::Cancelled,
        'created_by' => $this->user->id,
    ]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/checkin-batches?active=1');

    $response->assertOk();
    $codes = collect($response->json('data'))->pluck('code');

    expect($codes)->toContain('IN-TEST-01')
        ->and($codes)->not->toContain('IN-DONE-01')
        ->and($codes)->not->toContain('IN-CANCEL-01');
});
