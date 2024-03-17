<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StorePurchaseRequest;
use App\Http\Requests\V1\UpdatePurchaseRequest;
use App\Models\Image;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            $validColumns = ['id', 'status', 'purchase_number', 'date', 'supplier_id', 'created_by', 'updated_by'];

            $filter = $request->query('filter');
            $columns = $request->query('columns', $validColumns);

            $columns = array_intersect($columns, $validColumns);
            $query = Purchase::with('images', 'supplier', 'purchaseItems', 'createdBy', 'updatedBy')
                ->select($columns)
                ->orderBy('created_at', 'desc');

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
                'totalRowCount' => Purchase::count(),
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
    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();

        $purchase = Purchase::create([
            'status' => 0,
            'purchase_number' => $validated['purchase_number'],
            'date' => $validated['date'],
            'supplier_id' => $validated['supplier_id'],
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['purchase_items'] as $purchaseItemData) {
            $purchaseItem = new PurchaseItem();

            $purchaseItem->purchase_id = $purchase->id;
            $purchaseItem->category_id = $purchaseItemData['category_id'];
            $purchaseItem->product_id = $purchaseItemData['product_id'];
            $purchaseItem->description = $purchaseItemData['description'] ?? null;
            $purchaseItem->unit_price = $purchaseItemData['unit_price'];
            $purchaseItem->quantity = $purchaseItemData['quantity'];
            $purchaseItem->total_price = $purchaseItemData['unit_price'] * $purchaseItemData['quantity'];
            $purchaseItem->created_by = auth()->id();

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
            $purchase = Purchase::with('images', 'supplier', 'purchaseItems', 'purchaseItems.product', 'purchaseItems.category', 'createdBy', 'updatedBy')->find($id);

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
        $validated = $request->validated();

        $purchase = Purchase::findOrFail($id);

        $purchase->update([
            'status' => 0,
            'purchase_number' => $validated['purchase_number'],
            'date' => $validated['date'],
            'supplier_id' => $validated['supplier_id'],
            'updated_by' => auth()->id(),
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
                    'quantity' => $purchaseItemData['quantity'],
                    'total_price' => $purchaseItemData['unit_price'] * $purchaseItemData['quantity'],
                    'updated_by' => auth()->id(),
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

        Purchase::findOrFail($purchase)->update(['status' => 1]);

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
        $lastOrder = Purchase::latest()->first();

        if ($lastOrder) {
            $lastOrderNumber = $lastOrder->purchase_number;
            $lastOrderNumberArray = explode('/', $lastOrderNumber);
            $lastOrderMonth = $lastOrderNumberArray[2];
            $lastOrderYear = $lastOrderNumberArray[3];

            if ($lastOrderMonth == $month && $lastOrderYear == $year) {
                $newOrderNumber = $prefix . $month . '/' . $year . '/' . (sprintf('%04d', $lastOrderNumberArray[4] + 1));

            }
        } else {
            $newOrderNumber = $prefix . $month . '/' . $year . '/0001';
        }

        return ResponseFormatter::success(data: $newOrderNumber);
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
}
