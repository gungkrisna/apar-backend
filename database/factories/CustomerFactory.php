<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
        return [
            'company_name' =>fake()->unique()->company(),
            'pic_name' => fake()->unique()->name(),
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->unique()->address(),
        ];
    }
}
