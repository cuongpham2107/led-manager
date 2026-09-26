<?php

use App\Enums\LedScanMode;
use App\Enums\ProductLineType;
use App\Models\Asset;
use App\Models\LedConfiguration;
use App\Models\ProductLine;
use Illuminate\Database\QueryException;

beforeEach(function () {
    $this->line = ProductLine::create(['name' => 'P2.6 Sự kiện', 'code' => 'P26-TEST']);
});

test('product line defaults to panel type', function () {
    expect($this->line->fresh()->type)->toBe(ProductLineType::Panel);
});

test('asset belongs to a LED configuration of its product line', function () {
    $config = LedConfiguration::factory()->for($this->line)->create([
        'receiving_card' => 'Novastar A5s Plus',
        'scan_mode' => LedScanMode::Sixteenth,
        'controller_model' => 'Novastar VX600',
    ]);

    $asset = Asset::create([
        'serial_no' => 'CFG-001',
        'product_line_id' => $this->line->id,
        'led_configuration_id' => $config->id,
    ]);

    expect($asset->ledConfiguration->is($config))->toBeTrue()
        ->and($this->line->ledConfigurations)->toHaveCount(1)
        ->and($config->label)->toContain('Novastar A5s Plus')->toContain('1/16')->toContain('Novastar VX600');
});

test('a product line cannot have the same configuration twice', function () {
    $attributes = [
        'receiving_card' => 'Novastar A5s Plus',
        'scan_mode' => LedScanMode::Sixteenth,
        'controller_model' => 'Novastar VX600',
    ];

    LedConfiguration::factory()->for($this->line)->create($attributes);
    LedConfiguration::factory()->for($this->line)->create($attributes);
})->throws(QueryException::class);
