<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\DeviceType;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ProductionBatchService;
use Database\Seeders\LedOsDataSeeder;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    (new LedOsDataSeeder)->run();
});

test('createFromProduction creates N assets, 1 batch, N items and N status logs atomically', function () {
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $productLine = ProductLine::where('is_active', true)->first();
    $deviceType = DeviceType::first();
    $warehouse = Warehouse::where('is_active', true)->first();

    expect($productLine)->not->toBeNull('ProductLine seed missing');
    expect($deviceType)->not->toBeNull('DeviceType seed missing');
    expect($warehouse)->not->toBeNull('Warehouse seed missing');

    $service = app(ProductionBatchService::class);
    $prefix = $service->suggestSerialPrefix($productLine);

    $result = $service->createFromProduction(
        productLine: $productLine,
        deviceType: $deviceType,
        quantity: 10,
        warehouse: $warehouse,
        size: '500x500mm',
        serialPrefix: $prefix,
        note: 'Lô test Phase 1',
        createdBy: $user,
    );

    // Return shape
    expect($result['batch'])->toBeInstanceOf(CheckinBatch::class);
    expect($result['assets'])->toHaveCount(10);

    /** @var CheckinBatch $batch */
    $batch = $result['batch'];

    // Batch assertions
    expect($batch->batch_type)->toBe(CheckinBatchType::Production);
    expect($batch->status)->toBe(BatchStatus::Completed);
    expect($batch->quantity)->toBe(10);
    expect($batch->product_line_id)->toBe($productLine->id);
    expect($batch->device_type_id)->toBe($deviceType->id);
    expect($batch->warehouse_id)->toBe($warehouse->id);
    expect($batch->completed_at)->not->toBeNull();
    expect($batch->code)->toStartWith('IN-');

    // Asset assertions
    foreach ($result['assets'] as $i => $asset) {
        /** @var Asset $asset */
        expect($asset->serial_no)->toBe($prefix.'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT));
        expect($asset->qr_code)->toBe($asset->serial_no);
        expect($asset->current_status)->toBe(AssetStatus::Ready);
        expect($asset->current_warehouse_id)->toBe($warehouse->id);
        expect($asset->device_type_id)->toBe($deviceType->id);
        expect($asset->product_line_id)->toBe($productLine->id);
        expect($asset->size)->toBe('500x500mm');
    }

    // Items assertions
    expect(CheckinBatchItem::where('checkin_batch_id', $batch->id)->count())->toBe(10);
    foreach (CheckinBatchItem::where('checkin_batch_id', $batch->id)->get() as $item) {
        expect($item->is_received)->toBeTrue();
        expect($item->received_by)->toBe($user->id);
        expect($item->received_at)->not->toBeNull();
    }

    // Status log assertions
    expect(AssetStatusLog::where('source_type', CheckinBatch::class)
        ->where('source_id', $batch->id)
        ->count())->toBe(10);
});

test('createFromProduction rejects quantity below 1 and above 500', function () {
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $productLine = ProductLine::where('is_active', true)->first();
    $deviceType = DeviceType::first();
    $warehouse = Warehouse::where('is_active', true)->first();
    $service = app(ProductionBatchService::class);

    expect(fn () => $service->createFromProduction(
        productLine: $productLine,
        deviceType: $deviceType,
        quantity: 0,
        warehouse: $warehouse,
        size: null,
        serialPrefix: 'TEST-0',
        note: null,
        createdBy: $user,
    ))->toThrow(InvalidArgumentException::class);

    expect(fn () => $service->createFromProduction(
        productLine: $productLine,
        deviceType: $deviceType,
        quantity: 501,
        warehouse: $warehouse,
        size: null,
        serialPrefix: 'TEST-501',
        note: null,
        createdBy: $user,
    ))->toThrow(InvalidArgumentException::class);
});

test('createFromProduction aborts transaction and throws when a generated serial already exists', function () {
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $productLine = ProductLine::where('is_active', true)->first();
    $deviceType = DeviceType::first();
    $warehouse = Warehouse::where('is_active', true)->first();
    $service = app(ProductionBatchService::class);

    $prefix = 'COLLIDE-'.now()->format('ymd');

    // First batch succeeds
    $service->createFromProduction(
        productLine: $productLine,
        deviceType: $deviceType,
        quantity: 3,
        warehouse: $warehouse,
        size: null,
        serialPrefix: $prefix,
        note: 'lần 1',
        createdBy: $user,
    );

    // Second batch with the same prefix must throw and roll back
    $batchCountBefore = CheckinBatch::count();
    $assetCountBefore = Asset::count();

    expect(fn () => $service->createFromProduction(
        productLine: $productLine,
        deviceType: $deviceType,
        quantity: 3,
        warehouse: $warehouse,
        size: null,
        serialPrefix: $prefix,
        note: 'lần 2',
        createdBy: $user,
    ))->toThrow(RuntimeException::class);

    // Transaction must have rolled back
    expect(CheckinBatch::count())->toBe($batchCountBefore);
    expect(Asset::count())->toBe($assetCountBefore);
});

test('makeSerial builds zero-padded serial numbers with given prefix', function () {
    $service = app(ProductionBatchService::class);

    expect($service->makeSerial('P3.91', 1, 3))->toBe('P3.91-001');
    expect($service->makeSerial('P3.91', 10, 3))->toBe('P3.91-010');
    expect($service->makeSerial('P3.91', 100, 3))->toBe('P3.91-100');
    expect($service->makeSerial('', 5, 3))->toBe('ASSET-005');
    expect($service->makeSerial('  ', 7, 4))->toBe('ASSET-0007');
});

test('suggestSerialPrefix returns {ProductLine.code}-{YYMMDD} uppercased', function () {
    $service = app(ProductionBatchService::class);
    $productLine = ProductLine::where('is_active', true)->first();

    $prefix = $service->suggestSerialPrefix($productLine);

    expect($prefix)->toBe(strtoupper($productLine->code).'-'.now()->format('ymd'));
});
