<?php

namespace App\Http\Controllers\V1;

use App\Exports\V1\SuppliersExport;
use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreSupplierRequest;
use App\Http\Requests\V1\UpdateSupplierRequest;
use Illuminate\Http\Request;
use App\Models\Supplier;
use Maatwebsite\Excel\Facades\Excel;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->cannot('access suppliers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $filter = $request->query('filter');

            $query = Supplier::withoutTrashed()->orderBy('created_at', 'desc');

            if ($request->has('columns')) {
                $query = $query->select(explode(',', $request->columns));
            }

            if ($filter !== null && $filter !== '') {
                $query->where(function ($q) use ($filter) {
                    $q->where('name', 'like', '%' . $filter . '%')
                        ->orWhere('phone', 'like', '%' . $filter . '%')
                        ->orWhere('email', 'like', '%' . $filter . '%')
                        ->orWhere('address', 'like', '%' . $filter . '%');
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
                    'totalRowCount' => Supplier::withoutTrashed()->count(),
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
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        if ($request->user()->cannot('create suppliers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        $validated = $request->validated();

        $supplier = Supplier::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'address' => $validated['address']
        ]);

        return ResponseFormatter::success(data: $supplier);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        if ($request->user()->cannot('access suppliers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $supplier = Supplier::find($id);

            if (!$supplier) {
                return ResponseFormatter::error('404', 'Not Found');
            }

            return ResponseFormatter::success(data: $supplier);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupplierRequest $request, string $id)
    {
        if ($request->user()->cannot('update suppliers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $validated = $request->validated();

            $supplier = Supplier::find($id);

            if (!$supplier) {
                return ResponseFormatter::error(404, 'Supplier not found');
            }

            $supplier->name = $validated['name'];
            $supplier->phone = $validated['phone'];
            $supplier->email = $validated['email'];
            $supplier->address = $validated['address'];

            $supplier->save();

            return ResponseFormatter::success(data: $supplier);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', [$e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->cannot('delete suppliers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $suppliers = Supplier::withoutTrashed()->whereIn('id', $request->id);

            foreach ($suppliers->get() as $supplier) {
                $products = $supplier->products()->get();
                $purchases = $supplier->purchases()->get();

                if ($products->isNotEmpty() || $purchases->isNotEmpty()) {
                    return ResponseFormatter::error(409, 'Conflict');
                }
            };

            $suppliers->delete();
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
        if ($request->user()->cannot('access suppliers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        $supplierIds = $request->input('id', []);
        $fileType = $request->input('fileType');

        switch ($fileType) {
            case 'CSV':
                return Excel::raw(new SuppliersExport(
                    $supplierIds
                ), \Maatwebsite\Excel\Excel::CSV);
                break;
            case 'XLSX':
                return Excel::raw(new SuppliersExport(
                    $supplierIds
                ), \Maatwebsite\Excel\Excel::XLSX);
                break;
        };
    }
}
