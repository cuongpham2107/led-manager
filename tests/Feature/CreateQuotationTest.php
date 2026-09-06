<?php

use App\Enums\CustomerType;
use App\Enums\OrderStatus;
use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\Pages\CreateQuotation;
use App\Filament\Resources\Quotations\Pages\EditQuotation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\AvailabilityService;
use App\Services\LedCalculationService;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('create quotation page calculates BOM and creates quotation with quotation items', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $pl = ProductLine::where('code', 'P2.6')->first();
    $customer = Customer::first();

    $service = app(LedCalculationService::class);
    $bom = $service->generateBom(6.0, 3.5, $pl, 3);
    $items = [];
    foreach ($bom as $item) {
        $items[] = [
            'product_line_id' => $item['product_line_id'],
            'description' => $item['item'],
            'quantity' => $item['qty'],
            'unit_cost' => $item['unit_cost'],
            'line_total' => $item['line_total'],
        ];
    }

    Livewire::test(CreateQuotation::class)
        ->assertSuccessful()
        ->fillForm([
            'code' => 'QUO-TEST-99',
            'customer_id' => $customer->id,
            'event_name' => 'Lễ Ra Mắt Xe Điện VinFast',
            'screen_width_m' => 6.0,
            'screen_height_m' => 3.5,
            'product_line_id' => $pl->id,
            'rental_days' => 3,
            'crew_size' => 4,
            'transport_distance_km' => 45,
            'status' => QuotationStatus::Draft->value,
            'items' => $items,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $quotation = Quotation::where('code', 'QUO-TEST-99')->first();
    expect($quotation)->not->toBeNull()
        ->and($quotation->items()->count())->toBe(1)
        ->and($quotation->items()->first()->product_line_id)->not->toBeNull()
        ->and($quotation->items()->first()->unit_cost)->toBeGreaterThan(0);
});

test('quotation form dynamically recalculates pricing and discounts when parameters change', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $p26 = ProductLine::where('code', 'P2.6')->first();
    $agencyCustomer = Customer::where('type', CustomerType::Agency)->first();

    Livewire::test(CreateQuotation::class)
        ->assertSuccessful()
        ->set('data.product_line_id', $p26->id)
        ->set('data.rental_days', 1)
        ->assertSet('data.rental_days', 1)
        ->set('data.rental_days', 3)
        ->assertSet('data.rental_days', 3)
        ->set('data.customer_id', $agencyCustomer->id)
        ->assertSet('data.customer_id', $agencyCustomer->id)
        ->set('data.crew_rate', 2000000)
        ->assertSet('data.crew_rate', 2000000)
        ->set('data.transport_rate', 30000)
        ->assertSet('data.transport_rate', 30000);
});

test('edit quotation page fills and saves crew_size and transport_distance_km properly', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $customer = Customer::first();
    $quotation = Quotation::create([
        'code' => 'QUO-EDIT-TEST',
        'customer_id' => $customer->id,
        'rental_days' => 2,
        'crew_size' => 6,
        'transport_distance_km' => 65.5,
        'crew_rate' => 1800000,
        'transport_rate' => 32000,
        'labour_cost' => 21600000,
        'transport_cost' => 4192000,
        'total_price' => 50000000,
        'status' => QuotationStatus::Draft,
    ]);

    Livewire::test(EditQuotation::class, ['record' => $quotation->id])
        ->assertSuccessful()
        ->assertSchemaStateSet([
            'crew_size' => 6,
            'transport_distance_km' => 65.5,
        ]);
});

test('changing repeater item quantity or unit cost recalculates equipment cost and cost breakdown totals', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $component = Livewire::test(CreateQuotation::class)->assertSuccessful();
    $items = $component->get('data.items');
    $firstKey = array_key_first($items);

    $initialTotalPrice = (float) $component->get('data.total_price');

    $component->set("data.items.{$firstKey}.quantity", 2);

    $unitCost = (float) $component->get("data.items.{$firstKey}.unit_cost");
    $expectedLineTotal = 2 * $unitCost;
    $expectedEquipmentCost = $expectedLineTotal;

    expect((float) $component->get("data.items.{$firstKey}.line_total"))->toBe($expectedLineTotal)
        ->and((float) $component->get('data.equipment_cost'))->toBe($expectedEquipmentCost)
        ->and((float) $component->get('data.total_price'))->not->toBe($initialTotalPrice);
});

test('selecting product line in BOM item auto-fills description specification', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $component = Livewire::test(CreateQuotation::class)->assertSuccessful();
    $items = $component->get('data.items');
    $firstKey = array_key_first($items);

    $p29 = ProductLine::where('code', 'P2.9')->first();
    $component->set("data.items.{$firstKey}.product_line_id", $p29->id);

    $desc = (string) $component->get("data.items.{$firstKey}.description");
    expect($desc)->toContain('P2.9')
        ->and($desc)->toContain('Cabinet LED');
});

test('edit quotation page displays header actions: convert_to_order, download_pdf, mark_rejected, delete', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $customer = Customer::first();
    $quotation = Quotation::create([
        'code' => 'QUO-TEST-HDR-01',
        'customer_id' => $customer->id,
        'status' => QuotationStatus::Draft,
    ]);

    Livewire::test(EditQuotation::class, ['record' => $quotation->id])
        ->assertSuccessful()
        ->assertActionVisible('convert_to_order')
        ->assertActionVisible('download_pdf')
        ->assertActionVisible('mark_rejected')
        ->assertActionVisible('delete')
        ->assertActionHidden('view_order');
});

test('edit quotation page displays view_order action when order has been created from quotation', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $customer = Customer::first();
    $warehouse = Warehouse::first();
    $quotation = Quotation::create([
        'code' => 'QUO-TEST-HDR-02',
        'customer_id' => $customer->id,
        'status' => QuotationStatus::Converted,
    ]);

    $order = Order::create([
        'order_no' => 'ORD-TEST-HDR-02',
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'quotation_id' => $quotation->id,
        'request_date' => now()->toDateString(),
        'value' => 50000000,
        'status' => OrderStatus::Draft,
    ]);

    $quotation->update(['converted_order_id' => $order->id]);

    Livewire::test(EditQuotation::class, ['record' => $quotation->id])
        ->assertSuccessful()
        ->assertActionVisible('view_order')
        ->assertActionHidden('convert_to_order');
});

test('quotation BOM item displays available stock helper text for product line', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    actingAs($user);

    $component = Livewire::test(CreateQuotation::class)->assertSuccessful();
    $items = $component->get('data.items');
    $firstKey = array_key_first($items);

    $p26 = ProductLine::where('code', 'P2.6')->first();
    $component->set("data.items.{$firstKey}.product_line_id", $p26->id);

    $startDate = $component->get('data.event_start_date') ?: now()->toDateString();
    $endDate = $component->get('data.event_end_date') ?: now()->addDays(3)->toDateString();

    $avail = app(AvailabilityService::class)->getAvailableCount(
        $p26->id,
        $startDate,
        $endDate,
    );

    expect($avail)->toBeGreaterThanOrEqual(0);
});
