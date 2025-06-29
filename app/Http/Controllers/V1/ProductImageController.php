<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreProductImageRequest;
use App\Models\Image;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * Store a newly created product image in storage.
     */
    public function store(StoreProductImageRequest $request)
    {
        $path = $request->file('image')->store('images/products', 'public');

        $image = Image::create(['path' => $path]);

        return ResponseFormatter::success(data: [
            'id' => $image->id,
            'path' => Storage::url($path),
        ]);
    }

    /**
     * Remove the specified product image from storage.
     */
    public function destroy($id)
    {
        $image = Image::findOrFail($id);
        if($image->collection_name === 'product_images'){
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }
        
        return ResponseFormatter::success();
    }
}
