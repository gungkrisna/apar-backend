<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreCategoryImageRequest;
use App\Models\Image;
use Illuminate\Support\Facades\Storage;

class CategoryImageController extends Controller
{
    /**
     * Store a newly created category image in storage.
     */
    public function store(StoreCategoryImageRequest $request)
    {
        $path = $request->file('image')->store('images/categories', 'public');

        $image = Image::create(['path' => $path]);

        return ResponseFormatter::success(data: [
            'id' => $image->id,
            'path' => Storage::url($path),
        ]);
    }

    /**
     * Remove the specified category image from storage.
     */
    public function destroy($id)
    {
        $image = Image::findOrFail($id);
        if($image->collection_name === 'category_image'){  
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }
        
        return ResponseFormatter::success();
    }
}
