<?php

namespace Database\Factories;

use App\Enums\ReturnBatchStatus;
use App\Models\ReturnBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReturnBatch> */
class ReturnBatchFactory extends Factory
{
    protected $model = ReturnBatch::class;

    public function definition(): array
    {
        $status = fake()->randomElement([ReturnBatchStatus::Pending, ReturnBatchStatus::InProgress, ReturnBatchStatus::Completed]);
        $returnDate = fake()->dateTimeBetween('-5 days', '+10 days');

        return [
            'code' => 'RET-'.now()->format('ym').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'checkout_batch_id' => fake()->optional()->numberBetween(1, 100),
            'return_date' => $returnDate,
            'note' => fake()->optional()->sentence(),
            'status' => $status,
            'created_by' => fake()->numberBetween(1, 10),
            'completed_at' => $status === ReturnBatchStatus::Completed ? fake()->dateTimeBetween('-3 days', 'now') : null,
        ];
    }
}
