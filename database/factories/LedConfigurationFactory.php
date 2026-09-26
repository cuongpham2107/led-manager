<?php

namespace Database\Factories;

use App\Enums\LedScanMode;
use App\Models\LedConfiguration;
use App\Models\ProductLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LedConfiguration> */
class LedConfigurationFactory extends Factory
{
    protected $model = LedConfiguration::class;

    public function definition(): array
    {
        return [
            'product_line_id' => ProductLine::factory(),
            'name' => 'Cấu hình '.fake()->unique()->bothify('??-##'),
            'receiving_card' => fake()->randomElement(['Novastar A5s Plus', 'Novastar A8s', 'Colorlight 5A-75B']),
            'scan_mode' => fake()->randomElement(LedScanMode::cases()),
            'controller_model' => fake()->unique()->bothify('Controller-###'),
            'is_active' => true,
        ];
    }
}
