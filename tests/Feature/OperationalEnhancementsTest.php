<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\ProductLine;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Warehouse;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'code' => 'WH-TEST-ENH',
        'name' => 'Kho Test Enhancements',
        'city' => 'Hà Nội',
        'is_active' => true,
    ]);

    $this->user = User::create([
        'name' => 'Test Admin',
        'email' => 'admin.test.enh@ledmanager.com',
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);

    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $this->user->assignRole($role);

    $this->productLine = ProductLine::create([
        'name' => 'P1.5 Test Indoor',
        'code' => 'P1.5',
        'module_width_mm' => 500,
        'module_height_mm' => 500,
        'is_active' => true,
    ]);
});

test('checkin batch partial complete with autoReceiveRemaining false keeps unscanned items unreceived', function () {
    $batch = CheckinBatch::create([
        'code' => 'IN-TEST-PARTIAL-001',
        'warehouse_id' => $this->warehouse->id,
        'status' => BatchStatus::Pending,
        'created_by' => $this->user->id,
    ]);

    $asset1 = Asset::create([
        'serial_no' => 'PARTIAL-A1',
        'product_line_id' => $this->productLine->id,
        'current_status' => AssetStatus::NewlyAdded,
    ]);

    $asset2 = Asset::create([
        'serial_no' => 'PARTIAL-A2',
        'product_line_id' => $this->productLine->id,
        'current_status' => AssetStatus::NewlyAdded,
    ]);

    $item1 = CheckinBatchItem::create([
        'checkin_batch_id' => $batch->id,
        'asset_id' => $asset1->id,
        'is_received' => true,
        'condition' => 'ok',
        'received_by' => $this->user->id,
        'received_at' => now(),
    ]);

    $item2 = CheckinBatchItem::create([
        'checkin_batch_id' => $batch->id,
        'asset_id' => $asset2->id,
        'is_received' => false,
    ]);

    // Complete with autoReceiveRemaining: false
    $batch->complete($this->user, autoReceiveRemaining: false);

    expect($batch->fresh()->status)->toBe(BatchStatus::Completed);
    expect($item1->fresh()->is_received)->toBeTrue();
    expect($item2->fresh()->is_received)->toBeFalse();
    expect($asset2->fresh()->current_status)->toBe(AssetStatus::NewlyAdded);
});

test('checkout asset search strictly excludes assets in uncancelled, unreturned checkout batches', function () {
    $assetAvail = Asset::create([
        'serial_no' => 'CHECKOUT-AVAIL',
        'product_line_id' => $this->productLine->id,
        'current_status' => AssetStatus::Ready,
        'current_warehouse_id' => $this->warehouse->id,
    ]);

    $assetBooked = Asset::create([
        'serial_no' => 'CHECKOUT-BOOKED',
        'product_line_id' => $this->productLine->id,
        'current_status' => AssetStatus::Ready,
        'current_warehouse_id' => $this->warehouse->id,
    ]);

    $otherBatch = CheckoutBatch::create([
        'code' => 'OUT-OTHER-001',
        'warehouse_id' => $this->warehouse->id,
        'status' => BatchStatus::Pending,
        'created_by' => $this->user->id,
    ]);

    CheckoutBatchItem::create([
        'checkout_batch_id' => $otherBatch->id,
        'asset_id' => $assetBooked->id,
        'is_dispatched' => false,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('filament.checkout-assets', [
            'warehouse_id' => $this->warehouse->id,
            'product_line_id' => $this->productLine->id,
        ]));

    $response->assertOk();
    $items = collect($response->json('items'));

    expect($items->pluck('serial_no'))->toContain('CHECKOUT-AVAIL')
        ->and($items->pluck('serial_no'))->not->toContain('CHECKOUT-BOOKED');
});

test('repair log form excludes assets that are currently repairing or have pending repair logs', function () {
    $readyAsset = Asset::create([
        'serial_no' => 'REPAIR-READY-001',
        'product_line_id' => $this->productLine->id,
        'current_status' => AssetStatus::Ready,
        'current_warehouse_id' => $this->warehouse->id,
    ]);

    $repairingAsset = Asset::create([
        'serial_no' => 'REPAIR-ALREADY-002',
        'product_line_id' => $this->productLine->id,
        'current_status' => AssetStatus::Repairing,
        'current_warehouse_id' => $this->warehouse->id,
    ]);

    $pendingLogAsset = Asset::create([
        'serial_no' => 'REPAIR-PENDING-003',
        'product_line_id' => $this->productLine->id,
        'current_status' => AssetStatus::Ready,
        'current_warehouse_id' => $this->warehouse->id,
    ]);

    RepairLog::create([
        'asset_id' => $pendingLogAsset->id,
        'created_by' => $this->user->id,
        'start_date' => now()->toDateString(),
        'result_status' => 'pending',
    ]);

    // Query using the same logic configured in RepairLogForm
    $query = Asset::query();
    $query->where(function ($sub) {
        $sub->where(function ($q) {
            $q->where('current_status', '!=', AssetStatus::Repairing)
                ->where('current_status', '!=', AssetStatus::Disposed)
                ->whereDoesntHave('repairLogs', function ($rLogQ) {
                    $rLogQ->where('result_status', 'pending');
                });
        });
    });

    $allowedSerialNos = $query->pluck('serial_no');

    expect($allowedSerialNos)->toContain('REPAIR-READY-001')
        ->and($allowedSerialNos)->not->toContain('REPAIR-ALREADY-002')
        ->and($allowedSerialNos)->not->toContain('REPAIR-PENDING-003');
});
