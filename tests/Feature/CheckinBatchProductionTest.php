<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Filament\Resources\CheckinBatches\Actions\CreateProductionBatchAction;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use App\Services\ProductionBatchService;
use Carbon\Carbon;
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

test('createFromSpreadsheet creates batch with custom serial numbers and distinct specifications', function () {
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $warehouse = Warehouse::where('is_active', true)->first();
    $productLines = ProductLine::where('is_active', true)->take(2)->get();
    $location = WarehouseLocation::firstOrCreate(
        ['warehouse_id' => $warehouse->id, 'name' => 'Zone Production B'],
        ['is_active' => true]
    );

    $service = app(ProductionBatchService::class);

    $columnMap = [
        'serial_no' => 0,
        'product_line' => 1,
        'location' => 2,
        'size' => 3,
        'manufactured_date' => 4,
        'purchase_cost' => 5,
        'note' => 6,
    ];

    $rows = [
        ['CUSTOM-SN-001', $productLines[0]->name, $location->name, '500x500mm', '07/09/2026', 2500000, 'Vật tư module LED A'],
        ['CUSTOM-SN-002', $productLines[1]?->name ?? $productLines[0]->name, $location->name, '500x1000mm', '07/09/2026', 3000000, 'Vật tư module LED B'],
        ['CAB-PROC-099', '', '', '1U Rack', '07/09/2026', 15000000, 'Bộ xử lý hình ảnh'],
    ];

    $result = $service->createFromSpreadsheet(
        rows: $rows,
        columnMap: $columnMap,
        warehouse: $warehouse,
        defaultProductLine: $productLines[0],
        defaultLocation: $location,
        productionNote: 'Đợt sản xuất tuỳ biến linh kiện',
        updateExisting: true,
        createdBy: $user,
    );

    expect($result['batch'])->toBeInstanceOf(CheckinBatch::class);
    expect($result['created_count'])->toBe(3);
    expect($result['assets'])->toHaveCount(3);

    $batch = $result['batch'];
    expect($batch->batch_type)->toBe(CheckinBatchType::Production);
    expect($batch->status)->toBe(BatchStatus::Completed);
    expect($batch->quantity)->toBe(3);
    expect($batch->production_note)->toBe('Đợt sản xuất tuỳ biến linh kiện');

    // Check custom serials
    $asset1 = Asset::where('serial_no', 'CUSTOM-SN-001')->first();
    expect($asset1)->not->toBeNull();
    expect($asset1->size)->toBe('500x500mm');
    expect($asset1->current_warehouse_id)->toBe($warehouse->id);
    expect($asset1->warehouse_location_id)->toBe($location->id);
    expect($asset1->current_status)->toBe(AssetStatus::Ready);

    $asset3 = Asset::where('serial_no', 'CAB-PROC-099')->first();
    expect($asset3)->not->toBeNull();
    expect($asset3->size)->toBe('1U Rack');

    // Check items created
    expect(CheckinBatchItem::where('checkin_batch_id', $batch->id)->count())->toBe(3);
    expect(AssetStatusLog::where('source_type', CheckinBatch::class)->where('source_id', $batch->id)->count())->toBe(3);
});

test('createFromSpreadsheet updates existing asset when updateExisting is true', function () {
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $warehouse = Warehouse::where('is_active', true)->first();
    $productLine = ProductLine::where('is_active', true)->first();

    // Pre-existing asset
    $existing = Asset::create([
        'serial_no' => 'EXIST-SN-888',
        'qr_code' => 'LED-EXIST-SN-888',
        'product_line_id' => $productLine->id,
        'size' => 'Old Size',
        'current_status' => AssetStatus::Repairing,
        'current_warehouse_id' => $warehouse->id,
    ]);

    $service = app(ProductionBatchService::class);
    $columnMap = [
        'serial_no' => 0,
        'size' => 1,
    ];
    $rows = [
        ['EXIST-SN-888', 'Updated Size 500x500mm'],
    ];

    $result = $service->createFromSpreadsheet(
        rows: $rows,
        columnMap: $columnMap,
        warehouse: $warehouse,
        updateExisting: true,
        createdBy: $user,
    );

    expect($result['updated_count'])->toBe(1);
    expect($result['created_count'])->toBe(0);

    $existing->refresh();
    expect($existing->size)->toBe('Updated Size 500x500mm');
    expect($existing->current_status)->toBe(AssetStatus::Ready);

    // Batch items should link existing asset
    expect(CheckinBatchItem::where('checkin_batch_id', $result['batch']->id)->where('asset_id', $existing->id)->exists())->toBeTrue();
});

test('CreateProductionBatchAction parses sheet data and maps columns correctly', function () {
    $sheetData = [
        'sheets' => [
            'sheet-01' => [
                'cellData' => [
                    0 => [
                        0 => ['v' => 'Số Seri'],
                        1 => ['v' => 'Dòng sản phẩm'],
                        2 => ['v' => 'Kho lưu trữ'],
                        3 => ['v' => 'Vị trí'],
                        4 => ['v' => 'Kích thước'],
                    ],
                    1 => [
                        0 => ['v' => 'SN-PARSE-01'],
                        1 => ['v' => 'P2.6 Sự kiện'],
                        2 => ['v' => 'Kho Hà Nội'],
                        3 => ['v' => 'HN-K1'],
                        4 => ['v' => '500x500 mm'],
                    ],
                ],
            ],
        ],
    ];

    $parsed = CreateProductionBatchAction::parseSheetData($sheetData);
    expect($parsed['headers'])->toHaveCount(5);
    expect($parsed['headers'][0])->toBe('Số Seri');
    expect($parsed['rows'])->toHaveCount(1);
    expect($parsed['rows'][0][0])->toBe('SN-PARSE-01');

    $mapped = CreateProductionBatchAction::mapColumns($parsed['headers']);
    expect($mapped)->toHaveKey('serial_no');
    expect($mapped)->toHaveKey('product_line');
    expect($mapped)->toHaveKey('warehouse');
    expect($mapped)->toHaveKey('location');
    expect($mapped)->toHaveKey('size');
    expect($mapped['serial_no'])->toBe(0);

    $mappedExpected = CreateProductionBatchAction::mapColumns(['Số Seri', 'Ngày dự kiến']);
    expect($mappedExpected)->toHaveKey('expected_date');
    expect($mappedExpected['expected_date'])->toBe(1);
});

test('createFromSpreadsheet stores expected_date on CheckinBatch', function () {
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $warehouse = Warehouse::where('is_active', true)->first();
    $service = app(ProductionBatchService::class);

    $columnMap = ['serial_no' => 0];
    $rows = [['EXPECTED-SN-001']];

    $result = $service->createFromSpreadsheet(
        rows: $rows,
        columnMap: $columnMap,
        warehouse: $warehouse,
        updateExisting: true,
        createdBy: $user,
        expectedDate: '2026-09-15',
    );

    $batch = $result['batch'];
    expect($batch->expected_date)->not->toBeNull();
    expect(Carbon::parse($batch->expected_date)->format('Y-m-d'))->toBe('2026-09-15');
});
