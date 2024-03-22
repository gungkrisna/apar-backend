<?php

namespace App\Http\Controllers\V1;

use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreInvoiceRequest;
use App\Http\Requests\V1\UpdateInvoiceRequest;
use App\Models\Image;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InvoiceController extends Controller
{
 /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->user()->cannot('access invoices')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $validColumns = ['id', 'status', 'invoice_number', 'date', 'customer_id', 'created_by', 'updated_by', 'created_at', 'updated_at'];

            $filter = $request->query('filter');
            $columns = $request->query('columns', $validColumns);

            $columns = array_intersect($columns, $validColumns);
            $query = Invoice::with('images', 'customer', 'invoiceItems', 'createdBy', 'updatedBy')
                ->select($columns)
                ->withSum('invoiceItems as total', 'total_price')
                ->orderBy('created_at', 'desc');

           if ($filter !== null && $filter !== '') {
                $query->where(function ($q) use ($filter) {
                    $q->where('invoice_number', 'like', '%' . $filter . '%')
                        ->orWhereHas('invoiceItems', function ($q) use ($filter) {
                            $q->where('description', 'like', '%' . $filter . '%')
                                ->orWhereHas('product', function ($q) use ($filter) {
                                    $q->where('name', 'like', '%' . $filter . '%');
                                })
                                ->orWhereHas('category', function ($q) use ($filter) {
                                    $q->where('name', 'like', '%' . $filter . '%');
                                });
                        })
                        ->orWhereHas('customer', function ($q) use ($filter) {
                            $q->where('company_name', 'like', '%' . $filter . '%')
                            ->orWhere('pic_name', 'like', '%' . $filter . '%')
                            ->orWhere('phone', 'like', '%' . $filter . '%');
                        });
                        // ->orWhere('description', 'like', '%' . $filter . '%');
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
                'totalRowCount' => Invoice::count(),
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
    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();

        $invoice = Invoice::create([
            'status' => 0,
            'invoice_number' => $validated['invoice_number'],
            'date' => $validated['date'],
            'customer_id' => $validated['customer_id'],
            'created_by' => auth()->id(),
        ]);

        foreach ($validated['invoice_items'] as $invoiceItemData) {
            $invoiceItem = new InvoiceItem();

            $invoiceItem->invoice_id = $invoice->id;
            $invoiceItem->category_id = $invoiceItemData['category_id'];
            $invoiceItem->product_id = $invoiceItemData['product_id'];
            $invoiceItem->description = $invoiceItemData['description'] ?? null;
            $invoiceItem->unit_price = $invoiceItemData['unit_price'];
            $invoiceItem->quantity = $invoiceItemData['quantity'];
            $invoiceItem->total_price = $invoiceItemData['unit_price'] * $invoiceItemData['quantity'];
            $invoiceItem->created_by = auth()->id();

            $product = Product::find($invoiceItemData['product_id']);

            if ($product && isset($product->expiry_period)) {
                $expiryDate = date('Y-m-d', strtotime('+' . $product->expiry_period . ' months'));
                $invoiceItem->expiry_date = $expiryDate;
            } 

            $invoiceItem->save();
        }

        if ($request->filled('images')) {
            $images = $validated['images'];

            foreach ($images as $imageId) {
                $image = Image::find($imageId);
                $image->collection_name = 'invoice_images';
                $invoice->images()->save($image);
            }
        }

        return ResponseFormatter::success(data: $invoice);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        if ($request->user()->cannot('access invoices')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $invoice = Invoice::with('images', 'customer', 'invoiceItems', 'invoiceItems.product', 'invoiceItems.category', 'createdBy', 'updatedBy')
            ->withSum('invoiceItems as total', 'total_price')
            ->find($id);

            if (!$invoice) {
                return ResponseFormatter::error(404, 'Not Found');
            }

            return ResponseFormatter::success(data: $invoice);
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, $id)
    {
        $validated = $request->validated();

        $invoice = Invoice::findOrFail($id);

        $invoice->update([
            'status' => 0,
            'invoice_number' => $validated['invoice_number'],
            'date' => $validated['date'],
            'customer_id' => $validated['customer_id'],
            'updated_by' => auth()->id(),
        ]);
        
        $currentItemIds = $invoice->invoiceItems()->pluck('id')->toArray();

        foreach ($validated['invoice_items'] as $invoiceItemData) {
            InvoiceItem::updateOrCreate(
                ['id' => $invoiceItemData['id'] ?? null],
                [
                    'invoice_id' => $invoice->id,
                    'category_id' => $invoiceItemData['category_id'],
                    'product_id' => $invoiceItemData['product_id'],
                    'description' => $invoiceItemData['description'] ?? null,
                    'unit_price' => $invoiceItemData['unit_price'],
                    'quantity' => $invoiceItemData['quantity'],
                    'total_price' => $invoiceItemData['unit_price'] * $invoiceItemData['quantity'],
                    'updated_by' => auth()->id(),
                ]
            );
        }

        $itemsToDelete = array_diff($currentItemIds, array_column($validated['invoice_items'], 'id'));

        if (!empty($itemsToDelete)) {
            InvoiceItem::whereIn('id', $itemsToDelete)->delete();
        }

    if ($request->filled('images')) {
        $images = $validated['images'];

        $currImages = $invoice->images->pluck('id')->toArray();
        $imagesToDelete = array_diff($currImages, $images);

        foreach ($imagesToDelete as $imageId) {
            $image = Image::find($imageId);
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }

        foreach ($images as $imageId) {
            $image = Image::find($imageId);
            $image->collection_name = 'invoice_images';
            $invoice->images()->save($image);
        }
    } else {
        // empty images means delete all images if any
        $invoice->images()->delete();
    }

        return ResponseFormatter::success();
    }

    /**
     * Approve the specified resource.
     */
    public function approve(Request $request, $invoice)
    {
        if ($request->user()->cannot('approve invoices')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        $invoice = Invoice::findOrFail($invoice);
        $invoiceItems = $invoice->invoiceItems()->get();
       
        foreach ($invoiceItems as $invoiceItem) {
            if ($invoiceItem->product->stock < $invoiceItem->quantity) {
                return ResponseFormatter::error(422, 'Unprocessable Entity', 'Beberapa produk pada invoice tidak memiliki stock yang cukup.');
            }
        }

        foreach ($invoiceItems as $invoiceItem) {
            $invoiceItem->product->decrement('stock', $invoiceItem->quantity);
        }
        
        $invoice->update(['status' => 1]);

        return ResponseFormatter::success();
    }

    /**
     * Generate invoice number.
     */
    public function generateInvoiceNumber(Request $request)
    {
        if ($request->user()->cannot('create invoices')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        $prefix = 'INV/INDOKA/';
        $month = now()->format('m');
        $year = now()->year;
        $lastOrder = Invoice::latest()->first();

        if ($lastOrder) {
            $lastOrderNumber = $lastOrder->invoice_number;
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
        if ($request->user()->cannot('delete invoices')) {
            return ResponseFormatter::error(401, 'Unauthorized');
        }

        try {
            $invoices = Invoice::whereIn('id', $request->id)->where('status', '!=', 1)->get();
            
            if ($invoices->isEmpty()) {
                return ResponseFormatter::error(400, 'Semua pembelian sudah disetujui.');
            } else {
                $invoices->each(function ($invoice) {
                    $invoice->delete();
                });
            }

            return ResponseFormatter::success();
        } catch (\Exception $e) {
            return ResponseFormatter::error(400, 'Failed', $e->getMessage());
        }
    }
}
