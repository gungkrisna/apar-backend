<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Generator as Faker;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = app(Faker::class);

        $mobileNetworkPrefixes = [
            '81', '82', '85', '87', '88', '89',
        ];

        return [
            'company_name' => fake()->unique()->company(),
            'pic_name' => fake()->unique()->name(),
            'phone' => $this->generateIndonesianPhoneNumber($faker, $mobileNetworkPrefixes),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->unique()->address(),
        ];
    }

    private function generateIndonesianPhoneNumber(Faker $faker, array $mobileNetworkPrefixes): string
    {
        $prefix = $faker->randomElement($mobileNetworkPrefixes);
        $subscriberNumber = $faker->randomNumber(8, true);

        return "+62$prefix$subscriberNumber";
    }
}
