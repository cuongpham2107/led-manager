<?php

use App\Models\Asset;
use App\Models\User;
use Database\Seeders\LedOsDataSeeder;

beforeEach(function () {
    (new LedOsDataSeeder)->run();
});

test('guest can view public asset detail page via QR code route', function () {
    $asset = Asset::whereNotNull('serial_no')->first();
    expect($asset)->not->toBeNull();

    $response = $this->get(route('asset.public.show', ['code' => $asset->qr_code ?: $asset->serial_no]));

    $response->assertStatus(200)
        ->assertSee($asset->serial_no)
        ->assertSee('Số giờ chạy')
        ->assertSee('Số lần cho thuê');
});

test('public page displays product line specs correctly', function () {
    $asset = Asset::whereHas('productLine')->first();
    expect($asset)->not->toBeNull();

    $response = $this->get(route('asset.public.show', ['code' => $asset->serial_no]));

    $response->assertStatus(200)
        ->assertSee($asset->productLine->name);
});

test('public page displays rental history when asset has checkout items', function () {
    $asset = Asset::whereHas('checkoutBatchItems')->first();
    expect($asset)->not->toBeNull();

    $item = $asset->checkoutBatchItems()->with('checkoutBatch')->first();

    $response = $this->get(route('asset.public.show', ['code' => $asset->serial_no]));

    $response->assertStatus(200)
        ->assertSee($item->checkoutBatch->code);
});

test('public page displays repair history when asset has repair logs', function () {
    $asset = Asset::whereHas('repairLogs')->first();

    if (! $asset) {
        $this->markTestSkipped('No assets with repair logs found in seeder data.');
    }

    $response = $this->get(route('asset.public.show', ['code' => $asset->serial_no]));

    $response->assertStatus(200)
        ->assertSee('Bảo dưỡng');
});

test('public page shows not found state for invalid code', function () {
    $response = $this->get(route('asset.public.show', ['code' => 'INVALID-CODE-999']));

    $response->assertStatus(200)
        ->assertSee('Không tìm thấy thiết bị')
        ->assertSee('INVALID-CODE-999');
});

test('public page supports lookup by qr_code, serial_no, and numeric id', function () {
    $asset = Asset::whereNotNull('qr_code')->first();
    expect($asset)->not->toBeNull();

    // By qr_code
    $this->get(route('asset.public.show', ['code' => $asset->qr_code]))
        ->assertStatus(200)
        ->assertSee($asset->serial_no);

    // By serial_no
    $this->get(route('asset.public.show', ['code' => $asset->serial_no]))
        ->assertStatus(200)
        ->assertSee($asset->serial_no);

    // By numeric id
    $this->get(route('asset.public.show', ['code' => (string) $asset->id]))
        ->assertStatus(200)
        ->assertSee($asset->serial_no);
});

test('api asset lookup extracts code from URL-encoded QR', function () {
    $admin = User::where('email', 'admin@ledmanager.com')->first();
    $asset = Asset::whereNotNull('qr_code')->first();
    expect($asset)->not->toBeNull();

    $urlCode = 'https://example.com/q/'.($asset->qr_code ?: $asset->serial_no);

    $this->actingAs($admin, 'sanctum')
        ->getJson(route('api.v1.assets.lookup', ['code' => $urlCode]))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.asset.serial_no', $asset->serial_no);
});

test('public page does not expose financial data', function () {
    $asset = Asset::whereNotNull('purchase_cost')
        ->where('purchase_cost', '>', 0)
        ->first();

    if (! $asset) {
        $this->markTestSkipped('No assets with purchase_cost found in seeder data.');
    }

    $response = $this->get(route('asset.public.show', ['code' => $asset->serial_no]));

    $response->assertStatus(200)
        ->assertDontSee((string) $asset->purchase_cost);
});
