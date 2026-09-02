<?php

use App\Enums\ContractStatus;
use App\Models\Order;
use Database\Seeders\LedOsDataSeeder;

test('orders track deposit and total paid correctly', function () {
    (new LedOsDataSeeder)->run();

    $order = Order::where('order_no', 'ORD-2608-01')->first();

    expect($order)->not->toBeNull()
        ->and($order->deposit_paid)->toBeGreaterThan(0)
        ->and($order->total_paid)->toBeGreaterThanOrEqual($order->deposit_paid);

    $contract = $order->contracts->first();

    expect($contract)->not->toBeNull()
        ->and($contract->status)->toBe(ContractStatus::Active)
        ->and($contract->remaining_debt)->toEqual(max(0, (float) $contract->contract_value - (float) $order->total_paid));
});
