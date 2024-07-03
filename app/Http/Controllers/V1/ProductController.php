<?php

namespace App\Http\Controllers\V1;

use App\Exports\V1\ProductsExport;
use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreProductRequest;
use App\Http\Requests\V1\UpdateProductRequest;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    /**
     * Display a listing of the products.
     */
    public function index(Request $request)
    {
        try {
            $filter = $request->query('filter');
            $sortBy = $request->query('sortBy');

            $query = Product::with(['images', 'unit', 'category'])
                ->withoutTrashed();

            if ($request->user() && $request->user()->can('access products')) {
                $query->with('supplier');
            } else {
                $query->without('supplier');
                $query->where('status', '!=', 0);
            }

            switch ($sortBy) {
                case 'latest':
                    $query->orderBy('created_at', 'desc');
                    break;
                case 'highest_price':
                    $query->orderBy('price', 'desc');
                    break;
                case 'lowest_price':
                    $query->orderBy('price', 'asc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
                    break;
            }

            if ($request->has('columns')) {
                $query = $query->select(explode(',', $request->columns));
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->user() && $request->user()->can('access products') && $request->has('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($filter !== null && $filter !== '') {
                $query->where(function ($q) use ($filter, $request) {
                    $q->where('name', 'like', '%' . $filter . '%')
                        ->orWhere('serial_number', 'like', '%' . $filter . '%');

                    $user = $request->user();
                    if ($user && $user->can('access products')) {
                        $q->orWhereHas('supplier', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter . '%');
                        });
                    }

                    $q->orWhereHas('category', function ($q) use ($filter) {
                        $q->where('name', 'like', '%' . $filter . '%');
                    })
                        ->orWhereHas('unit', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter . '%');
                        });
                });
                $filteredRowCount = $query->count();
            }

            $products = $query->get();

            if (!$request->user() || !$request->user()->can('access products')) {
                foreach ($products as $product) {
                    unset($product->supplier_id);
                }
            }

            if (!$request->has('pageIndex') && !$request->has('pageSize')) {
                $responseData = $products;
            } else {
                $pageIndex = $request->query('pageIndex', 1);
                $pageSize = $request->query('pageSize', $query->count());
                $data = $query->paginate($pageSize, ['*'], 'page', $pageIndex);

                $responseData = [
                    'totalRowCount' => Product::withoutTrashed()->count(),
                    'filteredRowCount' => $filteredRowCount ?? 0,
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
     * Store a newly created product in storage.
     */
    public function store(StoreProductRequest $request)
    {
        if ($request->user()->cannot('create products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        $validated = $request->validated();

        $product = Product::create([
            'status' => $validated['status'],
            'serial_number' => $validated['serial_number'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'stock' => 0,
            'price' => $validated['price'],
            'unit_id' => $validated['unit_id'],
            'supplier_id' => $validated['supplier_id'],
            'category_id' => $validated['category_id'],
            'expiry_period' => $request->has('expiry_period') ? $validated['expiry_period'] : null
        ]);

        if ($request->filled('images')) {
            $images = $validated['images'];

            foreach ($images as $imageId) {
                $image = Image::find($imageId);
                $image->collection_name = 'product_images';
                $product->images()->save($image);
            }
        }

        return ResponseFormatter::success(data: $product);
    }


    /**
     * Display the specified product.
     */
    public function show(Request $request, string $id)
    {
        try {
            $product = Product::with('images', 'unit', 'category')
                ->withoutTrashed()
                ->find($id);

            if ($request->user() && $request->user()->can('access products')) {
                $product->load('supplier');
            } else {
                if ($product->status == 0) {
                    return ResponseFormatter::error(404, 'Product not found');
                }
                unset($product->supplier_id);
            }

            if (!$product) {
                return ResponseFormatter::error(404, 'Not Found');
            }

            return ResponseFormatter::success(data: $product);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Get the product by its serial number.
     */
    public function getBySerialNumber(Request $request, string $serialNumber)
    {
        if ($request->user()->cannot('access products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $product = Product::with('images', 'supplier', 'category')
                ->where('serial_number', $serialNumber)
                ->first();

            if (!$product) {
                return ResponseFormatter::error(404, 'Product not found');
            }

            return ResponseFormatter::success(data: $product);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Update the specified product in storage.
     */
    public function update(UpdateProductRequest $request, $id)
    {
        if ($request->user()->cannot('update products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        $validated = $request->validated();

        $product = Product::findOrFail($id);

        $product->update([
            'status' => $validated['status'],
            'serial_number' => $validated['serial_number'],
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'unit_id' => $validated['unit_id'],
            'supplier_id' => $validated['supplier_id'],
            'category_id' => $validated['category_id']
        ]);

        if ($request->has('expiry_period')) {
            $product->update(['expiry_period' => $validated['expiry_period']]);
        } else {
            $product->update(['expiry_period' => null]);
        }

        if ($request->filled('images')) {
            $images = $validated['images'];

            $currImages = $product->images->pluck('id')->toArray();
            $imagesToDelete = array_diff($currImages, $images);

            foreach ($imagesToDelete as $imageId) {
                $image = Image::find($imageId);
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }

            foreach ($images as $imageId) {
                $image = Image::find($imageId);
                $image->collection_name = 'product_images';
                $product->images()->save($image);
            }
        } else {
            // empty images means delete all images if any
            $product->images()->delete();
        }

        return ResponseFormatter::success(data: $product);
    }

    public function updateStatus(Request $request)
    {
        if ($request->user()->cannot('update products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        $validated = $request->validate([
            'product_ids' => 'required|array',
            'active' => 'required|boolean',
        ]);

        $productIds = $validated['product_ids'];
        $active = $validated['active'];

        Product::whereIn('id', $productIds)->update(['status' => $active]);

        return ResponseFormatter::success();
    }

    /**
     * Generate unique EAN code for serial number.
     */
    public function generateSerialNumber(Request $request)
    {
        if ($request->user()->cannot('create products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        $eanCode = Product::generateSerialNumber();

        return ResponseFormatter::success(data: $eanCode);
    }

    /**
     * Remove the specified product from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->cannot('delete products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $products = Product::withoutTrashed()->whereIn('id', $request->id);

            foreach ($products->get() as $product) {
                $invoices = $product->invoiceItems()->get();
                $purchases = $product->purchaseItems()->get();

                if ($invoices->isNotEmpty() || $purchases->isNotEmpty()) {
                    return ResponseFormatter::error(409, 'Conflict');
                }
            };

            $products->delete();
            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Export the specified product from storage.
     */
    public function export(Request $request)
    {
        if ($request->user()->cannot('access products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        $productIds = $request->input('id', []);
        $status = $request->input('status', null);
        $supplierId = $request->input('supplierId', null);
        $categoryId = $request->input('categoryId', null);
        $fileType = $request->input('fileType');

        switch ($fileType) {
            case 'CSV':
                return Excel::raw(new ProductsExport(
                    $productIds,
                    $status,
                    $supplierId,
                    $categoryId
                ), \Maatwebsite\Excel\Excel::CSV);
                break;
            case 'XLSX':
                return Excel::raw(new ProductsExport(
                    $productIds,
                    $status,
                    $supplierId,
                    $categoryId
                ), \Maatwebsite\Excel\Excel::XLSX);
                break;
        };
    }
}
