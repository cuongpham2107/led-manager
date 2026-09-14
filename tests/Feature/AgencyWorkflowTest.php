<?php

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Agency;
use App\Models\Order;
use App\Models\ProductLine;
use App\Models\User;
use App\Services\PricingService;
use Database\Seeders\LedOsDataSeeder;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    (new LedOsDataSeeder)->run();
});

test('agencies are initialized with 1000m2 quota and commission rate', function () {
    $agency = Agency::where('code', 'DL-HP')->first();

    expect($agency)->not->toBeNull()
        ->and((float) $agency->allocated_area_m2)->toEqual(1000.0)
        ->and((float) $agency->commission_rate)->toEqual(15.0)
        ->and($agency->warehouse)->not->toBeNull()
        ->and((float) $agency->current_inventory_area)->toBeGreaterThan(0.0);
});

test('pricing service prioritizes agency-specific rules over global rules', function () {
    $agencyHp = Agency::where('code', 'DL-HP')->firstOrFail();
    $plP26 = ProductLine::where('code', 'P2.6')->firstOrFail();

    $pricingService = app(PricingService::class);

    // HP has a specific rule for P2.6 at 320,000 / m2 / day
    $priceHp = $pricingService->resolveUnitPrice(
        productLineId: $plP26->id,
        requestDate: '2026-06-01',
        agencyId: $agencyHp->id
    );

    // Default global rule for P2.6 is 400,000 / m2 / day
    $priceGlobal = $pricingService->resolveUnitPrice(
        productLineId: $plP26->id,
        requestDate: '2026-06-01',
        agencyId: null
    );

    expect($priceHp)->toEqual(320000.0)
        ->and($priceGlobal)->toEqual(400000.0);
});

test('agency scoped users only see orders belonging to their agency', function () {
    $agencyUser = User::where('email', 'daily.haiphong@ledmanager.com')->firstOrFail();
    $adminUser = User::where('email', 'admin@ledmanager.com')->firstOrFail();

    expect($agencyUser->isAgencyScoped())->toBeTrue()
        ->and($agencyUser->getScopedAgencyId())->toEqual($agencyUser->agency_id)
        ->and($adminUser->isAgencyScoped())->toBeFalse();

    // Authenticate as agency user
    Auth::login($agencyUser);
    $agencyOrders = OrderResource::getEloquentQuery()->get();

    expect($agencyOrders)->not->toBeEmpty();
    foreach ($agencyOrders as $order) {
        expect($order->agency_id)->toEqual($agencyUser->agency_id);
    }

    // Authenticate as admin user - sees all orders
    Auth::login($adminUser);
    $adminOrders = OrderResource::getEloquentQuery()->get();

    expect($adminOrders->count())->toBeGreaterThan($agencyOrders->count());
});

test('direct orders can be created without quotation or contract and commission calculates on total_paid', function () {
    $agency = Agency::where('code', 'DL-HP')->firstOrFail();

    // ORD-DL-HP-02 has total value 60M but total_paid 30M (50% deposit)
    $order = Order::where('order_no', 'ORD-DL-HP-02')->firstOrFail();

    expect($order->quotation_id)->toBeNull()
        ->and($order->contracts)->toBeEmpty()
        ->and((float) $order->value)->toEqual(60000000.0)
        ->and((float) $order->total_paid)->toEqual(30000000.0);

    // Commission is 15% on actual collected (total_paid: 30,000,000), not order value
    $expectedCommission = 30000000.0 * ($agency->commission_rate / 100);
    $actualCommission = (float) $order->total_paid * ($agency->commission_rate / 100);

    expect($actualCommission)->toEqual($expectedCommission)
        ->and($actualCommission)->toEqual(4500000.0);
});
