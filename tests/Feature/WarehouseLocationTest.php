<?php

use App\Filament\Resources\WarehouseLocations\WarehouseLocationResource;
use App\Models\Asset;
use App\Models\Warehouse;
use Database\Seeders\LedOsDataSeeder;

test('warehouse has many locations and asset belongs to location', function () {
    (new LedOsDataSeeder)->run();

    $warehouse = Warehouse::where('code', 'WH-HN')->first();
    expect($warehouse->locations)->not->toBeEmpty();

    $location = $warehouse->locations->first();
    expect($location->warehouse->id)->toBe($warehouse->id);

    $asset = Asset::where('warehouse_location_id', $location->id)->first();
    if ($asset) {
        expect($asset->warehouseLocation->id)->toBe($location->id)
            ->and($asset->location_label)->toContain($location->name);
    }
});

test('warehouse locations resource does not register in navigation', function () {
    expect(WarehouseLocationResource::shouldRegisterNavigation())->toBeFalse();
});
