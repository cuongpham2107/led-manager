<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Actions\CreateCheckoutBatchAction;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Asset;
use App\Models\CheckoutBatch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('create checkout batch action automatically assigns 36 assets from warehouse', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $customer = Customer::first();
    $warehouse = Warehouse::first();
    $productLine = ProductLine::first();

    $order = Order::create([
        'order_no' => 'ORD-TEST-ASSIGN-36',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'product_line_id' => $productLine->id,
        'request_date' => now()->toDateString(),
        'expected_return_date' => now()->addDays(3)->toDateString(),
        'area_m2' => 18.0,
        'value' => 50000000,
        'status' => OrderStatus::Draft,
        'event' => 'Sự kiện kiểm tra xuất 36 thiết bị',
    ]);

    OrderItem::create([
        'order_id' => $order->id,
        'product_line_id' => $productLine->id,
        'quantity_required' => 36,
        'unit_price' => 500000,
        'note' => 'Cabinet LED sự kiện',
    ]);

    // Check resolveRequiredQuantity
    expect(CreateCheckoutBatchAction::resolveRequiredQuantity($order))->toBe(36);

    Livewire::test(ListOrders::class)
        ->callTableAction('create_checkout_batch', $order, [
            'auto_assign_assets' => true,
            'quantity' => 36,
            'mark_dispatched' => true,
        ])
        ->assertHasNoTableActionErrors();

    // Order status should be OutboundCreated
    expect($order->fresh()->status)->toBe(OrderStatus::OutboundCreated);

    // Checkout batch should exist
    $batch = CheckoutBatch::where('order_id', $order->id)->first();
    expect($batch)->not->toBeNull()
        ->and($batch->status)->toBe(BatchStatus::InProgress)
        ->and($batch->items)->toHaveCount(36);

    // Every item should be marked as dispatched
    expect($batch->items()->where('is_dispatched', true)->count())->toBe(36);

    // Assets should be transitioned to InTransit
    $assignedAssetIds = $batch->items->pluck('asset_id');
    $inTransitCount = Asset::whereIn('id', $assignedAssetIds)
        ->where('current_status', AssetStatus::InTransit)
        ->count();
    expect($inTransitCount)->toBe(36);
});

test('create checkout batch action respects unticked auto_assign_assets toggle', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $customer = Customer::first();
    $warehouse = Warehouse::first();

    $order = Order::create([
        'order_no' => 'ORD-TEST-MANUAL-01',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'request_date' => now()->toDateString(),
        'area_m2' => 10.0,
        'value' => 20000000,
        'status' => OrderStatus::Draft,
        'event' => 'Sự kiện xuất thủ công',
    ]);

    Livewire::test(ListOrders::class)
        ->callTableAction('create_checkout_batch', $order, [
            'auto_assign_assets' => false,
        ])
        ->assertHasNoTableActionErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::OutboundCreated);

    $batch = CheckoutBatch::where('order_id', $order->id)->first();
    expect($batch)->not->toBeNull()
        ->and($batch->status)->toBe(BatchStatus::Pending)
        ->and($batch->items)->toHaveCount(0);
});
