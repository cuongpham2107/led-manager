<?php

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Models\Asset;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\LedOsDataSeeder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use OpenSpout\Reader\XLSX\Reader;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

beforeEach(function () {
    (new LedOsDataSeeder)->run();
    Storage::fake('local');
});

test('admin can export assets to genuine xlsx excel with separate columns', function () {
    $admin = User::where('email', 'admin@ledmanager.com')->first();
    $this->actingAs($admin);

    $component = Livewire::actingAs($admin)->test(ListAssets::class);

    $response = $component->instance()->exportExcel();

    expect($response)->toBeInstanceOf(BinaryFileResponse::class);

    $filePath = $response->getFile()->getPathname();
    expect(file_exists($filePath))->toBeTrue();

    // Verify it is a valid XLSX file readable by OpenSpout
    $reader = new Reader;
    $reader->open($filePath);
    $rows = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = $row->toArray();
        }
        break;
    }
    $reader->close();

    // Check headers - each in its own column!
    $headers = $rows[0] ?? [];
    expect($headers)->toContain('Số Seri')
        ->and($headers)->toContain('Mã QR')
        ->and($headers)->toContain('Dòng sản phẩm')
        ->and($headers)->toContain('Kho hiện tại')
        ->and($headers)->toContain('Vị trí trong kho')
        ->and($headers)->toContain('Trạng thái')
        ->and(count($headers))->toBeGreaterThanOrEqual(10);

    // Verify first asset serial exists in column 0
    $firstAsset = Asset::where('current_warehouse_id', $admin->warehouse_id)->first() ?? Asset::first();
    $found = false;
    foreach ($rows as $row) {
        if (($row[0] ?? '') === $firstAsset->serial_no) {
            $found = true;
            break;
        }
    }
    expect($found)->toBeTrue();
});

test('scoped warehouse user only exports assets of their assigned warehouse', function () {
    $warehouse = Warehouse::first();
    $user = User::factory()->create([
        'warehouse_id' => $warehouse->id,
    ]);
    $user->assignRole('warehouse_manager');

    $this->actingAs($user);

    $otherWarehouse = Warehouse::where('id', '!=', $warehouse->id)->first();
    $otherAsset = Asset::where('current_warehouse_id', $otherWarehouse->id)->first();
    $ownAsset = Asset::where('current_warehouse_id', $warehouse->id)->first();

    $component = Livewire::actingAs($user)->test(ListAssets::class);
    $response = $component->instance()->exportExcel();

    $reader = new Reader;
    $reader->open($response->getFile()->getPathname());
    $serialList = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $serialList[] = $row->toArray()[0] ?? '';
        }
        break;
    }
    $reader->close();

    expect($serialList)->toContain($ownAsset->serial_no);
    if ($otherAsset) {
        expect($serialList)->not->toContain($otherAsset->serial_no);
    }
});

test('super admin exports assets across all warehouses regardless of assigned warehouse', function () {
    $warehouse = Warehouse::first();
    $admin = User::factory()->create([
        'warehouse_id' => $warehouse->id,
    ]);
    $admin->assignRole('super_admin');

    $this->actingAs($admin);

    $otherWarehouse = Warehouse::where('id', '!=', $warehouse->id)->first();
    $otherAsset = Asset::where('current_warehouse_id', $otherWarehouse->id)->first();
    $ownAsset = Asset::where('current_warehouse_id', $warehouse->id)->first();

    $component = Livewire::actingAs($admin)->test(ListAssets::class);
    $response = $component->instance()->exportExcel();

    $reader = new Reader;
    $reader->open($response->getFile()->getPathname());
    $serialList = [];
    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $serialList[] = $row->toArray()[0] ?? '';
        }
        break;
    }
    $reader->close();

    expect($serialList)->toContain($ownAsset->serial_no);
    if ($otherAsset) {
        expect($serialList)->toContain($otherAsset->serial_no);
    }
});

test('univer sheet initial data contains preconfigured column headers without ma qr and sample row', function () {
    $admin = User::where('email', 'admin@ledmanager.com')->first();
    $this->actingAs($admin);

    $component = Livewire::actingAs($admin)->test(ListAssets::class);
    $data = $component->instance()->getDefaultSheetData();

    expect($data)->toHaveKey('sheets');
    $sheet = $data['sheets']['sheet-led-01'] ?? [];
    expect($sheet)->toHaveKey('cellData');

    $cellData = $sheet['cellData'];
    $headers = collect($cellData[0])->pluck('v')->toArray();
    expect($headers)->not->toContain('Trạng thái')
        ->and($headers)->not->toContain('Mã QR')
        ->and($cellData[0][0]['v'])->toBe('Số Seri')
        ->and($cellData[0][1]['v'])->toBe('Dòng sản phẩm')
        ->and($cellData[0][2]['v'])->toBe('Kho lưu trữ')
        ->and($cellData[0][3]['v'])->toBe('Vị trí')
        ->and($cellData[1][0]['v'])->toBe('P26-HN-SAMPLE01');
});

