<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Filament\Resources\Assets\AssetResource;
use App\Filament\Resources\CheckinBatches\CheckinBatchResource;
use App\Filament\Resources\CheckoutBatches\CheckoutBatchResource;
use App\Filament\Resources\Customers\CustomerResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\PricingRules\PricingRuleResource;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Filament\Resources\RepairLogs\RepairLogResource;
use App\Filament\Resources\ReturnBatches\ReturnBatchResource;
use App\Filament\Resources\Warehouses\WarehouseResource;
use App\Filament\Widgets\EventCalendarWidget;
use App\Models\Agency;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\CheckoutBatch;
use App\Models\Order;
use App\Models\ProductLine;
use App\Models\ReturnBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PricingService;
use Database\Seeders\LedOsDataSeeder;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

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

test('order saves commission_rate snapshot from agency and preserves it', function () {
    $agency = Agency::where('code', 'DL-HP')->firstOrFail();
    $order = Order::where('order_no', 'ORD-DL-HP-01')->firstOrFail();

    expect((float) $order->commission_rate)->toEqual(15.0);

    // If agency rate changes later, existing order's rate snapshot remains unchanged
    $agency->update(['commission_rate' => 25.0]);
    $order->refresh();

    expect((float) $order->commission_rate)->toEqual(15.0);
});

test('deactivated agency blocks panel access for its scoped users', function () {
    $agency = Agency::where('code', 'DL-HP')->firstOrFail();
    $agencyUser = User::where('email', 'daily.haiphong@ledmanager.com')->firstOrFail();
    $panel = Filament::getPanel('admin');

    expect($agencyUser->canAccessPanel($panel))->toBeTrue();

    // Deactivate agency
    $agency->update(['is_active' => false]);
    $agencyUser->refresh();

    expect($agencyUser->canAccessPanel($panel))->toBeFalse();
});

test('pricing service respects rental days tiered pricing', function () {
    $plP26 = ProductLine::where('code', 'P2.6')->firstOrFail();
    $pricingService = app(PricingService::class);

    // 1 day -> matches 1-day tier (400,000 / m2 / day)
    $shortPrice = $pricingService->resolveUnitPrice(
        productLineId: $plP26->id,
        requestDate: '2026-06-01',
        agencyId: null,
        rentalDays: 1
    );
    expect($shortPrice)->toEqual(400000.0);

    // 2 days -> matches 2-3 days tier (350,000 / m2 / day)
    $midPrice = $pricingService->resolveUnitPrice(
        productLineId: $plP26->id,
        requestDate: '2026-06-01',
        agencyId: null,
        rentalDays: 2
    );
    expect($midPrice)->toEqual(350000.0);

    // 5 days -> matches 4+ days tier (300,000 / m2 / day)
    $longPrice = $pricingService->resolveUnitPrice(
        productLineId: $plP26->id,
        requestDate: '2026-06-01',
        agencyId: null,
        rentalDays: 5
    );
    expect($longPrice)->toEqual(300000.0);
});

test('checkin batch blocks completion when destination agency warehouse quota is exceeded', function () {
    $agency = Agency::where('code', 'DL-HP')->firstOrFail();
    $wh = $agency->warehouse;
    $otherWh = Warehouse::where('id', '!=', $wh->id)->firstOrFail();
    $pl = ProductLine::where('code', 'P3.9')->firstOrFail();

    // Set allocated quota to 10m2 (current inventory in DL-HP is already > 10m2)
    $agency->update(['allocated_area_m2' => 10.0]);

    // Create checkin batch for this agency warehouse
    $batch = CheckinBatch::create([
        'code' => 'BATCH-TEST-QUOTA-BLOCK',
        'warehouse_id' => $wh->id,
        'status' => BatchStatus::Pending,
        'batch_type' => CheckinBatchType::Transfer,
        'product_line_id' => $pl->id,
        'quantity' => 10,
    ]);

    // Create an asset currently at another warehouse
    $asset = Asset::create([
        'serial_no' => 'TEST-SN-QUOTA-OVERFLOW',
        'product_line_id' => $pl->id,
        'current_warehouse_id' => $otherWh->id,
        'current_status' => AssetStatus::Ready,
    ]);

    CheckinBatchItem::create([
        'checkin_batch_id' => $batch->id,
        'asset_id' => $asset->id,
        'is_received' => false,
    ]);

    // Attempting to complete should throw ValidationException
    expect(fn () => $batch->complete())->toThrow(ValidationException::class);
});

test('agency scoped users have restricted fields in order form', function () {
    $agencyUser = User::where('email', 'daily.haiphong@ledmanager.com')->firstOrFail();
    $adminUser = User::where('email', 'admin@ledmanager.com')->firstOrFail();

    Auth::login($agencyUser);
    expect(OrderForm::isCurrentUserAgencyScoped())->toBeTrue();

    Auth::login($adminUser);
    expect(OrderForm::isCurrentUserAgencyScoped())->toBeFalse();
});

