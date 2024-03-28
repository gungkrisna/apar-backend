<?php

namespace App\Exports\V1;

use App\Models\Purchase;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;
use PhpOffice\PhpSpreadsheet\Calculation\MathTrig\Subtotal;

class PurchasesExport implements FromCollection, WithHeadings, WithColumnFormatting, WithStyles, WithTitle, ShouldAutoSize, WithStrictNullComparison
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
        $query = Purchase::query();
        $query->with(['supplier', 'purchaseItems']);

        if (!empty($this->purchaseIds)) {
            $query->whereIn('id', $this->purchaseIds);
        }

        if (isset($this->status)) {
            $query->where('status', $this->status);
        }

        if (!empty($this->supplierId)) {
            $query->where('supplier_id', $this->supplierId);
        }

        if (!empty($this->startDate)) {
            $query->where('date', '>=', $this->startDate);
        }

        if (!empty($this->endDate)) {
            $query->where('date', '<=', $this->endDate);
        }

        // Transform the results into an array format matching the headings
        $data = $query->get()->map(function ($item) {
            return [
                $item->id,
                $item->date,
                $item->status == 1 ? 'Disetujui' : 'Pending',
                $item->purchase_number,
                $item->supplier->name,
                $item->subtotal,
                $item->discount,
                $item->tax,
                $item->total,
                $item->description,
                $item->supplier->email,
                $item->supplier->phone,
                $item->supplier->address,
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
            'Supplier',
            'Subtotal',
            'Diskon',
            'Pajak (%)',
            'Total',
            'Catatan',
            'Email Supplier',
            'Telepon Supplier',
            'Alamat Supplier',
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
        return 'Purchases';
    }
}
