<?php

use App\Filament\Resources\CheckinBatches\Pages\CreateCheckinBatch;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\LedOsDataSeeder;
use Livewire\Livewire;

beforeEach(function () {
    (new LedOsDataSeeder)->run();
});

test('creating a check-in batch with selected assets adds them as pending (chưa quét)', function () {
    $user = User::where('email', 'admin@ledmanager.com')->firstOrFail();
    $warehouse = Warehouse::where('is_active', true)->firstOrFail();
    $productLine = ProductLine::where('is_active', true)->firstOrFail();

    $assetA = Asset::create(['serial_no' => 'CREATE-PENDING-A', 'product_line_id' => $productLine->id, 'size' => '0.5x0.5']);
    $assetB = Asset::create(['serial_no' => 'CREATE-PENDING-B', 'product_line_id' => $productLine->id, 'size' => '0.5x0.5']);

    Livewire::actingAs($user)
        ->test(CreateCheckinBatch::class)
        ->assertSuccessful()
        ->fillForm([
            'code' => 'IN-CREATE-01',
            'warehouse_id' => $warehouse->id,
            'selected_assets' => [$assetA->id, $assetB->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $items = CheckinBatchItem::whereIn('asset_id', [$assetA->id, $assetB->id])->get();

    expect($items)->toHaveCount(2)
        ->and($items->every(fn ($i) => (bool) $i->is_received === false))->toBeTrue();

    // Mục tiêu (quantity) khớp số thiết bị đã chọn -> tiến độ web/app nhất quán.
    $batch = CheckinBatch::where('code', 'IN-CREATE-01')->firstOrFail();
    expect((int) $batch->quantity)->toBe(2);
});
