<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'invoice_number',
        'date',
        'discount',
        'tax',
        'description',
        'customer_id'
    ];

    protected $appends = ['subtotal', 'total'];

    public static function generateInvoiceNumber()
    {
        $prefix = 'INV/INDOKA/';
        $month = now()->format('m');
        $year = now()->year;
        $invoiceNumber = $prefix . $month . '/' . $year . '/0001';

        $lastInv = Invoice::latest()->first();
        if ($lastInv) {
            list(,, $lastInvMonth, $lastInvYear, $lastSequence) = explode('/', $lastInv->invoice_number);

            if ($lastInvMonth == $month && $lastInvYear == $year) {
                $sequenceNumber = (int)$lastSequence + 1;
                $invoiceNumber = $prefix . $month . '/' . $year . '/' . sprintf('%04d', $sequenceNumber);
            }
        }
        
        return $invoiceNumber;
    }
    
    public function getSubtotalAttribute()
    {
        $subtotal = 0;
        foreach ($this->invoiceItems as $item) {
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
        return $this->morphMany(Image::class, 'imageable')->where('collection_name', 'invoice_images');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }
}
