<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Purchase extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'purchase_number',
        'date',
        'discount',
        'tax',
        'description',
        'supplier_id'
    ];

    protected $appends = ['subtotal', 'total'];

    public function getSubtotalAttribute()
    {
        $subtotal = 0;
            foreach ($this->purchaseItems as $item) {
                $subtotal += $item->getTotalPriceAttribute(); 
            }
        return $subtotal;
    }

    public function getTotalAttribute()
    {
        $discountedSubtotal = $this->getSubtotalAttribute() - $this->discount;
        $tax =  $discountedSubtotal * ($this->tax / 100);

        return $discountedSubtotal + $tax;
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable')->where('collection_name', 'purchase_images');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseItems()
    {
        return $this->hasMany(PurchaseItem::class);
    }
}