test('order assignments and sales user only include employees belonging to the agency', function () {
    $agencyHp = Agency::where('code', 'DL-HP')->firstOrFail();
    $agencyDn = Agency::where('code', 'DL-DN')->firstOrFail();

    $agencyHpUsers = User::where('agency_id', $agencyHp->id)->get();
    $agencyDnUsers = User::where('agency_id', $agencyDn->id)->get();
    $hqUsers = User::whereNull('agency_id')->get();

    expect($agencyHpUsers->count())->toBeGreaterThanOrEqual(2)
        ->and($agencyDnUsers->count())->toBeGreaterThanOrEqual(2)
        ->and($hqUsers->count())->toBeGreaterThanOrEqual(2);

    $hpUserIds = $agencyHpUsers->pluck('id')->all();
    foreach ($agencyDnUsers as $dnUser) {
        expect(in_array($dnUser->id, $hpUserIds, true))->toBeFalse();
    }
    foreach ($hqUsers as $hqUser) {
        expect(in_array($hqUser->id, $hpUserIds, true))->toBeFalse();
    }
});

test('agency scoped users only see records belonging to their agency across batches, repair, assets and quotations', function () {
    $agencyHpUser = User::where('email', 'daily.haiphong@ledmanager.com')->firstOrFail();
    $agencyDnUser = User::where('email', 'daily.danang@ledmanager.com')->firstOrFail();
    $adminUser = User::where('email', 'admin@ledmanager.com')->firstOrFail();

    $agencyHp = Agency::where('code', 'DL-HP')->firstOrFail();
    $agencyDn = Agency::where('code', 'DL-DN')->firstOrFail();

    // Authenticate as HP Agency
    Auth::login($agencyHpUser);

    // 1. Checkin batches
    $hpCheckins = CheckinBatchResource::getEloquentQuery()->get();
    foreach ($hpCheckins as $b) {
        expect($b->warehouse_id)->toEqual($agencyHp->warehouse_id);
    }

    // 2. Checkout batches
    $hpCheckouts = CheckoutBatchResource::getEloquentQuery()->get();
    foreach ($hpCheckouts as $b) {
        expect($b->warehouse_id)->toEqual($agencyHp->warehouse_id);
    }

    // 3. Return batches
    $hpReturns = ReturnBatchResource::getEloquentQuery()->get();
    foreach ($hpReturns as $r) {
        expect($r->checkoutBatch?->warehouse_id)->toEqual($agencyHp->warehouse_id);
    }

    // 4. Assets in warehouse
    $hpAssets = AssetResource::getEloquentQuery()->get();
    foreach ($hpAssets as $a) {
        expect($a->current_warehouse_id)->toEqual($agencyHp->warehouse_id);
    }

    // 5. Repair logs
    $hpRepairs = RepairLogResource::getEloquentQuery()->get();
    foreach ($hpRepairs as $rp) {
        expect($rp->asset?->current_warehouse_id)->toEqual($agencyHp->warehouse_id);
    }

    // 6. Quotations
    $hpQuotations = QuotationResource::getEloquentQuery()->get();
    foreach ($hpQuotations as $q) {
        expect($q->salesUser?->agency_id)->toEqual($agencyHp->id);
    }

    // Authenticate as DN Agency - should see DN warehouse data only
    Auth::login($agencyDnUser);
    $dnCheckins = CheckinBatchResource::getEloquentQuery()->get();
    foreach ($dnCheckins as $b) {
        expect($b->warehouse_id)->toEqual($agencyDn->warehouse_id);
    }

    // Authenticate as Admin - sees all warehouses
    Auth::login($adminUser);
    $adminCheckins = CheckinBatchResource::getEloquentQuery()->get();
    $distinctWarehouses = $adminCheckins->pluck('warehouse_id')->unique();
    expect($distinctWarehouses->count())->toBeGreaterThan(1);
});

test('checkin, checkout, return batches and repair logs have agency_id foreign key column and link correctly', function () {
    $agencyHp = Agency::where('code', 'DL-HP')->firstOrFail();

    // Checkin batch with agency warehouse
    $checkin = CheckinBatch::create([
        'code' => 'TEST-IN-001',
        'warehouse_id' => $agencyHp->warehouse_id,
        'note' => 'Test agency checkin',
    ]);
    expect($checkin->fresh()->agency_id)->toEqual($agencyHp->id)
        ->and($checkin->agency)->not->toBeNull()
        ->and($checkin->agency->id)->toEqual($agencyHp->id);

    // Checkout batch with agency warehouse
    $checkout = CheckoutBatch::create([
        'code' => 'TEST-OUT-001',
        'warehouse_id' => $agencyHp->warehouse_id,
        'note' => 'Test agency checkout',
    ]);
    expect($checkout->fresh()->agency_id)->toEqual($agencyHp->id)
        ->and($checkout->agency)->not->toBeNull()
        ->and($checkout->agency->id)->toEqual($agencyHp->id);

    // Return batch linked to checkout batch
    $return = ReturnBatch::create([
        'code' => 'TEST-RET-001',
        'checkout_batch_id' => $checkout->id,
        'return_date' => now()->toDateString(),
    ]);
    expect($return->fresh()->agency_id)->toEqual($agencyHp->id)
        ->and($return->fresh()->warehouse_id)->toEqual($agencyHp->warehouse_id)
        ->and($return->agency)->not->toBeNull();

    // Clean up test records
    $return->delete();
    $checkout->delete();
    $checkin->delete();
});

