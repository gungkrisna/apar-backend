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
            $filter = $request->query('filter');

            $query = Product::with(['images', 'unit', 'supplier', 'category'])
                ->onlyTrashed()->orderBy('created_at', 'desc');

            if ($request->has('columns')) {
                $query = $query->select(explode(',', $request->columns));
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($filter !== null && $filter !== '') {
                $query->where(function ($q) use ($filter) {
                    $q->where('name', 'like', '%' . $filter . '%')
                        ->orWhere('serial_number', 'like', '%' . $filter . '%')
                        ->orWhereHas('supplier', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter . '%');
                        })
                        ->orWhereHas('category', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter . '%');
                        })
                        ->orWhereHas('unit', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter . '%');
                        });
                });
                $filteredRowCount = $query->count();
            }

            if (!$request->has('pageIndex') && !$request->has('pageSize')) {
                $responseData = $query->get();
            } else {
                $pageIndex = $request->query('pageIndex', 1);
                $pageSize = $request->query('pageSize', $query->count());
                $data = $query->paginate($pageSize, ['*'], 'page', $pageIndex);

                $responseData = [
                    'totalRowCount' => Product::onlyTrashed()->count(),
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
            $products = Product::onlyTrashed()->whereIn('id', $request->id);

            foreach ($products->get() as $product) {
                $product->images()->get()->each(function ($image) {
                    Storage::disk('public')->delete($image->path);
                });

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
