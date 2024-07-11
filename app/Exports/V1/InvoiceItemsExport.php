<?php

namespace App\Exports\V1;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InvoiceItemsExport implements FromCollection, WithHeadings, WithColumnFormatting, WithStyles, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    use Exportable;

    protected $invoiceIds;
    protected $customerId;
    protected $status;
    protected $startDate;
    protected $endDate;

    public function __construct(array $invoiceIds = [], $customerId = null, $status = null, $startDate = null, $endDate = null)
    {
        $this->invoiceIds = $invoiceIds;
        $this->customerId = $customerId;
        $this->status = $status;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = InvoiceItem::query();
        $query->with(['category', 'product', 'invoice']);

        if (!empty($this->invoiceIds)) {
            $query->whereHas('invoice', function ($q) {
                $q->whereIn('id', $this->invoiceIds);
            });
        }

        if (isset($this->status)) {
            $query->whereHas('invoice', function ($q) {
                $q->where('status', $this->status);
            });
        }

        if (!empty($this->customerId)) {
            $query->whereHas('invoice', function ($q) {
                $q->where('customer_id', $this->customerId);
            });
        }

        if (!empty($this->startDate)) {
            $query->whereHas('invoice', function ($q) {
                $q->where('date', '>=', $this->startDate);
            });
        }

        if (!empty($this->endDate)) {
            $query->whereHas('invoice', function ($q) {
                $q->where('date', '<=', $this->endDate);
            });
        }

        // Transform the results into an array format matching the headings
        $data = $query->get()->map(function ($item) {
            return [
                $item->id,
                $item->invoice->date,
                $item->invoice->status == 1 ? 'Disetujui' : 'Pending',
                $item->invoice->invoice_number,
                $item->product->name,
                $item->category->name,
                $item->quantity,
                $item->unit_price,
                $item->total_price,
                $item->description,
                $item->invoice->description,
                $item->invoice->customer->company_name,
                $item->invoice->customer->pic_name,
                $item->invoice->customer->email,
                $item->invoice->customer->phone,
                $item->invoice->customer->address,
                $item->invoice->createdBy->name,
                $item->invoice->created_at,
                $item->invoice->updated_at,
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
            'No. Invoice',
            'Nama Produk',
            'Kategori Produk',
            'Kuantitas',
            'Harga Satuan',
            'Total Harga',
            'Catatan Produk',
            'Catatan Invoice',
            'Nama Perusahaan',
            'Person in Charge',
            'Email Perusahaan',
            'Telepon Perusahaan',
            'Alamat Perusahaan',
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
        return 'InvoiceItems';
    }
}
