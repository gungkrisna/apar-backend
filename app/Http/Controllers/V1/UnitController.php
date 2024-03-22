<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreUnitRequest;
use App\Http\Requests\V1\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->cannot('access units')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $filter = $request->query('filter');

            $query = Unit::withoutTrashed()->orderBy('created_at', 'desc');

            if ($request->has('columns')) {
                $query = $query->select(explode(',', $request->columns));
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
                    'totalRowCount' => Unit::withoutTrashed()->count(),
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
    public function store(StoreUnitRequest $request)
    {
        $validated = $request->validated();

        $unit = Unit::create([
            'name' => $validated['name'],
            'created_by' => $request->user()->id
        ]);

        return ResponseFormatter::success(data: $unit);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        if ($request->user()->cannot('access units')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $unit = Unit::find($id);

            if (!$unit) {
                return ResponseFormatter::error('404', 'Not Found');
            }

            return ResponseFormatter::success(data: $unit);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUnitRequest $request, string $id)
    {
        try {
            $validated = $request->validated();

            $unit = Unit::find($id);

            if (!$unit) {
                return ResponseFormatter::error(404, 'Unit not found');
            }

            $unit->name = $validated['name'];

            $unit->save();

            return ResponseFormatter::success(data: $unit);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', [$e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->cannot('delete units')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            Unit::withoutTrashed()
                ->whereIn('id', $request->id)
                ->delete();

            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }
}
