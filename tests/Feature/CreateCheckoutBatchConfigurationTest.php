<?php

use App\Enums\AssetStatus;
use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Asset;
use App\Models\CheckoutBatch;
use App\Models\Customer;
use App\Models\LedConfiguration;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->warehouse = Warehouse::create(['code' => 'WH-CFG', 'name' => 'Kho Cấu Hình', 'city' => 'Hà Nội', 'is_active' => true]);
    $this->user = User::create([
        'name' => 'Admin Cấu Hình',
        'email' => 'admin.cfg@ledmanager.com',
        'password' => bcrypt('password123'),
        'is_active' => true,
    ]);
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    foreach (['ViewAny:Order', 'View:Order', 'Update:Order', 'CreateCheckoutBatch:Order'] as $name) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
    $this->user->assignRole($role);

    $this->line = ProductLine::factory()->create(['name' => 'P2.6 Sự kiện']);
    $this->otherLine = ProductLine::factory()->create(['name' => 'P3.9 Outdoor']);
    $this->configA = LedConfiguration::factory()->for($this->line)->create(['name' => 'Bộ A']);
    $this->configB = LedConfiguration::factory()->for($this->line)->create(['name' => 'Bộ B']);

    $makePanels = function (ProductLine $line, ?LedConfiguration $config, int $count) {
        Asset::factory()->count($count)->create([
            'product_line_id' => $line->id,
            'led_configuration_id' => $config?->id,
            'current_warehouse_id' => $this->warehouse->id,
            'current_status' => AssetStatus::Ready,
        ]);
    };
    $makePanels($this->line, $this->configA, 2);
    $makePanels($this->line, $this->configB, 5);
    $makePanels($this->otherLine, null, 4);

    $this->order = Order::create([
        'order_no' => 'ORD-CFG-01',
        'customer_id' => Customer::create(['name' => 'Khách Cấu Hình', 'phone' => '0900000000'])->id,
        'warehouse_id' => $this->warehouse->id,
        'request_date' => now()->addDays(30)->toDateString(),
        'expected_return_date' => now()->addDays(32)->toDateString(),
        'status' => OrderStatus::Draft,
        'event' => 'Sự kiện kiểm tra cấu hình',
    ]);
    $this->item = OrderItem::create([
        'order_id' => $this->order->id,
        'product_line_id' => $this->line->id,
        'quantity_required' => 3,
        'unit_price' => 350000,
    ]);

    actingAs($this->user);
});

function assignedAssets(Order $order)
{
    return Asset::whereIn('id', CheckoutBatch::where('order_id', $order->id)->firstOrFail()->items()->select('asset_id'))->get();
}

test('assigns panels only from the chosen configuration and remembers it on the order item', function () {
    Livewire::test(ListOrders::class)
        ->callTableAction('create_checkout_batch', $this->order, [
            'auto_assign_assets' => true,
            'lines' => [[
                'order_item_id' => $this->item->id,
                'product_line_id' => $this->line->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 3,
                'led_configuration_id' => $this->configB->id,
            ]],
        ])
        ->assertHasNoTableActionErrors();

    $assets = assignedAssets($this->order);

    expect($assets)->toHaveCount(3)
        ->and($assets->pluck('led_configuration_id')->unique()->all())->toBe([$this->configB->id])
        ->and($this->item->fresh()->led_configuration_id)->toBe($this->configB->id);
});

test('does not top up with other configurations or product lines when stock is short', function () {
    Livewire::test(ListOrders::class)
        ->callTableAction('create_checkout_batch', $this->order, [
            'auto_assign_assets' => true,
            'lines' => [[
                'order_item_id' => $this->item->id,
                'product_line_id' => $this->line->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 3,
                'led_configuration_id' => $this->configA->id,
            ]],
        ])
        ->assertHasNoTableActionErrors();

    $assets = assignedAssets($this->order);

    expect($assets)->toHaveCount(2)
        ->and($assets->pluck('led_configuration_id')->unique()->all())->toBe([$this->configA->id]);
});

test('without an explicit choice it picks the best configuration that covers the order', function () {
    Livewire::test(ListOrders::class)
        ->callTableAction('create_checkout_batch', $this->order, [
            'auto_assign_assets' => true,
        ])
        ->assertHasNoTableActionErrors();

    $assets = assignedAssets($this->order);

    expect($assets)->toHaveCount(3)
        ->and($assets->pluck('led_configuration_id')->unique()->all())->toBe([$this->configB->id]);
});
