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
use App\Models\WarehouseLocation;
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
    $warehouse = Warehouse::where('is_active', true)->first();

    expect($productLine)->not->toBeNull('ProductLine seed missing');
    expect($warehouse)->not->toBeNull('Warehouse seed missing');

    $service = app(ProductionBatchService::class);
    $prefix = $service->suggestSerialPrefix($productLine);

    $result = $service->createFromProduction(
        productLine: $productLine,
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

test('createFromProduction assigns warehouse location to created assets when provided', function () {
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $productLine = ProductLine::where('is_active', true)->first();
    $warehouse = Warehouse::where('is_active', true)->first();
    $location = WarehouseLocation::firstOrCreate(
        ['warehouse_id' => $warehouse->id, 'name' => 'Zone Production A'],
        ['is_active' => true]
    );

    $service = app(ProductionBatchService::class);
    $prefix = 'PROD-LOC-'.now()->format('ymd');

    $result = $service->createFromProduction(
        productLine: $productLine,
        quantity: 3,
        warehouse: $warehouse,
        size: '500x500mm',
        serialPrefix: $prefix,
        note: 'Test nhập kèm vị trí kho',
        createdBy: $user,
        warehouseLocation: $location,
    );

    expect($result['assets'])->toHaveCount(3);
    foreach ($result['assets'] as $asset) {
        expect($asset->warehouse_location_id)->toBe($location->id)
            ->and($asset->current_warehouse_id)->toBe($warehouse->id);
    }
});

test('createFromProduction supports equipment without product line (e.g. Processors, Cables)', function () {
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $warehouse = Warehouse::where('is_active', true)->first();

    $service = app(ProductionBatchService::class);
    $prefix = $service->suggestSerialPrefix(null);

    $result = $service->createFromProduction(
        productLine: null,
        quantity: 5,
        warehouse: $warehouse,
        size: '1U Rack',
        serialPrefix: $prefix,
        note: 'Lô Video Processor nhập mới',
        createdBy: $user,
    );

    expect($result['batch'])->toBeInstanceOf(CheckinBatch::class);
    expect($result['assets'])->toHaveCount(5);

    /** @var CheckinBatch $batch */
    $batch = $result['batch'];
    expect($batch->product_line_id)->toBeNull();
    expect($batch->quantity)->toBe(5);

    foreach ($result['assets'] as $i => $asset) {
        /** @var Asset $asset */
        expect($asset->product_line_id)->toBeNull();
        expect($asset->size)->toBe('1U Rack');
        expect($asset->serial_no)->toBe($prefix.'-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT));
    }
});

test('createFromProduction rejects quantity below 1 and above 500', function () {
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $productLine = ProductLine::where('is_active', true)->first();
    $warehouse = Warehouse::where('is_active', true)->first();
    $service = app(ProductionBatchService::class);

    expect(fn () => $service->createFromProduction(
        productLine: $productLine,
        quantity: 0,
        warehouse: $warehouse,
        size: null,
        serialPrefix: 'TEST-0',
        note: null,
        createdBy: $user,
    ))->toThrow(InvalidArgumentException::class);

    expect(fn () => $service->createFromProduction(
        productLine: $productLine,
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
    $warehouse = Warehouse::where('is_active', true)->first();
    $service = app(ProductionBatchService::class);

    $prefix = 'COLLIDE-'.now()->format('ymd');

    // First batch succeeds
    $service->createFromProduction(
        productLine: $productLine,
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

test('check-in table progress column computes scanned/target percent', function () {
    $warehouse = Warehouse::where('is_active', true)->first();
    $productLine = ProductLine::where('is_active', true)->first();
    $creator = User::first();

    $batch = CheckinBatch::create([
        'code' => 'IN-PROG-01',
        'warehouse_id' => $warehouse->id,
        'batch_type' => CheckinBatchType::Production,
        'product_line_id' => $productLine->id,
        'quantity' => 10,
        'status' => BatchStatus::InProgress,
        'created_by' => $creator->id,
    ]);

    // 4 items exist, 2 received
    for ($i = 1; $i <= 4; $i++) {
        $asset = Asset::create([
            'serial_no' => 'PROG-'.sprintf('%03d', $i),
            'product_line_id' => $productLine->id,
            'current_warehouse_id' => $warehouse->id,
            'current_status' => AssetStatus::Ready,
        ]);
        CheckinBatchItem::create([
            'checkin_batch_id' => $batch->id,
            'asset_id' => $asset->id,
            'is_received' => $i <= 2,
            'received_at' => $i <= 2 ? now() : null,
        ]);
    }

    $batch = $batch->fresh('items');
    $scanned = $batch->items->where('is_received', true)->count();
    $target = max((int) $batch->quantity, $batch->items->count());
    $percent = $target > 0 ? (int) round(($scanned / $target) * 100) : 0;

    expect($batch->items)->toHaveCount(4);
    expect($scanned)->toBe(2);
    expect($target)->toBe(10); // quantity wins when larger than items
    expect($percent)->toBe(20);
    expect("{$scanned}/{$target} ({$percent}%)")->toBe('2/10 (20%)');
});

test('check-in table progress falls back to items count when quantity is null', function () {
    $warehouse = Warehouse::where('is_active', true)->first();
    $productLine = ProductLine::where('is_active', true)->first();
    $creator = User::first();

    $batch = CheckinBatch::create([
        'code' => 'IN-LEGACY-01',
        'warehouse_id' => $warehouse->id,
        'status' => BatchStatus::InProgress,
        'created_by' => $creator->id,
        // quantity not set (legacy batch)
    ]);

    for ($i = 1; $i <= 3; $i++) {
        $asset = Asset::create([
            'serial_no' => 'LEG-'.sprintf('%03d', $i),
            'product_line_id' => $productLine->id,
            'current_warehouse_id' => $warehouse->id,
            'current_status' => AssetStatus::Ready,
        ]);
        CheckinBatchItem::create([
            'checkin_batch_id' => $batch->id,
            'asset_id' => $asset->id,
            'is_received' => $i <= 1,
            'received_at' => $i <= 1 ? now() : null,
        ]);
    }

    $batch = $batch->fresh('items');
    $scanned = $batch->items->where('is_received', true)->count();
    $target = max((int) $batch->quantity, $batch->items->count());
    $percent = $target > 0 ? (int) round(($scanned / $target) * 100) : 0;

    expect($target)->toBe(3); // falls back to items count
    expect($percent)->toBe(33); // 1/3 = 33%
    expect("{$scanned}/{$target} ({$percent}%)")->toBe('1/3 (33%)');
});
