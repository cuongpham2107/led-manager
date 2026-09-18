<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Enums\OrderStatus;
use App\Enums\ReturnBatchStatus;
use App\Models\Agency;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\CheckoutBatch;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ProductLine;
use App\Models\ReturnBatch;
use App\Models\User;
use App\Models\Warehouse;
use Database\Seeders\LedOsDataSeeder;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    (new LedOsDataSeeder)->run();

    $this->agencyHp = Agency::where('code', 'DL-HP')->firstOrFail();
    $this->userHp = User::where('email', 'daily.haiphong@ledmanager.com')->firstOrFail();
    $this->whHp = $this->agencyHp->warehouse;

    $this->agencyDn = Agency::where('code', 'DL-DN')->firstOrFail();
    $this->whDn = $this->agencyDn->warehouse;
    $this->userDn = User::where('email', 'daily.danang@ledmanager.com')->firstOrFail();

    $this->adminUser = User::where('email', 'admin@ledmanager.com')->firstOrFail();
    $this->whHanoi = Warehouse::where('code', 'WH-HN')->firstOrFail();
});

test('agency user cannot login via mobile API if agency is inactive', function () {
    $this->agencyHp->update(['is_active' => false]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'daily.haiphong@ledmanager.com',
        'password' => 'password',
    ]);

    $response->assertStatus(403)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Đại lý của bạn đang bị vô hiệu hóa hoặc tạm ngưng hoạt động. Vui lòng liên hệ quản trị viên.');
});

test('active agency user can login and receives warehouse fallback and agency metadata', function () {
    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'daily.haiphong@ledmanager.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.warehouse.id', $this->whHp->id)
        ->assertJsonPath('data.user.agency.id', $this->agencyHp->id)
        ->assertJsonPath('data.user.is_agency', true);
});

test('agency user can get profile from /auth/me with agency data', function () {
    Sanctum::actingAs($this->userHp);

    $response = $this->getJson('/api/v1/auth/me');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.warehouse.id', $this->whHp->id)
        ->assertJsonPath('data.agency.id', $this->agencyHp->id)
        ->assertJsonPath('data.is_agency', true);
});

test('agency user only sees their own agency warehouse in /api/v1/warehouses', function () {
    Sanctum::actingAs($this->userHp);

    $response = $this->getJson('/api/v1/warehouses');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $this->whHp->id);

    // Super admin can see all active warehouses
    Sanctum::actingAs($this->adminUser);
    $adminResponse = $this->getJson('/api/v1/warehouses');
    $adminResponse->assertOk()
        ->assertJsonPath('success', true);
    expect(count($adminResponse->json('data')))->toBeGreaterThan(1);
});

test('agency user checkin batches are scoped to their warehouse', function () {
    $productLine = ProductLine::firstOrFail();

    // Batch 1: for HP agency warehouse
    $hpBatch = CheckinBatch::create([
        'code' => 'CK-HP-001',
        'warehouse_id' => $this->whHp->id,
        'product_line_id' => $productLine->id,
        'batch_type' => CheckinBatchType::Production,
        'quantity' => 2,
        'status' => BatchStatus::Pending,
        'created_by' => $this->adminUser->id,
    ]);

    // Batch 2: for Hanoi central warehouse
    $hnBatch = CheckinBatch::create([
        'code' => 'CK-HN-001',
        'warehouse_id' => $this->whHanoi->id,
        'product_line_id' => $productLine->id,
        'batch_type' => CheckinBatchType::Production,
        'quantity' => 2,
        'status' => BatchStatus::Pending,
        'created_by' => $this->adminUser->id,
    ]);

    Sanctum::actingAs($this->userHp);

    // List: only HP batch returned
    $listResponse = $this->getJson('/api/v1/checkin-batches');
    $listResponse->assertOk();
    $codes = collect($listResponse->json('data'))->pluck('code')->all();
    expect($codes)->toContain('CK-HP-001')
        ->and($codes)->not->toContain('CK-HN-001');

    // Show HP batch: allowed
    $this->getJson("/api/v1/checkin-batches/{$hpBatch->id}")
        ->assertOk()
        ->assertJsonPath('data.code', 'CK-HP-001');

    // Show HN batch: forbidden (403)
    $this->getJson("/api/v1/checkin-batches/{$hnBatch->id}")
        ->assertStatus(403);
});

