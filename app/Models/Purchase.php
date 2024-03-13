<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'purchase_number',
        'date',
        'supplier_id',
        'category_id',
        'product_id',
        'description',
        'unit_price',
        'quantity',
        'total_price',
        'created_by',
        'updated_by',
    ];

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->where('collection_name', 'images');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
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
