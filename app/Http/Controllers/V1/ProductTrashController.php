<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductTrashController extends Controller
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
        $validColumns = ['id', 'name', 'description', 'stock', 'price', 'expiry_period', 'created_at', 'updated_at'];

        $pageIndex = $request->query('pageIndex');
        $pageSize = $request->query('pageSize');
        $filter = $request->query('filter');
        $columns = $request->query('columns', $validColumns);

        $columns = array_intersect($columns, $validColumns);
        $query = Product::onlyTrashed()->orderBy('created_at', 'desc')->with('unit', 'supplier', 'category')->select($columns);

        if ($filter !== null && $filter !== '') {
            $query->where(function ($q) use ($filter) {
                $q->where('name', 'like', '%' . $filter . '%')
                    ->orWhere('description', 'like', '%' . $filter . '%');
            });
        }

        $data = $query->paginate(perPage: $pageSize ?? $query->count(), page: $pageIndex ?? 0);

        $responseData = [
            'totalRowCount' => Product::onlyTrashed()->count(),
            'filteredRowCount' => $query->count(),
            'pageCount' => $data->lastPage(),
            'rows' => $data->items()
        ];

        return ResponseFormatter::success(data: $responseData);
    } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Request $request)
    {
        if ($request->user()->cannot('restore products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        try {
            $successes = [];
            $failures = [];

            foreach ($request->input('id') as $id) {
                $product = Product::onlyTrashed()->find($id);

                if ($product) {
                    $product->restore();
                    $successes[] = $product;
                } else {
                    $failures[] = [
                        'id' => $id,
                        'error' => 'Produk dengan ID ' . $id . ' tidak ditemukan di folder sampah.',
                    ];
                }
            }
            $data = [
                'successes' => $successes,
                'failures' => $failures,
            ];

            if (!empty($successes) && !empty($failures)) {
                return ResponseFormatter::success(207, 'Multi-Status', $data);
            } else if (empty($failures)) {
                return ResponseFormatter::success(data: $data);
            } else {
                return ResponseFormatter::error(errors: $failures);
            }
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->cannot('force delete products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $product = Product::onlyTrashed()->whereIn('id', $request->id)->first();

            if ($product) {
                // Delete associated images from storage
                $product->images()->get()->each(function ($image) {
                    Storage::disk('public')->delete($image->path);
                });

                // Delete the product
                $product->forceDelete();
            }

            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Empty the storage.
     */
    public function empty(Request $request)
    {
        if ($request->user()->cannot('force delete products')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            DB::beginTransaction();

            $trashedProducts = Product::onlyTrashed()->with('images')->get();

            foreach ($trashedProducts as $product) {
                // Delete associated images from storage
                $product->images->each(function ($image) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                });

                // Force delete the product
                $product->forceDelete();
            }

            DB::commit();

            return ResponseFormatter::success();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }
}
