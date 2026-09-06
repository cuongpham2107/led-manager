<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\RepairResultStatus;
use App\Enums\ReturnBatchStatus;
use App\Enums\ReturnGrade;
use App\Filament\Resources\CheckoutBatches\Pages\ListCheckoutBatches;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Asset;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\RepairLog;
use App\Models\ReturnBatch;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('order return action successfully creates return batch, transitions asset statuses, and generates repair log', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $customer = Customer::first();
    $warehouse = Warehouse::first();

    $order = Order::create([
        'order_no' => 'ORD-TEST-RET-01',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'request_date' => now()->toDateString(),
        'value' => 100000000,
        'status' => OrderStatus::Dispatched,
        'event' => 'Sự kiện Triển lãm Quốc tế 2026',
    ]);

    // Create a checkout batch for this order
    $checkoutBatch = CheckoutBatch::create([
        'code' => 'OUT-TEST-01',
        'order_id' => $order->id,
        'customer_id' => $order->customer_id,
        'warehouse_id' => $order->warehouse_id,
        'status' => BatchStatus::Dispatched,
        'created_by' => $user->id,
    ]);

    $assets = Asset::take(2)->get();
    $normalAsset = $assets[0];
    $damagedAsset = $assets[1];

    $normalAsset->update(['current_status' => AssetStatus::InEvent]);
    $damagedAsset->update(['current_status' => AssetStatus::InEvent]);

    $item1 = CheckoutBatchItem::create([
        'checkout_batch_id' => $checkoutBatch->id,
        'asset_id' => $normalAsset->id,
        'is_dispatched' => true,
    ]);

    $item2 = CheckoutBatchItem::create([
        'checkout_batch_id' => $checkoutBatch->id,
        'asset_id' => $damagedAsset->id,
        'is_dispatched' => true,
    ]);

    Livewire::test(ListOrders::class)
        ->callTableAction('return_order', $order, [
            'return_date' => now()->toDateString(),
            'received_by' => $user->id,
            'note' => 'Thu hồi sau sự kiện triển lãm',
            'items' => [
                [
                    'asset_id' => $normalAsset->id,
                    'checkout_batch_item_id' => $item1->id,
                    'is_received' => true,
                    'grade' => ReturnGrade::Normal->value,
                    'grade_note' => null,
                ],
                [
                    'asset_id' => $damagedAsset->id,
                    'checkout_batch_item_id' => $item2->id,
                    'is_received' => true,
                    'grade' => ReturnGrade::Damaged->value,
                    'grade_note' => 'Chết 3 bóng LED hàng thứ 2',
                ],
            ],
        ])
        ->assertHasNoTableActionErrors();

    // Verify order status transitioned to Returned
    expect($order->fresh()->status)->toBe(OrderStatus::Returned);

    // Verify checkout batch completed
    expect($checkoutBatch->fresh()->status)->toBe(BatchStatus::Completed);

    // Verify ReturnBatch created (gộp từ nhiều đợt xuất kho -> checkout_batch_id nullable)
    $returnBatch = ReturnBatch::query()
        ->whereHas('items', fn ($q) => $q->whereIn('checkout_batch_item_id', [$item1->id, $item2->id]))
        ->latest()
        ->first();
    expect($returnBatch)->not->toBeNull()
        ->and($returnBatch->status)->toBe(ReturnBatchStatus::Completed)
        ->and($returnBatch->items)->toHaveCount(2);

    // Verify normal asset is Ready
    expect($normalAsset->fresh()->current_status)->toBe(AssetStatus::Ready);

    // Verify damaged asset is Repairing and RepairLog is created
    expect($damagedAsset->fresh()->current_status)->toBe(AssetStatus::Repairing);

    $repairLog = RepairLog::where('asset_id', $damagedAsset->id)->first();
    expect($repairLog)->not->toBeNull()
        ->and($repairLog->result_status)->toBe(RepairResultStatus::Pending)
        ->and($repairLog->repair_note)->toContain('Chết 3 bóng LED hàng thứ 2');
});

test('order complete action transitions order status from Returned to Completed', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $order = Order::first();
    $order->update([
        'status' => OrderStatus::Returned,
    ]);

    Livewire::test(ListOrders::class)
        ->callTableAction('complete_order', $order)
        ->assertHasNoTableActionErrors();

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

test('order return action marks unreceived asset as Missing and still advances order', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $customer = Customer::first();
    $warehouse = Warehouse::first();

    $order = Order::create([
        'order_no' => 'ORD-TEST-RET-02',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'request_date' => now()->toDateString(),
        'value' => 50000000,
        'status' => OrderStatus::Dispatched,
        'event' => 'Sự kiện Roadshow',
    ]);

    $checkoutBatch = CheckoutBatch::create([
        'code' => 'OUT-TEST-02',
        'order_id' => $order->id,
        'customer_id' => $order->customer_id,
        'warehouse_id' => $order->warehouse_id,
        'status' => BatchStatus::Dispatched,
        'created_by' => $user->id,
    ]);

    $assets = Asset::take(2)->get();
    $receivedAsset = $assets[0];
    $missingAsset = $assets[1];

    $receivedAsset->update(['current_status' => AssetStatus::InEvent]);
    $missingAsset->update(['current_status' => AssetStatus::InEvent]);

    $item1 = CheckoutBatchItem::create([
        'checkout_batch_id' => $checkoutBatch->id,
        'asset_id' => $receivedAsset->id,
        'is_dispatched' => true,
    ]);

    $item2 = CheckoutBatchItem::create([
        'checkout_batch_id' => $checkoutBatch->id,
        'asset_id' => $missingAsset->id,
        'is_dispatched' => true,
    ]);

    Livewire::test(ListOrders::class)
        ->callTableAction('return_order', $order, [
            'return_date' => now()->toDateString(),
            'received_by' => $user->id,
            'note' => 'Thu hồi có 1 thiết bị mất',
            'items' => [
                [
                    'asset_id' => $receivedAsset->id,
                    'checkout_batch_item_id' => $item1->id,
                    'is_received' => true,
                    'grade' => ReturnGrade::Normal->value,
                    'grade_note' => null,
                ],
                [
                    'asset_id' => $missingAsset->id,
                    'checkout_batch_item_id' => $item2->id,
                    'is_received' => false,
                    'grade' => ReturnGrade::Normal->value,
                    'grade_note' => null,
                ],
            ],
        ])
        ->assertHasNoTableActionErrors();

    // Received asset -> Ready
    expect($receivedAsset->fresh()->current_status)->toBe(AssetStatus::Ready);

    // Unreceived asset -> Missing (not stuck InEvent)
    expect($missingAsset->fresh()->current_status)->toBe(AssetStatus::Missing);

    // Order still advances to Returned
    expect($order->fresh()->status)->toBe(OrderStatus::Returned);
});

