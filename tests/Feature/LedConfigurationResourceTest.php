<?php

use App\Enums\AssetStatus;
use App\Enums\LedScanMode;
use App\Filament\Resources\CheckinBatches\Pages\ListCheckinBatches;
use App\Filament\Resources\LedConfigurations\Pages\ListLedConfigurations;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\LedConfiguration;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\LedSetGuard;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->warehouse = Warehouse::create(['code' => 'WH-RES', 'name' => 'Kho Resource', 'city' => 'Hà Nội', 'is_active' => true]);
    $this->user = User::create(['name' => 'Admin Resource', 'email' => 'admin.res@ledmanager.com', 'password' => bcrypt('password123'), 'is_active' => true]);
    $role = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    foreach (['ViewAny:LedConfiguration', 'Create:LedConfiguration', 'Update:LedConfiguration', 'ViewAny:CheckinBatch', 'Create:CheckinBatch', 'Update:CheckinBatch'] as $name) {
        $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
    }
    $this->user->assignRole($role);
    $this->line = ProductLine::factory()->create(['name' => 'P2.6 Sự kiện']);
    actingAs($this->user);
});

test('LED configuration list page renders for authorised users', function () {
    $config = LedConfiguration::factory()->for($this->line)->create(['receiving_card' => 'Novastar A5s Plus']);

    Livewire::test(ListLedConfigurations::class)
        ->assertOk()
        ->assertCanSeeTableRecords([$config]);
});

test('can create a LED configuration from the modal', function () {
    Livewire::test(ListLedConfigurations::class)
        ->callAction('create', [
            'product_line_id' => $this->line->id,
            'name' => 'Lô 09/2026',
            'receiving_card' => 'Novastar A5s Plus',
            'scan_mode' => LedScanMode::Sixteenth->value,
            'controller_model' => 'Novastar VX600',
            'is_active' => true,
        ])
        ->assertHasNoActionErrors();

    expect(LedConfiguration::where('name', 'Lô 09/2026')->where('product_line_id', $this->line->id)->exists())->toBeTrue();
});

test('duplicate configuration of the same product line is rejected by the form', function () {
    LedConfiguration::factory()->for($this->line)->create(['receiving_card' => 'Novastar A5s Plus', 'scan_mode' => LedScanMode::Sixteenth, 'controller_model' => 'Novastar VX600']);

    Livewire::test(ListLedConfigurations::class)
        ->callAction('create', [
            'product_line_id' => $this->line->id,
            'name' => 'Trùng',
            'receiving_card' => 'Novastar A5s Plus',
            'scan_mode' => LedScanMode::Sixteenth->value,
            'controller_model' => 'Novastar VX600',
        ])
        ->assertHasActionErrors(['receiving_card' => 'unique']);
});

test('check-in batch configuration is applied to selected assets without one and blocks different ones', function () {
    $config = LedConfiguration::factory()->for($this->line)->create();
    $other = LedConfiguration::factory()->for($this->line)->create();
    $fresh = Asset::factory()->create(['product_line_id' => $this->line->id, 'led_configuration_id' => null, 'current_status' => AssetStatus::NewlyAdded, 'current_warehouse_id' => null]);
    $mismatched = Asset::factory()->create(['product_line_id' => $this->line->id, 'led_configuration_id' => $other->id, 'current_status' => AssetStatus::NewlyAdded, 'current_warehouse_id' => null]);

    expect(LedSetGuard::applyBatchConfiguration($config->id, [$fresh->id, $mismatched->id]))->toContain($mismatched->serial_no)
        ->and($fresh->fresh()->led_configuration_id)->toBeNull();

    Livewire::test(ListCheckinBatches::class)
        ->callAction('create', [
            'code' => 'IN-CFG-01',
            'warehouse_id' => $this->warehouse->id,
            'led_configuration_id' => $config->id,
            'selected_assets' => [$fresh->id],
        ])
        ->assertHasNoActionErrors();

    expect(CheckinBatch::where('code', 'IN-CFG-01')->value('led_configuration_id'))->toBe($config->id)
        ->and($fresh->fresh()->led_configuration_id)->toBe($config->id);
});
