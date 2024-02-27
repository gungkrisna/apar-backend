<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Feature;
use App\Models\Image;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'image',
        'created_by',
        'updated_by',
    ];

    public function features()
    {
        return $this->hasMany(Feature::class);
    }

    public function headerImage(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->where('collection_name', 'image');
    }
}
