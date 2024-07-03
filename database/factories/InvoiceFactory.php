<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Image;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        // todo fix gambar purchase dan invoice
        return [
            'status' => 0,
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'date' => Carbon::parse($this->faker->dateTime($max = 'now', $timezone = 'Asia/Makassar'))->format('Y-m-d'),
            'discount' => 0,
            'tax' => 0,
            'description' => $this->faker->sentence(),
            'customer_id' => Customer::pluck('id')->random(),
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function (Invoice $invoice) {
            $selectedProductIds = [];

            for ($i = 0; $i < 2; $i++) {
                do {
                    $product = Product::inRandomOrder()->first();
                } while (in_array($product->id, $selectedProductIds));

                $selectedProductIds[] = $product->id;
                $quantity = rand(1, $product->stock); //fix if stock 0

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'category_id' => $product->category_id,
                    'product_id' => $product->id,
                    'description' => $this->faker->sentence(),
                    'unit_price' => $product->price,
                    'quantity' => $quantity,
                    'total_price' => $quantity * $product->price
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

            $invoice->images()->save($image);
        });
    }
}
