<?php

use App\Enums\AssetStatus;
use App\Enums\BatchStatus;
use App\Enums\LedScanMode;
use App\Models\Asset;
use App\Models\CheckinBatch;
use App\Models\CheckinBatchItem;
use App\Models\CheckoutBatch;
use App\Models\CheckoutBatchItem;
use App\Models\LedConfiguration;
use App\Models\ProductLine;
use App\Models\Warehouse;
use App\Services\PanelSuggestionService;

beforeEach(function () {
    $this->service = new PanelSuggestionService;
    $this->warehouse = Warehouse::create(['code' => 'WH-SUG', 'name' => 'Kho Gợi Ý', 'city' => 'Hà Nội', 'is_active' => true]);
    $this->line = ProductLine::factory()->create(['module_width_mm' => 500, 'module_height_mm' => 500]);
    $this->configA = LedConfiguration::factory()->for($this->line)->create(['scan_mode' => LedScanMode::Sixteenth, 'receiving_card' => 'Novastar A5s Plus']);
    $this->configB = LedConfiguration::factory()->for($this->line)->create(['scan_mode' => LedScanMode::ThirtySecond, 'receiving_card' => 'Colorlight 5A-75B']);

    /** Create $count ready panels of a configuration, optionally received through a check-in lot. */
    $this->makePanels = function (LedConfiguration $config, int $count, ?CheckinBatch $lot = null): array {
        return collect(range(1, $count))->map(function () use ($config, $lot) {
            $asset = Asset::factory()->create([
                'product_line_id' => $config->product_line_id,
                'led_configuration_id' => $config->id,
                'current_warehouse_id' => $this->warehouse->id,
                'current_status' => AssetStatus::Ready,
            ]);
            if ($lot) {
                CheckinBatchItem::create(['checkin_batch_id' => $lot->id, 'asset_id' => $asset->id, 'condition' => 'ok', 'is_received' => true]);
            }

            return $asset;
        })->all();
    };
    $this->makeLot = fn (string $code) => CheckinBatch::create(['code' => $code, 'warehouse_id' => $this->warehouse->id, 'status' => BatchStatus::Completed]);
});

test('grid rounds each side up to whole panels', function () {
    expect($this->service->gridFor($this->line, 1.5, 0.5))->toMatchArray(['cols' => 3, 'rows' => 1, 'required' => 3])
        ->and($this->service->gridFor($this->line, 1.6, 1.0))->toMatchArray(['cols' => 4, 'rows' => 2, 'required' => 8]);
});

test('grid falls back to 500x500 mm panels when module size is unknown', function () {
    $line = ProductLine::factory()->create(['module_width_mm' => null, 'module_height_mm' => null]);

    expect($this->service->gridFor($line, 1.0, 1.0))->toMatchArray(['cols' => 2, 'rows' => 2, 'required' => 4]);
});

test('suggest lists configurations that can cover the screen first', function () {
    ($this->makePanels)($this->configA, 2);
    ($this->makePanels)($this->configB, 5);

    $options = $this->service->suggest($this->line, $this->warehouse->id, 3);

    expect($options->first()['configuration']->is($this->configB))->toBeTrue()
        ->and($options->first())->toMatchArray(['available' => 5, 'is_enough' => true])
        ->and($options->last())->toMatchArray(['available' => 2, 'is_enough' => false]);
});

test('suggest applies receiving card, scan mode and controller filters', function () {
    ($this->makePanels)($this->configA, 2);
    ($this->makePanels)($this->configB, 5);

    $options = $this->service->suggest($this->line, $this->warehouse->id, 3, ['scan_mode' => '1/16', 'receiving_card' => '', 'controller_model' => null]);

    expect($options)->toHaveCount(1)
        ->and($options->first()['configuration']->is($this->configA))->toBeTrue();
});

test('panels already held by an open checkout batch are not available', function () {
    [$held] = ($this->makePanels)($this->configA, 3);
    $batch = CheckoutBatch::create(['code' => 'OUT-HOLD', 'warehouse_id' => $this->warehouse->id, 'status' => BatchStatus::Pending]);
    CheckoutBatchItem::create(['checkout_batch_id' => $batch->id, 'asset_id' => $held->id, 'is_dispatched' => false]);

    $options = $this->service->suggest($this->line, $this->warehouse->id, 3);

    expect($options->firstWhere(fn ($o) => $o['configuration']->is($this->configA))['available'])->toBe(2);
});

test('pickAssets covers the request from a single lot when one lot is big enough', function () {
    ($this->makePanels)($this->configA, 2, ($this->makeLot)('IN-L1'));
    $bigLot = ($this->makePanels)($this->configA, 4, ($this->makeLot)('IN-L2'));

    $result = $this->service->pickAssets($this->configA, $this->warehouse->id, 3);

    expect($result['assets'])->toHaveCount(3)
        ->and($result['mixed_lots'])->toBeFalse()
        ->and($result['assets']->pluck('id')->diff(collect($bigLot)->pluck('id')))->toBeEmpty();
});

test('pickAssets mixes lots of the same configuration and flags it when one lot is not enough', function () {
    ($this->makePanels)($this->configA, 2, ($this->makeLot)('IN-L1'));
    ($this->makePanels)($this->configA, 4, ($this->makeLot)('IN-L2'));
    ($this->makePanels)($this->configB, 5);

    $result = $this->service->pickAssets($this->configA, $this->warehouse->id, 5);

    expect($result['assets'])->toHaveCount(5)
        ->and($result['mixed_lots'])->toBeTrue()
        ->and($result['assets']->pluck('led_configuration_id')->unique()->all())->toBe([$this->configA->id]);
});
