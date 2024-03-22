<?php

namespace App\Exports\V1;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class CustomersExport implements FromCollection, WithHeadings, WithColumnFormatting, WithStyles, WithTitle, ShouldAutoSize
{
    use Exportable;

    protected $customerIds;
    protected $startDate;
    protected $endDate;

    public function __construct(array $customerIds = [], $startDate = null, $endDate = null)
    {
        $this->customerIds = $customerIds;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Customer::query();

        if (!empty($this->customerIds)) {
            $query->whereIn('id', $this->customerIds);
        }

        if (!empty($this->startDate)) {
            $query->where('created_at', '>=', $this->startDate);
        }

        if (!empty($this->endDate)) {
            $query->where('created_at', '<=', $this->endDate);
        }

        $columns = ["id", "company_name", "pic_name", "phone", "email", "address", "created_at", "updated_at"];

        $query->select($columns);

        return $query->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nama Perusahaan',
            'Person in Contact',
            'Telepon',
            'Email',
            'Alamat',
            'Tanggal Dibuat',
            'Terakhir Diperbarui'
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
        return 'Customers';
    }
}