test('completing checkin batch via API enforces agency warehouse quota', function () {
    $productLine = ProductLine::firstOrFail();

    // Set agency quota to very small
    $this->agencyHp->update(['allocated_area_m2' => 0.01]);

    $asset = Asset::create([
        'serial_no' => 'LED-QUOTA-TEST-001',
        'product_line_id' => $productLine->id,
        'current_warehouse_id' => $this->whHanoi->id,
        'current_status' => AssetStatus::Ready,
        'size' => '0.5×1.0m',
    ]);

    $batch = CheckinBatch::create([
        'code' => 'CK-HP-QUOTA-01',
        'warehouse_id' => $this->whHp->id,
        'product_line_id' => $productLine->id,
        'batch_type' => CheckinBatchType::Production,
        'quantity' => 1,
        'status' => BatchStatus::Pending,
        'created_by' => $this->adminUser->id,
    ]);

    CheckinBatchItem::create([
        'checkin_batch_id' => $batch->id,
        'asset_id' => $asset->id,
        'is_received' => false,
    ]);

    Sanctum::actingAs($this->userHp);

    // Complete batch should fail with 422 because quota is exceeded
    $response = $this->postJson("/api/v1/checkin-batches/{$batch->id}/complete");
    $response->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('agency user checkout batch scoping and foreign asset scan denial', function () {
    $customer = Customer::create([
        'name' => 'Khách HP',
        'phone' => '0988111222',
        'email' => 'khach.hp@test.com',
        'agency_id' => $this->agencyHp->id,
    ]);

    $order = Order::create([
        'order_no' => 'ORD-HP-001',
        'customer_id' => $customer->id,
        'agency_id' => $this->agencyHp->id,
        'warehouse_id' => $this->whHp->id,
        'sales_user_id' => $this->userHp->id,
        'status' => OrderStatus::Draft,
        'request_date' => now()->toDateString(),
        'event_name' => 'HP Event',
        'start_date' => now()->toDateString(),
        'end_date' => now()->addDays(2)->toDateString(),
        'total_amount' => 5000000,
    ]);

    $hpBatch = CheckoutBatch::create([
        'code' => 'CO-HP-001',
        'order_id' => $order->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $this->whHp->id,
        'status' => BatchStatus::Pending,
        'required_area_m2' => 2,
        'created_by' => $this->userHp->id,
    ]);

    $hnBatch = CheckoutBatch::create([
        'code' => 'CO-HN-001',
        'warehouse_id' => $this->whHanoi->id,
        'status' => BatchStatus::Pending,
        'required_area_m2' => 2,
        'created_by' => $this->adminUser->id,
    ]);

    Sanctum::actingAs($this->userHp);

    // HP batch is accessible
    $this->getJson("/api/v1/checkout-batches/{$hpBatch->id}")
        ->assertOk();

    // HN batch is denied (403)
    $this->getJson("/api/v1/checkout-batches/{$hnBatch->id}")
        ->assertStatus(403);

    // Scanning an asset located in Hanoi warehouse into HP batch should be rejected
    $foreignAsset = Asset::where('current_warehouse_id', $this->whHanoi->id)
        ->where('current_status', AssetStatus::Ready)
        ->firstOrFail();

    $scanResponse = $this->postJson("/api/v1/checkout-batches/{$hpBatch->id}/scan", [
        'code' => $foreignAsset->serial_no,
    ]);

    $scanResponse->assertStatus(422)
        ->assertJsonPath('success', false);
});

test('agency user return batch scoping', function () {
    $customer = Customer::create([
        'name' => 'Khách HP',
        'phone' => '0988111222',
        'email' => 'khach.hp@test.com',
        'agency_id' => $this->agencyHp->id,
    ]);

    $hpCheckout = CheckoutBatch::create([
        'code' => 'CO-HP-RET-01',
        'customer_id' => $customer->id,
        'warehouse_id' => $this->whHp->id,
        'status' => BatchStatus::Dispatched,
        'required_area_m2' => 2,
        'created_by' => $this->userHp->id,
    ]);

    $hpReturn = ReturnBatch::create([
        'code' => 'RET-HP-001',
        'checkout_batch_id' => $hpCheckout->id,
        'status' => ReturnBatchStatus::Pending,
        'created_by' => $this->userHp->id,
    ]);

    $hnCheckout = CheckoutBatch::create([
        'code' => 'CO-HN-RET-01',
        'warehouse_id' => $this->whHanoi->id,
        'status' => BatchStatus::Dispatched,
        'required_area_m2' => 2,
        'created_by' => $this->adminUser->id,
    ]);

    $hnReturn = ReturnBatch::create([
        'code' => 'RET-HN-001',
        'checkout_batch_id' => $hnCheckout->id,
        'status' => ReturnBatchStatus::Pending,
        'created_by' => $this->adminUser->id,
    ]);

    Sanctum::actingAs($this->userHp);

    // Listing only shows HP return batch
    $listResponse = $this->getJson('/api/v1/return-batches');
    $listResponse->assertOk();
    $codes = collect($listResponse->json('data'))->pluck('code')->all();
    expect($codes)->toContain('RET-HP-001')
        ->and($codes)->not->toContain('RET-HN-001');

    // Show HP return batch: allowed
    $this->getJson("/api/v1/return-batches/{$hpReturn->id}")
        ->assertOk();

    // Show HN return batch: denied (403)
    $this->getJson("/api/v1/return-batches/{$hnReturn->id}")
        ->assertStatus(403);
});

test('agency user asset lookup scoping denies assets from other agencies or central warehouse', function () {
    $hpAsset = Asset::where('current_warehouse_id', $this->whHp->id)->firstOrFail();
    $dnAsset = Asset::where('current_warehouse_id', $this->whDn->id)->firstOrFail();

    Sanctum::actingAs($this->userHp);

    // Lookup HP asset: allowed
    $this->getJson("/api/v1/assets/lookup?code={$hpAsset->serial_no}")
        ->assertOk()
        ->assertJsonPath('data.asset.serial_no', $hpAsset->serial_no);

    // Lookup Da Nang asset: denied (403)
    $this->getJson("/api/v1/assets/lookup?code={$dnAsset->serial_no}")
        ->assertStatus(403)
        ->assertJsonPath('message', 'Thiết bị này không thuộc phạm vi quản lý của đại lý.');
});
