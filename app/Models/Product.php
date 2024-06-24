<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Product extends Model
{
    use HasFactory, SoftDeletes, Prunable;

    protected $fillable = [
        'status',
        'serial_number',
        'name',
        'description',
        'stock',
        'price',
        'expiry_period',
        'unit_id',
        'supplier_id',
        'category_id'
    ];

    // public function prunable() {
    //     return static::where('deleted_at', '<=', now()->subDays(90));
    // }

    public static function generateSerialNumber()
    {
        $prefix = '200';
        $randomNumber = mt_rand(100000000, 999999999); // 9-digit random number

        // Calculate the check digit
        $checkDigit = self::calculateEanCheckDigit($prefix . $randomNumber);

        $eanCode = $prefix . $randomNumber . $checkDigit;

        while (self::where('serial_number', $eanCode)->exists()) {
            $randomNumber = mt_rand(100000000, 999999999);
            $checkDigit = self::calculateEanCheckDigit($prefix . $randomNumber);
            $eanCode = $prefix . $randomNumber . $checkDigit;
        }

        return $eanCode;
    }

    private static function calculateEanCheckDigit($eanWithoutCheckDigit)
    {
        $sum = 0;
        $weight = 3;

        for ($i = strlen($eanWithoutCheckDigit) - 1; $i >= 0; $i--) {
            $sum += $weight * intval($eanWithoutCheckDigit[$i]);
            $weight = 4 - $weight;
        }

        $checkDigit = (10 - ($sum % 10)) % 10;

        return $checkDigit;
    }


    private function num($num)
    {
        return intval($num) == $num ? intval($num) : $num;
    }

    public function getStockAttribute($value)
    {
        return $this->num($value);
    }

    public function getPriceAttribute($value)
    {
        return $this->num($value);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->where('collection_name', 'product_images');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
