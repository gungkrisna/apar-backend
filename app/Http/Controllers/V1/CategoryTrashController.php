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
            $filter = $request->query('filter');
            $columns = $request->query('columns');
            $selectColumns = $columns ? explode(',', $columns) : ['*'];

            $query = Category::query();

            // Eager-load relationships and remove them from select columns
            if (in_array('image', $selectColumns)) {
                $query->with('image');
                $selectColumns = array_diff($selectColumns, ['image']);
            }

            if (in_array('features', $selectColumns)) {
                $query->with('features');
                $selectColumns = array_diff($selectColumns, ['features']);
            }
            $query->onlyTrashed()->orderBy('created_at', 'desc');

            if ($columns) {
                $query->select($selectColumns);
            }

            if ($filter !== null && $filter !== '') {
                $query->where(function ($q) use ($filter) {
                    $q->where('name', 'like', '%' . $filter . '%');
                });
            }

            if (!$request->has('pageIndex') && !$request->has('pageSize')) {
                $responseData = $query->get();
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
            $categories = Category::onlyTrashed()->whereIn('id', $request->id);

            foreach ($categories->get() as $category) {
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
            DB::beginTransaction();

            $categories = Category::onlyTrashed()->with('image');

            foreach ($categories->get() as $category) {
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
