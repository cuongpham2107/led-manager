<?php

use App\Models\Asset;
use App\Models\DeviceType;
use App\Models\ProductLine;
use App\Models\Warehouse;
use App\Services\DepreciationService;
use Database\Seeders\LedOsDataSeeder;
use Illuminate\Support\Facades\Artisan;

test('depreciation service correctly calculates straight line monthly depreciation', function () {
    (new LedOsDataSeeder)->run();

    $service = app(DepreciationService::class);
    $dt = DeviceType::first();
    $pl = ProductLine::first();
    $wh = Warehouse::first();

    $asset = Asset::create([
        'serial_no' => 'ASSET-DEP-001',
        'device_type_id' => $dt->id,
        'product_line_id' => $pl->id,
        'current_warehouse_id' => $wh->id,
        'purchase_cost' => 36000000,
        'salvage_value' => 0,
        'useful_life_months' => 36,
        'accumulated_depreciation' => 0,
    ]);

    expect($asset->monthly_depreciation)->toBe(1000000.0)
        ->and($asset->current_book_value)->toBe(36000000.0);

    $processed = $service->processMonthlyDepreciation();
    expect($processed)->toBeGreaterThan(0);

    $asset->refresh();
    expect((float) $asset->accumulated_depreciation)->toBe(1000000.0)
        ->and($asset->current_book_value)->toBe(35000000.0);
});

test('calculate depreciation artisan command executes successfully', function () {
    (new LedOsDataSeeder)->run();

    $exitCode = Artisan::call('assets:calculate-depreciation');
    expect($exitCode)->toBe(0);
});
