<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Asset> */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /** @var int */
    protected static $assetCounter = 0;

    public function definition(): array
    {
        if (self::$assetCounter === 0) {
            self::$assetCounter = (int) Asset::count();
        }
        self::$assetCounter++;
        $tag = strtoupper(str_pad((string) self::$assetCounter, 8, '0', STR_PAD_LEFT));

        return [
            'serial_no' => 'SN-'.$tag,
            'qr_code' => 'QR-'.$tag,
            'product_line_id' => null,
            'size' => fake()->randomElement(['P1.5', 'P2', 'P2.5', 'P3', 'P3.9', 'P4', 'P5', 'P6']),
            'operating_hours' => fake()->numberBetween(0, 1500),
            'rental_count' => fake()->numberBetween(0, 35),
            'manufactured_date' => fake()->dateTimeBetween('-5 years', '-1 month'),
            'purchase_cost' => fake()->randomFloat(2, 15_000_000, 180_000_000),
            'accumulated_depreciation' => fake()->randomFloat(2, 1_000_000, 80_000_000),
            'useful_life_months' => fake()->randomElement([24, 36, 48, 60]),
            'depreciation_method' => fake()->randomElement(['straight_line', 'declining']),
            'salvage_value' => fake()->randomFloat(2, 1_000_000, 10_000_000),
            'purchase_date' => fake()->dateTimeBetween('-4 years', '-2 months'),
            'current_status' => fake()->randomElement([
                AssetStatus::Ready,
                AssetStatus::InEvent,
                AssetStatus::InTransit,
                AssetStatus::Repairing,
                AssetStatus::Missing,
                AssetStatus::Disposed,
            ]),
            'current_warehouse_id' => null,
            'warehouse_location_id' => null,
            'note' => fake()->optional()->sentence(),
        ];
    }
}
