<?php

use App\Enums\AssignmentRole;
use App\Enums\MilestoneStatus;
use App\Enums\MilestoneType;
use App\Models\Customer;
use App\Models\EventAssignment;
use App\Models\EventMilestone;
use App\Models\Order;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\LedOsDataSeeder;
use Illuminate\Support\Facades\Artisan;

test('crew can be assigned to an order and managed', function () {
    (new LedOsDataSeeder)->run();

    $customer = Customer::first();
    $wh = Warehouse::first();
    $user = User::first();

    $order = Order::create([
        'order_no' => 'ORD-CREW-TEST',
        'customer_id' => $customer->id,
        'warehouse_id' => $wh->id,
        'request_date' => '2026-11-01',
        'expected_return_date' => '2026-11-04',
    ]);

    $assignment = EventAssignment::create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'role' => AssignmentRole::Lead,
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-04',
        'note' => 'Chỉ huy trưởng thi công',
    ]);

    expect($order->assignments)->toHaveCount(1)
        ->and($order->assignments->first()->role)->toBe(AssignmentRole::Lead)
        ->and($order->assignments->first()->user->id)->toBe($user->id);
});

test('timeline milestones can be added and converted to calendar events', function () {
    (new LedOsDataSeeder)->run();

    $customer = Customer::first();
    $wh = Warehouse::first();

    $order = Order::create([
        'order_no' => 'ORD-MILESTONE-TEST',
        'customer_id' => $customer->id,
        'warehouse_id' => $wh->id,
        'request_date' => '2026-11-01',
        'expected_return_date' => '2026-11-04',
        'event' => 'Sự kiện Triển lãm Quốc tế',
    ]);

    $milestone = EventMilestone::create([
        'order_id' => $order->id,
        'type' => MilestoneType::Setup,
        'planned_at' => now()->addDays(2),
        'status' => MilestoneStatus::Pending,
        'note' => 'Bắt đầu lắp dựng màn hình',
    ]);

    expect($order->milestones)->toHaveCount(1)
        ->and($order->milestones->first()->type)->toBe(MilestoneType::Setup);

    $calendarEvent = $milestone->toCalendarEvent();
    expect($calendarEvent->getTitle())->toContain('Lắp đặt khung');
});

test('maintenance reminders command identifies assets needing maintenance', function () {
    (new LedOsDataSeeder)->run();

    $exitCode = Artisan::call('maintenance:check-reminders', ['--threshold' => 5, '--days' => 60]);
    expect($exitCode)->toBe(0);
});
