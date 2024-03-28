<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_id',
        'category_id',
        'product_id',
        'description',
        'unit_price',
        'quantity',
    ];

    protected $appends = ['total_price'];

    public function getQuantityAttribute($value)
    {
        return $this->num($value);
    }

    public function getUnitPriceAttribute($value)
    {
        return $this->num($value);
    }

    public function getTotalPriceAttribute()
    {
        return $this->num($this->quantity * $this->unit_price);
    }

    public function num($num)
    {
        return intval($num) == $num ? intval($num) : $num;
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
