<?php

use App\Enums\OrderStatus;
use App\Enums\QuotationStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use App\Models\Warehouse;

test('converting quotation to order copies BOM items to order items and assigns warehouse', function () {
    $user = User::factory()->create();

    $warehouse = Warehouse::create([
        'name' => 'Tổng kho Hà Nội',
        'code' => 'WH-HN-TEST',
        'address' => 'Hà Nội',
        'phone' => '0123456789',
        'is_active' => true,
    ]);

    $customer = Customer::create([
        'name' => 'Vingroup JSC',
        'code' => 'CUS-TEST-01',
        'type' => 'corporate',
    ]);

    $pl = ProductLine::create([
        'name' => 'P2.6',
        'code' => 'P2.6-TEST',
        'pixel_pitch' => 2.6,
        'module_width_mm' => 500,
        'module_height_mm' => 500,
        'weight_kg' => 6.8,
        'power_watt' => 380,
        'is_active' => true,
    ]);

    $quotation = Quotation::create([
        'code' => 'QUO-TEST-001',
        'customer_id' => $customer->id,
        'sales_user_id' => $user->id,
        'screen_width_m' => 6.0,
        'screen_height_m' => 3.5,
        'screen_area_m2' => 21.0,
        'product_line_id' => $pl->id,
        'rental_days' => 3,
        'event_name' => 'VinFast Show',
        'status' => QuotationStatus::Approved,
        'total_price' => 56370000,
    ]);

    $quotationItem = QuotationItem::create([
        'quotation_id' => $quotation->id,
        'product_line_id' => $pl->id,
        'description' => 'Cabinet P2.6 LED',
        'quantity' => 84,
        'unit_cost' => 400000,
        'line_total' => 33600000,
    ]);

    // Simulate Conversion
    $order = Order::create([
        'order_no' => 'ORD-TEST-001',
        'customer_id' => $quotation->customer_id,
        'warehouse_id' => $warehouse->id,
        'quotation_id' => $quotation->id,
        'request_date' => now()->toDateString(),
        'expected_return_date' => now()->addDays(3)->toDateString(),
        'area_m2' => $quotation->screen_area_m2,
        'event' => $quotation->event_name,
        'value' => $quotation->total_price,
        'status' => OrderStatus::Draft,
    ]);

    foreach ($quotation->items as $item) {
        OrderItem::create([
            'order_id' => $order->id,
            'product_line_id' => $item->product_line_id,
            'quantity_required' => (int) $item->quantity,
            'unit_price' => $item->unit_cost,
            'note' => $item->description,
        ]);
    }

    $quotation->update([
        'status' => QuotationStatus::Converted,
        'converted_order_id' => $order->id,
    ]);

    expect($order->items()->count())->toBe(1)
        ->and($order->items()->first()->quantity_required)->toBe(84)
        ->and($order->items()->first()->product_line_id)->toBe($pl->id)
        ->and($order->warehouse_id)->toBe($warehouse->id)
        ->and($quotation->status)->toBe(QuotationStatus::Converted);
});
