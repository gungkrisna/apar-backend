<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CategoryTrashController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     public function index(Request $request)
    {
        if ($request->user()->cannot('force delete categories')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $validColumns = ['id', 'name', 'description', 'created_at', 'updated_at'];

            $filter = $request->query('filter');
            $columns = $request->query('columns', $validColumns);

            $columns = array_intersect($columns, $validColumns);
            $category = $columns ? Category::query() : Category::with('features', 'image');
            $query = $category->onlyTrashed()->orderBy('created_at', 'desc')->select($columns);

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
                'totalRowCount' => Category::onlyTrashed()->count(),
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
     * Restore the specified resource from storage.
     */
    public function restore(Request $request)
    {
        if ($request->user()->cannot('restore categories')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        try {
            $successes = [];
            $failures = [];

            foreach ($request->input('id') as $id) {
                $category = Category::onlyTrashed()->find($id);

                if ($category) {
                    $category->restore();
                    $successes[] = $category;
                } else {
                    $failures[] = [
                        'id' => $id,
                        'error' => 'Kategori dengan ID ' . $id . ' tidak ditemukan di folder sampah.',
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
        if ($request->user()->cannot('force delete categories')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $category = Category::onlyTrashed()->whereIn('id', $request->id)->first();

            if ($category) {
                if ($category->image) {
                    Storage::disk('public')->delete($category->image->path);
                    $category->image->delete();
                }
                $category->forceDelete();
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
        if ($request->user()->cannot('force delete categories')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $trashedCategories = Category::onlyTrashed()->with('image')->get();

            foreach ($trashedCategories as $category) {
                if ($category->image) {
                    Storage::disk('public')->delete($category->image->path);
                    $category->image->delete();
                }
                $category->forceDelete();
            }

            DB::commit();

            return ResponseFormatter::success();
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }
}