test('user can import assets directly from univer sheet snapshot data', function () {
    $admin = User::where('email', 'admin@ledmanager.com')->first();
    $this->actingAs($admin);

    $pl = ProductLine::first();
    $wh = Warehouse::with('locations')->first();
    $loc = $wh->locations->first();

    // Prepare simulated Univer Sheet snapshot
    $snapshot = [
        'id' => 'workbook-led',
        'sheets' => [
            'sheet-led-01' => [
                'cellData' => [
                    0 => [
                        0 => ['v' => 'Số Seri'],
                        1 => ['v' => 'Dòng sản phẩm'],
                        2 => ['v' => 'Kho lưu trữ'],
                        3 => ['v' => 'Vị trí'],
                        4 => ['v' => 'Kích thước'],
                        5 => ['v' => 'Trạng thái'],
                        6 => ['v' => 'Ngày mua'],
                        7 => ['v' => 'Nguyên giá'],
                        8 => ['v' => 'Ghi chú'],
                    ],
                    1 => [
                        0 => ['v' => 'P26-HN-SAMPLE01'], // Sample row to be skipped
                        1 => ['v' => 'Sample'],
                    ],
                    2 => [
                        0 => ['v' => 'UNIVER-LED-001'],
                        1 => ['v' => $pl->name],
                        2 => ['v' => $wh->name],
                        3 => ['v' => $loc->name],
                        4 => ['v' => '500×500 mm'],
                        5 => ['v' => 'Sẵn sàng trong kho'],
                        6 => ['v' => '15/01/2026'],
                        7 => ['v' => 3800000],
                        8 => ['v' => 'Direct from Univer Sheet'],
                    ],
                    3 => [
                        0 => ['v' => 'UNIVER-LED-002'],
                        1 => ['v' => $pl->name],
                        2 => ['v' => $wh->name],
                        3 => ['v' => $loc->name],
                        4 => ['v' => '500×1000 mm'],
                        5 => ['v' => 'Đang bảo dưỡng'],
                        6 => ['v' => '20/01/2026'],
                        7 => ['v' => 4500000],
                        8 => ['v' => 'Univer item 2'],
                    ],
                ],
            ],
        ],
    ];

    Livewire::actingAs($admin)
        ->test(ListAssets::class)
        ->callAction('importExcel', [
            'sheet_data' => $snapshot,
            'default_product_line_id' => $pl->id,
            'default_warehouse_id' => $wh->id,
            'update_existing' => true,
        ])
        ->assertHasNoActionErrors();

    // Sample row should be skipped
    expect(Asset::where('serial_no', 'P26-HN-SAMPLE01')->exists())->toBeFalse();

    // Real rows imported
    $asset1 = Asset::where('serial_no', 'UNIVER-LED-001')->first();
    expect($asset1)->not->toBeNull()
        ->and($asset1->qr_code)->toBe('LED-UNIVER-LED-001')
        ->and($asset1->product_line_id)->toBe($pl->id)
        ->and($asset1->current_warehouse_id)->toBe($wh->id)
        ->and($asset1->warehouse_location_id)->toBe($loc->id)
        ->and((float) $asset1->purchase_cost)->toBe(3800000.0)
        ->and($asset1->note)->toBe('Direct from Univer Sheet');

    $asset2 = Asset::where('serial_no', 'UNIVER-LED-002')->first();
    expect($asset2)->not->toBeNull()
        ->and($asset2->current_status)->toBe(AssetStatus::Repairing);
});

test('univer sheet updates existing asset when update_existing is true', function () {
    $admin = User::where('email', 'admin@ledmanager.com')->first();
    $this->actingAs($admin);

    $existing = Asset::first();

    $snapshot = [
        'id' => 'workbook-led',
        'sheets' => [
            'sheet-led-01' => [
                'cellData' => [
                    0 => [
                        0 => ['v' => 'Số Seri'],
                        1 => ['v' => 'Nguyên giá'],
                        2 => ['v' => 'Ghi chú'],
                    ],
                    1 => [
                        0 => ['v' => $existing->serial_no],
                        1 => ['v' => 7777000],
                        2 => ['v' => 'Updated via Univer Sheet'],
                    ],
                ],
            ],
        ],
    ];

    Livewire::actingAs($admin)
        ->test(ListAssets::class)
        ->callAction('importExcel', [
            'sheet_data' => $snapshot,
            'update_existing' => true,
        ])
        ->assertHasNoActionErrors();

    $existing->refresh();
    expect((float) $existing->purchase_cost)->toBe(7777000.0)
        ->and($existing->note)->toBe('Updated via Univer Sheet');
});

test('univer sheet applies default product line, warehouse, and location selects when columns are omitted', function () {
    $admin = User::where('email', 'admin@ledmanager.com')->first();
    $this->actingAs($admin);

    $pl = ProductLine::first();
    $wh = Warehouse::with('locations')->first();
    $loc = $wh->locations->first();

    $snapshot = [
        'id' => 'workbook-led',
        'sheets' => [
            'sheet-led-01' => [
                'cellData' => [
                    0 => [
                        0 => ['v' => 'Số Seri'],
                    ],
                    1 => [
                        0 => ['v' => 'ONLY-SERIAL-001'],
                    ],
                ],
            ],
        ],
    ];

    Livewire::actingAs($admin)
        ->test(ListAssets::class)
        ->callAction('importExcel', [
            'sheet_data' => $snapshot,
            'default_product_line_id' => $pl->id,
            'default_warehouse_id' => $wh->id,
            'default_warehouse_location_id' => $loc->id,
            'update_existing' => true,
        ])
        ->assertHasNoActionErrors();

    $asset = Asset::where('serial_no', 'ONLY-SERIAL-001')->first();
    expect($asset)->not->toBeNull()
        ->and($asset->qr_code)->toBe('LED-ONLY-SERIAL-001')
        ->and($asset->product_line_id)->toBe($pl->id)
        ->and($asset->current_warehouse_id)->toBe($wh->id)
        ->and($asset->warehouse_location_id)->toBe($loc->id);
});

test('asset model automatically generates qr_code from serial_no if empty on save', function () {
    $asset = Asset::create([
        'serial_no' => 'AUTO-QR-TEST-999',
        'product_line_id' => ProductLine::first()->id,
        'current_status' => AssetStatus::Ready,
    ]);

    expect($asset->qr_code)->toBe('LED-AUTO-QR-TEST-999');
});
