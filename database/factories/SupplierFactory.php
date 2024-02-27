<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SupplierFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->company(),
            'phone' =>fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->companyEmail(),
            'address' => fake()->unique()->address(),
            'created_by' => User::all()->random()->id
        ];
    }
}
