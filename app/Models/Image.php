<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Image extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'path',
        'collection_name',
    ];

    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }
}

