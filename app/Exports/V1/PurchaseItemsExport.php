<?php

namespace App\Exports\V1;

use App\Models\PurchaseItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class PurchaseItemsExport implements FromCollection, WithHeadings, WithColumnFormatting, WithStyles, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    use Exportable;

    protected $purchaseIds;
    protected $supplierId;
    protected $status;
    protected $startDate;
    protected $endDate;

    public function __construct(array $purchaseIds = [], $supplierId = null, $status = null, $startDate = null, $endDate = null)
    {
        $this->purchaseIds = $purchaseIds;
        $this->supplierId = $supplierId;
        $this->status = $status;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = PurchaseItem::query();
        $query->with(['category', 'product', 'purchase']);

        if (!empty($this->purchaseIds)) {
            $query->whereHas('purchase', function ($q) {
                $q->whereIn('id', $this->purchaseIds);
            });
        }

        if (isset($this->status)) {
            $query->whereHas('purchase', function ($q) {
                $q->where('status', $this->status);
            });
        }

        if (!empty($this->supplierId)) {
            $query->whereHas('purchase', function ($q) {
                $q->where('supplier_id', $this->supplierId);
            });
        }

        if (!empty($this->startDate)) {
            $query->whereHas('purchase', function ($q) {
                $q->where('date', '>=', $this->startDate);
            });
        }

        if (!empty($this->endDate)) {
            $query->whereHas('purchase', function ($q) {
                $q->where('date', '<=', $this->endDate);
            });
        }

        // Transform the results into an array format matching the headings
        $data = $query->get()->map(function ($item) {
            return [
                $item->id,
                $item->purchase->date,
                $item->purchase->status == 1 ? 'Disetujui' : 'Pending',
                $item->purchase->purchase_number,
                $item->product->name,
                $item->category->name,
                $item->quantity,
                $item->unit_price,
                $item->total_price,
                $item->description,
                $item->purchase->description,
                $item->purchase->supplier->name,
                $item->purchase->supplier->email,
                $item->purchase->supplier->phone,
                $item->purchase->supplier->address,
                $item->createdBy->name,
                $item->created_at,
                $item->updated_at,
            ];
        });

        return $data;
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Tanggal',
            'Status',
            'No. Pembelian',
            'Nama Produk',
            'Kategori Produk',
            'Kuantitas',
            'Harga Satuan',
            'Subtotal',
            'Catatan Produk',
            'Catatan Pembelian',
            'Supplier',
            'Email Supplier',
            'Telepon Supplier',
            'Alamat Supplier',
            'Dibuat Oleh',
            'Tanggal Dibuat',
            'Terakhir Diperbarui',
        ];
    }
    /**
     * @return array
     */
    public function columnFormats(): array
    {
        return [
            'C' => '@'
        ];
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [
                        'rgb' => 'FFFF00',
                    ],
                ],
                'font' => ['bold' => true]
            ]
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'PurchaseItems';
    }
}
