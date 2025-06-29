<?php

namespace App\Exports\V1;

use App\Models\Invoice;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class InvoicesExport implements FromCollection, WithHeadings, WithColumnFormatting, WithStyles, WithTitle, ShouldAutoSize, WithStrictNullComparison
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
        $query = Invoice::query();
        $query->with(['customer', 'invoiceItems']);

        if (!empty($this->invoiceIds)) {
            $query->whereIn('id', $this->invoiceIds);
        }

        if (isset($this->status)) {
            $query->where('status', $this->status);
        }

        if (!empty($this->customerId)) {
            $query->where('customer_id', $this->customerId);
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
                $item->invoice_number,
                $item->customer->company_name,
                $item->invoiceItems->sum('total_price'),
                $item->customer->pic_name,
                $item->customer->email,
                $item->customer->phone,
                $item->customer->address,
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
            'No. Invoice',
            'Nama Perusahaan',
            'Total',
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
        return 'Invoices';
    }
}
