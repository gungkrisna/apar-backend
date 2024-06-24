<?php

namespace App\Http\Controllers\V1;


use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreInvoiceImageRequest;
use App\Models\Image;
use Illuminate\Support\Facades\Storage;

class InvoiceImageController extends Controller
{
    /**
     * Store a newly created invoice image in storage.
     */
    public function store(StoreInvoiceImageRequest $request)
    {
        $path = $request->file('image')->store('images/invoices', 'public');

        $image = Image::create(['path' => $path]);

        return ResponseFormatter::success(data: [
            'id' => $image->id,
            'path' => Storage::url($path),
        ]);
    }

    /**
     * Remove the specified invoice image from storage.
     */
    public function destroy($id)
    {
        $image = Image::findOrFail($id);
        if($image->collection_name === 'invoice_images'){
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }

        return ResponseFormatter::success();
    }
}
