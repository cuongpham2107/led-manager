<?php

namespace Database\Factories;

use App\Enums\BatchStatus;
use App\Enums\CheckinBatchType;
use App\Models\CheckinBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CheckinBatch> */
class CheckinBatchFactory extends Factory
{
    protected $model = CheckinBatch::class;

    public function definition(): array
    {
        $status = fake()->randomElement([BatchStatus::Pending, BatchStatus::InProgress, BatchStatus::Completed, BatchStatus::Cancelled]);
        $expectedDate = fake()->dateTimeBetween('-20 days', '+10 days');

        return [
            'code' => 'IN-'.now()->format('ym').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'warehouse_id' => fake()->numberBetween(1, 20),
            'note' => fake()->optional()->sentence(),
            'expected_date' => $expectedDate,
            'batch_type' => fake()->randomElement([CheckinBatchType::Production, CheckinBatchType::Purchase, CheckinBatchType::Transfer]),
            'product_line_id' => fake()->optional()->numberBetween(1, 20),
            'quantity' => fake()->numberBetween(5, 40),
            'production_note' => fake()->optional()->sentence(),
            'status' => $status,
            'created_by' => fake()->numberBetween(1, 10),
            'completed_at' => $status === BatchStatus::Completed ? fake()->dateTimeBetween('-10 days', 'now') : null,
        ];
    }
}
