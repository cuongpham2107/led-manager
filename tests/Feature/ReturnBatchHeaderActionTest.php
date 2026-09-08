<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\OrderStatus;
use App\Enums\ReturnBatchStatus;
use App\Filament\Resources\ReturnBatches\Pages\ListReturnBatches;
use App\Models\Asset;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductLine;
use App\Models\ReturnBatch;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'code' => 'WH-RET-TEST',
        'name' => 'Kho Test Nhập Trả',
        'city' => 'Hà Nội',
        'is_active' => true,
    ]);

    $this->user = User::create([
        'name' => 'Admin Return Test',
        'email' => 'admin.return@ledmanager.com',
        'password' => bcrypt('password123'),
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);

    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $permissions = [
        'ViewAny:ReturnBatch',
        'Create:ReturnBatch',
        'Update:ReturnBatch',
        'Delete:ReturnBatch',
        'ViewAny:CheckoutBatch',
    ];
    foreach ($permissions as $perm) {
        $p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        $role->givePermissionTo($p);
    }
    $this->user->assignRole($role);

    $this->customer = Customer::create([
        'name' => 'Khách Hàng Return',
        'phone' => '0988776655',
    ]);

    $this->productLine = ProductLine::create([
        'code' => 'P3.9',
        'name' => 'P3.9 Outdoor',
    ]);

    $this->asset = Asset::create([
        'serial_no' => 'RET-P39-000001',
        'qr_code' => 'LED-RET-P39-000001',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::InTransit,
        'size' => '0.5×1 m',
    ]);
});

test('list return batches page displays create action', function () {
    actingAs($this->user);

    Livewire::test(ListReturnBatches::class)
        ->assertActionExists('create');
});

test('can create return batch for a checkout batch via modal action', function () {
    actingAs($this->user);

    $order = Order::create([
        'order_no' => 'ORD-TEST-RET-01',
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'request_date' => now()->toDateString(),
        'status' => OrderStatus::Dispatched,
        'event' => 'Sự kiện Test Trả',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
    ]);

    $batch = CheckoutBatch::create([
        'code' => 'OUT-TEST-RET-01',
        'order_id' => $order->id,
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => BatchStatus::Dispatched,
        'created_by' => $this->user->id,
    ]);

    CheckoutBatchItem::create([
        'checkout_batch_id' => $batch->id,
        'asset_id' => $this->asset->id,
        'is_dispatched' => true,
        'dispatched_by' => $this->user->id,
        'dispatched_at' => now(),
    ]);

    $test = Livewire::test(ListReturnBatches::class)
        ->callAction('create', [
            'checkout_batch_id' => $batch->id,
            'note' => 'Thiết bị trả về nguyên vẹn',
        ])
        ->assertHasNoActionErrors();

    $mountedActions = $test->get('mountedActions');
    expect($mountedActions)->toBeArray()
        ->and($mountedActions[0]['name'] ?? null)->toBe('edit');

    $returnBatch = ReturnBatch::where('checkout_batch_id', $batch->id)->first();
    expect($returnBatch)->not->toBeNull()
        ->and($returnBatch->note)->toBe('Thiết bị trả về nguyên vẹn')
        ->and($returnBatch->status)->toBe(ReturnBatchStatus::InProgress)
        ->and($returnBatch->items()->count())->toBe(1)
        ->and($returnBatch->items()->first()->is_received)->toBeFalse();

    // Receiving item and completing batch
    $returnBatch->complete($this->user);

    expect($returnBatch->fresh()->status)->toBe(ReturnBatchStatus::Completed);
    expect($batch->fresh()->status)->toBe(BatchStatus::Completed);
    expect($order->fresh()->status)->toBe(OrderStatus::Returned);
});

test('can create return batch for a checkout batch without order via modal action', function () {
    actingAs($this->user);

    $batch = CheckoutBatch::create([
        'code' => 'OUT-NO-ORD-RET-01',
        'order_id' => null,
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => BatchStatus::InProgress,
        'purpose' => 'Sự kiện Demo',
        'created_by' => $this->user->id,
    ]);

    CheckoutBatchItem::create([
        'checkout_batch_id' => $batch->id,
        'asset_id' => $this->asset->id,
        'is_dispatched' => true,
        'dispatched_by' => $this->user->id,
        'dispatched_at' => now(),
    ]);

    Livewire::test(ListReturnBatches::class)
        ->callAction('create', [
            'checkout_batch_id' => $batch->id,
            'note' => 'Trả hàng không qua đơn',
        ])
        ->assertHasNoActionErrors();

    $returnBatch = ReturnBatch::where('checkout_batch_id', $batch->id)->first();
    expect($returnBatch)->not->toBeNull()
        ->and($returnBatch->note)->toBe('Trả hàng không qua đơn')
        ->and($returnBatch->status)->toBe(ReturnBatchStatus::InProgress)
        ->and($returnBatch->items()->count())->toBe(1);

    $returnBatch->complete($this->user);

    expect($returnBatch->fresh()->status)->toBe(ReturnBatchStatus::Completed);
    expect($batch->fresh()->status)->toBe(BatchStatus::Completed);
});
