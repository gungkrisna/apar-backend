<?php

namespace App\Exports\V1;

use App\Models\Supplier;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;

class SuppliersExport implements FromCollection, WithHeadings, WithColumnFormatting, WithStyles, WithTitle, ShouldAutoSize
{
    use Exportable;

    protected $supplierIds;
    protected $startDate;
    protected $endDate;

    public function __construct(array $supplierIds = [], $startDate = null, $endDate = null)
    {
        $this->supplierIds = $supplierIds;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Supplier::query();

        if (!empty($this->supplierIds)) {
            $query->whereIn('id', $this->supplierIds);
        }

        if (!empty($this->startDate)) {
            $query->where('created_at', '>=', $this->startDate);
        }

        if (!empty($this->endDate)) {
            $query->where('created_at', '<=', $this->endDate);
        }

        $columns = ["id", "name", "phone", "email", "address", "created_at", "updated_at"];

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
            'Nama',
            'Telepon',
            'Email',
            'Alamat',
            'Tanggal Pembuatan',
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
        return 'Suppliers';
    }

    // /**
    //  * @return array
    //  */
    // public function drawings()
    // {
    //     $logoPath = file_get_contents('https://upload.wikimedia.org/wikipedia/commons/thumb/9/9a/Laravel.svg/1969px-Laravel.svg.png');

    //     $logo = new MemoryDrawing();
    //     $logo->setName('Logo');
    //     $logo->setDescription('Your Company Logo');
    //     $logo->setImageResource(imagecreatefrompng($logoPath));
    //     $logo->setRenderingFunction(MemoryDrawing::RENDERING_PNG);
    //     $logo->setMimeType(MemoryDrawing::MIMETYPE_PNG);
    //     $logo->setCoordinates('A1'); 

    //     return [$logo];
    // }
}
