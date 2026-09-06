<?php

use App\Enums\AssetStatus;
use App\Enums\OrderStatus;
use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\Actions\ConvertToOrderAction;
use App\Filament\Resources\Quotations\Pages\EditQuotation;
use App\Models\Asset;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\LedOsDataSeeder;

use function Pest\Laravel\actingAs;

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

test('convert to order action blocks conversion when selected warehouse has insufficient stock without force convert', function () {
    (new LedOsDataSeeder)->run();
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $warehouse = Warehouse::create([
        'name' => 'Kho Hà Nội',
        'code' => 'WH-HN-TEST-2',
        'address' => 'Hà Nội',
        'is_active' => true,
    ]);

    $customer = Customer::create([
        'name' => 'Vingroup JSC',
        'code' => 'CUS-TEST-02',
        'type' => 'corporate',
    ]);

    $pl = ProductLine::create([
        'name' => 'P2.9 Sự kiện',
        'code' => 'P2.9-TEST-2',
        'pixel_pitch' => 2.9,
        'module_width_mm' => 500,
        'module_height_mm' => 1000,
        'weight_kg' => 13.5,
        'power_watt' => 450,
        'is_active' => true,
    ]);

    // Create only 3 assets in this warehouse
    for ($i = 0; $i < 3; $i++) {
        Asset::create([
            'serial_no' => "SN-TEST-HN-{$i}",
            'qr_code' => "QR-TEST-HN-{$i}",
            'product_line_id' => $pl->id,
            'current_warehouse_id' => $warehouse->id,
            'current_status' => AssetStatus::Ready,
        ]);
    }

    $quotation = Quotation::create([
        'code' => 'QUO-TEST-SHORTAGE',
        'customer_id' => $customer->id,
        'sales_user_id' => $user->id,
        'product_line_id' => $pl->id,
        'rental_days' => 2,
        'event_start_date' => now()->addDays(5)->toDateString(),
        'event_end_date' => now()->addDays(7)->toDateString(),
        'event_name' => 'Test Shortage Event',
        'location' => 'Hà Nội',
        'status' => QuotationStatus::Approved,
        'total_price' => 30000000,
    ]);

    QuotationItem::create([
        'quotation_id' => $quotation->id,
        'product_line_id' => $pl->id,
        'description' => 'Cabinet P2.9 Sự kiện',
        'quantity' => 36, // Needs 36, but warehouse only has 3
        'unit_cost' => 900000,
        'line_total' => 32400000,
    ]);

    Livewire\Livewire::test(EditQuotation::class, ['record' => $quotation->id])
        ->callAction('convert_to_order', data: [
            'warehouse_id' => $warehouse->id,
            'force_convert' => false,
        ])
        ->assertNotified('Không thể chuyển đổi — thiếu thiết bị khả dụng');

    expect($quotation->fresh()->status)->toBe(QuotationStatus::Approved)
        ->and($quotation->fresh()->converted_order_id)->toBeNull();
});

test('convert to order action allows conversion with force convert toggle when stock is short', function () {
    (new LedOsDataSeeder)->run();
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $warehouse = Warehouse::create([
        'name' => 'Kho Hà Nội',
        'code' => 'WH-HN-TEST-3',
        'address' => 'Hà Nội',
        'is_active' => true,
    ]);

    $customer = Customer::create([
        'name' => 'Vingroup JSC',
        'code' => 'CUS-TEST-03',
        'type' => 'corporate',
    ]);

    $pl = ProductLine::create([
        'name' => 'P2.9 Sự kiện',
        'code' => 'P2.9-TEST-3',
        'pixel_pitch' => 2.9,
        'module_width_mm' => 500,
        'module_height_mm' => 1000,
        'weight_kg' => 13.5,
        'power_watt' => 450,
        'is_active' => true,
    ]);

    $quotation = Quotation::create([
        'code' => 'QUO-TEST-FORCE',
        'customer_id' => $customer->id,
        'sales_user_id' => $user->id,
        'product_line_id' => $pl->id,
        'rental_days' => 2,
        'event_start_date' => now()->addDays(5)->toDateString(),
        'event_end_date' => now()->addDays(7)->toDateString(),
        'event_name' => 'Test Force Event',
        'location' => 'Hà Nội',
        'status' => QuotationStatus::Approved,
        'total_price' => 30000000,
    ]);

    QuotationItem::create([
        'quotation_id' => $quotation->id,
        'product_line_id' => $pl->id,
        'description' => 'Cabinet P2.9 Sự kiện',
        'quantity' => 36,
        'unit_cost' => 900000,
        'line_total' => 32400000,
    ]);

    Livewire\Livewire::test(EditQuotation::class, ['record' => $quotation->id])
        ->callAction('convert_to_order', data: [
            'warehouse_id' => $warehouse->id,
            'force_convert' => true,
        ])
        ->assertNotified('Đã tạo Đơn hàng (Cần lưu ý tồn kho)!');

    $quotation->refresh();
    expect($quotation->status)->toBe(QuotationStatus::Converted)
        ->and($quotation->converted_order_id)->not->toBeNull();

    $order = Order::find($quotation->converted_order_id);
    expect($order)->not->toBeNull()
        ->and($order->note)->toContain('[Lưu ý tồn kho]');
});

