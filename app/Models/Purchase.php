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
        'supplier_id',
        'created_by',
    ];

    protected $appends = ['subtotal', 'total'];

    public static function generatePurchaseNumber()
    {
        $prefix = 'PO/INDOKA/';
        $month = now()->format('m');
        $year = now()->year;
        $purchaseNumber = $prefix . $month . '/' . $year . '/0001';

        $lastPo = Purchase::latest()->first();
        if ($lastPo) {
            list(,, $lastPoMonth, $lastPoYear, $lastSequence) = explode('/', $lastPo->purchase_number);

            if ($lastPoMonth == $month && $lastPoYear == $year) {
                $sequenceNumber = (int)$lastSequence + 1;
                $purchaseNumber = $prefix . $month . '/' . $year . '/' . sprintf('%04d', $sequenceNumber);
            }
        }

        return $purchaseNumber;
    }

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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
