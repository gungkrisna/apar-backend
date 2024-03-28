<?php

namespace App\Models;

use App\Mail\InvoiceItemExpiryNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;

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
        'expiry_date',
    ];

    protected $appends = ['total_price'];
    protected $casts = [
        'expiry_date' => 'datetime',
    ];

    public function sendExpiryNotification()
    {
        $daysUntilExpiry = now()->diffInDays($this->expiry_date);

        if ($daysUntilExpiry == 30) {
            $customer = $this->invoice->customer;
            Mail::to($customer->email)->send(new InvoiceItemExpiryNotification($this));
        }
    }

    public function getUnitPriceAttribute($value)
    {
        return $this->num($value);
    }

    public function getQuantityAttribute($value)
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

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
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
