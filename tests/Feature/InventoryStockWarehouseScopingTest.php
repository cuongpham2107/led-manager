<?php

use App\Filament\Resources\InventoryStocks\Pages\ListInventoryStocks;
use App\Models\Asset;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseLocation;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('scopes inventory stocks table by user warehouse for non-admin but allows full visibility for super_admin and admin', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $whRole = Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);
    $perm = Permission::firstOrCreate(['name' => 'ViewAny:Asset', 'guard_name' => 'web']);
    $whRole->givePermissionTo($perm);

    $whHn = Warehouse::firstOrCreate(['code' => 'WH-HN-TEST'], ['name' => 'Kho Hà Nội Test', 'is_active' => true]);
    $whHcm = Warehouse::firstOrCreate(['code' => 'WH-HCM-TEST'], ['name' => 'Kho HCM Test', 'is_active' => true]);

    $locHn = WarehouseLocation::firstOrCreate(['warehouse_id' => $whHn->id, 'name' => 'Kệ A1 - HN']);
    $locHcm = WarehouseLocation::firstOrCreate(['warehouse_id' => $whHcm->id, 'name' => 'Kệ B1 - HCM']);

    $managerHn = User::factory()->create([
        'warehouse_id' => $whHn->id,
    ]);
    $managerHn->assignRole('warehouse_manager');

    $managerHcm = User::factory()->create([
        'warehouse_id' => $whHcm->id,
    ]);
    $managerHcm->assignRole('warehouse_manager');

    $superAdmin = User::factory()->create([
        'warehouse_id' => $whHn->id, // Even if super_admin is assigned a warehouse_id
    ]);
    $superAdmin->assignRole('super_admin');

    $admin = User::factory()->create([
        'warehouse_id' => $whHcm->id, // Even if admin is assigned a warehouse_id
    ]);
    $admin->assignRole('admin');

    $assetHn = Asset::factory()->create([
        'serial_no' => 'LED-HN-001',
        'current_warehouse_id' => $whHn->id,
        'warehouse_location_id' => $locHn->id,
        'product_line_id' => null,
    ]);

    $assetHcm = Asset::factory()->create([
        'serial_no' => 'LED-HCM-001',
        'current_warehouse_id' => $whHcm->id,
        'warehouse_location_id' => $locHcm->id,
        'product_line_id' => null,
    ]);

    // 1. Warehouse Manager Hà Nội -> only sees HN and scoped page title
    $this->actingAs($managerHn);
    $compHn = Livewire::test(ListInventoryStocks::class)
        ->assertSuccessful()
        ->assertSee('LED-HN-001')
        ->assertDontSee('LED-HCM-001');
    expect($compHn->instance()->getTitle())->toBe('Tài sản trong kho • '.$whHn->name);

    // 2. Warehouse Manager HCM -> only sees HCM
    $this->actingAs($managerHcm);
    $compHcm = Livewire::test(ListInventoryStocks::class)
        ->assertSuccessful()
        ->assertSee('LED-HCM-001')
        ->assertDontSee('LED-HN-001');
    expect($compHcm->instance()->getTitle())->toBe('Tài sản trong kho • '.$whHcm->name);

    // 3. Super Admin (even with warehouse_id) -> sees both HN & HCM, and title is global
    $this->actingAs($superAdmin);
    $compSuper = Livewire::test(ListInventoryStocks::class)
        ->assertSuccessful()
        ->assertSee('LED-HN-001')
        ->assertSee('LED-HCM-001');
    expect($compSuper->instance()->getTitle())->toBe('Tất cả tài sản trong kho');

    // Super Admin can filter by selecting warehouse and location
    $compSuper->call('selectWarehouse', $whHn->id)
        ->assertSee('LED-HN-001')
        ->assertDontSee('LED-HCM-001');

    $compSuper->call('selectLocation', $whHn->id, (string) $locHn->id)
        ->assertSee('LED-HN-001')
        ->assertDontSee('LED-HCM-001');

    // 4. Admin (even with warehouse_id) -> sees both HN & HCM, and title is global
    $this->actingAs($admin);
    $compAdmin = Livewire::test(ListInventoryStocks::class)
        ->assertSuccessful()
        ->assertSee('LED-HN-001')
        ->assertSee('LED-HCM-001');
    expect($compAdmin->instance()->getTitle())->toBe('Tất cả tài sản trong kho');
});

