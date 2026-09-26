<?php

namespace Database\Factories;

use App\Enums\ProductLineType;
use App\Models\ProductLine;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ProductLine> */
class ProductLineFactory extends Factory
{
    protected $model = ProductLine::class;

    public function definition(): array
    {
        return [
            'name' => 'P2.6 '.fake()->unique()->word(),
            'code' => fake()->unique()->bothify('PL-####'),
            'type' => ProductLineType::Panel,
            'pixel_pitch' => 2.6,
            'module_width_mm' => 500,
            'module_height_mm' => 500,
            'is_active' => true,
        ];
    }

    public function controller(): static
    {
        return $this->state(['type' => ProductLineType::Controller, 'module_width_mm' => null, 'module_height_mm' => null]);
    }
}
