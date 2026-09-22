<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AssetTemplateExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection(): Collection
    {
        return collect([
            ['P26-HN-001', 'P2.6', '22/09/2026', '500 x 500 mm'],
            ['P15-HP-001', 'P1.5', '22/09/2026', '500 x 500 mm'],
            ['P39-HN-001', 'P3.9', '22/09/2026', '500 x 1000 mm'],
        ]);
    }

    public function headings(): array
    {
        return ['Số Seri', 'Mã dòng SP', 'Ngày sản xuất', 'Kích thước'];
    }

    /**
     * @param  array<int, string>  $row
     * @return array<int, string>
     */
    public function map($row): array
    {
        return (array) $row;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
