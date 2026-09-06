<?php

use App\Filament\Resources\Assets\Pages\ListAssets;
use App\Models\Asset;
use App\Models\User;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

beforeEach(function () {
    (new LedOsDataSeeder)->run();
});

test('assets table displays operating hours and rental count columns with proper format', function () {
    $admin = User::where('email', 'admin@ledmanager.com')->first();
    $this->actingAs($admin);

    $asset = Asset::first();
    $asset->update([
        'operating_hours' => 890,
        'rental_count' => 21,
    ]);

    Livewire::actingAs($admin)
        ->test(ListAssets::class)
        ->searchTable($asset->serial_no)
        ->assertCanSeeTableRecords([$asset])
        ->assertTableColumnExists('operating_hours')
        ->assertTableColumnExists('rental_count')
        ->assertSee('890 h')
        ->assertSee('21');
});

test('asset working history component renders checkout batch and return inspection details', function () {
    $admin = User::where('email', 'admin@ledmanager.com')->first();
    $this->actingAs($admin);

    $asset = Asset::whereHas('checkoutBatchItems')->first();
    expect($asset)->not->toBeNull();

    $item = $asset->checkoutBatchItems()->with(['checkoutBatch.order.customer', 'returnBatchItem'])->first();
    expect($item)->not->toBeNull();

    $view = view('filament.components.asset-working-history', [
        'history' => $asset->checkoutBatchItems()->with(['checkoutBatch.order.customer', 'checkoutBatch.warehouse', 'returnBatchItem'])->get(),
        'repairLogs' => $asset->repairLogs()->with('creator')->get(),
        'record' => $asset,
    ])->render();

    expect($view)->toContain('Số giờ chạy')
        ->and($view)->toContain('Số lần cho thuê')
        ->and($view)->toContain($item->checkoutBatch->code)
        ->and($view)->toContain($item->checkoutBatch->order->order_no);
});

test('admin can update asset operating hours and rental count via edit action modal', function () {
    $admin = User::where('email', 'admin@ledmanager.com')->first();
    $this->actingAs($admin);

    $asset = Asset::first();

    Livewire::actingAs($admin)
        ->test(ListAssets::class)
        ->callTableAction('edit', $asset, data: [
            'serial_no' => $asset->serial_no,
            'current_status' => $asset->current_status->value,
            'current_warehouse_id' => $asset->current_warehouse_id,
            'operating_hours' => 1250,
            'rental_count' => 30,
        ])
        ->assertHasNoTableActionErrors();

    $asset->refresh();
    expect($asset->operating_hours)->toBe(1250)
        ->and($asset->rental_count)->toBe(30);
});
