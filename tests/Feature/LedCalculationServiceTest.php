<?php

use App\Models\ProductLine;
use App\Services\LedCalculationService;
use Database\Seeders\LedOsDataSeeder;

test('led calculation service computes derived configuration and BOM accurately', function () {
    (new LedOsDataSeeder)->run();

    $service = new LedCalculationService;

    $p26 = ProductLine::where('code', 'P2.6')->first();

    $config = $service->deriveConfiguration(6.0, 3.5, $p26);

    expect($config['wall_area'])->toBe(21.0)
        ->and($config['grid_cols'])->toBe(12)
        ->and($config['grid_rows'])->toBe(7)
        ->and($config['grid_display'])->toBe('12 × 7')
        ->and($config['cabinets_qty'])->toBe(84)
        ->and($config['resolution_display'])->toBe('2308 × 1346')
        ->and($config['load_kg'])->toBe(631.2)
        ->and($config['peak_power_kw'])->toBe(31.9)
        ->and($config['flight_cases'])->toBe(14);

    $bom = $service->generateBom(6.0, 3.5, $p26);

    expect($bom)->toBeArray()
        ->and(count($bom))->toBe(1)
        ->and($bom[0]['qty'])->toBe(84)
        ->and($bom[0]['product_line_id'])->not->toBeNull()
        ->and($bom[0]['unit_cost'])->toBeGreaterThan(0);

    $pricing = $service->calculatePricing(6.0, 3.5, $p26, rentalDays: 3, crewSize: 4, transportDistanceKm: 45.0);

    expect($pricing['equipment_rental'])->toBeGreaterThan(0)
        ->and($pricing['crew_labour'])->toEqual(19200000)
        ->and($pricing['total_price'])->toBeGreaterThan(0);
});

test('pricing rules apply tiered discount for longer rental days and agency customer', function () {
    (new LedOsDataSeeder)->run();

    $service = new LedCalculationService;
    $p26 = ProductLine::where('code', 'P2.6')->first();

    // 1 day standard
    $rate1 = $service->resolvePricing($p26, rentalDays: 1);
    expect($rate1['base_price'])->toEqual(400000);

    // 3 days tiered discount (350k/day)
    $rate3 = $service->resolvePricing($p26, rentalDays: 3);
    expect($rate3['base_price'])->toEqual(350000);

    // Agency customer
    $rateAgency = $service->resolvePricing($p26, rentalDays: 1, customerType: 'agency');
    expect($rateAgency['base_price'])->toEqual(350000)
        ->and($rateAgency['discount_percent'])->toEqual(10);
});
