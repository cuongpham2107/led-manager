<?php

use App\Enums\AssetStatus;
use App\Enums\RepairResultStatus;
use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Models\Asset;
use App\Models\AssetStatusLog;
use App\Models\RepairLog;
use App\Models\User;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

test('send to maintenance bulk action marks assets as repairing and creates repair logs', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    $this->actingAs($user);

    $assets = Asset::where('current_status', AssetStatus::Ready)->take(3)->get();
    expect($assets)->toHaveCount(3);

    Livewire::test(ListAssets::class)
        ->callTableBulkAction('send_to_maintenance_bulk', $assets, [
            'start_date' => '2026-08-29',
            'created_by' => $user->id,
            'repair_cost' => 150000,
            'repair_note' => 'Kiểm tra điểm chết bóng LED P3.91',
        ])
        ->assertHasNoTableBulkActionErrors();

    foreach ($assets as $asset) {
        $asset->refresh();
        expect($asset->current_status)->toBe(AssetStatus::Repairing);

        $repairLog = RepairLog::where('asset_id', $asset->id)->latest()->first();
        expect($repairLog)->not->toBeNull()
            ->and($repairLog->result_status)->toBe(RepairResultStatus::Pending)
            ->and((float) $repairLog->repair_cost)->toBe(150000.0)
            ->and($repairLog->repair_note)->toBe('Kiểm tra điểm chết bóng LED P3.91');

        $statusLog = AssetStatusLog::where('asset_id', $asset->id)
            ->where('to_status', AssetStatus::Repairing)
            ->latest()
            ->first();
        expect($statusLog)->not->toBeNull();
    }
});

test('complete maintenance bulk action returns assets to ready status', function () {
    (new LedOsDataSeeder)->run();

    $user = User::where('email', 'admin@ledmanager.com')->first();
    $this->actingAs($user);

    $assets = Asset::where('current_status', AssetStatus::Ready)->take(2)->get();

    // First send to maintenance
    Livewire::test(ListAssets::class)
        ->callTableBulkAction('send_to_maintenance_bulk', $assets, [
            'start_date' => '2026-08-29',
            'created_by' => $user->id,
            'repair_note' => 'Cần bảo trì',
        ]);

    // Now complete maintenance
    Livewire::test(ListAssets::class)
        ->callTableBulkAction('complete_maintenance_bulk', $assets, [
            'end_date' => '2026-08-30',
            'result_status' => 'fixed',
            'repair_cost' => 200000,
            'repair_note' => 'Đã thay IC driver, chạy test đạt chuẩn',
        ])
        ->assertHasNoTableBulkActionErrors();

    foreach ($assets as $asset) {
        $asset->refresh();
        expect($asset->current_status)->toBe(AssetStatus::Ready);

        $repairLog = RepairLog::where('asset_id', $asset->id)->latest()->first();
        expect($repairLog->result_status)->toBe(RepairResultStatus::Fixed)
            ->and((float) $repairLog->repair_cost)->toBe(200000.0);
    }
});
