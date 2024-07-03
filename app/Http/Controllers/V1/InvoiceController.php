<?php

namespace App\Http\Controllers\V1;

use App\Exports\V1\InvoiceItemsExport;
use App\Exports\V1\InvoicesExport;
use App\Helpers\V1\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreInvoiceRequest;
use App\Http\Requests\V1\UpdateInvoiceRequest;
use App\Models\Image;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceController extends Controller
{
    /**
     * Display a listing of the invoices.
     */
    public function index(Request $request)
    {
        if ($request->user()->cannot('access invoices')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $filter = $request->query('filter');

            $query = Invoice::with(['images', 'customer', 'invoiceItems', 'createdBy'])->orderBy('created_at', 'desc');

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has(['from_date', 'to_date'])) {
                $fromDate = $request->input('from_date', Carbon::now()->subYear()->startOfMonth());
                $toDate = $request->input('to_date', Carbon::now()->endOfMonth());

                $query->whereBetween('date', [$fromDate, $toDate]);
            }

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
                        })
                        ->orWhere('description', 'like', '%' . $filter . '%');
                });
                $filteredRowCount = $query->count();
            }


            if (!$request->has('pageIndex') && !$request->has('pageSize')) {
                $responseData = $query->get();
                if ($request->has('columns')) {
                    $responseData = $responseData->select(explode(',', $request->columns));
                }
            } else {
                $pageIndex = $request->query('pageIndex', 1);
                $pageSize = $request->query('pageSize', $query->count());
                $data = $query->paginate($pageSize, ['*'], 'page', $pageIndex);

                $responseData = [
                    'totalRowCount' => Invoice::count(),
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
     * Store a newly created invoice in storage.
     */
    public function store(StoreInvoiceRequest $request)
    {
        if ($request->user()->cannot('create invoices')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        $validated = $request->validated();

        $invoice = Invoice::create([
            'status' => 0,
            'invoice_number' => $validated['invoice_number'],
            'date' => $validated['date'],
            'discount' => $validated['discount'] ?? 0,
            'tax' => $validated['tax'] ?? 0,
            'description' => $validated['description'] ?? null,
            'customer_id' => $validated['customer_id']
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
     * Display the specified invoice.
     */
    public function show(Request $request, string $id)
    {
        if ($request->user()->cannot('access invoices')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        try {
            $invoice = Invoice::with('images', 'customer', 'invoiceItems', 'invoiceItems.product', 'invoiceItems.category', 'createdBy')
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
     * Update the specified invoice in storage.
     */
    public function update(UpdateInvoiceRequest $request, $id)
    {
        if ($request->user()->cannot('update invoices')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        }

        $validated = $request->validated();

        $invoice = Invoice::findOrFail($id);

        $invoice->update([
            'status' => 0,
            'invoice_number' => $validated['invoice_number'],
            'date' => $validated['date'],
            'discount' => $validated['discount'] ?? 0,
            'tax' => $validated['tax'] ?? 0,
            'description' => $validated['description'] ?? null,
            'customer_id' => $validated['customer_id']
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
                    'total_price' => $invoiceItemData['unit_price'] * $invoiceItemData['quantity']
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

        return ResponseFormatter::success(data: $invoice);
    }

    /**
     * Approve the specified invoice.
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

            $expiryPeriod = $invoiceItem->product->expiry_period;

            if (isset($expiryPeriod)) {
                $invoiceItem->expiry_date = now()->addMonth($expiryPeriod);
            }

            $invoiceItem->save();
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

        $invoiceNumber = Invoice::generateInvoiceNumber();

        return ResponseFormatter::success(data: $invoiceNumber);
    }

    /**
     * Remove the specified invoice from storage.
     */
    public function destroy(Request $request)
    {
        if ($request->user()->cannot('delete invoices')) {
            return ResponseFormatter::error(401, 'Unauthorized');
        }

        try {
            $invoices = Invoice::whereIn('id', $request->id)->where('status', '!=', 1)->get();

            if ($invoices->isEmpty()) {
                return ResponseFormatter::error(400, 'Penjualan tidak ditemukan atau telah disetujui.');
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

    /**
     * Export the specified invoice from storage.
     */
    public function export(Request $request)
    {
        if ($request->user()->cannot('access invoices')) {
            return ResponseFormatter::error('401', 'Unauthorized');
        };

        $productIds = $request->input('id', []);
        $customerId = $request->input('customerId', null);
        $status = $request->input('status', null);
        $startDate = $request->input('startDate', null);
        $endDate = $request->input('endDate', null);
        $isGrouped = $request->input('isGrouped', 1);
        $fileType = $request->input('fileType');

        if ($isGrouped) {
            switch ($fileType) {
                case 'CSV':
                    return Excel::raw(new InvoicesExport($productIds, $customerId, $status, $startDate, $endDate), \Maatwebsite\Excel\Excel::CSV);
                    break;
                case 'XLSX':
                    return Excel::raw(new InvoicesExport($productIds, $customerId, $status, $startDate, $endDate), \Maatwebsite\Excel\Excel::XLSX);
                    break;
            };
        } else {
            switch ($fileType) {
                case 'CSV':
                    return Excel::raw(new InvoiceItemsExport($productIds, $customerId, $status, $startDate, $endDate), \Maatwebsite\Excel\Excel::CSV);
                    break;
                case 'XLSX':
                    return Excel::raw(new InvoiceItemsExport($productIds, $customerId, $status, $startDate, $endDate), \Maatwebsite\Excel\Excel::XLSX);
                    break;
            };
        };
    }
}
