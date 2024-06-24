<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status' => rand(0, 1),
            'serial_number' => Product::generateSerialNumber(),
            'name' => fake()->unique()->word(), 
            'description' => fake()->unique()->paragraph(),
            'stock' => rand(0, 150),
            'price' => rand(50000, 5500000),  
            'unit_id' => Unit::pluck('id')->random(),
            'supplier_id' => Supplier::pluck('id')->random(),
            'category_id' => Category::pluck('id')->random(),
            'expiry_period' => fake()->randomElement([0, 6, 12, 24, 36, 48, 60]),
        ];
    }
}
