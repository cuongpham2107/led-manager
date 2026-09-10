<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Filament\Resources\CheckinBatches\Pages\CreateCheckinBatch;
use App\Filament\Resources\CheckinBatches\Pages\EditCheckinBatch;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

beforeEach(function () {
    (new LedOsDataSeeder)->run();
});

test('checkin batch create form exposes the quick import action in the asset selector', function () {
    $user = User::where('email', 'admin@ledmanager.com')->firstOrFail();

    Livewire::actingAs($user)
        ->test(CreateCheckinBatch::class)
        ->assertActionExists('import_checkin_batch_items');

    $selector = file_get_contents(resource_path('views/filament/components/checkin-batch-assets-selector.blade.php'));

    expect($selector)->toContain('x-if="!isEdit"')
        ->and($selector)->toContain("\$wire.mountAction('import_checkin_batch_items')")
        ->and($selector)->toContain('Import &amp; tạo nhanh thiết bị')
        ->and($selector)->toContain('onAssetsImported($event.detail)');
});

test('quick import in create mode creates assets and dispatches them to the form', function () {
    $user = User::where('email', 'admin@ledmanager.com')->firstOrFail();
    $warehouse = Warehouse::where('is_active', true)->firstOrFail();

    $serial = 'QUICK-IMPORT-TEST-001';
    expect(Asset::where('serial_no', $serial)->exists())->toBeFalse();

    $sheetData = json_encode([
        'sheetOrder' => ['s1'],
        'sheets' => [
            's1' => [
                'cellData' => [
                    0 => [
                        0 => ['v' => 'Số Seri'],
                        1 => ['v' => 'Dòng sản phẩm'],
                        2 => ['v' => 'Kích thước'],
                    ],
                    1 => [
                        0 => ['v' => $serial],
                        1 => ['v' => ''],
                        2 => ['v' => '500×500 mm'],
                    ],
                ],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(CreateCheckinBatch::class)
        ->callAction('import_checkin_batch_items', data: [
            'sheet_data' => $sheetData,
            'create_if_not_exists' => true,
            'default_warehouse_id' => $warehouse->id,
        ])
        ->assertDispatched('checkin-assets-imported');

    $asset = Asset::where('serial_no', $serial)->first();
    expect($asset)->not->toBeNull()
        ->and($asset->current_warehouse_id)->toBe($warehouse->id)
        ->and($asset->current_status)->toBe(AssetStatus::NewlyAdded);
});

test('quick import accepts pasted excel rows (tab separated) in create mode', function () {
    $user = User::where('email', 'admin@ledmanager.com')->firstOrFail();
    $warehouse = Warehouse::where('is_active', true)->firstOrFail();

    $serialA = 'PASTE-IMPORT-A-001';
    $serialB = 'PASTE-IMPORT-B-002';

    $pasted = "Số Seri\tDòng sản phẩm\tKích thước\n"
        ."{$serialA}\tP2.6 Sự kiện\t500×500 mm\n"
        ."{$serialB}\tP2.6 Sự kiện\t500×500 mm";

    Livewire::actingAs($user)
        ->test(CreateCheckinBatch::class)
        ->callAction('import_checkin_batch_items', data: [
            'sheet_data' => $pasted,
            'create_if_not_exists' => true,
            'default_warehouse_id' => $warehouse->id,
        ])
        ->assertDispatched('checkin-assets-imported');

    expect(Asset::where('serial_no', $serialA)->exists())->toBeTrue()
        ->and(Asset::where('serial_no', $serialB)->exists())->toBeTrue();
});

test('import into a saved batch on the edit page creates checkin items', function () {
    $user = User::where('email', 'admin@ledmanager.com')->firstOrFail();
    $warehouse = Warehouse::where('is_active', true)->firstOrFail();

    $batch = CheckinBatch::factory()->create([
        'warehouse_id' => $warehouse->id,
        'status' => BatchStatus::Pending,
    ]);

    $serial = 'EDIT-IMPORT-TEST-001';
    $pasted = "Số Seri\tKích thước\n{$serial}\t500×500 mm";

    Livewire::actingAs($user)
        ->test(EditCheckinBatch::class, ['record' => $batch->getRouteKey()])
        ->callAction('import_checkin_batch_items', data: [
            'sheet_data' => $pasted,
            'create_if_not_exists' => true,
        ]);

    $asset = Asset::where('serial_no', $serial)->firstOrFail();
    $item = CheckinBatchItem::where('checkin_batch_id', $batch->id)->where('asset_id', $asset->id)->firstOrFail();

    // Thiết bị tạo mới có trạng thái "Mới" và item ở dạng chờ nhận.
    expect($asset->current_status)->toBe(AssetStatus::NewlyAdded)
        ->and((bool) $item->is_received)->toBeFalse();

    // Hoàn tất đợt nhập -> thiết bị chuyển "Sẵn sàng".
    $batch->complete($user);
    expect($asset->refresh()->current_status)->toBe(AssetStatus::Ready);
});
