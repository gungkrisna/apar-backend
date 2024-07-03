<?php

namespace Database\Factories;

use App\Models\Image;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'status' => 0,
            'purchase_number' => Purchase::generatePurchaseNumber(),
            'date' => Carbon::parse($this->faker->dateTime($max = 'now', $timezone = 'Asia/Makassar'))->format('Y-m-d'),
            'discount' => 0,
            'tax' => 0,
            'description' => $this->faker->sentence(),
            'supplier_id' => Supplier::pluck('id')->random(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Purchase $purchase) {
            $selectedProductIds = [];

            for ($i = 0; $i < 2; $i++) {
                do {
                    $product = Product::inRandomOrder()->first();
                } while (in_array($product->id, $selectedProductIds));

                $selectedProductIds[] = $product->id;
                $discount = [0.25, 0.50][array_rand([0.25, 0.50])];

                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'category_id' => $product->category_id,
                    'product_id' => $product->id,
                    'description' => $this->faker->sentence(),
                    'unit_price' => $product->price * (1 - $discount),
                    'quantity' => $this->faker->numberBetween(1, 100),
                ]);
            }

            $url = fake()->imageUrl(640, 480, 'purchases', true);
            $contents = file_get_contents($url);
            $fileName = 'images/purchases/' . uniqid() . '.jpg';

            Storage::disk('public')->put($fileName, $contents);

            $image = new Image([
                'path' => $fileName,
                'collection_name' => 'purchase_images',
            ]);

            $purchase->images()->save($image);
        });
    }
}
