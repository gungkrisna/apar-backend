<?php

namespace App\Http\Controllers\V1;

use App\Exports\V1\PurchasesExport;
use App\Exports\V1\PurchaseItemsExport;
use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StorePurchaseRequest;
use App\Http\Requests\V1\UpdatePurchaseRequest;
use App\Models\Image;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class PurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->cannot('access purchases')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $filter = $request->query('filter');

            $query = Purchase::with(['images', 'supplier', 'purchaseItems'])->orderBy('created_at', 'desc');

            if ($request->has('columns')) {
                $query = $query = $query->select(explode(',', $request->columns));
            }

            if ($filter !== null && $filter !== '') {
                $query->where(function ($q) use ($filter) {
                    $q->where('status', 'like', '%' . $filter . '%')
                        ->orWhere('purchase_number', 'like', '%' . $filter . '%')
                        ->orWhereHas('purchaseItems', function ($q) use ($filter) {
                            $q->where('description', 'like', '%' . $filter . '%')
                                ->orWhereHas('product', function ($q) use ($filter) {
                                    $q->where('name', 'like', '%' . $filter . '%');
                                })
                                ->orWhereHas('category', function ($q) use ($filter) {
                                    $q->where('name', 'like', '%' . $filter . '%');
                                });
                        })
                        ->orWhereHas('supplier', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter . '%');
                        })
                        ->orWhere('description', 'like', '%' . $filter . '%');
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
                    'totalRowCount' => Purchase::count(),
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
    public function store(StorePurchaseRequest $request)
    {
        if ($request->user()->cannot('create purchases')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        $validated = $request->validated();

        $purchase = Purchase::create([
            'status' => 0,
            'purchase_number' => $validated['purchase_number'],
            'date' => $validated['date'],
            'discount' => $validated['discount'] ?? null,
            'tax' => $validated['tax'] ?? null,
            'description' => $validated['description'] ?? null,
            'supplier_id' => $validated['supplier_id']
        ]);

        foreach ($validated['purchase_items'] as $purchaseItemData) {
            $purchaseItem = new PurchaseItem();

            $purchaseItem->purchase_id = $purchase->id;
            $purchaseItem->category_id = $purchaseItemData['category_id'];
            $purchaseItem->product_id = $purchaseItemData['product_id'];
            $purchaseItem->description = $purchaseItemData['description'] ?? null;
            $purchaseItem->unit_price = $purchaseItemData['unit_price'];
            $purchaseItem->quantity = $purchaseItemData['quantity'];

            $purchaseItem->save();
        }

        if ($request->filled('images')) {
            $images = $validated['images'];

            foreach ($images as $imageId) {
                $image = Image::find($imageId);
                $image->collection_name = 'purchase_images';
                $purchase->images()->save($image);
            }
        }

        return ResponseFormatter::success(data: $purchase);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        if ($request->user()->cannot('access purchases')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $purchase = Purchase::with('images', 'supplier', 'purchaseItems', 'purchaseItems.product', 'purchaseItems.category')
                ->find($id);

            if (!$purchase) {
                return ResponseFormatter::error(404, 'Not Found');
            }

            return ResponseFormatter::success(data: $purchase);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseRequest $request, $id)
    {
        if ($request->user()->cannot('update purchases')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        $validated = $request->validated();

        $purchase = Purchase::findOrFail($id);

        $purchase->update([
            'status' => 0,
            'purchase_number' => $validated['purchase_number'],
            'date' => $validated['date'],
            'discount' => $validated['discount'] ?? 0,
            'tax' => $validated['tax'] ?? 0,
            'description' => $validated['description'] ?? null,
            'supplier_id' => $validated['supplier_id']
        ]);

        $currentItemIds = $purchase->purchaseItems()->pluck('id')->toArray();

        foreach ($validated['purchase_items'] as $purchaseItemData) {
            PurchaseItem::updateOrCreate(
                ['id' => $purchaseItemData['id'] ?? null],
                [
                    'purchase_id' => $purchase->id,
                    'category_id' => $purchaseItemData['category_id'],
                    'product_id' => $purchaseItemData['product_id'],
                    'description' => $purchaseItemData['description'] ?? null,
                    'unit_price' => $purchaseItemData['unit_price'],
                    'quantity' => $purchaseItemData['quantity']
                ]
            );
        }

        $itemsToDelete = array_diff($currentItemIds, array_column($validated['purchase_items'], 'id'));

        if (!empty($itemsToDelete)) {
            PurchaseItem::whereIn('id', $itemsToDelete)->delete();
        }

        if ($request->filled('images')) {
            $images = $validated['images'];

            $currImages = $purchase->images->pluck('id')->toArray();
            $imagesToDelete = array_diff($currImages, $images);

            foreach ($imagesToDelete as $imageId) {
                $image = Image::find($imageId);
                Storage::disk('public')->delete($image->path);
                $image->delete();
            }

            foreach ($images as $imageId) {
                $image = Image::find($imageId);
                $image->collection_name = 'purchase_images';
                $purchase->images()->save($image);
            }
        } else {
            // empty images means delete all images if any
            $purchase->images()->delete();
        }

        return ResponseFormatter::success();
    }

    /**
     * Approve the specified resource.
     */
    public function approve(Request $request, $purchase)
    {
        if ($request->user()->cannot('approve purchases')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        $purchase = Purchase::findOrFail($purchase);
        $purchaseItems = $purchase->purchaseItems()->get();

        foreach ($purchaseItems as $purchaseItem) {
            $purchaseItem->product->increment('stock', $purchaseItem->quantity);
        }

        $purchase->update(['status' => 1]);

        return ResponseFormatter::success();
    }

    /**
     * Generate purchase number.
     */
    public function generatePurchaseNumber(Request $request)
    {
        if ($request->user()->cannot('create purchases')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        $prefix = 'PO/INDOKA/';
        $month = now()->format('m');
        $year = now()->year;
        $purchaseNumber = $prefix . $month . '/' . $year . '/0001';

        $lastPo = Purchase::latest()->first();
        if ($lastPo) {
            list(,, $lastPoMonth, $lastPoYear, $lastSequence) = explode('/', $lastPo->purchase_number);

            if ($lastPoMonth == $month && $lastPoYear == $year) {
                $sequenceNumber = (int)$lastSequence + 1;
                $purchaseNumber = $prefix . $month . '/' . $year . '/' . sprintf('%04d', $sequenceNumber);
            }
        }

        return ResponseFormatter::success(data: $purchaseNumber);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->cannot('delete purchases')) {
            return ResponseFormatter::error(401, 'Unauthorized');
        }

        try {
            $purchases = Purchase::whereIn('id', $request->id)->where('status', '!=', 1)->get();

            if ($purchases->isEmpty()) {
                return ResponseFormatter::error(400, 'Semua pembelian sudah disetujui.');
            } else {
                $purchases->each(function ($purchase) {
                    $purchase->delete();
                });
            }

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
        if ($request->user()->cannot('access purchases')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };
        $purchaseIds = $request->input('id', []);
        $supplierId = $request->input('supplierId', null);
        $status = $request->input('status', null);
        $startDate = $request->input('startDate', null);
        $endDate = $request->input('endDate', null);
        $isGrouped = $request->input('isGrouped', 1);
        $fileType = $request->input('fileType');

        if ($isGrouped) {
            switch ($fileType) {
                case 'CSV':
                    return Excel::raw(new PurchasesExport($purchaseIds, $supplierId, $status, $startDate, $endDate), \Maatwebsite\Excel\Excel::CSV);
                    break;
                case 'XLSX':
                    return Excel::raw(new PurchasesExport($purchaseIds, $supplierId, $status, $startDate, $endDate), \Maatwebsite\Excel\Excel::XLSX);
                    break;
            };
        } else {
            switch ($fileType) {
                case 'CSV':
                    return Excel::raw(new PurchaseItemsExport($purchaseIds, $supplierId, $status, $startDate, $endDate), \Maatwebsite\Excel\Excel::CSV);
                    break;
                case 'XLSX':
                    return Excel::raw(new PurchaseItemsExport($purchaseIds, $supplierId, $status, $startDate, $endDate), \Maatwebsite\Excel\Excel::XLSX);
                    break;
            };
        };
    }
}
