<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Order> */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $requestDate = fake()->dateTimeBetween('-30 days', '+20 days');

        return [
            'order_no' => 'ORD-'.now()->format('ym').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'note' => fake()->optional()->sentence(),
            'warehouse_id' => fake()->numberBetween(1, 20),
            'customer_id' => fake()->numberBetween(1, 50),
            'quotation_id' => fake()->optional()->numberBetween(1, 100),
            'request_date' => $requestDate,
            'expected_return_date' => fake()->optional(0.7)->dateTimeBetween($requestDate, '+20 days'),
            'area_m2' => fake()->randomFloat(2, 10, 180),
            'event' => fake()->words(3, true).' Event',
            'device_type_id' => fake()->optional()->numberBetween(1, 30),
            'value' => fake()->randomFloat(2, 50_000_000, 500_000_000),
            'deposit_paid' => 0,
            'total_paid' => 0,
            'paid_at' => null,
            'status' => fake()->randomElement([
                OrderStatus::Draft,
                OrderStatus::OutboundCreated,
                OrderStatus::Dispatched,
                OrderStatus::Returned,
                OrderStatus::Completed,
                OrderStatus::Cancelled,
            ]),
            'sales_user_id' => fake()->numberBetween(1, 10),
        ];
    }
}