test('agency accounts cannot view warehouse list or navigate to warehouses', function () {
    $agencyUser = User::where('email', 'daily.haiphong@ledmanager.com')->firstOrFail();
    $adminUser = User::where('email', 'admin@ledmanager.com')->firstOrFail();

    Auth::login($agencyUser);
    expect(WarehouseResource::canViewAny())->toBeFalse()
        ->and(WarehouseResource::shouldRegisterNavigation())->toBeFalse();

    Auth::login($adminUser);
    expect(WarehouseResource::canViewAny())->toBeTrue()
        ->and(WarehouseResource::shouldRegisterNavigation())->toBeTrue();
});

test('agency scoped users only see customers belonging to their agency', function () {
    $agencyHpUser = User::where('email', 'daily.haiphong@ledmanager.com')->firstOrFail();
    $adminUser = User::where('email', 'admin@ledmanager.com')->firstOrFail();

    // Authenticate as agency user
    Auth::login($agencyHpUser);
    $agencyCustomers = CustomerResource::getEloquentQuery()->get();

    expect($agencyCustomers)->not->toBeEmpty();
    foreach ($agencyCustomers as $customer) {
        expect($customer->agency_id)->toEqual($agencyHpUser->agency_id);
    }

    // Authenticate as admin user - sees all customers
    Auth::login($adminUser);
    $adminCustomers = CustomerResource::getEloquentQuery()->get();

    expect($adminCustomers->count())->toBeGreaterThan($agencyCustomers->count());
});

test('agency scoped users only see pricing rules belonging to their agency', function () {
    $agencyHpUser = User::where('email', 'daily.haiphong@ledmanager.com')->firstOrFail();
    $adminUser = User::where('email', 'admin@ledmanager.com')->firstOrFail();

    // Authenticate as agency user
    Auth::login($agencyHpUser);
    $agencyRules = PricingRuleResource::getEloquentQuery()->get();

    expect($agencyRules)->not->toBeEmpty();
    foreach ($agencyRules as $rule) {
        expect($rule->agency_id)->toEqual($agencyHpUser->agency_id);
    }

    // Authenticate as admin user - sees all rules
    Auth::login($adminUser);
    $adminRules = PricingRuleResource::getEloquentQuery()->get();

    expect($adminRules->count())->toBeGreaterThan($agencyRules->count());
});

test('event calendar widget only fetches events belonging to agency scope', function () {
    $agencyHpUser = User::where('email', 'daily.haiphong@ledmanager.com')->firstOrFail();
    $adminUser = User::where('email', 'admin@ledmanager.com')->firstOrFail();

    $widget = new EventCalendarWidget;
    $info = [
        'start' => '2026-01-01',
        'end' => '2026-12-31',
    ];

    Auth::login($agencyHpUser);
    $agencyEvents = $widget->fetchEvents($info);

    Auth::login($adminUser);
    $adminEvents = $widget->fetchEvents($info);

    expect(count($agencyEvents))->toBeGreaterThan(0)
        ->and(count($adminEvents))->toBeGreaterThan(count($agencyEvents));
});

test('agency accounts cannot create or delete checkin batches', function () {
    $agencyUser = User::where('email', 'daily.haiphong@ledmanager.com')->firstOrFail();
    $adminUser = User::where('email', 'admin@ledmanager.com')->firstOrFail();
    $batch = CheckinBatch::firstOrFail();

    Auth::login($agencyUser);
    expect(CheckinBatchResource::canCreate())->toBeFalse()
        ->and(CheckinBatchResource::canDelete($batch))->toBeFalse()
        ->and(CheckinBatchResource::canDeleteAny())->toBeFalse()
        ->and(Gate::allows('create', CheckinBatch::class))->toBeFalse();

    Auth::login($adminUser);
    expect(CheckinBatchResource::canCreate())->toBeTrue()
        ->and(CheckinBatchResource::canDelete($batch))->toBeTrue()
        ->and(CheckinBatchResource::canDeleteAny())->toBeTrue()
        ->and(Gate::allows('create', CheckinBatch::class))->toBeTrue();
});
