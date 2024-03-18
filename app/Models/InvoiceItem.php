<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'category_id',
        'product_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'expiry_date',
        'created_by',
        'updated_by',
    ];

    public function getUnitPriceAttribute($value)
    {
        return $this->num($value);
    }

    public function getQuantityAttribute($value)
    {
        return $this->num($value);
    }

    public function getTotalPriceAttribute($value)
    {
        return $this->num($value);
    }

    public function num($num)
    {
        return intval($num) == $num ? intval($num) : $num;
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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
