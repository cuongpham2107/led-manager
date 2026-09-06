<?php

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Warehouse;
use App\Services\AvailabilityService;
use App\Services\InventoryReservationService;
use Database\Seeders\LedOsDataSeeder;

test('soft lock reserves stock temporarily and releases correctly', function () {
    (new LedOsDataSeeder)->run();

    $availabilityService = app(AvailabilityService::class);
    $reservationService = app(InventoryReservationService::class);

    $pl = ProductLine::where('code', 'P2.6')->first();
    $wh = Warehouse::where('code', 'WH-HN')->first();
    $customer = Customer::first();

    $initialAvailable = $availabilityService->getAvailableCount($pl->id, '2026-11-10', '2026-11-15', $wh->id);

    // Create a quotation with 40 cabinets
    $quotation = Quotation::create([
        'code' => 'QUO-TEST-LOCK-01',
        'customer_id' => $customer->id,
        'event_start_date' => '2026-11-10',
        'event_end_date' => '2026-11-15',
        'status' => 'sent',
    ]);

    QuotationItem::create([
        'quotation_id' => $quotation->id,
        'product_line_id' => $pl->id,
        'quantity' => 40,
        'unit_price' => 500000,
    ]);

    // Apply soft lock
    $reservationService->softLock($quotation, 48);

    // Available count should be reduced by 40
    $availAfterSoftLock = $availabilityService->getAvailableCount($pl->id, '2026-11-10', '2026-11-15', $wh->id);
    expect($availAfterSoftLock)->toBe($initialAvailable - 40);

    // Release soft lock
    $reservationService->release($quotation->id);

    // Stock should be restored
    $availAfterRelease = $availabilityService->getAvailableCount($pl->id, '2026-11-10', '2026-11-15', $wh->id);
    expect($availAfterRelease)->toBe($initialAvailable);
});

test('hard lock creates confirmed reservation for contract', function () {
    (new LedOsDataSeeder)->run();

    $reservationService = app(InventoryReservationService::class);
    $customer = Customer::first();
    $wh = Warehouse::where('code', 'WH-HN')->first();
    $pl = ProductLine::where('code', 'P2.6')->first();

    $order = Order::create([
        'order_no' => 'ORD-TEST-HARDLOCK',
        'customer_id' => $customer->id,
        'warehouse_id' => $wh->id,
        'request_date' => '2026-11-20',
        'expected_return_date' => '2026-11-25',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_line_id' => $pl->id,
        'quantity_required' => 20,
        'unit_price' => 400000,
    ]);

    $contract = Contract::create([
        'code' => 'HD-TEST-001',
        'customer_id' => $customer->id,
        'order_id' => $order->id,
        'start_date' => '2026-11-20',
        'end_date' => '2026-11-25',
        'contract_value' => 20000000,
    ]);

    $reservations = $reservationService->hardLock($contract);
    expect($reservations)->toHaveCount(1)
        ->and($reservations->first()->lock_type)->toBe('hard')
        ->and($reservations->first()->quantity)->toBe(20);
});
