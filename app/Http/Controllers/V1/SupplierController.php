<?php

namespace App\Http\Controllers\V1;

use App\Exports\V1\SuppliersExport;
use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreSupplierRequest;
use App\Http\Requests\V1\UpdateSupplierRequest;
use Illuminate\Http\Request;
use App\Models\Supplier;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Browsershot\Browsershot;

class SupplierController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->cannot('access suppliers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        try {
            $validColumns = ['id', 'name', 'phone', 'email', 'address', 'status', 'created_at', 'updated_at'];

            $pageIndex = $request->query('pageIndex');
            $pageSize = $request->query('pageSize');
            $filter = $request->query('filter');
            $columns = $request->query('columns', $validColumns);

            $columns = array_intersect($columns, $validColumns);
            $query = Supplier::withoutTrashed()->orderBy('created_at', 'desc')->select($columns);

            if ($filter !== null && $filter !== '') {
                $query->where(function ($q) use ($filter) {
                    $q->where('name', 'like', '%' . $filter . '%')
                        ->orWhere('phone', 'like', '%' . $filter . '%')
                        ->orWhere('email', 'like', '%' . $filter . '%')
                        ->orWhere('address', 'like', '%' . $filter . '%');
                });
            }

            $data = $query->paginate(perPage: $pageSize ?? $query->count(), page: $pageIndex ?? 0);

            $responseData = [
                'totalRowCount' => Supplier::withoutTrashed()->count(),
                'filteredRowCount' => $query->count(),
                'pageCount' => $data->lastPage(),
                'rows' => $data->items()
            ];

            return ResponseFormatter::success(200, 'Success', $responseData);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSupplierRequest $request)
    {
        $validated = $request->validated();

        $supplier = Supplier::create([
            'name' => $validated['name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'address' => $validated['address'],
            'created_by' => $request->user()->id
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
            $supplier->updated_by = $request->user()->id;

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
            Supplier::whereIn('id', $request->id)
                ->withoutTrashed() 
                ->delete();

            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        if ($request->user()->cannot('access suppliers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        $supplierIds = $request->input('id', []);
        $startDate = $request->input('startDate', null);
        $endDate = $request->input('endDate', null);
        $fileType = $request->input('fileType');

        switch ($fileType) {
            case 'CSV':
                return Excel::raw(new SuppliersExport($supplierIds, $startDate, $endDate), \Maatwebsite\Excel\Excel::CSV);
                break;
            case 'XLSX':
                return Excel::raw(new SuppliersExport($supplierIds, $startDate, $endDate), \Maatwebsite\Excel\Excel::XLSX);
                break;
            case 'PDF':
                // Create a query builder for the Supplier model
                $query = Supplier::query();

                // Apply filtering conditions
                if (!empty($supplierIds)) {
                    $query->whereIn('id', $supplierIds);
                }

                if (!empty($startDate)) {
                    $query->where('created_at', '>=', $startDate);
                }

                if (!empty($endDate)) {
                    $query->where('created_at', '<=', $endDate);
                }

                // Retrieve the filtered data
                $suppliers = $query->get();

                $html = View::make('supplier-table', compact('suppliers'))->with('model', 'Supplier')->render();
                $pdf = Browsershot::html($html)
                    ->landscape()
                    ->format('A4')
                    ->waitUntilNetworkIdle()
                    ->showBrowserHeaderAndFooter()
                    ->pdf();
                return response($pdf)->header('Content-Type', 'application/pdf');
        };
    }
}
