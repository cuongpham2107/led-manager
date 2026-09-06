<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductLine;
use App\Models\Warehouse;
use App\Services\AvailabilityService;
use Database\Seeders\LedOsDataSeeder;

test('availability service correctly calculates available stock considering overlapping orders', function () {
    (new LedOsDataSeeder)->run();

    $service = new AvailabilityService;
    $pl = ProductLine::where('code', 'P2.6')->first();
    $wh = Warehouse::where('code', 'WH-HN')->first();

    // Check available count in future dates
    $availFuture = $service->getAvailableCount($pl->id, '2026-11-01', '2026-11-05', $wh->id);
    expect($availFuture)->toBeGreaterThan(0);

    // Create a new order that books 50 cabinets
    $order = Order::create([
        'order_no' => 'ORD-TEST-AVAIL',
        'customer_id' => 1,
        'warehouse_id' => $wh->id,
        'request_date' => '2026-11-01',
        'expected_return_date' => '2026-11-05',
        'status' => OrderStatus::Draft,
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_line_id' => $pl->id,
        'quantity_required' => 50,
        'unit_price' => 400000,
    ]);

    // Now available stock for these dates should be reduced by 50
    $availAfterBooking = $service->getAvailableCount($pl->id, '2026-11-01', '2026-11-05', $wh->id);
    expect($availAfterBooking)->toBe($availFuture - 50);

    // For a non-overlapping date, stock should remain unaffected
    $availOtherDates = $service->getAvailableCount($pl->id, '2026-12-01', '2026-12-05', $wh->id);
    expect($availOtherDates)->toBe($availFuture);
});
