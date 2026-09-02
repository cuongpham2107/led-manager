<?php

namespace Database\Factories;

use App\Enums\CustomerType;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Customer> */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    /** @var int */
    protected static $customerCodeCounter = 0;

    public function definition(): array
    {
        $companySuffixes = [
            'Technology', 'Media', 'Events', 'Advertising', 'Communications',
            'Marketing', 'Solutions', 'Vietnam',
        ];

        $name = fake()->company().' '.fake()->randomElement($companySuffixes);

        return [
            'code' => 'CUS-'.now()->format('ym').'-'.str_pad((string) (++self::$customerCodeCounter), 4, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3)),
            'name' => $name,
            'type' => fake()->randomElement([
                CustomerType::Corporate,
                CustomerType::Agency,
                CustomerType::Individual,
            ]),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'tax_code' => fake()->numerify('##########'),
            'address' => fake()->address(),
            'contact_person' => fake()->name(),
            'note' => fake()->optional()->sentence(),
            'is_active' => fake()->boolean(90),
        ];
    }
}
