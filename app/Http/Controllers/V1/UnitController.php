<?php

namespace App\Http\Controllers\V1;

use App\Exports\V1\UnitsExport;
use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreUnitRequest;
use App\Http\Requests\V1\UpdateUnitRequest;
use App\Models\Unit;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class UnitController extends Controller
{
    /**
     * Display a listing of the units.
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
                $filteredRowCount = $query->count();
            }

            if (!$request->has('pageIndex') && !$request->has('pageSize')) {
                $responseData = $query->get();
            } else {
                $pageIndex = $request->query('pageIndex', 1);
                $pageSize = $request->query('pageSize', $query->count());
                $data = $query->paginate($pageSize, ['*'], 'page', $pageIndex);

                $responseData = [
                    'totalRowCount' => Unit::withoutTrashed()->count(),
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
     * Store a newly created unit in storage.
     */
    public function store(StoreUnitRequest $request)
    {
        if ($request->user()->cannot('create units')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        $validated = $request->validated();

        $unit = Unit::create([
            'name' => $validated['name']
        ]);

        return ResponseFormatter::success(data: $unit);
    }

    /**
     * Display the specified unit.
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
     * Update the specified unit in storage.
     */
    public function update(UpdateUnitRequest $request, string $id)
    {
        if ($request->user()->cannot('update units')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

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
     * Remove the specified unit from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->cannot('delete units')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $units = Unit::withoutTrashed()->whereIn('id', $request->id);

            foreach ($units->get() as $unit) {
                $products = $unit->products()->get();

                if ($products->isNotEmpty()) {
                    return ResponseFormatter::error(409, 'Conflict');
                }
            };

            $units->delete();
            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Export the specified unit from storage.
     */
    public function export(Request $request)
    {
        if ($request->user()->cannot('access units')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        $supplierIds = $request->input('id', []);
        $fileType = $request->input('fileType');

        switch ($fileType) {
            case 'CSV':
                return Excel::raw(new UnitsExport(
                    $supplierIds
                ), \Maatwebsite\Excel\Excel::CSV);
                break;
            case 'XLSX':
                return Excel::raw(new UnitsExport(
                    $supplierIds
                ), \Maatwebsite\Excel\Excel::XLSX);
                break;
        };
    }
}