test('checkout batch return advances order to Returned only when every batch is returned', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $customer = Customer::first();
    $warehouse = Warehouse::first();

    $order = Order::create([
        'order_no' => 'ORD-TEST-RET-03',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'request_date' => now()->toDateString(),
        'value' => 80000000,
        'status' => OrderStatus::Dispatched,
        'event' => 'Sự kiện 2 đợt xuất',
    ]);

    $batch1 = CheckoutBatch::create([
        'code' => 'OUT-TEST-03A',
        'order_id' => $order->id,
        'customer_id' => $order->customer_id,
        'warehouse_id' => $order->warehouse_id,
        'status' => BatchStatus::Dispatched,
        'created_by' => $user->id,
    ]);

    $batch2 = CheckoutBatch::create([
        'code' => 'OUT-TEST-03B',
        'order_id' => $order->id,
        'customer_id' => $order->customer_id,
        'warehouse_id' => $order->warehouse_id,
        'status' => BatchStatus::Dispatched,
        'created_by' => $user->id,
    ]);

    $asset1 = Asset::find(1);
    $asset2 = Asset::find(2);
    $asset1->update(['current_status' => AssetStatus::InEvent]);
    $asset2->update(['current_status' => AssetStatus::InEvent]);

    $item1 = CheckoutBatchItem::create([
        'checkout_batch_id' => $batch1->id,
        'asset_id' => $asset1->id,
        'is_dispatched' => true,
    ]);

    $item2 = CheckoutBatchItem::create([
        'checkout_batch_id' => $batch2->id,
        'asset_id' => $asset2->id,
        'is_dispatched' => true,
    ]);

    // Return batch 1 only
    Livewire::test(ListCheckoutBatches::class)
        ->callTableAction('create_return_batch', $batch1, [
            'return_date' => now()->toDateString(),
            'received_by' => $user->id,
            'note' => 'Trả đợt 1',
            'items' => [
                [
                    'asset_id' => $asset1->id,
                    'checkout_batch_item_id' => $item1->id,
                    'is_received' => true,
                    'grade' => ReturnGrade::Normal->value,
                    'grade_note' => null,
                ],
            ],
        ])
        ->assertHasNoTableActionErrors();

    // Batch 1 completed, batch 2 still dispatched, order still dispatched
    expect($batch1->fresh()->status)->toBe(BatchStatus::Completed);
    expect($batch2->fresh()->status)->toBe(BatchStatus::Dispatched);
    expect($order->fresh()->status)->toBe(OrderStatus::Dispatched);

    // Return batch 2
    Livewire::test(ListCheckoutBatches::class)
        ->callTableAction('create_return_batch', $batch2, [
            'return_date' => now()->toDateString(),
            'received_by' => $user->id,
            'note' => 'Trả đợt 2',
            'items' => [
                [
                    'asset_id' => $asset2->id,
                    'checkout_batch_item_id' => $item2->id,
                    'is_received' => true,
                    'grade' => ReturnGrade::Normal->value,
                    'grade_note' => null,
                ],
            ],
        ])
        ->assertHasNoTableActionErrors();

    // Now all batches returned -> order advances to Returned
    expect($batch2->fresh()->status)->toBe(BatchStatus::Completed);
    expect($order->fresh()->status)->toBe(OrderStatus::Returned);
    expect($asset1->fresh()->current_status)->toBe(AssetStatus::Ready);
    expect($asset2->fresh()->current_status)->toBe(AssetStatus::Ready);
});

test('draft order displays only initial header actions in edit page', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $customer = Customer::first();
    $warehouse = Warehouse::first();

    $order = Order::create([
        'order_no' => 'ORD-TEST-DRAFT-01',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'request_date' => now()->toDateString(),
        'value' => 30000000,
        'status' => OrderStatus::Draft,
        'event' => 'Sự kiện khai mạc',
    ]);

    Livewire::test(EditOrder::class, ['record' => $order->id])
        ->assertActionVisible('create_contract')
        ->assertActionVisible('create_checkout_batch')
        ->assertActionHidden('view_contract')
        ->assertActionHidden('view_checkout_batch')
        ->assertActionHidden('change_order')
        ->assertActionHidden('assign_crew')
        ->assertActionHidden('manage_timeline')
        ->assertActionHidden('dispatch_order')
        ->assertActionHidden('return_order')
        ->assertActionHidden('complete_order');
});
