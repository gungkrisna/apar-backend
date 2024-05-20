<?php

namespace App\Http\Controllers\V1;

use App\Exports\V1\CategoriesExport;
use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreCategoryRequest;
use App\Http\Requests\V1\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Feature;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.c
     */
    public function index(Request $request)
    {
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
            $query->withoutTrashed()->orderBy('created_at', 'desc');

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
                    'totalRowCount' => Category::withoutTrashed()->count(),
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
    public function store(StoreCategoryRequest $request)
    {
        $validated = $request->validated();

        $category = Category::create([
            'name' => $validated['name'],
            'description' => $validated['description']
        ]);

        if ($request->has('image')) {
            $image = Image::find($request->input('image'));
            $image->collection_name = 'category_image';
            $category->image()->save($image);
        }

        if ($request->has('features')) {
            $features = $validated['features'];
            foreach ($features as $feature) {
                $categoryFeature = new Feature([
                    'icon' => $feature['icon'],
                    'name' => $feature['name'],
                    'description' => $feature['description'],
                ]);

                $category->features()->save($categoryFeature);
            }
        }

        return ResponseFormatter::success(data: $category);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        try {
            $category = Category::with('image', 'features')->find($id);

            if (!$category) {
                return ResponseFormatter::error(404, 'Not Found');
            }

            return ResponseFormatter::success(data: $category);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCategoryRequest $request, $id)
    {
        $validated = $request->validated();

        $category = Category::findOrFail($id);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description']
        ]);

        if ($request->has('image')) {
            $image = Image::find($request->input('image'));

            if ($category->image && $category->image != $image) {
                Storage::disk('public')->delete($category->image->path);
                $category->image->delete();
            }

            $image->collection_name = 'category_image';
            $category->image()->save($image);
        }

        $category->features()->delete();

        if ($request->has('features')) {
            $features = $validated['features'];
            foreach ($features as $feature) {
                $categoryFeature = new Feature([
                    'icon' => $feature['icon'],
                    'name' => $feature['name'],
                    'description' => $feature['description'],
                ]);

                $category->features()->save($categoryFeature);
            }
        }

        return ResponseFormatter::success(data: $category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->cannot('delete categories')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $categories = Category::withoutTrashed()->whereIn('id', $request->id);

            foreach ($categories->get() as $category) {
                $products = $category->products()->get();

                if ($products->isNotEmpty()) {
                    return ResponseFormatter::error(409, 'Conflict');
                }
            };

            $categories->delete();
            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Export the specified resource from storage.
     */
    public function export(Request $request)
    {
        if ($request->user()->cannot('access categories')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        $supplierIds = $request->input('id', []);
        $startDate = $request->input('startDate', null);
        $endDate = $request->input('endDate', null);
        $fileType = $request->input('fileType');

        switch ($fileType) {
            case 'CSV':
                return Excel::raw(new CategoriesExport($supplierIds, $startDate, $endDate), \Maatwebsite\Excel\Excel::CSV);
                break;
            case 'XLSX':
                return Excel::raw(new CategoriesExport($supplierIds, $startDate, $endDate), \Maatwebsite\Excel\Excel::XLSX);
                break;
        };
    }
}
