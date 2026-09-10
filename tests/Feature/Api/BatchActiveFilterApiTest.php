<?php

use App\Enums\BatchStatus;
use App\Enums\ReturnBatchStatus;
use App\Models\CheckoutBatch;
use App\Models\ReturnBatch;
use App\Models\User;
use App\Models\Warehouse;

beforeEach(function () {
    $this->warehouse = Warehouse::create([
        'code' => 'WH-FILTER',
        'name' => 'Kho Lọc Test',
        'city' => 'Hà Nội',
        'is_active' => true,
    ]);

    $this->user = User::create([
        'name' => 'Thủ Kho Lọc',
        'email' => 'kho-loc.test@ledmanager.com',
        'password' => bcrypt('password123'),
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);
});

test('checkout active filter shows only batches not yet dispatched', function () {
    $pending = CheckoutBatch::factory()->create(['warehouse_id' => $this->warehouse->id, 'status' => BatchStatus::Pending, 'order_id' => null, 'customer_id' => null, 'created_by' => $this->user->id]);
    $inProgress = CheckoutBatch::factory()->create(['warehouse_id' => $this->warehouse->id, 'status' => BatchStatus::InProgress, 'order_id' => null, 'customer_id' => null, 'created_by' => $this->user->id]);
    $dispatched = CheckoutBatch::factory()->create(['warehouse_id' => $this->warehouse->id, 'status' => BatchStatus::Dispatched, 'order_id' => null, 'customer_id' => null, 'created_by' => $this->user->id]);
    $completed = CheckoutBatch::factory()->create(['warehouse_id' => $this->warehouse->id, 'status' => BatchStatus::Completed, 'order_id' => null, 'customer_id' => null, 'created_by' => $this->user->id]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/checkout-batches?active=1&warehouse_id='.$this->warehouse->id);

    $response->assertOk();
    $codes = collect($response->json('data'))->pluck('code');

    expect($codes)->toContain($pending->code)
        ->and($codes)->toContain($inProgress->code)
        ->and($codes)->not->toContain($dispatched->code)
        ->and($codes)->not->toContain($completed->code);
});

test('return active filter shows newly created pending batches and hides completed', function () {
    $checkout = CheckoutBatch::factory()->create(['warehouse_id' => $this->warehouse->id, 'order_id' => null, 'customer_id' => null, 'created_by' => $this->user->id]);

    $pending = ReturnBatch::factory()->create(['checkout_batch_id' => $checkout->id, 'status' => ReturnBatchStatus::Pending, 'completed_at' => null, 'created_by' => $this->user->id]);
    $inProgress = ReturnBatch::factory()->create(['checkout_batch_id' => $checkout->id, 'status' => ReturnBatchStatus::InProgress, 'completed_at' => null, 'created_by' => $this->user->id]);
    $completed = ReturnBatch::factory()->create(['checkout_batch_id' => $checkout->id, 'status' => ReturnBatchStatus::Completed, 'created_by' => $this->user->id]);

    $response = $this->actingAs($this->user)->getJson('/api/v1/return-batches?active=1&warehouse_id='.$this->warehouse->id);

    $response->assertOk();
    $codes = collect($response->json('data'))->pluck('code');

    expect($codes)->toContain($pending->code)
        ->and($codes)->toContain($inProgress->code)
        ->and($codes)->not->toContain($completed->code);
});
