<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Models\Asset;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\LedConfiguration;
use App\Models\ProductLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\LedSetGuard;

beforeEach(function () {
    $this->warehouse = Warehouse::create(['code' => 'WH-GUARD', 'name' => 'Kho Guard', 'city' => 'Hà Nội', 'is_active' => true]);
    $this->user = User::create([
        'name' => 'Thủ Kho Guard',
        'email' => 'kho.guard@ledmanager.com',
        'password' => bcrypt('password123'),
        'warehouse_id' => $this->warehouse->id,
        'is_active' => true,
    ]);
    $this->line = ProductLine::factory()->create();
    $this->otherLine = ProductLine::factory()->create();
    $this->configA = LedConfiguration::factory()->for($this->line)->create(['name' => 'Bộ A']);
    $this->configB = LedConfiguration::factory()->for($this->line)->create(['name' => 'Bộ B']);
    $this->configOther = LedConfiguration::factory()->for($this->otherLine)->create(['name' => 'Bộ khác dòng']);

    $this->panel = fn (?LedConfiguration $config, ?ProductLine $line = null, string $serial = '') => Asset::factory()->create([
        'serial_no' => $serial ?: 'G-'.fake()->unique()->numerify('####'),
        'product_line_id' => ($line ?? $config?->productLine)?->id,
        'led_configuration_id' => $config?->id,
        'current_warehouse_id' => $this->warehouse->id,
        'current_status' => AssetStatus::Ready,
    ]);

    $this->batch = CheckoutBatch::create(['code' => 'OUT-GUARD', 'warehouse_id' => $this->warehouse->id, 'status' => BatchStatus::Pending, 'created_by' => $this->user->id]);
    CheckoutBatchItem::create(['checkout_batch_id' => $this->batch->id, 'asset_id' => ($this->panel)($this->configA)->id, 'is_dispatched' => false]);
});

test('panels of the same product line with different configurations conflict', function () {
    $message = LedSetGuard::conflict([($this->panel)($this->configA), ($this->panel)($this->configB, serial: 'G-LECH-01')]);

    expect($message)->toContain('G-LECH-01')->toContain('Bộ A')->toContain('Bộ B');
});

test('different product lines and panels without configuration do not conflict', function () {
    expect(LedSetGuard::conflict([
        ($this->panel)($this->configA),
        ($this->panel)($this->configOther),
        ($this->panel)(null, $this->line),
    ]))->toBeNull();
});

test('mobile scan rejects a panel from another configuration of the same product line', function () {
    $token = $this->user->createToken('test')->plainTextToken;
    ($this->panel)($this->configB, serial: 'G-LECH-API');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/checkout-batches/{$this->batch->id}/scan", ['code' => 'G-LECH-API'])
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', fn (string $m) => str_contains($m, 'G-LECH-API'));

    ($this->panel)($this->configA, serial: 'G-DUNG-API');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/checkout-batches/{$this->batch->id}/scan", ['code' => 'G-DUNG-API'])
        ->assertOk();
});

test('web dispatch-item rejects a panel from another configuration of the same product line', function () {
    $wrong = ($this->panel)($this->configB);
    $right = ($this->panel)($this->configA);

    $this->actingAs($this->user)
        ->postJson(route('filament.checkout-dispatch-item'), ['batch_id' => $this->batch->id, 'asset_id' => $wrong->id])
        ->assertStatus(422);

    $this->actingAs($this->user)
        ->postJson(route('filament.checkout-dispatch-item'), ['batch_id' => $this->batch->id, 'asset_id' => $right->id])
        ->assertOk();

    expect($this->batch->items()->where('asset_id', $wrong->id)->exists())->toBeFalse();
});

test('conflict blames the odd panel, not the majority configuration', function () {
    $majority = collect(range(1, 3))->map(fn () => ($this->panel)($this->configA));
    $odd = ($this->panel)($this->configB, serial: 'G-LE-LOI');

    $message = LedSetGuard::conflict($odd->newCollection([$odd])->merge($majority));

    expect($message)->toStartWith('Thiết bị G-LE-LOI thuộc cấu hình "'.$this->configB->label.'"');
});

test('warehouse picker hides panels whose configuration differs from the batch', function () {
    $wrong = ($this->panel)($this->configB);
    $right = ($this->panel)($this->configA);
    $otherLine = ($this->panel)($this->configOther);

    $ids = $this->actingAs($this->user)
        ->getJson(route('filament.checkout-assets', ['batch_id' => $this->batch->id, 'per_page' => 200]))
        ->assertOk()
        ->json('items.*.id');

    expect($ids)->toContain($right->id, $otherLine->id)->not->toContain($wrong->id);
});
