<?php

use App\Enums\AssignmentRole;
use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use App\Models\Order;
use Database\Seeders\LedOsDataSeeder;

test('event assignments and milestones are tracked correctly per order', function () {
    (new LedOsDataSeeder)->run();

    $order = Order::where('order_no', 'ORD-2608-01')->first();
    expect($order)->not->toBeNull()
        ->and($order->assignments)->toHaveCount(2)
        ->and($order->milestones)->toHaveCount(5);

    $leadTech = $order->assignments()->where('role', AssignmentRole::Lead)->first();
    expect($leadTech)->not->toBeNull()
        ->and($leadTech->user)->not->toBeNull();

    $setupMilestone = $order->milestones()->where('type', MilestoneType::Setup)->first();
    expect($setupMilestone)->not->toBeNull()
        ->and($setupMilestone->status)->toBe(MilestoneStatus::Pending);
});
