<?php

namespace Database\Factories;

use App\Enums\BatchStatus;
use App\Models\CheckoutBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CheckoutBatch> */
class CheckoutBatchFactory extends Factory
{
    protected $model = CheckoutBatch::class;

    public function definition(): array
    {
        $status = fake()->randomElement([BatchStatus::Pending, BatchStatus::InProgress, BatchStatus::Completed, BatchStatus::Cancelled]);
        $expectedReturn = fake()->dateTimeBetween('+1 days', '+20 days');

        return [
            'code' => 'OUT-'.now()->format('ym').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'order_id' => fake()->numberBetween(1, 100),
            'customer_id' => fake()->numberBetween(1, 50),
            'warehouse_id' => fake()->numberBetween(1, 20),
            'required_area_m2' => fake()->randomFloat(2, 10, 120),
            'expected_return_date' => $expectedReturn,
            'status' => $status,
            'created_by' => fake()->numberBetween(1, 10),
            'dispatched_at' => in_array($status, [BatchStatus::Completed, BatchStatus::Cancelled], true) ? fake()->dateTimeBetween('-10 days', 'now') : null,
        ];
    }
}