it('supports explorer tree navigation by warehouse and location', function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $whRole = Role::firstOrCreate(['name' => 'warehouse_manager', 'guard_name' => 'web']);
    $perm = Permission::firstOrCreate(['name' => 'ViewAny:Asset', 'guard_name' => 'web']);
    $whRole->givePermissionTo($perm);

    $whA = Warehouse::firstOrCreate(['code' => 'WH-TREE-A'], ['name' => 'Kho Cầu Giấy', 'is_active' => true]);
    $whB = Warehouse::firstOrCreate(['code' => 'WH-TREE-B'], ['name' => 'Kho Thanh Xuân', 'is_active' => true]);

    $locA1 = WarehouseLocation::firstOrCreate(['warehouse_id' => $whA->id, 'name' => 'Kệ G1']);
    $locA2 = WarehouseLocation::firstOrCreate(['warehouse_id' => $whA->id, 'name' => 'Kệ G2']);

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole('super_admin');

    $manager = User::factory()->create(['warehouse_id' => $whA->id]);
    $manager->assignRole('warehouse_manager');

    // Asset in Loc A1
    Asset::factory()->create([
        'serial_no' => 'LED-TREE-001',
        'current_warehouse_id' => $whA->id,
        'warehouse_location_id' => $locA1->id,
    ]);

    // Asset in Loc A2
    Asset::factory()->create([
        'serial_no' => 'LED-TREE-002',
        'current_warehouse_id' => $whA->id,
        'warehouse_location_id' => $locA2->id,
    ]);

    // Unassigned asset in Wh A
    Asset::factory()->create([
        'serial_no' => 'LED-TREE-UNASSIGNED',
        'current_warehouse_id' => $whA->id,
        'warehouse_location_id' => null,
    ]);

    // Asset in Wh B
    Asset::factory()->create([
        'serial_no' => 'LED-TREE-WH-B',
        'current_warehouse_id' => $whB->id,
        'warehouse_location_id' => null,
    ]);

    $this->actingAs($superAdmin);

    // 1. Initial view: see all assets and tree data
    $test = Livewire::test(ListInventoryStocks::class)
        ->assertSuccessful()
        ->assertSee('Kho Cầu Giấy')
        ->assertSee('Kho Thanh Xuân')
        ->assertSee('LED-TREE-001')
        ->assertSee('LED-TREE-WH-B');

    $treeData = $test->instance()->getTreeData();
    expect($treeData['grand_total'])->toBeGreaterThanOrEqual(4);

    // 2. Click warehouse A -> filters to WH A only
    $test->call('selectWarehouse', $whA->id)
        ->assertSee('LED-TREE-001')
        ->assertSee('LED-TREE-002')
        ->assertSee('LED-TREE-UNASSIGNED')
        ->assertDontSee('LED-TREE-WH-B');

    // 3. Click location A1 -> filters to location A1 only
    $test->call('selectLocation', $whA->id, (string) $locA1->id)
        ->assertSee('LED-TREE-001')
        ->assertDontSee('LED-TREE-002')
        ->assertDontSee('LED-TREE-UNASSIGNED')
        ->assertDontSee('LED-TREE-WH-B');

    // 4. Click unassigned -> filters to unassigned assets in WH A
    $test->call('selectLocation', $whA->id, 'unassigned')
        ->assertSee('LED-TREE-UNASSIGNED')
        ->assertDontSee('LED-TREE-001')
        ->assertDontSee('LED-TREE-002');

    // 5. Click selectAll -> resets filter to see all
    $test->call('selectAll')
        ->assertSee('LED-TREE-001')
        ->assertSee('LED-TREE-WH-B');

    // 6. Scoped warehouse manager only sees their warehouse in tree
    $this->actingAs($manager);
    $mgrTest = Livewire::test(ListInventoryStocks::class)
        ->assertSuccessful()
        ->assertSee('Kho Cầu Giấy')
        ->assertDontSee('Kho Thanh Xuân')
        ->assertSee('LED-TREE-001')
        ->assertDontSee('LED-TREE-WH-B');
});
