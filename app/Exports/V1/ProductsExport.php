<?php

namespace App\Exports\V1;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithStrictNullComparison;

class ProductsExport implements FromCollection, WithHeadings, WithColumnFormatting, WithStyles, WithTitle, ShouldAutoSize, WithStrictNullComparison
{
    use Exportable;

    protected $productIds;
    protected $status;
    protected $supplierId;
    protected $categoryId;
    protected $startDate;
    protected $endDate;

    public function __construct(array $productIds = [], $status = null, $supplierId = null, $categoryId = null, $startDate = null, $endDate = null)
    {
        $this->productIds = $productIds;
        $this->status = $status;
        $this->supplierId = $supplierId;
        $this->categoryId = $categoryId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $query = Product::query();
        $query->with(['unit', 'supplier', 'category']);

        if (!empty($this->productIds)) {
            $query->whereIn('id', $this->productIds);
        }

        if (isset($this->status)) {
            $query->where('status', $this->status);
        }

        if (!empty($this->supplierId)) {
            $query->where('supplier_id', $this->supplierId);
        }

        if (!empty($this->categoryId)) {
            $query->where('category_id', $this->categoryId);
        }

        if (!empty($this->startDate)) {
            $query->where('created_at', '>=', $this->startDate);
        }

        if (!empty($this->endDate)) {
            $query->where('created_at', '<=', $this->endDate);
        }

        // Transform the results into an array format matching the headings
        $data = $query->get()->map(function ($item) {
            return [
                $item->id,
                $item->status == 1 ? 'Aktif' : 'Nonaktif',
                $item->serial_number,
                $item->name,
                $item->description,
                $item->stock,
                $item->price,
                $item->expiry_period,
                $item->unit->name,
                $item->supplier->name, 
                $item->category->name, 
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
            'Status',
            'Serial Number',
            'Nama',
            'Deskripsi',
            'Stock',
            'Harga',
            'Masa Kedaluwarsa (Bulan)',
            'Unit',
            'Supplier',
            'Kategori',
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
        return 'Products';
    }
}
