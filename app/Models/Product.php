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
        'category_id',
        'created_by',
        'updated_by',
    ];

    // public function prunable() {
    //     return static::where('deleted_at', '<=', now()->subDays(90));
    // }

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
        return $this->belongsTo(Supplier::class,);
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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
