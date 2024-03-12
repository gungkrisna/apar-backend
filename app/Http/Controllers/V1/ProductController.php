<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreProductRequest;
use App\Http\Requests\V1\UpdateProductRequest;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->cannot('access products')) {
        return ResponseFormatter::error('401', 'Unauthorized');
    }

    try {
        $validColumns = ['id', 'serial_number', 'name', 'description', 'stock', 'price', 'expiry_period', 'created_by', 'updated_by', 'unit_id', 'category_id', 'supplier_id', 'created_at', 'updated_at'];

        $filter = $request->query('filter');
        $columns = $request->query('columns', $validColumns);

        $columns = array_intersect($columns, $validColumns);
        $query = Product::with('images', 'unit', 'supplier', 'category', 'createdBy', 'updatedBy')
            ->select($columns)
            ->withoutTrashed()
            ->orderBy('created_at', 'desc');

        if ($filter !== null && $filter !== '') {
            $query->where(function ($q) use ($filter) {
                $q->where('name', 'like', '%' . $filter . '%')
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
                'totalRowCount' => Product::withoutTrashed()->count(),
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
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        $product = Product::create([
            'status' => $validated['status'],
            'serial_number' => $validated['serial_number'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'stock' => 0,
            'price' => $validated['price'],
            'expiry_period' => $validated['expiry_period'],
            'unit_id' => $validated['unit_id'],
            'supplier_id' => $validated['supplier_id'],
            'category_id' => $validated['category_id'],
            'created_by' => auth()->id(),
        ]);

        if ($request->has('images')) {
            $images = $validated['images'];
            foreach ($images as $imageId) {
                $image = Image::find($imageId);
                $image->collection_name = 'images';
                $product->images()->save($image);
            }
        }

        return ResponseFormatter::success(data: $product);
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        if ($request->user()->cannot('access products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $product = Product::with('images', 'supplier', 'category', 'createdBy', 'updatedBy')->find($id);

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
    public function update(UpdateProductRequest $request, $id)
    {
        $validated = $request->validated();

        $product = Product::findOrFail($id);

        $product->update([
            'status' => $validated['status'],
            'serial_number' => $validated['serial_number'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            // 'stock' => $validated['stock'],
            'price' => $validated['price'],
            'expiry_period' => $validated['expiry_period'],
            'unit_id' => $validated['unit_id'],
            'supplier_id' => $validated['supplier_id'],
            'category_id' => $validated['category_id'],
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
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->cannot('delete products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            Product::withoutTrashed()
                ->whereIn('id', $request->id)
                ->delete();

            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }
}
