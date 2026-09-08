<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Filament\Resources\CheckoutBatches\Pages\ListCheckoutBatches;
use App\Models\Asset;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\Customer;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'code' => 'WH-EDIT-TEST',
        'name' => 'Kho Test Sửa Xuất',
        'city' => 'Hà Nội',
        'is_active' => true,
    ]);

    $this->user = User::create([
        'name' => 'Admin Edit User',
        'email' => 'admin.edit@ledmanager.com',
        'password' => bcrypt('password123'),
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);

    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $permissions = [
        'ViewAny:CheckoutBatch',
        'Create:CheckoutBatch',
        'Update:CheckoutBatch',
        'Delete:CheckoutBatch',
    ];
    foreach ($permissions as $perm) {
        $p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        $role->givePermissionTo($p);
    }
    $this->user->assignRole($role);

    $this->customer = Customer::create([
        'name' => 'Khách Hàng Edit',
        'phone' => '0987654321',
    ]);

    $this->productLine = ProductLine::create([
        'code' => 'P3.9',
        'name' => 'P3.9 Ngoài trời',
    ]);

    $this->asset1 = Asset::create([
        'serial_no' => 'OUT-P39-001',
        'qr_code' => 'LED-OUT-001',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::InTransit,
        'size' => '0.5×1 m',
    ]);

    $this->asset2 = Asset::create([
        'serial_no' => 'OUT-P39-002',
        'qr_code' => 'LED-OUT-002',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::Ready,
        'size' => '0.5×1 m',
    ]);

    $this->batch = CheckoutBatch::create([
        'code' => 'OUT-EDIT-BATCH-01',
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => BatchStatus::InProgress,
        'created_by' => $this->user->id,
    ]);

    CheckoutBatchItem::create([
        'checkout_batch_id' => $this->batch->id,
        'asset_id' => $this->asset1->id,
        'is_dispatched' => true,
        'dispatched_by' => $this->user->id,
        'dispatched_at' => now(),
    ]);
});

test('can add and remove assets when saving modal edit in CheckoutBatchesTable', function () {
    actingAs($this->user);

    // Initial state: asset1 is in batch, asset2 is ready
    expect($this->batch->items)->toHaveCount(1)
        ->and($this->asset1->fresh()->current_status)->toBe(AssetStatus::InTransit)
        ->and($this->asset2->fresh()->current_status)->toBe(AssetStatus::Ready);

    // Modal Edit: remove asset1, add asset2
    Livewire::test(ListCheckoutBatches::class)
        ->callTableAction('edit', $this->batch, [
            'selected_assets' => [$this->asset2->id],
        ])
        ->assertHasNoTableActionErrors();

    // Now batch should have only asset2
    $this->batch->refresh();
    expect($this->batch->items)->toHaveCount(1)
        ->and($this->batch->items->first()->asset_id)->toBe($this->asset2->id);

    // asset1 should be reverted to Ready, asset2 should now be InTransit
    expect($this->asset1->fresh()->current_status)->toBe(AssetStatus::Ready);
    expect($this->asset2->fresh()->current_status)->toBe(AssetStatus::InTransit);
});
