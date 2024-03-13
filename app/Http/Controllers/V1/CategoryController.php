<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreCategoryRequest;
use App\Http\Requests\V1\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Feature;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.c
     */
    public function index(Request $request)
    {
        if ($request->user()->cannot('access categories')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $validColumns = ['id', 'name', 'description', 'created_at', 'updated_at'];

            $filter = $request->query('filter');
            $columns = $request->query('columns', $validColumns);

            $columns = array_intersect($columns, $validColumns);
            $category = $columns ? Category::query() : Category::with('features', 'image');
            $query = $category->withoutTrashed()->orderBy('created_at', 'desc')->select($columns);

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
            'description' => $validated['description'],
            'created_by' => auth()->id()
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
        if ($request->user()->cannot('access categories')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $category = Category::with('features', 'image')->find($id);

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
            'description' => $validated['description'],
            'updated_by' => auth()->id()
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
            Category::withoutTrashed()
                ->whereIn('id', $request->id)
                ->delete();

            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }
}
