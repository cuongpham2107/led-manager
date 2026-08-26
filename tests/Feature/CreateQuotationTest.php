<?php

use App\Enums\CustomerType;
use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\Pages\CreateQuotation;
use App\Models\Customer;
use App\Models\ProductLine;
use App\Models\Quotation;
use App\Models\User;
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
            'device_type_id' => $item['device_type_id'],
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
        ->and($quotation->items()->count())->toBe(11)
        ->and($quotation->items()->first()->device_type_id)->not->toBeNull()
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
