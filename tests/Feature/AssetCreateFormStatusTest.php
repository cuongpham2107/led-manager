<?php

use App\Enums\AssetStatus;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Models\Asset;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

beforeEach(function () {
    (new LedOsDataSeeder)->run();
});

test('create asset modal hides current_status and operation history section and sets default dates and status', function () {
    $admin = User::where('email', 'admin@ledmanager.com')->first();
    $warehouse = Warehouse::first();
    $productLine = ProductLine::first();

    Livewire::actingAs($admin)
        ->test(ListAssets::class)
        ->assertActionExists('create')
        ->callAction('create', [
            'serial_no' => 'TEST-SERIAL-999',
            'product_line_id' => $productLine->id,
            'current_warehouse_id' => $warehouse->id,
        ])
        ->assertHasNoActionErrors();

    $asset = Asset::where('serial_no', 'TEST-SERIAL-999')->first();
    expect($asset)->not->toBeNull()
        ->and($asset->current_status)->toBe(AssetStatus::Ready)
        ->and($asset->manufactured_date?->toDateString())->toBe(now()->toDateString())
        ->and($asset->purchase_date?->toDateString())->toBe(now()->toDateString());
});

test('edit asset modal allows modifying status and operation history fields', function () {
    $admin = User::where('email', 'admin@ledmanager.com')->first();
    $asset = Asset::first();

    Livewire::actingAs($admin)
        ->test(ListAssets::class)
        ->callTableAction('edit', $asset, data: [
            'serial_no' => $asset->serial_no,
            'current_status' => AssetStatus::Repairing->value,
            'current_warehouse_id' => $asset->current_warehouse_id,
            'manufactured_date' => '2025-01-15',
            'purchase_date' => '2025-02-20',
            'operating_hours' => 500,
        ])
        ->assertHasNoTableActionErrors();

    $asset->refresh();
    expect($asset->current_status)->toBe(AssetStatus::Repairing)
        ->and($asset->manufactured_date?->toDateString())->toBe('2025-01-15')
        ->and($asset->purchase_date?->toDateString())->toBe('2025-02-20')
        ->and($asset->operating_hours)->toBe(500);
});
