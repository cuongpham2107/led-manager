<?php

use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\RepairLog;
use App\Models\ReturnBatch;
use App\Models\ReturnBatchItem;
use App\Models\User;
use App\Models\Warehouse;

test('models and basic relationships can be instantiated', function () {
    $user = User::factory()->create();

    $warehouse = Warehouse::create([
        'name' => 'Kho Hà Nội',
        'code' => 'WH-HN',
        'is_active' => true,
    ]);

    $productLine = ProductLine::create([
        'name' => 'P2.5 Indoor',
        'code' => 'P25-IN',
        'is_active' => true,
    ]);

    $customer = Customer::create([
        'name' => 'Công ty ABC',
        'code' => 'CUS-001',
        'type' => 'corporate',
    ]);

    $asset = Asset::create([
        'serial_no' => 'LED-0001',
        'product_line_id' => $productLine->id,
        'current_warehouse_id' => $warehouse->id,
        'current_status' => 'ready',
    ]);

    expect($asset->productLine->id)->toBe($productLine->id)
        ->and($asset->currentWarehouse->id)->toBe($warehouse->id);

    $quotation = Quotation::create([
        'code' => 'QUO-001',
        'customer_id' => $customer->id,
        'sales_user_id' => $user->id,
        'product_line_id' => $productLine->id,
        'status' => 'draft',
    ]);

    $quotationItem = QuotationItem::create([
        'quotation_id' => $quotation->id,
        'product_line_id' => $productLine->id,
        'description' => 'Cabinets 500x500',
        'quantity' => 10,
    ]);

    expect($quotation->items)->toHaveCount(1)
        ->and($quotation->customer->id)->toBe($customer->id);

    $order = Order::create([
        'order_no' => 'ORD-001',
        'warehouse_id' => $warehouse->id,
        'customer_id' => $customer->id,
        'quotation_id' => $quotation->id,
        'product_line_id' => $productLine->id,
        'request_date' => now()->toDateString(),
        'status' => 'draft',
    ]);

    $orderItem = OrderItem::create([
        'order_id' => $order->id,
        'product_line_id' => $productLine->id,
        'quantity_required' => 10,
    ]);

    expect($order->items)->toHaveCount(1)
        ->and($order->warehouse->id)->toBe($warehouse->id);

    $checkoutBatch = CheckoutBatch::create([
        'code' => 'OUT-001',
        'order_id' => $order->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'created_by' => $user->id,
    ]);

    $checkoutItem = CheckoutBatchItem::create([
        'checkout_batch_id' => $checkoutBatch->id,
        'asset_id' => $asset->id,
    ]);

    expect($checkoutBatch->items)->toHaveCount(1)
        ->and($checkoutBatch->assets)->toHaveCount(1);

    $returnBatch = ReturnBatch::create([
        'code' => 'RET-001',
        'checkout_batch_id' => $checkoutBatch->id,
        'created_by' => $user->id,
    ]);

    $returnItem = ReturnBatchItem::create([
        'return_batch_id' => $returnBatch->id,
        'asset_id' => $asset->id,
        'checkout_batch_item_id' => $checkoutItem->id,
    ]);

    expect($returnBatch->items)->toHaveCount(1)
        ->and($returnBatch->assets)->toHaveCount(1);

    $repairLog = RepairLog::create([
        'asset_id' => $asset->id,
        'start_date' => now()->toDateString(),
        'repair_note' => 'Hỏng module',
        'created_by' => $user->id,
    ]);

    expect($repairLog->asset->id)->toBe($asset->id);

    $statusLog = AssetStatusLog::create([
        'asset_id' => $asset->id,
        'from_status' => 'ready',
        'to_status' => 'in_event',
        'from_warehouse_id' => $warehouse->id,
        'source_type' => CheckoutBatch::class,
        'source_id' => $checkoutBatch->id,
        'changed_by' => $user->id,
    ]);

    expect($statusLog->source->id)->toBe($checkoutBatch->id);
});
