<?php

namespace Database\Factories;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Quotation> */
class QuotationFactory extends Factory
{
    protected $model = Quotation::class;

    public function definition(): array
    {
        $status = fake()->randomElement([
            QuotationStatus::Draft,
            QuotationStatus::Sent,
            QuotationStatus::Approved,
            QuotationStatus::Rejected,
            QuotationStatus::Expired,
        ]);

        $start = fake()->dateTimeBetween('-60 days', '+30 days');
        $end = (clone $start)->modify('+'.fake()->numberBetween(1, 14).' days');
        $equipmentCost = fake()->randomFloat(2, 5_000_000, 80_000_000);
        $labourCost = fake()->randomFloat(2, 2_000_000, 25_000_000);
        $transportCost = fake()->randomFloat(2, 1_000_000, 20_000_000);
        $accessoryCost = fake()->randomFloat(2, 500_000, 10_000_000);
        $totalCost = $equipmentCost + $labourCost + $transportCost + $accessoryCost;
        $discountAmount = fake()->randomFloat(2, 0, 3_000_000);
        $totalPrice = max(0, $totalCost - $discountAmount);

        return [
            'code' => 'QUO-'.now()->format('ym').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999), 3, '0', STR_PAD_LEFT),
            'customer_id' => fake()->numberBetween(1, 50),
            'sales_user_id' => fake()->numberBetween(1, 10),
            'product_line_id' => fake()->optional()->numberBetween(1, 20),
            'event_name' => fake()->words(3, true).' '.fake()->randomElement(['Festival', 'Launch', 'Gala', 'Roadshow', 'Conference', 'Concert']),
            'event_start_date' => $start,
            'event_end_date' => $end,
            'screen_width_m' => fake()->randomFloat(2, 3, 20),
            'screen_height_m' => fake()->randomFloat(2, 2, 10),
            'screen_area_m2' => fake()->randomFloat(2, 10, 200),
            'rental_days' => fake()->numberBetween(1, 14),
            'crew_size' => fake()->numberBetween(2, 12),
            'transport_distance_km' => fake()->randomFloat(2, 5, 300),
            'crew_rate' => fake()->randomFloat(2, 500, 3000),
            'transport_rate' => fake()->randomFloat(2, 800, 4000),
            'equipment_cost' => $equipmentCost,
            'labour_cost' => $labourCost,
            'transport_cost' => $transportCost,
            'accessory_cost' => $accessoryCost,
            'total_cost' => $totalCost,
            'discount_amount' => $discountAmount,
            'total_price' => $totalPrice,
            'margin_percent' => fake()->randomFloat(2, 10, 45),
            'status' => $status,
            'lost_reason' => $status === QuotationStatus::Rejected ? fake()->sentence() : null,
            'location' => fake()->city().', '.fake()->country(),
            'estimated_cabinet_qty' => fake()->numberBetween(4, 120),
            'estimated_processor_qty' => fake()->numberBetween(2, 20),
            'estimated_load_kg' => fake()->randomFloat(2, 200, 5000),
            'estimated_power_kw' => fake()->randomFloat(2, 3, 120),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
