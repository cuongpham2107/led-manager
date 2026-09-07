<?php

use App\Enums\AssetStatus;
use App\Enums\RepairResultStatus;
use App\Filament\Resources\RepairLogs\Pages\ListRepairLogs;
use App\Models\Asset;
use App\Models\ProductLine;
use App\Models\RepairLog;
use App\Models\User;
use App\Models\Warehouse;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'code' => 'WH-REP-01',
        'name' => 'Kho Sửa Chữa',
        'city' => 'Hà Nội',
        'is_active' => true,
    ]);

    $this->user = User::create([
        'name' => 'Kỹ Thuật Viên Test',
        'email' => 'tech.test@ledmanager.com',
        'password' => bcrypt('password123'),
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);

    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    $permissions = [
        'ViewAny:RepairLog',
        'Create:RepairLog',
        'Update:RepairLog',
        'Delete:RepairLog',
    ];
    foreach ($permissions as $perm) {
        $p = Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        $role->givePermissionTo($p);
    }
    $this->user->assignRole($role);

    $this->productLine = ProductLine::create([
        'code' => 'P3.9-TEST',
        'name' => 'P3.9 Sửa Chữa',
    ]);

    $this->asset = Asset::create([
        'serial_no' => 'REP-ASSET-001',
        'qr_code' => 'LED-REP-001',
        'product_line_id' => $this->productLine->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::Ready,
        'size' => '0.5×1 m',
    ]);
});

test('create repair log modal hides result_status and created_by and uses defaults', function () {
    actingAs($this->user);

    Livewire::test(ListRepairLogs::class)
        ->assertActionExists('create')
        ->callAction('create', [
            'asset_id' => $this->asset->id,
            'start_date' => now()->toDateString(),
            'repair_cost' => 500000,
            'repair_note' => 'Cháy nguồn module LED 1',
        ])
        ->assertHasNoActionErrors();

    $log = RepairLog::where('asset_id', $this->asset->id)->first();
    expect($log)->not->toBeNull()
        ->and($log->result_status)->toBe(RepairResultStatus::Pending)
        ->and($log->created_by)->toBe($this->user->id)
        ->and((float) $log->repair_cost)->toBe(500000.0)
        ->and($this->asset->fresh()->current_status)->toBe(AssetStatus::Repairing);
});

test('edit repair log allows modifying result_status and created_by', function () {
    actingAs($this->user);

    $log = RepairLog::create([
        'asset_id' => $this->asset->id,
        'start_date' => now()->toDateString(),
        'result_status' => RepairResultStatus::Pending,
        'created_by' => $this->user->id,
        'repair_note' => 'Cần bảo trì',
    ]);

    $otherUser = User::create([
        'name' => 'Kỹ Thuật 2',
        'email' => 'tech2@ledmanager.com',
        'password' => bcrypt('password123'),
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);

    Livewire::test(ListRepairLogs::class)
        ->callTableAction('edit', $log, [
            'result_status' => RepairResultStatus::Fixed->value,
            'end_date' => now()->toDateString(),
            'created_by' => $otherUser->id,
            'repair_note' => 'Đã thay IC nguồn thành công',
        ])
        ->assertHasNoTableActionErrors();

    $log->refresh();
    expect($log->result_status)->toBe(RepairResultStatus::Fixed)
        ->and($log->created_by)->toBe($otherUser->id)
        ->and($log->end_date)->not->toBeNull();
});
