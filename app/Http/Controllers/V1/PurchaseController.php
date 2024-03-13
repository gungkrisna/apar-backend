<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StorePurchaseRequest;
use App\Http\Requests\V1\UpdatePurchaseRequest;
use App\Models\Image;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->cannot('access purchases')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $validColumns = ['id', 'status', 'purchase_number', 'date', 'supplier_id', 'category_id', 'product_id', 'description', 'unit_price', 'quantity', 'total_price', 'created_by', 'updated_by'];

            $filter = $request->query('filter');
            $columns = $request->query('columns', $validColumns);

            $columns = array_intersect($columns, $validColumns);
            $query = Purchase::with('images', 'unit', 'supplier', 'category', 'createdBy', 'updatedBy')
                ->select($columns)
                ->orderBy('created_at', 'desc');

            if ($filter !== null && $filter !== '') {
                $query->where(function ($q) use ($filter) {
                    $q->where('status', 'like', '%' . $filter . '%')
                        ->orWhere('purchase_number', 'like', '%' . $filter . '%')
                        ->orWhereHas('unit', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter . '%');
                        })
                        ->orWhereHas('supplier', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter . '%');
                        })
                        ->orWhereHas('category', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter . '%');
                        })
                        ->orWhere('description', 'like', '%' . $filter . '%');
                });
            }

        if (!$request->has('pageIndex') && !$request->has('pageSize')) {
            $data = $query->get();
            $responseData = [
                'rows' => $data,
                'totalRowCount' => $query->count(),
                'filteredRowCount' => $query->count(),
                'pageCount' => 1,
            ];
        } else {
            $pageIndex = $request->query('pageIndex', 1);
            $pageSize = $request->query('pageSize', $query->count());
            $data = $query->paginate($pageSize, ['*'], 'page', $pageIndex);

            $responseData = [
                'totalRowCount' => Purchase::count(),
                'filteredRowCount' => $query->count(),
                'pageCount' => $data->lastPage(),
                'rows' => $data->items(),
            ];
        }

            return ResponseFormatter::success(data: $responseData);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

     /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();

        $purchase = Purchase::create([
            'status' => 0,
            'purchase_number' => $validated['purchase_number'],
            'date' => $validated['date'],
            'supplier_id' => $validated['supplier_id'],
            'category_id' => $validated['category_id'],
            'product_id' => $validated['product_id'],
            'description' => $validated['description'],
            'unit_price' => $validated['unit_price'],
            'quantity' => $validated['quantity'],
            'total_price' => $validated['unit_price'] * $validated['quantity'],
            'created_by' => auth()->id(),
        ]);

        if ($request->has('images')) {
            $images = $validated['images'];
            foreach ($images as $imageId) {
                $image = Image::find($imageId);
                $image->collection_name = 'images';
                $purchase->images()->save($image);
            }
        }

        return ResponseFormatter::success(data: $purchase);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        if ($request->user()->cannot('access purchases')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $product = Purchase::with('images', 'unit', 'supplier', 'category', 'createdBy', 'updatedBy')->find($id);

            if (!$product) {
                return ResponseFormatter::error(404, 'Not Found');
            }

            return ResponseFormatter::success(data: $product);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseRequest $request, $id)
    {
        $validated = $request->validated();

        $product = Purchase::findOrFail($id);

        $product->update([
            'purchase_number' => $validated['purchase_number'],
            'date' => $validated['date'],
            'supplier_id' => $validated['supplier_id'],
            'category_id' => $validated['category_id'],
            'product_id' => $validated['product_id'],
            'description' => $validated['description'],
            'unit_price' => $validated['unit_price'],
            'quantity' => $validated['quantity'],
            'total_price' => $validated['total_price'],
            'updated_by' => auth()->id(),
        ]);

    if ($request->has('images')) {
        $newImages = $validated['images'];

        $currImages = $product->images->pluck('id')->toArray();
        $imagesToDelete = array_diff($currImages, $newImages);

        foreach ($imagesToDelete as $imageId) {
            $image = Image::find($imageId);
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }

        foreach ($newImages as $imageId) {
            $image = Image::find($imageId);
            $image->collection_name = 'images';
            $product->images()->save($image);
        }
    }

        return ResponseFormatter::success(data: $product);
    }

    /**
     * Approve the specified resource.
     */
    public function approve(Request $request)
    {
        if ($request->user()->cannot('approve purchases')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        $product = Purchase::findOrFail($request->id);
        $product->status = 1;
        $product->save();

        return ResponseFormatter::success();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->cannot('delete purchases')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $purchase = Purchase::findOrFail($request->id);
            
            if ($purchase->status !== 1) {
                $purchase->delete();
            } else {
                return ResponseFormatter::error(400, 'Purchase is already approved.');
            }

            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }
}
