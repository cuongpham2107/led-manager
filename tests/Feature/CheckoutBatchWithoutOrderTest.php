<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Filament\Resources\CheckoutBatches\Pages\ListCheckoutBatches;
use App\Models\Asset;
use App\Models\CheckoutBatch;
use App\Models\Customer;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReturnProcessingService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'code' => 'WH-TEST',
        'name' => 'Kho Test',
        'city' => 'Hà Nội',
        'is_active' => true,
    ]);

    $this->user = User::create([
        'name' => 'Admin User',
        'email' => 'admin.test@ledmanager.com',
        'password' => bcrypt('password123'),
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);

    // Create super_admin role and assign to user
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $permissions = [
        'ViewAny:CheckoutBatch',
        'Create:CheckoutBatch',
        'Update:CheckoutBatch',
        'Delete:CheckoutBatch',
        'CreateReturnBatch:CheckoutBatch',
    ];
    foreach ($permissions as $perm) {
        $p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        $role->givePermissionTo($p);
    }
    $this->user->assignRole($role);

    $this->customer = Customer::create([
        'name' => 'Khách Hàng Test',
        'phone' => '0912345678',
    ]);

    $this->productLine = ProductLine::create([
        'code' => 'P2.9',
        'name' => 'P2.9 Sự kiện',
    ]);

    $this->asset = Asset::create([
        'serial_no' => 'GE-R29-000104',
        'qr_code' => 'LED-GE-R29-000104',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::Ready,
        'size' => '0.5×1 m',
    ]);
});

test('can create checkout batch without an order in database', function () {
    $batch = CheckoutBatch::create([
        'code' => 'OUT-NO-ORD-01',
        'order_id' => null,
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => BatchStatus::Pending,
        'purpose' => 'Sự kiện',
        'note' => 'Test ghi chú',
        'created_by' => $this->user->id,
    ]);

    expect($batch->id)->not->toBeNull()
        ->and($batch->order_id)->toBeNull()
        ->and($batch->purpose)->toBe('Sự kiện')
        ->and($batch->order)->toBeNull();
});

test('list checkout batches page displays create action with modal heading', function () {
    actingAs($this->user);

    Livewire::test(ListCheckoutBatches::class)
        ->assertActionExists('create');
});

test('can create checkout batch through modal action on ListCheckoutBatches', function () {
    actingAs($this->user);

    Livewire::test(ListCheckoutBatches::class)
        ->callAction('create', [
            'code' => 'OUT-MODAL-TEST-01 · Hệ thống tự sinh',
            'note' => 'Xuất đi sự kiện thử nghiệm',
            'warehouse_id' => $this->warehouse->id,
            'customer_id' => $this->customer->id,
            'export_date' => now()->toDateString(),
            'expected_return_date' => now()->addDays(2)->toDateString(),
            'required_area_m2' => 20,
            'purpose' => 'Sự kiện',
            'selected_assets' => [$this->asset->id],
        ])
        ->assertHasNoActionErrors();

    $batch = CheckoutBatch::where('code', 'OUT-MODAL-TEST-01')->first();
    expect($batch)->not->toBeNull()
        ->and($batch->order_id)->toBeNull()
        ->and($batch->purpose)->toBe('Sự kiện')
        ->and($batch->note)->toBe('Xuất đi sự kiện thử nghiệm')
        ->and($batch->items)->toHaveCount(1);

    expect($this->asset->fresh()->current_status)->toBe(AssetStatus::InTransit);
});

test('checkout assets api returns ready assets with short name, type, and status', function () {
    actingAs($this->user);

    $response = $this->getJson(route('filament.checkout-assets', ['warehouse_id' => $this->warehouse->id]));

    $response->assertOk()
        ->assertJsonPath('total', 1)
        ->assertJsonPath('items.0.serial_no', 'GE-R29-000104')
        ->assertJsonPath('items.0.name', 'P2.9')
        ->assertJsonPath('items.0.size', '0.5×1 m')
        ->assertJsonPath('items.0.type', 'Sự kiện')
        ->assertJsonPath('items.0.status', 'ready')
        ->assertJsonPath('items.0.status_label', 'Sẵn sàng trong kho');
});

test('checkout assets api filters by warehouse and product line', function () {
    actingAs($this->user);

    $otherWarehouse = Warehouse::create([
        'code' => 'WH-OTHER',
        'name' => 'Kho Khác',
        'city' => 'TP.HCM',
        'is_active' => true,
    ]);

    $otherProductLine = ProductLine::create([
        'code' => 'P1.5',
        'name' => 'P1.5 Trong nhà',
    ]);

    $otherAsset = Asset::create([
        'serial_no' => 'OTHER-P15-001',
        'qr_code' => 'LED-OTHER-001',
        'product_line_id' => $otherProductLine->id,
        'current_warehouse_id' => $otherWarehouse->id,
        'current_status' => AssetStatus::Ready,
        'size' => '0.5×0.5 m',
    ]);

    // Query with first warehouse and first product line -> only original asset
    $res1 = $this->getJson(route('filament.checkout-assets', [
        'warehouse_id' => $this->warehouse->id,
        'product_line_id' => $this->productLine->id,
    ]));
    $res1->assertOk()->assertJsonPath('total', 1)->assertJsonPath('items.0.serial_no', 'GE-R29-000104');

    // Query with other warehouse and other product line -> only other asset
    $res2 = $this->getJson(route('filament.checkout-assets', [
        'warehouse_id' => $otherWarehouse->id,
        'product_line_id' => $otherProductLine->id,
    ]));
    $res2->assertOk()->assertJsonPath('total', 1)->assertJsonPath('items.0.serial_no', 'OTHER-P15-001');

    // Query with mismatch (warehouse 1, product line 2) -> empty
    $res3 = $this->getJson(route('filament.checkout-assets', [
        'warehouse_id' => $this->warehouse->id,
        'product_line_id' => $otherProductLine->id,
    ]));
    $res3->assertOk()->assertJsonPath('total', 0);
});

test('can process return for a checkout batch without an order', function () {
    $batch = CheckoutBatch::create([
        'code' => 'OUT-RET-TEST-01',
        'order_id' => null,
        'customer_id' => $this->customer->id,
        'warehouse_id' => $this->warehouse->id,
        'status' => BatchStatus::Dispatched,
        'created_by' => $this->user->id,
    ]);

    $item = $batch->items()->create([
        'asset_id' => $this->asset->id,
        'is_dispatched' => true,
    ]);

    $this->asset->update(['current_status' => AssetStatus::InEvent]);

    $service = app(ReturnProcessingService::class);
    $returnBatch = $service->processReturn(
        order: null,
        items: [
            [
                'asset_id' => $this->asset->id,
                'checkout_batch_item_id' => $item->id,
                'is_received' => true,
                'grade' => 'normal',
                'grade_note' => null,
            ],
        ],
        receivedBy: $this->user->id,
        completeBatchIds: [$batch->id],
        warehouseId: $this->warehouse->id,
    );

    expect($returnBatch)->not->toBeNull()
        ->and($batch->fresh()->status)->toBe(BatchStatus::Completed)
        ->and($this->asset->fresh()->current_status)->toBe(AssetStatus::Ready);
});