test('convert to order action converts successfully when warehouse has sufficient stock', function () {
    (new LedOsDataSeeder)->run();
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $warehouseHcm = Warehouse::create([
        'name' => 'Kho TP.HCM',
        'code' => 'WH-HCM-TEST-4',
        'address' => 'TP.HCM',
        'is_active' => true,
    ]);

    $customer = Customer::create([
        'name' => 'Vingroup JSC',
        'code' => 'CUS-TEST-04',
        'type' => 'corporate',
    ]);

    $pl = ProductLine::create([
        'name' => 'P2.9 Sự kiện',
        'code' => 'P2.9-TEST-4',
        'pixel_pitch' => 2.9,
        'module_width_mm' => 500,
        'module_height_mm' => 1000,
        'weight_kg' => 13.5,
        'power_watt' => 450,
        'is_active' => true,
    ]);

    // Create 40 assets in TP.HCM
    for ($i = 0; $i < 40; $i++) {
        Asset::create([
            'serial_no' => "SN-TEST-HCM-{$i}",
            'qr_code' => "QR-TEST-HCM-{$i}",
            'product_line_id' => $pl->id,
            'current_warehouse_id' => $warehouseHcm->id,
            'current_status' => AssetStatus::Ready,
        ]);
    }

    $quotation = Quotation::create([
        'code' => 'QUO-TEST-SUCCESS',
        'customer_id' => $customer->id,
        'sales_user_id' => $user->id,
        'product_line_id' => $pl->id,
        'rental_days' => 2,
        'event_start_date' => now()->addDays(5)->toDateString(),
        'event_end_date' => now()->addDays(7)->toDateString(),
        'event_name' => 'Wedding Showcase',
        'location' => 'Quận 7, TP.HCM',
        'status' => QuotationStatus::Approved,
        'total_price' => 30000000,
    ]);

    QuotationItem::create([
        'quotation_id' => $quotation->id,
        'product_line_id' => $pl->id,
        'description' => 'Cabinet P2.9 Sự kiện',
        'quantity' => 36,
        'unit_cost' => 900000,
        'line_total' => 32400000,
    ]);

    Livewire\Livewire::test(EditQuotation::class, ['record' => $quotation->id])
        ->callAction('convert_to_order', data: [
            'warehouse_id' => $warehouseHcm->id,
            'force_convert' => false,
        ])
        ->assertNotified('Chuyển đổi đơn hàng thành công!');

    $quotation->refresh();
    expect($quotation->status)->toBe(QuotationStatus::Converted)
        ->and($quotation->converted_order_id)->not->toBeNull();

    $order = Order::find($quotation->converted_order_id);
    expect($order)->not->toBeNull()
        ->and($order->warehouse_id)->toBe($warehouseHcm->id);
});

test('convert to order action automatically chooses the warehouse that has stock and matches location', function () {
    (new LedOsDataSeeder)->run();
    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $warehouseHn = Warehouse::create([
        'name' => 'Kho Hà Nội',
        'code' => 'WH-HN-TEST-AUTO',
        'address' => 'Hà Nội',
        'is_active' => true,
    ]);

    $warehouseHcm = Warehouse::create([
        'name' => 'Kho TP.HCM',
        'code' => 'WH-HCM-TEST-AUTO',
        'address' => 'TP.HCM',
        'is_active' => true,
    ]);

    $customer = Customer::create([
        'name' => 'Vingroup JSC',
        'code' => 'CUS-TEST-AUTO',
        'type' => 'corporate',
    ]);

    $pl = ProductLine::create([
        'name' => 'P2.9 Sự kiện',
        'code' => 'P2.9-TEST-AUTO',
        'pixel_pitch' => 2.9,
        'module_width_mm' => 500,
        'module_height_mm' => 1000,
        'weight_kg' => 13.5,
        'power_watt' => 450,
        'is_active' => true,
    ]);

    // In HN: only 3 assets
    for ($i = 0; $i < 3; $i++) {
        Asset::create([
            'serial_no' => "SN-AUTO-HN-{$i}",
            'qr_code' => "QR-AUTO-HN-{$i}",
            'product_line_id' => $pl->id,
            'current_warehouse_id' => $warehouseHn->id,
            'current_status' => AssetStatus::Ready,
        ]);
    }

    // In HCM: 40 assets
    for ($i = 0; $i < 40; $i++) {
        Asset::create([
            'serial_no' => "SN-AUTO-HCM-{$i}",
            'qr_code' => "QR-AUTO-HCM-{$i}",
            'product_line_id' => $pl->id,
            'current_warehouse_id' => $warehouseHcm->id,
            'current_status' => AssetStatus::Ready,
        ]);
    }

    $quotation = Quotation::create([
        'code' => 'QUO-TEST-AUTO-PICK',
        'customer_id' => $customer->id,
        'sales_user_id' => $user->id,
        'product_line_id' => $pl->id,
        'rental_days' => 2,
        'event_start_date' => now()->addDays(5)->toDateString(),
        'event_end_date' => now()->addDays(7)->toDateString(),
        'event_name' => 'Wedding Showcase TP.HCM',
        'location' => 'Chloe Gallery Riverside, Quận 7, TP.HCM',
        'status' => QuotationStatus::Approved,
        'total_price' => 30000000,
    ]);

    QuotationItem::create([
        'quotation_id' => $quotation->id,
        'product_line_id' => $pl->id,
        'description' => 'Cabinet P2.9 Sự kiện',
        'quantity' => 36,
        'unit_cost' => 900000,
        'line_total' => 32400000,
    ]);

    $action = ConvertToOrderAction::make();
    $reflection = new ReflectionClass($action);
    $method = $reflection->getMethod('getBestWarehouseId');
    $method->setAccessible(true);

    $bestId = $method->invoke($action, $quotation);

    expect($bestId)->toBe($warehouseHcm->id);
});
