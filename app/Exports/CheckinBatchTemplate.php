<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CheckinBatchTemplate implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    public function collection(): Collection
    {
        return collect([
            ['P26-HN-001', 'P2.6 Sự kiện', '500×500 mm', 'Ghi chú mẫu (có thể xóa)'],
            ['P26-HN-002', 'P2.6 Sự kiện', '500×500 mm', ''],
            ['P39-HN-001', 'P3.9 Outdoor', '1000×500 mm', ''],
        ]);
    }

    public function headings(): array
    {
        return ['Số Seri', 'Dòng sản phẩm', 'Kích thước', 'Ghi chú'];
    }

    public function map($row): array
    {
        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 11]],
        ];
    }
}
