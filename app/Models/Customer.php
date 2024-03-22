<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    use SoftDeletes, Prunable, HasFactory, Notifiable;

    protected $fillable = [
        'company_name',
        'pic_name',
        'phone',
        'email',
        'address',
        'created_by',
        'updated_by',
    ];

    // make sure it doesnt prune data that has relation
    // public function prunable() {
    //     return static::where('deleted_at', '<=', now()->subDays(90));
    // }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
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
