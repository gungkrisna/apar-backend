<?php

namespace App\Http\Controllers\V1;

use App\Exports\V1\CustomersExport;
use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreCustomerRequest;
use App\Http\Requests\V1\UpdateCustomerRequest;
use App\Models\Customer;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
     public function index(Request $request)
    {
        if ($request->user()->cannot('access customers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $filter = $request->query('filter');

            $query = Customer::withoutTrashed()->orderBy('created_at', 'desc');

            if ($request->has('columns')) {
                $query = $query->select(explode(',', $request->columns));
            }

            if ($filter !== null && $filter !== '') {
                $query->where(function ($q) use ($filter) {
                    $q->where('company_name', 'like', '%' . $filter . '%')
                        ->orWhere('pic_name', 'like', '%' . $filter . '%')
                        ->orWhere('phone', 'like', '%' . $filter . '%')
                        ->orWhere('phone', 'like', '%' . $filter . '%')
                        ->orWhere('email', 'like', '%' . $filter . '%')
                        ->orWhere('address', 'like', '%' . $filter . '%');
                });
            }

            if (!$request->has('pageIndex') && !$request->has('pageSize')) {
                $responseData = $query->get();
            } else {
            $pageIndex = $request->query('pageIndex', 1);
            $pageSize = $request->query('pageSize', $query->count());
            $data = $query->paginate($pageSize, ['*'], 'page', $pageIndex);

            $responseData = [
                'totalRowCount' => Customer::withoutTrashed()->count(),
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
    public function store(StoreCustomerRequest $request)
    {
        $validated = $request->validated();

        $customer = Customer::create([
            'company_name' => $validated['company_name'],
            'pic_name' => $validated['pic_name'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'address' => $validated['address']
        ]);

        return ResponseFormatter::success(data: $customer);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        if ($request->user()->cannot('access customers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return ResponseFormatter::error('404', 'Not Found');
            }

            return ResponseFormatter::success(data: $customer);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, string $id)
    {
        try {
            $validated = $request->validated();

            $customer = Customer::find($id);

            if (!$customer) {
                return ResponseFormatter::error(404, 'Customer not found');
            }

            $customer->company_name = $validated['company_name'];
            $customer->pic_name = $validated['pic_name'];
            $customer->phone = $validated['phone'];
            $customer->email = $validated['email'];
            $customer->address = $validated['address'];

            $customer->save();

            return ResponseFormatter::success(data: $customer);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', [$e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->cannot('delete customers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            $customers = Customer::withoutTrashed()->whereIn('id', $request->id);

            foreach ($customers->get() as $customer) {
                $invoices = $customer->invoices()->get();

                if ($invoices->isNotEmpty()) {
                    return ResponseFormatter::error(409, 'Conflict');
                }
            };

            $customers->delete();

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
        if ($request->user()->cannot('access customers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        $supplierIds = $request->input('id', []);
        $startDate = $request->input('startDate', null);
        $endDate = $request->input('endDate', null);
        $fileType = $request->input('fileType');

        switch ($fileType) {
            case 'CSV':
                return Excel::raw(new CustomersExport($supplierIds, $startDate, $endDate), \Maatwebsite\Excel\Excel::CSV);
                break;
            case 'XLSX':
                return Excel::raw(new CustomersExport($supplierIds, $startDate, $endDate), \Maatwebsite\Excel\Excel::XLSX);
                break;
        };
    }
}
