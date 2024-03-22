<?php

namespace App\Exports\V1;

use App\Models\Unit;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class UnitsExport implements FromCollection, WithHeadings, WithColumnFormatting, WithStyles, WithTitle, ShouldAutoSize
{
    use Exportable;

    protected $unitIds;
    protected $startDate;
    protected $endDate;

    public function __construct(array $unitIds = [], $startDate = null, $endDate = null)
    {
        $this->unitIds = $unitIds;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Unit::query();

        if (!empty($this->unitIds)) {
            $query->whereIn('id', $this->unitIds);
        }

        if (!empty($this->startDate)) {
            $query->where('created_at', '>=', $this->startDate);
        }

        if (!empty($this->endDate)) {
            $query->where('created_at', '<=', $this->endDate);
        }

        $columns = ["id", "name", "created_at", "updated_at"];

        return $query->select($columns)->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Nama Unit of Measure',
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
        return 'Units';
    }
}
