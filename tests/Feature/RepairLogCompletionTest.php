<?php

use App\Enums\AssetStatus;
use App\Enums\RepairResultStatus;
use App\Filament\Resources\RepairLogs\Pages\ListRepairLogs;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\RepairLog;
use App\Models\User;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

/**
 * Seed dữ liệu và dựng sẵn một phiếu sửa chữa đang chờ xử lý (Repairing + Pending).
 *
 * @return array{0: User, 1: Asset, 2: RepairLog}
 */
function seedPendingRepairTicket(): array
{
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->firstOrFail();

    $asset = Asset::where('current_status', AssetStatus::Ready)->firstOrFail();
    $asset->update(['current_status' => AssetStatus::Repairing]);

    $repairLog = RepairLog::create([
        'asset_id' => $asset->id,
        'start_date' => now()->toDateString(),
        'repair_note' => 'Chờ kiểm tra kỹ thuật',
        'result_status' => RepairResultStatus::Pending,
        'created_by' => $user->id,
    ]);

    return [$user, $asset, $repairLog];
}

test('complete repair action closes the ticket and returns the asset to ready', function () {
    [$user, $asset, $repairLog] = seedPendingRepairTicket();
    actingAs($user);

    Livewire::test(ListRepairLogs::class)
        ->assertTableActionVisible('complete_repair', $repairLog)
        ->callTableAction('complete_repair', $repairLog, [
            'end_date' => '2026-08-30',
            'result_status' => 'fixed',
            'repair_cost' => '500,000',
            'repair_note' => 'Đã thay module LED',
        ])
        ->assertHasNoTableActionErrors();

    $repairLog->refresh();

    expect($repairLog->result_status)->toBe(RepairResultStatus::Fixed)
        ->and($repairLog->end_date?->toDateString())->toBe('2026-08-30')
        ->and((float) $repairLog->repair_cost)->toBe(500000.0)
        ->and($repairLog->repair_note)->toContain('Đã thay module LED')
        ->and($asset->fresh()->current_status)->toBe(AssetStatus::Ready);

    $statusLog = AssetStatusLog::where('asset_id', $asset->id)
        ->where('source_type', RepairLog::class)
        ->where('source_id', $repairLog->id)
        ->latest('id')
        ->first();

    expect($statusLog)->not->toBeNull()
        ->and($statusLog->from_status)->toBe(AssetStatus::Repairing)
        ->and($statusLog->to_status)->toBe(AssetStatus::Ready);
});

test('complete repair action can dispose an asset that cannot be repaired', function () {
    [$user, $asset, $repairLog] = seedPendingRepairTicket();
    actingAs($user);

    Livewire::test(ListRepairLogs::class)
        ->callTableAction('complete_repair', $repairLog, [
            'end_date' => now()->toDateString(),
            'result_status' => 'disposed',
        ])
        ->assertHasNoTableActionErrors();

    expect($repairLog->fresh()->result_status)->toBe(RepairResultStatus::Disposed)
        ->and($asset->fresh()->current_status)->toBe(AssetStatus::Disposed);
});

test('complete repair action is hidden once the ticket is already closed', function () {
    [$user, $asset, $repairLog] = seedPendingRepairTicket();
    actingAs($user);

    Livewire::test(ListRepairLogs::class)
        ->assertTableActionVisible('complete_repair', $repairLog);

    $repairLog->update([
        'result_status' => RepairResultStatus::Fixed,
        'end_date' => now()->toDateString(),
    ]);
    $asset->update(['current_status' => AssetStatus::Ready]);

    Livewire::test(ListRepairLogs::class)
        ->assertTableActionHidden('complete_repair', $repairLog->fresh());
});
