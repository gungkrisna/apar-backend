<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierTrashController extends Controller
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
            $validColumns = ['id', 'name', 'phone', 'email', 'address'];

            $pageIndex = $request->query('pageIndex');
            $pageSize = $request->query('pageSize');
            $filter = $request->query('filter');
            $columns = $request->query('columns', $validColumns);

            $columns = array_intersect($columns, $validColumns);
            $query = Supplier::onlyTrashed()->orderBy('created_at', 'desc')->select($columns);


            if ($filter !== null && $filter !== '') {
                $query->where(function ($q) use ($filter) {
                    $q->where('name', 'like', '%' . $filter . '%')
                        ->orWhere('phone', 'like', '%' . $filter . '%')
                        ->orWhere('email', 'like', '%' . $filter . '%')
                        ->orWhere('address', 'like', '%' . $filter . '%');
                });
            }

            $data = $query->paginate(perPage: $pageSize ?? $query->count(), page: $pageIndex ?? 1);

            $responseData = [
                'totalRowCount' => Supplier::onlyTrashed()->count(),
                'filteredRowCount' => $query->count(),
                'pageCount' => $data->lastPage(),
                'rows' => $data->items(),
            ];

            return ResponseFormatter::success(200, 'Success', $responseData);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Restore the specified resource from storage.
     */
    public function restore(Request $request)
    {
        if ($request->user()->cannot('restore suppliers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        try {
            $successes = [];
            $failures = [];

            foreach ($request->input('id') as $id) {
                $supplier = Supplier::onlyTrashed()->find($id);

                if ($supplier) {
                    $supplier->restore();
                    $successes[] = $supplier;
                } else {
                    $failures[] = [
                        'id' => $id,
                        'error' => 'Supplier dengan ID ' . $id . ' tidak ditemukan di folder sampah.',
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
        if ($request->user()->cannot('force delete suppliers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            Supplier::whereIn('id', $request->id)
                ->onlyTrashed()
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
        if ($request->user()->cannot('force delete suppliers')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        try {
            Supplier::onlyTrashed()->forceDelete();
            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }
}
