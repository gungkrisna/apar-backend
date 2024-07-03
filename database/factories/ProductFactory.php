<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Image;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

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

    public function configure()
    {
        return $this->afterCreating(function (Product $product) {
            for ($i = 0; $i < 3; $i++) {
                $url = fake()->imageUrl(640, 480, 'products', true);
                $contents = file_get_contents($url);

                $fileName = 'images/products/' . uniqid() . '.jpg';

                Storage::disk('public')->put($fileName, $contents);

                $image = new Image([
                    'path' => $fileName,
                    'collection_name' => 'product_images',
                ]);

                $product->images()->save($image);
            }
        });
    }
}
