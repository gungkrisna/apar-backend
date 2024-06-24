<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Supplier extends Model
{
    use SoftDeletes, Prunable, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'category',
        'phone',
        'email',
        'address',
    ];

    // public function prunable() {
    //     return static::where('deleted_at', '<=', now()->subDays(90));
    // }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }
}

