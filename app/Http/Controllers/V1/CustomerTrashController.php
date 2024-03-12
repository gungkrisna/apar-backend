<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerTrashController extends Controller
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
            $validColumns = ['id', 'company_name', 'pic_name', 'phone', 'email', 'address', 'created_at', 'updated_at'];

            $filter = $request->query('filter');
            $columns = $request->query('columns', $validColumns);

            $columns = array_intersect($columns, $validColumns);
            $query = Customer::onlyTrashed()
                ->orderBy('created_at', 'desc')
                ->select($columns);

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
                'totalRowCount' => Customer::onlyTrashed()->count(),
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
        if ($request->user()->cannot('restore customers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        try {
            $successes = [];
            $failures = [];

            foreach ($request->input('id') as $id) {
                $customer = Customer::onlyTrashed()->find($id);

                if ($customer) {
                    $customer->restore();
                    $successes[] = $customer;
                } else {
                    $failures[] = [
                        'id' => $id,
                        'error' => 'Pelanggan dengan ID ' . $id . ' tidak ditemukan di folder sampah.',
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
        if ($request->user()->cannot('force delete customers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            Customer::onlyTrashed()
                ->whereIn('id', $request->id)
                ->forceDelete();

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
        if ($request->user()->cannot('force delete customers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            Customer::onlyTrashed()->forceDelete();
            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }
}
