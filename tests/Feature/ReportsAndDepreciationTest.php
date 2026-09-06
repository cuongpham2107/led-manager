<?php

use App\Filament\Pages\AssetUtilizationReport;
use App\Filament\Pages\InventoryReport;
use App\Filament\Pages\LostDealReport;
use App\Filament\Pages\MovementHistoryReport;
use App\Filament\Pages\RepairFrequencyReport;
use App\Filament\Pages\RevenueReport;
use App\Filament\Pages\SalesConversionReport;
use App\Models\Asset;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\DepreciationService;
use Database\Seeders\LedOsDataSeeder;
use Illuminate\Support\Facades\Artisan;

test('depreciation service correctly calculates straight line monthly depreciation', function () {
    (new LedOsDataSeeder)->run();

    $service = app(DepreciationService::class);
    $pl = ProductLine::first();
    $wh = Warehouse::first();

    $asset = Asset::create([
        'serial_no' => 'ASSET-DEP-001',
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

test('only the 4 expected report pages are registered in navigation under group Bao cao', function () {
    expect(InventoryReport::getNavigationGroup())->toBe('Báo cáo')
        ->and(InventoryReport::getNavigationLabel())->toBe('Tồn kho')
        ->and(InventoryReport::getNavigationSort())->toBe(1)
        ->and(InventoryReport::shouldRegisterNavigation())->toBeTrue();

    expect(AssetUtilizationReport::getNavigationGroup())->toBe('Báo cáo')
        ->and(AssetUtilizationReport::getNavigationLabel())->toBe('Sử dụng tài sản')
        ->and(AssetUtilizationReport::getNavigationSort())->toBe(2)
        ->and(AssetUtilizationReport::shouldRegisterNavigation())->toBeTrue();

    expect(MovementHistoryReport::getNavigationGroup())->toBe('Báo cáo')
        ->and(MovementHistoryReport::getNavigationLabel())->toBe('Lịch sử nhập/xuất')
        ->and(MovementHistoryReport::getNavigationSort())->toBe(3)
        ->and(MovementHistoryReport::shouldRegisterNavigation())->toBeTrue();

    expect(RevenueReport::getNavigationGroup())->toBe('Báo cáo')
        ->and(RevenueReport::getNavigationLabel())->toBe('Doanh thu')
        ->and(RevenueReport::getNavigationSort())->toBe(4)
        ->and(RevenueReport::shouldRegisterNavigation())->toBeTrue();

    // The other report pages should be hidden from navigation
    expect(SalesConversionReport::shouldRegisterNavigation())->toBeFalse()
        ->and(LostDealReport::shouldRegisterNavigation())->toBeFalse()
        ->and(RepairFrequencyReport::shouldRegisterNavigation())->toBeFalse();
});

test('super admin can access and render all 4 report pages', function () {
    (new LedOsDataSeeder)->run();
    $admin = User::where('email', 'admin@ledmanager.com')->first();

    $this->actingAs($admin)
        ->get(InventoryReport::getUrl())
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get(AssetUtilizationReport::getUrl())
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get(MovementHistoryReport::getUrl())
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get(RevenueReport::getUrl())
        ->assertSuccessful();
});
